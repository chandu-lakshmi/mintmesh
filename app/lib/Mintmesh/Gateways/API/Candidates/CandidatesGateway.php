<?php

namespace Mintmesh\Gateways\API\Candidates;

/**
 * This is the Post Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Repositories\API\Candidates\CandidatesRepository;
use Mintmesh\Repositories\API\Candidates\NeoCandidatesRepository;
use Mintmesh\Repositories\API\Enterprise\EnterpriseRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
use Mintmesh\Services\Emails\API\User\UserEmailManager;
use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Mintmesh\Gateways\API\Post\PostGateway;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Services\Validators\API\Candidates\CandidatesValidator;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\APPEncode\APPEncode;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use Lang;
use Config;
use OAuth;
use URL,
    Queue;
use Mail;
use Cache;
use lib\Parser\MyEncrypt;


class CandidatesGateway {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    const DEFAULT_USER_COUNTRY = 'us';
    const CURL_CALL_TYPE = 1;
    const CURL_CALL_TYPE_FILE = 2;
    const SOURCE_FROM_BULK_UPLOAD = 1;
    const SOURCE_FROM_EMAIL_UPLOAD = 3;
    const COMPANY_RESUME_STATUS = 0;
    const COMPANY_RESUME_S3_MOVED_STATUS = 1;
    const CAREER_HEROSHOT_IMAGE_HEIGHT = 336;
    const EMAIL_FAILURE_STATUS = 0;
    const EMAIL_SUCCESS_STATUS = 1;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $enterpriseRepository, $commonFormatter, $authorizer, $appEncodeDecode,$neoEnterpriseRepository,$userFileUploader, $neoUserRepository,$userRepository;
    protected $createdNeoUser, $candidatesValidator, $referralsRepository, $enterpriseGateway, $userEmailManager, $candidatesRepository, $neoCandidatesRepository;
    protected $postGateway, $userGateway;

    public function __construct(ReferralsGateway $referralsGateway, 
                                EnterpriseGateway $enterpriseGateway,
                                Authorizer $authorizer, 
                                CommonFormatter $commonFormatter, 
                                APPEncode $appEncodeDecode, 
                                CandidatesValidator $candidatesValidator, 
                                referralsRepository $referralsRepository,
                                EnterpriseRepository $enterpriseRepository,
                                UserFileUploader $userFileUploader,
                                UserEmailManager $userEmailManager,
                                CandidatesRepository $candidatesRepository,
                                NeoCandidatesRepository $neoCandidatesRepository,
                                UserRepository $userRepository, 
                                NeoUserRepository $neoUserRepository,
                                PostGateway $postGateway,
                                UserGateway $userGateway,
                                NeoPostRepository $neoPostRepository
    ) {
        
        $this->referralsRepository      = $referralsRepository;
        $this->referralsGateway         = $referralsGateway;
        $this->enterpriseGateway        = $enterpriseGateway;
        $this->authorizer               = $authorizer;
        $this->candidatesValidator      = $candidatesValidator;
        $this->commonFormatter          = $commonFormatter;
        $this->appEncodeDecode          = $appEncodeDecode;
        $this->enterpriseRepository     = $enterpriseRepository;
        $this->userFileUploader         = $userFileUploader;
        $this->userEmailManager         = $userEmailManager;
        $this->candidatesRepository     = $candidatesRepository;
        $this->neoCandidatesRepository  = $neoCandidatesRepository;
        $this->userRepository = $userRepository;
        $this->neoUserRepository        = $neoUserRepository;
        $this->postGateway              = $postGateway;
        $this->userGateway              = $userGateway;
        $this->neoPostRepository        = $neoPostRepository;
    }
    
    public function doValidation($validatorFilterKey, $langKey) {
        $data = array();
        //validator passes method accepts validator filter key as param
        if ($this->candidatesValidator->passes($validatorFilterKey)) {
            /* validation passes successfully */
            $message        = array('msg' => array(Lang::get($langKey)));
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
        } else {
            /* Return validation errors to the controller */
            $message        = $this->candidatesValidator->getErrors();
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }
    
    //validation on validate Get Candidate Email Templates Input
    public function validateGetCandidateEmailTemplatesInput($input) {
        return $this->doValidation('get_candidate_email_templates', 'MINTMESH.user.valid');
    }
    
    //validation on validate Get Candidate Details Input
    public function validateGetCandidateDetailsInput($input) {
        return $this->doValidation('get_candidate_details', 'MINTMESH.user.valid');
    }
    //validation on validate Get Candidate Email Templates Input
    public function validateAddCandidateScheduleInput($input) {
        return $this->doValidation('add_candidate_schedule', 'MINTMESH.user.valid');
    }
    //validation on validate Get Candidate Email Templates Input
    public function validateAddCandidateEmailInput($input) {
        return $this->doValidation('add_candidate_email', 'MINTMESH.user.valid');
    }
    //validation on validate Get Candidate Email Templates Input
    public function validateAddCandidateCommentInput($input) {
        return $this->doValidation('add_candidate_comment', 'MINTMESH.user.valid');
    }
    //validation on validate Get Candidate Email Templates Input
    public function validateGetCandidateActivitiesInput($input) {
        return $this->doValidation('get_candidate_activities', 'MINTMESH.user.valid');
    }
    //validation on validate Get Candidate Tag Jobs Input
    public function validateGetCandidateTagJobsListInput($input) {
        return $this->doValidation('get_candidate_tag_jobs_list', 'MINTMESH.user.valid');
    }
    //validation on validate Add Candidate Tag Jobs Input
    public function validateAddCandidateTagJobsInput($input) {
        return $this->doValidation('add_candidate_tag_jobs', 'MINTMESH.user.valid');
    }
    public function validategetCandidateCommentsActivitiesInput($input) {
        return $this->doValidation('get_candidate_comments', 'MINTMESH.user.valid');
    }
    
    public function validategetCandidateSentEmailsInput($input) {
        return $this->doValidation('get_candidate_sent_emails', 'MINTMESH.user.valid');
    }
    //validate Get Candidate Referral List Input
    public function validateGetCandidateReferralListInput($input) {
        return $this->doValidation('get_candidate_referral_list', 'MINTMESH.user.valid');
    }
    public function validategetCandidateSchedulesActivitiesInput($input) {
        return $this->doValidation('get_candidate_schedules', 'MINTMESH.user.valid');
    }
    public function validateEditCandidateReferralStatusInput($input) {
        return $this->doValidation('edit_candidate_referral_status', 'MINTMESH.user.valid');
    }
    public function validategetCandidatesTagsInput($input) {
        return $this->doValidation('get_candidates_tags', 'MINTMESH.user.valid');
    }
    public function validateaddCandidatesTagsInput($input) {
        return $this->doValidation('add_candidate_tags', 'MINTMESH.user.valid');
    }
    public function validategetCandidateTagsInput($input) {
        return $this->doValidation('get_candidate_tags', 'MINTMESH.user.valid');
    }
    public function validatedeleteCandidateTagInput($input) {
        return $this->doValidation('delete_candidate_tag', 'MINTMESH.user.valid');
    }
    public function validateaddCandidatePersonalStatusInput($input) {
        return $this->doValidation('add_candidate_personal_status', 'MINTMESH.user.valid');
    }
    public function validategetCandidatePersonalStatusInput($input) {
        return $this->doValidation('get_candidate_personal_status', 'MINTMESH.user.valid');
    }
    public function validateGetQuestionTypesInput($input) {
        return $this->doValidation('get_candidate_email_templates', 'MINTMESH.user.valid');
    }
    public function validateGetQuestionLibrariesInput($input) {
        return $this->doValidation('get_candidate_email_templates', 'MINTMESH.user.valid');
    }
    public function validateAddQuestionInput($input) {
        return $this->doValidation('add_question', 'MINTMESH.user.valid');
    }
    public function validateEditQuestionInput($input) {
        return $this->doValidation('edit_question', 'MINTMESH.user.valid');
    }
    public function validateViewQuestionInput($input) {
        return $this->doValidation('view_question', 'MINTMESH.user.valid');
    }
    public function validateAddEditExamInput($input) {
        return $this->doValidation('add_edit_exam', 'MINTMESH.user.valid');
    }
    public function validateEditExamSettingsInput($input) {
        return $this->doValidation('edit_exam_settings', 'MINTMESH.user.valid');
    }
    public function validateGetQuestionsListInput($input) {
        return $this->doValidation('only_company_code', 'MINTMESH.user.valid');
    }

    public function validategetCompanyAssessmentsListInput($input) {
        return $this->doValidation('get_company_assessments_list', 'MINTMESH.user.valid');
    }
    public function validateAddEditExamQuestionInput($input) {
        return $this->doValidation('add_edit_exam_question', 'MINTMESH.user.valid');
    }
    public function validateViewExamQuestionInput($input) {
        return $this->doValidation('view_exam_question', 'MINTMESH.user.valid');
    }
    public function validategetExamDetailsInput($input) {
        return $this->doValidation('get_exam_details', 'MINTMESH.user.valid');
    }
    public function validatedeleteQuestionInput($input) {
        return $this->doValidation('delete_question', 'MINTMESH.user.valid');
    }
    public function validategetCompanyAssessmentsAllInput($input) {
        return $this->doValidation('get_company_assessments_all', 'MINTMESH.user.valid');
    }    
    public function validateGetAssessmentInput($input) {
        return $this->doValidation('get_assessment', 'MINTMESH.user.valid');
    }
    
    public function getCandidateEmailTemplates($input) {
        
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        $companyName    = isset($companyDetails[0]) ? $companyDetails[0]->name : 0;
        
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if($resultArr){
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $postDetails    = isset($resultArr[2]) ? $resultArr[2] : '';
            $serviceName    = $postDetails->service_name;
            $candidateEmail = $candidate->emailid; 
            
            $referredBy         = !empty($relation->referred_by) ? $relation->referred_by : '';
            $candidateName      = $this->postGateway->getCandidateFullNameByEmail($candidateEmail, $referredBy, $companyId);    
            $returnArr          = $this->candidatesRepository->getCandidateEmailTemplates();

            foreach($returnArr as  &$resVal){
                $arrayReplace    = array( "%fname%" => $candidateName, "%company%" => $companyName, "%jobtitle%" => $serviceName);
                $body_text       = strtr($resVal->body, $arrayReplace);
                $subject         = strtr($resVal->subject, $arrayReplace);
                $resVal->subject = $subject;
                $resVal->body    = $body_text;
            }

            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateFullDetails($candidateEmail = '') {
        
        #variable declaration here
        $certification = "";
        $skillsArray = $extraDetails = $returnArr = $neoUserDetails = $moreDetails = array();
        if($candidateEmail){
            #get the user details neo4j node here
            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($candidateEmail);
            #form the user details array here
            $returnArr      = $this->userGateway->formUserDetailsArray($neoUserDetails);
            #get user professional details here
            $moreDetails    = $this->neoUserRepository->getMoreDetails($candidateEmail);
            if (!empty($moreDetails))
            {
                $extraDetails = $this->userGateway->formUserMoreDetailsArray($moreDetails);
            }
            #get user Experience details here
            $returnArr['total_experience']  = !empty($extraDetails['total_experience']) ? $extraDetails['total_experience'] : '';
            $returnArr['Experience']        = !empty($extraDetails['Experience']) ? $extraDetails['Experience'] : '';
            #current company details here
            if(isset($extraDetails['Experience']) && !empty($extraDetails['Experience'][0])){
                $expArr    = $extraDetails['Experience'][0];
                $startDate = !empty($expArr['start_date']) ? $expArr['start_date'] : '';
                $endDate   = !empty($expArr['end_date']) ? $expArr['end_date'] : '';
                $returnArr['current_company_name']     =  !empty($expArr['company_name']) ? $expArr['company_name'] : '';
                $returnArr['current_company_location'] =  !empty($expArr['location']) ? $expArr['location'] : '';
                $returnArr['current_company_position'] =  !empty($expArr['job_title']) ? $expArr['job_title'] : '';
                if(!empty($startDate) || !empty($endDate)){
                  $returnArr['current_company_details']  =  $startDate." - ".$endDate;
                }
            }
            #get user skills here
            $skills = $this->neoUserRepository->getUserSkills($candidateEmail);
            if (!empty($skills))
            {
                foreach ($skills as $skill)
                {
                    $skillsArray[] = $skill[0]->getProperties();
                }
                $extraDetails['skills'] = $skillsArray ;
            }
            #merge all result arrays here
            if (!empty($extraDetails))
            {
                foreach ($extraDetails as $k=>$v){
                    $returnArr[$k] = $v ;
                }
            }
            #get Certification details form here
            if(!empty($returnArr['Certification'])){
                foreach ($returnArr['Certification'] as $val){
                    $certification .= $val['description'].", ";
                }
                $returnArr['Certification'] = rtrim($certification, ', ');
            }
            #get qualification form here
            if(isset($returnArr['Education']) && !empty($returnArr['Education'][0])) {
                $value = $returnArr['Education'][0];
                $description   = isset($value['description']) ? $value['description'] : '';
                $degree        = isset($value['degree']) ? "(".$value['degree'].")" : '';
                $schoolCollege = isset($value['school_college']) ? " From ".$value['school_college'] : '';
                $returnArr['qualification'] = $description.$degree.$schoolCollege;
            }
        }
        return $returnArr;
    }
    
    public function getCandidateDetails($input) {
        
        $data   = $returnArr = $resultArr = $contactArr = $candidateTags = $personalStatus = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $contactId    = !empty($input['contact_id']) ? $input['contact_id'] : '';
        $timeZone     = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get conadidate id by contact id
        if($contactId){
            $contactArr   = $this->enterpriseRepository->getContactById($contactId);
            $contactEmail = isset($contactArr[0]) ? $contactArr[0]->emailid : '';
            $candidateId  = $this->neoPostRepository->getUserNodeIdByEmailId($contactEmail);
        }
        #get candidate details
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArr)){
            
            $qualification  = $candidateName = $referredByName = $skills = $createdAt = '';
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $postDetails    = isset($resultArr[2]) ? $resultArr[2] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            $serviceName    = !empty($postDetails->service_name) ? $postDetails->service_name : '';
            $candidateArr   = $this->getCandidateFullDetails($candidateEmail);
           
            #get skills form here
            if(!empty($candidateArr['skills'])){
                foreach ($candidateArr['skills'] as $val){
                    $skills .= $val['name'].", ";
                }
                $skills = rtrim($skills, ', ');
            }
            #get referred by name here
            $referredBy     = !empty($relation->referred_by) ? $relation->referred_by : '';
            $referredByName = $this->postGateway->getReferredbyUserFullName($referredBy, $companyId);
            $candidateName  = $this->postGateway->getCandidateFullNameByEmail($candidateEmail, $referredBy, $companyId);    
            
            if(!empty($relation->created_at)){
                $createdAt = date("M d,Y", strtotime($this->appEncodeDecode->UserTimezone($relation->created_at, $timeZone)));
            }
            $cvName = !empty($candidateArr['cv_original_name']) ? $candidateArr['cv_original_name'] : Lang::get('MINTMESH.candidates.awaiting_resume');
            $cvPath = !empty($candidateArr['cv_path']) ? $candidateArr['cv_path'] : '';
            #get Candidate Tags details here
            $candidateTagsArr    = $this->candidatesRepository->getCandidateTags($companyId, $referenceId, $candidateId);
            if(!empty($candidateTagsArr[0])){
                foreach ($candidateTagsArr as $value) {
                    $record = array();
                    $record['id']       = $value->id;
                    $record['tag_id']   = $value->tag_id;
                    $record['tag_name'] = $value->tag_name;
                    $candidateTags[] = $record;
                }
            }
            #get Candidate Personal Status details here
            $personalStatusArr    = $this->candidatesRepository->getCandidatePersonalStatus($companyId, $referenceId, $candidateId);
            if(!empty($personalStatusArr[0])){
                $value = $personalStatusArr[0];
                $personalStatus['status_id']   = $value->id;
                $personalStatus['status_name'] = $value->status_name;
            }
            
            $returnArr['candidate_id']  = $candidateId;
            $returnArr['name']          = $candidateName;
            $returnArr['emailid']       = $candidateEmail;//'nitinranganath@gmail.com';
            $returnArr['phone']         = !empty($candidateArr['phone']) ? $candidateArr['phone'] : '';//'+91 9852458752';
            $returnArr['dp_path']       = !empty($candidateArr['dp_path']) ? $candidateArr['dp_path'] : '';
            #candidate qualification details form here
            $returnArr['location']      = !empty($candidateArr['location']) ? $candidateArr['location'] : '';//'Hyderabad, Telangana';
            $returnArr['qualification'] = !empty($candidateArr['qualification']) ? $candidateArr['qualification'] : '' ;//$qualification;//'B Tech (CSC) From JNTU, Hyderabad';
            $returnArr['certification'] = !empty($candidateArr['Certification']) ? $candidateArr['Certification'] : '' ;//'Android Developer Certification from Google .Inc';
            $returnArr['skills']        = !empty($skills) ? array($skills) : array();//array("Java & XML, C, C++", "Building to Devices", "Cocoa Touch");
            #referral details form here
            $returnArr['document_id']   = !empty($relation->document_id) ? $relation->document_id : 0;
            $returnArr['resume_name']   = !empty($relation->resume_original_name) ? $relation->resume_original_name : $cvName;
            $returnArr['resume_path']   = !empty($relation->resume_path) ? $relation->resume_path : $cvPath;
            $returnArr['referred_by']   = $referredByName;
            $returnArr['referred_at']   = $createdAt;
            $returnArr['referred_job']  = $serviceName;
            $returnArr['candidate_tags']    = $candidateTags;
            $returnArr['candidate_status']  = $personalStatus;
            $returnArr['referral_status']   = !empty($relation->referral_status) ? $relation->referral_status : 'New';
            #candidate professional details form here
            $returnArr['current_company_name']      = !empty($candidateArr['current_company_name']) ? $candidateArr['current_company_name'] : '' ;//'EnterPi Software Solutions Pvt Ltd';
            $returnArr['current_company_details']   = !empty($candidateArr['current_company_details']) ? $candidateArr['current_company_details'] : '' ;//'May 2015 - Present(2 years 3 months)';
            $returnArr['current_company_location']  = !empty($candidateArr['current_company_location']) ? $candidateArr['current_company_location'] : '' ;//'Hyderabad Area, India';
            $returnArr['current_company_position']  = !empty($candidateArr['current_company_position']) ? $candidateArr['current_company_position'] : '' ;//'Sr Android Engineer';
            $returnArr['previous_company_name']     = '';//'HTC Global Services (India) Private Ltd';
            $returnArr['previous_company_details']  = '';//'May 2013 - May 2015 Present(2 years)';
            $returnArr['previous_company_location'] = '';//'Bangalore Area, India';
            $returnArr['previous_company_position'] = '';//'Jr. Android Engineer';
            #check get candidate details not empty
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function addCandidateSchedule($input) {
        
        $data = $returnArr = $arrayNewSchedules = array();
        $candidatefirstname = $candidatelastname = $service_name = $company = $candidateEmail = '';
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        
        $input['interview_date'] = $intDate = date('Y-m-d', strtotime($input['interview_date']));
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        $companyName    = isset($companyDetails[0]) ? $companyDetails[0]->name : 0;
        $companyLogo    = !empty($companyDetails[0]->logo)?$companyDetails[0]->logo:''; 
        #get the logged in user details
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId             = $this->loggedinUser->id;
        $userName           = $this->loggedinUser->firstname.' '.$this->loggedinUser->lastname;
        $userEmailId        = $this->loggedinUser->emailid;
        #get Candidate Details here
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $postDetails    = isset($resultArr[2]) ? $resultArr[2] : '';
            $candidateEmail = $candidate->emailid; 
            $candidateId    = $candidate->getID();
            $referredBy     = !empty($relation->referred_by) ? $relation->referred_by : '';
            $candidateName  = $this->postGateway->getCandidateFullNameByEmail($candidateEmail, $referredBy, $companyId); 
            $serviceName    = $postDetails->service_name;
            $dataSet = array();
            $subject                        = ' Interview with ';
            $dataSet['interview_when']      = '';//date('D j M Y', strtotime($input['interview_date'])).' '.$input['interview_from_time'].date('A', strtotime($input['interview_date'])).'-'.$input['interview_to_time'].date('A', strtotime($input['interview_date']));
            $dataSet['interview_timezone']  = !empty($input['interview_time_zone']) ? $input['interview_time_zone'] : '';
            $dataSet['interview_who']       = $candidateName;
            $dataSet['company_logo']        = $companyLogo;
            $dataSet['name']                = $candidateName;
            
            
            $notes       = !empty($input['notes']) ? $input['notes'] : '';
            $fromTime    = !empty($input['interview_from_time']) ? $input['interview_from_time'] : '';
            $toTime      = !empty($input['interview_to_time']) ? $input['interview_to_time'] : '';
            $location    = !empty($input['interview_location']) ? $input['interview_location'] : '';
            $scheduleFor = !empty($input['schedule_for']) ? $input['schedule_for'] : $subject;
            
            $emailData = array();
            $emailData = array();
            $emailData['from_name']     = $userName;//"Company Epi 1";        
            $emailData['from_address']  = "'support@mintmesh.com";        
            $emailData['to_name']       = $candidateName;//"karthik enterpi";        
            $emailData['to_address']    = $candidateEmail;//"j.karthik@enterpi.com";        
            $emailData['start_time']    = $intDate." ".$fromTime;//"09-03-2017 16:00";        
            $emailData['end_time']      = $intDate." ".$toTime;//"09-03-2017 17:00";        
            $emailData['subject']       = $scheduleFor;//"Interview with Epi";        
            $emailData['description']   = $notes;//"My Awesome Description";        
            $emailData['location']      = $location;//"Hyderabad, Telangana, India";
            $emailData['domain']        = 'mintmesh.com';
               
            $emailStatus = self::EMAIL_FAILURE_STATUS;
            $emailSent = $this->sendEvent($emailData);   

            $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.candidate_interview_schedule');
            $this->userEmailManager->emailId = $candidateEmail;
            $this->userEmailManager->dataSet = $dataSet;
            $this->userEmailManager->subject = $subject;
            $this->userEmailManager->name    = $candidateName;
            //$emailSent = $this->userEmailManager->sendMail();
            //log email status
            if (!empty($emailSent)) {
                $emailStatus = self::EMAIL_SUCCESS_STATUS;
                $returnArr   = $this->candidatesRepository->addCandidateSchedule($input, $userId, $referenceId, $candidateId, $companyId);
                $arrayNewSchedules = $this->getlastInsertSchedules($returnArr);
                $data = $arrayNewSchedules;
            }
            $emailLog = array(
                'emails_types_id'   => 10,
                'from_user'         => $userId,
                'from_email'        => $userEmailId,
                'to_email'          => $this->appEncodeDecode->filterString(strtolower($candidateEmail)),
                'related_code'      => $companyCode,
                'sent'              => $emailStatus,
                'ip_address'        => $_SERVER['REMOTE_ADDR']
            );
            $this->userRepository->logEmail($emailLog); 
            
            if(!empty($input['attendees'])){
                
                $emails = explode(',', $input['attendees']);
                foreach ($emails as $email) {
                    
                   if ($email && preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $email)) {
                       
                       $referredBy     = '';
                       $emailName      = $this->postGateway->getCandidateFullNameByEmail($email, $referredBy, $companyId); 
                       
                        $emailData = array();
                        $emailData['from_name']     = $userName;//"Company Epi 1";        
                        $emailData['from_address']  = "'support@mintmesh.com";        
                        $emailData['to_name']       = $emailName;//"karthik enterpi";        
                        $emailData['to_address']    = $email;//"j.karthik@enterpi.com";        
                        $emailData['start_time']    = $intDate." ".$fromTime;//"09-03-2017 16:00";        
                        $emailData['end_time']      = $intDate." ".$toTime;//"09-03-2017 17:00";        
                        $emailData['subject']       = $scheduleFor;//"Interview with Epi";        
                        $emailData['description']   = $notes;//"My Awesome Description";        
                        $emailData['location']      = $location;//"Hyderabad, Telangana, India";
                        $emailData['domain']        = 'mintmesh.com';

                        $email_sent = $this->sendEvent($emailData); 

                        $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.candidate_interview_schedule');
                        $this->userEmailManager->emailId = $email;
                        $this->userEmailManager->dataSet = $dataSet;
                        $this->userEmailManager->subject = $subject;
                        $this->userEmailManager->name    = $companyName;
                        //$email_sent = $this->userEmailManager->sendMail();
                        //log email status
                        $emailStatus = self::EMAIL_FAILURE_STATUS;
                        if (!empty($email_sent)) {
                            $emailStatus = self::EMAIL_SUCCESS_STATUS;
                        }
                        $emailLog = array(
                            'emails_types_id'   => 10,
                            'from_user'         => $userId,
                            'from_email'        => $userEmailId,
                            'to_email'          => $this->appEncodeDecode->filterString(strtolower($candidateEmail)),
                            'related_code'      => $companyCode,
                            'sent'              => $emailStatus,
                            'ip_address'        => $_SERVER['REMOTE_ADDR']
                        );
                        $this->userRepository->logEmail($emailLog);
                   }
                } 
            }

            if($emailStatus == self::EMAIL_SUCCESS_STATUS){
                
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    
     public function addCandidateEmail($input) {
        
        $data = $returnArr  = $arrayNewEmail = array();
        #basic input params
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #email input
        $emailSubject  = !empty($input['email_subject']) ? $input['email_subject'] : '';
        $emailSubject  = !empty($input['email_subject_custom']) ? $input['email_subject_custom'] : $emailSubject;
        $emailBody     = !empty($input['email_body']) ? $input['email_body'] : '';
        $subjectId     = !empty($input['subject_id']) ? $input['subject_id'] : '';
        #get company Details by company code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyLogo    = !empty($companyDetails[0]->logo) ? $companyDetails[0]->logo : ''; 
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get the logged in user details
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId             = $this->loggedinUser->id;
        $userName           = $this->loggedinUser->firstname.' '.$this->loggedinUser->lastname;
        $userEmailId        = $this->loggedinUser->emailid;
        #get candidate details here
        $resultArr   = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if($resultArr){
            #form cndidate details here
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $candidateEmail = $candidate->emailid; 
            $candidateId    = $candidate->getID();
            $referredBy     = !empty($relation->referred_by) ? $relation->referred_by : '';
            $candidateName  = $this->postGateway->getCandidateFullNameByEmail($candidateEmail, $referredBy, $companyId);    
            #email input form here
            $dataSet = $userArr = array();
            $dataSet['name']          = $candidateName;
            $dataSet['email']         = $candidateEmail;
            $dataSet['email_subject'] = $emailSubject;
            $dataSet['subject_id']    = $subjectId;
            $dataSet['email_body']    = $emailBody;
            $dataSet['company_logo']  = $companyLogo;
            #send email here
            $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.candidate_invitation');
            $this->userEmailManager->emailId = $candidateEmail;
            $this->userEmailManager->dataSet = $dataSet;
            $this->userEmailManager->subject = $emailSubject;
            $this->userEmailManager->name    = $candidateName;
            $email_sent = $this->userEmailManager->sendMail();
            #logged in user here
            $userArr['user_id']      =  $userId;
            $userArr['user_name']    =  $userName;
            $userArr['user_emailid'] =  $userEmailId;
            //log email status
            $emailStatus = self::EMAIL_FAILURE_STATUS;
            if (!empty($email_sent)) {
                $emailStatus = self::EMAIL_SUCCESS_STATUS;
                $returnArr   = $this->candidatesRepository->addCandidateEmail($dataSet, $userArr, $companyId, $referenceId, $candidateId);
                $arrayNewEmail = $this->getLastInsertEmail($returnArr);
                $data = $arrayNewEmail;
            }
            $emailLog = array(
                'emails_types_id'   => 9,
                'from_user'         => $userId,
                'from_email'        => $userEmailId,
                'to_email'          => $this->appEncodeDecode->filterString(strtolower($candidateEmail)),
                'related_code'      => $companyCode,
                'sent'              => $emailStatus,
                'ip_address'        => $_SERVER['REMOTE_ADDR']
            );
            $this->userRepository->logEmail($emailLog);

            if($emailStatus == self::EMAIL_SUCCESS_STATUS){
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_email.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_email.failure')));
            }
            
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    
    public function addCandidateComment($input) {
        $data = $returnArr = $arrayNewComment = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $comment     = !empty($input['comment']) ? $input['comment'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #get candidate details here
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
        
            $returnArr = $this->candidatesRepository->addCandidateComment($companyId, $comment, $referenceId, $candidateId, $userId);
            #check get career settings details not empty
            if($returnArr){
                $arrayNewComment = $this->getLastInsertComment($returnArr);
                $data = $arrayNewComment;//return career settings details
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_comment.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_comment.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    
    public function getCandidateActivities($input) {
        
        $returnArr   = $data = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
        $resultArr = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if($resultArr){
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            #get Candidate Activities here
            $activitiesArr = $this->candidatesRepository->getCandidateActivities($companyId, $referenceId, $candidateId);
            
            if($activitiesArr){
                foreach($activitiesArr as $activity){
                    $timelinedate = '';
                    $createdAt    = $activity->created_at;
                    $timelinedate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                    $activityText = $activity->activity_text;
                    $moduleType   = $activity->module_name;
                    $comment      = $activity->comment;
                    
                    switch ($moduleType) {
                        case 'candidate_schedules':
                            $message =  "Scheduled ".$activityText." Interview";
                            break;
                        case 'candidate_status':
                            $message = $this->getCandidateStatusMessage($activityText);
                            if($comment){
                                $message.= "<b> - ".$comment."</b>";
                            }
                            break;
                        case 'candidate_link_job':
                            $message = $comment;
                            break;
                        case 'candidate_comments':
                            $message = $comment;
                            break;
                        case 'candidate_emails':
                            $message = $comment;
                            break;
                        default :
                            $message = '';    
                    }       
                    $createdBy    = trim($activity->created_by);
                    $returnArr[]  = array(
                            'activity_id'       => $activity->id,
                            'activity_type'     => $activity->module_name,
                            'activity_status'   => $activityText,
                            'activity_message'  => $message,
                            'activity_comment'  => $comment,
                            'activity_by'       => "by ".$createdBy,
                            'activity_on'       => $timelinedate
                    );
                }
            }

            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    public function addCandidateTagJobs($input) {
        
        $data = $returnArr = $resultArr =  array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $contactId    = !empty($input['contact_id']) ? $input['contact_id'] : '';
        $post_ids     = !empty($input['job_ids']) ? explode(',', $input['job_ids']) : array();
        $pending      = Config::get('constants.REFERRALS.STATUSES.PENDING');
        #get loggedin User Detils here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser();
        $userId             = $this->loggedinUser->id;
        $userEmailId        = $this->loggedinUser->emailid;
        $userFirstname      = $this->loggedinUser->firstname;
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get conadidate id by contact id
        if($contactId){
            $contactArr   = $this->enterpriseRepository->getContactById($contactId);
            $contactEmail = isset($contactArr[0]) ? $contactArr[0]->emailid : '';
            $candidateId  = $this->neoPostRepository->getUserNodeIdByEmailId($contactEmail);
        }
        #get candidate details
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArr)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            $moduleType     = 5;
            $activityText   = 'Link Job';
            
            $neoInput['referral']               = $candidateEmail;
            $neoInput['referred_by']            = $userEmailId;
            $neoInput['status']                 = $pending;
            $neoInput['one_way_status']         = $pending;
            $neoInput['completed_status']       = $pending;
            $neoInput['awaiting_action_status'] = $pending;
            $neoInput['awaiting_action_by']     = $userEmailId;
            $neoInput['relation_count']         = '1';
            $neoInput['uploaded_by_p2']         = '1';
            $neoInput['document_id']            = !empty($relation->document_id) ? $relation->document_id : '';
            $neoInput['resume_original_name']   = !empty($relation->resume_original_name) ? $relation->resume_original_name : '';
            $neoInput['resume_path']            = !empty($relation->resume_path) ? $relation->resume_path : '';
            $neoInput['created_at']             = gmdate('Y-m-d H:i:s'); 
                
            foreach ($post_ids as $postId) {
                
                $refInput       = array();
                $postDetails    = $this->neoPostRepository->getPosts($postId);
                $refInput['post_id']       = $postId;
                $neoInput['referred_for']  = !empty($postDetails->created_by) ? $postDetails->created_by : '';
                $referCandidateArr = $this->neoPostRepository->referCandidate($neoInput, $refInput);
                #add Candidate Activity Logs here
                $serviceName  = !empty($postDetails->service_name) ? $postDetails->service_name : '';
                $comment      = "Linked to ".$serviceName;
                $activityId   = $this->candidatesRepository->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText, $comment) ;
                #return response form here
                $createdAt    = gmdate('Y-m-d H:i:s');;
                $timelineDate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                
                $returnArr['link_job']  = array(
                            'reference_id'  => $referenceId,
                            'post_name'     => $serviceName,
                            'referred_by'   => 'by '.$userFirstname,
                            'referred_on'   => $timelineDate
                );
                $returnArr['timeline']  = array(
                            'activity_id'       => $activityId,
                            'activity_type'     => 'candidate_link_job',
                            'activity_status'   => $activityText,
                            'activity_message'  => $comment,
                            'activity_comment'  => $comment,
                            'activity_by'       => 'by '.$userFirstname,
                            'activity_on'       => $timelineDate
                );
            }
            #check Candidate Refer status
            if($returnArr){
                $data = $returnArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateTagJobsList($input) {
        
        $data = $returnArr = $resultArr =  $jobsList = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $search       = !empty($input['search']) ? $input['search'] : '';
        #get candidate details
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if(!empty($resultArr)){
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateId    = $candidate->getID();
            #get Candidate Got Referred Jobs List here
            $gotReferredJobsArr = $this->neoCandidatesRepository->getCandidateGotReferredJobsList($companyCode, $candidateId);
            if(!empty($gotReferredJobsArr)){
                foreach ($gotReferredJobsArr as $value) {
                    $jobsList[] = isset($value[0]) ? $value[0] : '';
                }
            }
        } 
        #get suggested Tag Jobs List here
        $jobsListArr = $this->neoCandidatesRepository->getCandidateTagJobsList($companyCode, $search);
        
        if(!empty($jobsListArr)){
            
            foreach ($jobsListArr as $val) {
                $post   = array();
                $val    = isset($val[0]) ? $val[0] : '';
                $postId = $val->getID();
                #check already got referred job or not
                if(!in_array($postId, $jobsList)){
                    
                    $post['post_id']   = $postId;
                    $post['post_name'] = isset ($val->service_name) ? $val->service_name : '';
                    $returnArr[] = $post;
                }
            }
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;//return career settings details
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateComments($input) {
        
        $data = $returnArr = $arrayReturn = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
        $resultArrs     = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArrs)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArrs[0]) ? $resultArrs[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            #get Candidate Comments here
            $commentsArr    = $this->candidatesRepository->getCandidateComments($companyId,$referenceId,$candidateId);
            if($commentsArr){
                foreach($commentsArr as $activity){
                    $timelinedate  = '';
                    $createdAt     = $activity->created_at;
                    $timeZone      = !empty($input['time_zone']) ? $input['time_zone'] : 0;
                    $timelinedate  = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();

                    $returnArr[]  = array(
                            'id'                => $activity->id,
                            'comment'           => $activity->comment,
                            'created_by'        => $activity->created_by,
                            'created_at'        => $timelinedate
                    );
                }    
            }

            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateSentEmails($input) {
        
        $data = $returnArr = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
        $resultArrs  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if(!empty($resultArrs)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArrs[0]) ? $resultArrs[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            #get Candidate Sent Emails here
            $sentEmailsArr  = $this->candidatesRepository->getCandidateSentEmails($companyId, $referenceId, $candidateId);

            if($sentEmailsArr){
                
                foreach($sentEmailsArr as $email){
                    $timelinedate   = '';
                    $createdAt      = $email->created_at;
                    $timelinedate   = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                    $subject        = $email->subject;
                    if(!empty($email->custom_subject)){
                         $subject = $email->custom_subject;
                    }
                    $returnArr[] = array(
                            'id'            => $email->id,
                            'to_name'       => $email->to_name,
                            'to_emailid'    => $email->to,
                            'from_emailid'  => $email->from,
                            'from_name'     => $email->from_name,
                            'subject'       => $subject,
                            'body'          => $email->body,
                            'created_by'    => $email->created_by,
                            'created_at'    => $timelinedate
                    );
                }    
            }
            
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateReferralList($input) {
        
        $data = $returnArr = $resultArr = $referralArr = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $timeZone     = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        $search       = !empty($input['search']) ? $input['search'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get Candidate Details
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArr)){
            
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateId    = $candidate->getID();
            #get Candidate Referral List here
            $referralArr = $this->neoCandidatesRepository->getCandidateReferralList($companyCode, $candidateId, $search);
        
            foreach ($referralArr as $val) {
                
                $timeline   = '';
                $record     = array();
                $postVal    = isset($val[0]) ? $val[0] : '';
                $refVal     = isset($val[1]) ? $val[1] : '';
                $createdAt  = isset ($refVal->created_at) ? $refVal->created_at : '';
                $referredBy = isset ($refVal->referred_by) ? $refVal->referred_by : '';
                
                $referredByName = $this->postGateway->getReferredbyUserFullName($referredBy, $companyId);
                $createdDate    = date("M d,Y", strtotime($this->appEncodeDecode->UserTimezone($createdAt, $timeZone)));
                $timeline       = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                #form return result here 
                $record['reference_id']   = $refVal->getID();
                $record['post_name']      = isset ($postVal->service_name) ? $postVal->service_name : '';
                $record['referred_by']    = $referredByName;
                $record['referred_on']    = $timeline;
                $record['referred_date']  = $createdDate;
                $returnArr[] = $record;
            }
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
       
    public function getCandidateSchedules($input) {
        
        $data = $returnArr = $schedulesArr = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
        $resultArrs  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArrs)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArrs[0]) ? $resultArrs[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
             
            #get Candidate Schedules here
            $schedulesArr      = $this->candidatesRepository->getCandidateSchedules($companyId, $referenceId, $candidateId);
            if($schedulesArr){

                foreach($schedulesArr as $res){

                    $timelinedate  = '';
                    $createdAt     = $res->created_at;
                    $timelinedate  = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                    $returnArr[]   = array(
                            'id'                    => $res->id,
                            'schedule_for'          => $res->schedule_for,
                            'attendees'             => explode(',',$res->attendees),
                            'interview_date'        => date('D j M Y', strtotime($res->interview_date)),
                            'interview_from_time'   => date("g:i A", strtotime($res->interview_from_time)),
                            'interview_to_time'     => date("g:i A", strtotime($res->interview_to_time)),
                            'interview_time_zone'   => $res->interview_time_zone,
                            'interview_location'    => $res->interview_location,
                            'notes'                 => $res->notes,
                            'created_by'            => $res->created_by,
                            'created_at'            => $timelinedate
                    );
                }    
            }

            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function editCandidateReferralStatus($input) {
        
        $timelineDate = '';
        $data = $returnArr = $resultArr =  array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $refStatus    = !empty($input['referral_status']) ? $input['referral_status'] : '';
        $refComment   = !empty($input['referral_msg']) ? $input['referral_msg'] : '';
        #get loggedin User Detils here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser();
        $userId             = $this->loggedinUser->id;
        $userEmailId        = $this->loggedinUser->emailid;
        $userFirstname      = $this->loggedinUser->firstname;
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArr)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            $moduleType     = 4;
            $activityText   = $refStatus;
            $activityMsg    = $this->getCandidateStatusMessage($refStatus);
             
            if($referenceId){
                $refStatus    = $this->neoCandidatesRepository->editCandidateReferralStatus($referenceId, $refStatus, $userEmailId);
                $activityId   = $this->candidatesRepository->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText, $refComment) ;
                #return response form here
                if($refComment){
                    $activityMsg.= "<b> - ".$refComment."</b>";
                }
                $createdAt    = gmdate('Y-m-d H:i:s');
                $timelineDate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $returnArr['timeline']  = array(
                            'activity_id'       => $activityId,
                            'activity_type'     => 'candidate_status',
                            'activity_status'   => $activityText,
                            'activity_message'  => $activityMsg,
                            'activity_comment'  => $refComment,
                            'activity_by'       => 'by '.$userFirstname,
                            'activity_on'       => $timelineDate
                );
            }
            #check Candidate Refer status
            if($returnArr){
                $data = $returnArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.apply_job.ref_success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.apply_job.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    

    public function getLastInsertComment($returnArr){
        
                    $timelinedate  = '';
                    $createdAt     = $returnArr[0]->created_at;
                    $timelinedate  = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                    $arrayNewComment['comment']  = array(
                            'id'       => $returnArr[0]->id,
                            'comment'   => $returnArr[0]->comment,
                            'created_by'       => $returnArr[0]->created_by,
                            'created_at'       => $timelinedate
                    );
                    $message = $returnArr[0]->comment;
                    $arrayNewComment['timeline']  = array(
                            'activity_id'       => 0,
                            'activity_type'     => 'candidate_comments',
                            'activity_status'   => 'Comment Added',
                            'activity_message'  => $message,
                            'activity_comment'  => $message,
                            'activity_by'       => 'by '.$returnArr[0]->created_by,
                            'activity_on'       => $timelinedate
                );
            return $arrayNewComment;        
    }
    
   public function getlastInsertEmail($returnArr){
            $timelinedate   = '';
            $createdAt      = $returnArr[0]->created_at;
            $timelinedate   = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
            $subject        = $returnArr[0]->subject;
            if(!empty($returnArr[0]->custom_subject)){
                $subject = $returnArr[0]->custom_subject;
            }
            $arrayNewEmail['email'] = array(
               'id'            => $returnArr[0]->id,
               'to_name'       => $returnArr[0]->to_name,
               'to_emailid'    => $returnArr[0]->to,
               'from_emailid'  => $returnArr[0]->from,
               'from_name'     => $returnArr[0]->from_name,
               'subject'       => $subject,
               'body'          => $returnArr[0]->body,
               'created_by'    => $returnArr[0]->created_by,
               'created_at'    => $timelinedate
            );
             $arrayNewEmail['timeline']  = array(
                            'activity_id'       => 0,
                            'activity_type'     => 'candidate_emails',
                            'activity_status'   => 'Email Sent',
                            'activity_message'  => $subject,
                            'activity_comment'  => $subject,
                            'activity_by'       => 'by '.$returnArr[0]->created_by,
                            'activity_on'       => $timelinedate
                );
            return $arrayNewEmail;
       
   }
   public function getlastInsertSchedules($returnArr) {
                $timelinedate = '';
                $createdAt = $returnArr[0]->created_at;
                $timelinedate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $arrayNewSchedules['schedule'] = array(
                        'id'                    => $returnArr[0]->id,
                        'schedule_for'          => $returnArr[0]->schedule_for,
                        'attendees'             => explode(',',$returnArr[0]->attendees),
                        'interview_date'        => date('D j M Y', strtotime($returnArr[0]->interview_date)),
                        'interview_from_time'   => date("g:i A", strtotime($returnArr[0]->interview_from_time)),
                        'interview_to_time'     => date("g:i A", strtotime($returnArr[0]->interview_to_time)),
                        'interview_time_zone'   => $returnArr[0]->interview_time_zone,
                        'interview_location'    => $returnArr[0]->interview_location,
                        'notes'                 => $returnArr[0]->notes,
                        'created_by'            => 'by '.$returnArr[0]->created_by,
                        'created_at'            => $timelinedate
                );
                $message =  "Scheduled ".$returnArr[0]->schedule_for." Interview";
                $arrayNewSchedules['timeline']  = array(
                            'activity_id'       => 0,
                            'activity_type'     => 'candidate_schedules',
                            'activity_status'   => $returnArr[0]->schedule_for,
                            'activity_message'  => $message,
                            'activity_comment'  => '',
                            'activity_by'       => 'by '.$returnArr[0]->created_by,
                            'activity_on'       => $timelinedate
                );
                
                return $arrayNewSchedules;
       
   }
   
   public function getCandidatesTags($input) {
       $data = $returnArr = $arrayReturn = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
            #get Candidate Comments here
            $returnArr    = $this->candidatesRepository->getCandidatesTags($input['tag_name']);
            if($returnArr){
                $data = $returnArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
         
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
       
   }
   
   
   public function addCandidateTags($input) {
        $data = $returnArr = $arrayNewComment = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $tag_id     = !empty($input['tag_id']) ? $input['tag_id'] : '';
        $tag_name     = !empty($input['tag_name']) ? $input['tag_name'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #get candidate details here
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
        
            $lastInsert = $this->candidatesRepository->addCandidateTags($companyId, $tag_id, $referenceId, $candidateId, $userId);
          
            #check get career settings details not empty
            if($lastInsert){
               // $arrayNewComment = $this->getLastInsertComment($returnArr);
                $arrayNewComment['id'] = $lastInsert;
                $arrayNewComment['tag_id'] = $tag_id;
                $arrayNewComment['tag_name'] = $tag_name;
                $data = $arrayNewComment;//return career settings details
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
   
    
    public function getCandidateTags($input) {
       
       $data = $returnArr = $arrayReturn = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
       $resultArrs     = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
       if(!empty($resultArrs)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArrs[0]) ? $resultArrs[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            #get Candidate Comments here
            $returnArr    = $this->candidatesRepository->getCandidateTags($companyId,$referenceId,$candidateId);
            if($returnArr){
                $data = $returnArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_tag_jobs.candidate_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
       
    }
   
   
   
    public function sendEvent($emailData = array()) {
            
        $return = TRUE;
               $from_name    = $emailData['from_name'];//"Company Epi 1";        
               $from_address = $emailData['from_address'];//"webmaster@example.com";        
               $to_name      = $emailData['to_name'];//"karthik enterpi";        
               $to_address   = $emailData['to_address'];//"j.karthik@enterpi.com";        
               $startTime    = $emailData['start_time'];//"09-03-2017 16:00";        
               $endTime      = $emailData['end_time'];//"09-03-2017 17:00";        
               $subject      = $emailData['subject'];//"Interview with Epi";        
               $description  = $emailData['description'];//"My Awesome Description";        
               $location     = $emailData['location'];//"Hyderabad, Telangana, India";
               $domain       = $emailData['domain'];//'exchangecore.com';

               try{
                    //Create Email Headers
                     $mime_boundary = "----Meeting Booking----".MD5(TIME());
                     $headers = "From: ".$from_name." <".$from_address.">\n";
                     $headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
                     $headers .= "MIME-Version: 1.0\n";
                     $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
                     $headers .= "Content-class: urn:content-classes:calendarmessage\n";

                    //Create Email Body (HTML)
                     $message = "--$mime_boundary\r\n";
                     $message .= "Content-Type: text/html; charset=UTF-8\n";
                     $message .= "Content-Transfer-Encoding: 8bit\n\n";
                     /*$message .= "<html>\n";
                     $message .= "<body>\n";
                     $message .= '<p>Dear '.$to_name.',</p>';
                     $message .= '<p>'.$description.'</p>';
                     $message .= "</body>\n";
                     $message .= "</html>\n";*/
                     $message .= "--$mime_boundary\r\n";

                    $ical = 'BEGIN:VCALENDAR' . "\r\n" .
                    'PRODID:-//Microsoft Corporation//Outlook 10.0 MIMEDIR//EN' . "\r\n" .
                    'VERSION:2.0' . "\r\n" .
                    'METHOD:REQUEST' . "\r\n" .
                    'BEGIN:VTIMEZONE' . "\r\n" .
                    'TZID:Eastern Time' . "\r\n" .
                    'BEGIN:STANDARD' . "\r\n" .
                    'DTSTART:20091101T020000' . "\r\n" .
                    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11' . "\r\n" .
                    'TZOFFSETFROM:-0400' . "\r\n" .
                    'TZOFFSETTO:-0500' . "\r\n" .
                    'TZNAME:EST' . "\r\n" .
                    'END:STANDARD' . "\r\n" .
                    'BEGIN:DAYLIGHT' . "\r\n" .
                    'DTSTART:20090301T020000' . "\r\n" .
                    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3' . "\r\n" .
                    'TZOFFSETFROM:-0500' . "\r\n" .
                    'TZOFFSETTO:-0400' . "\r\n" .
                    'TZNAME:EDST' . "\r\n" .
                    'END:DAYLIGHT' . "\r\n" .
                    'END:VTIMEZONE' . "\r\n" .    
                    'BEGIN:VEVENT' . "\r\n" .
                    'ORGANIZER;CN="'.$from_name.'":MAILTO:'.$from_address. "\r\n" .
                    'ATTENDEE;CN="'.$to_name.'";ROLE=REQ-PARTICIPANT;RSVP=TRUE:MAILTO:'.$to_address. "\r\n" .
                    'LAST-MODIFIED:' . date("Ymd\TGis") . "\r\n" .
                    'UID:'.date("Ymd\TGis", strtotime($startTime)).rand()."@".$domain."\r\n" .
                    'DTSTAMP:'.date("Ymd\TGis"). "\r\n" .
                    'DTSTART;TZID="Eastern Time":'.date("Ymd\THis", strtotime($startTime)). "\r\n" .
                    'DTEND;TZID="Eastern Time":'.date("Ymd\THis", strtotime($endTime)). "\r\n" .
                    'TRANSP:OPAQUE'. "\r\n" .
                    'SEQUENCE:1'. "\r\n" .
                    'SUMMARY:' . $subject . "\r\n" .
                    'LOCATION:' . $location . "\r\n" .
                    'CLASS:PUBLIC'. "\r\n" .
                    'PRIORITY:5'. "\r\n" .
                    'BEGIN:VALARM' . "\r\n" .
                    'TRIGGER:-PT15M' . "\r\n" .
                    'ACTION:DISPLAY' . "\r\n" .
                    'DESCRIPTION:Reminder' . "\r\n" .
                    'END:VALARM' . "\r\n" .
                    'END:VEVENT'. "\r\n" .
                    'END:VCALENDAR'. "\r\n";
                    $message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST'."\n";
                    $message .= "Content-Transfer-Encoding: 8bit\n\n";
                    $message .= $ical;

                    $mailsent = mail($to_address, $subject, $message, $headers);

                       if( count(Mail::failures()) > 0 ) {
                          $return = false ;
                       } else {
                           $return = true ;
                       }
                }
                 catch(\RuntimeException $e)
                {
                    $return = false ;
                }
                
            return true;    
       }
       
       public function getCandidateStatusMessage($status = '') {
           
            $message = '';
            if($status){
                $status = strtoupper(trim($status));
                switch ($status) {
                        case 'NEW':
                            $message =  "";
                            break;
                        case 'REVIEWED':
                            $message =  "Profile <b>Reviewed</b>";
                            break;
                        case 'SHORTLISTED':
                            $message =  "Profile <b>Shortlisted</b>";
                            break;
                        case 'SCHEDULED FOR INTERVIEW':
                            $message =  "<b>Scheduled Interview</b>";
                            break;
                        case 'NOT SUITABLE':
                            $message =  "Profile is <b>Not Suitable</b>";
                            break;
                        case 'SELECTED':
                            $message =  "Status changed to <b>Selected</b>";
                            break;
                        case 'OFFERED':
                            $message =  "Status changed to <b>Offered</b>";
                            break;
                        case 'OFFER ACCEPTED':
                            $message =  "Status changed to <b>Offer Accepted</b>";
                            break;
                        case 'ON HOLD':
                            $message =  "Status changed to <b>On Hold</b>";
                            break;
                        case 'OFFER REJECTED':
                            $message =  "Status changed to <b>Offer Rejected</b> ";
                            break;
                        case 'CONFIRMED TO JOIN':
                            $message =  "Status changed to <b>Confirmed to Join</b>";
                            break;
                        case 'HIRED':
                            $message =  "Status changed to <b>Hired</b> ";
                            break;
                        case 'NOT JOINED':
                            $message =  "Status changed to <b>Not Joined</b>";
                            break;
                        case 'JOINED':
                            $message =  "Status changed to <b>Joined</b>";
                            break;
                        default :$message = "";
                            break;
                    }  
            }
            return $message;  
       }   
       
       
       public function deleteCandidateTag($input) {
       
       $data = $returnArr = $arrayReturn = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $id  = !empty($input['id']) ? $input['id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
        
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        
        
       $resultArrs     = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
       if(!empty($resultArrs)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArrs[0]) ? $resultArrs[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            #get Candidate Comments here
            $returnArr    = $this->candidatesRepository->deleteCandidateTag($companyId, $id, $referenceId, $candidateId, $userId);
           
            if($returnArr){
                $arrayNewComment['id'] = $id;
                $data = $arrayNewComment;//return career settings details
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.deleteContact.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.deleteContact.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
       
    }
    
    
    public function addCandidatePersonalStatus($input) {
        $data = $returnArr = $arrayNewComment = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $status_name     = !empty($input['status_name']) ? $input['status_name'] : '';
        
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #get candidate details here
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
        
            $returnArr = $this->candidatesRepository->addCandidatePersonalStatus($companyId,$referenceId, $candidateId, $userId,$status_name);
          
            #check get career settings details not empty
            if($returnArr['status']==true){
                //$data = $arrayNewComment;//return career settings details
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => $returnArr['msg'] );
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
   
    public function getCandidatePersonalStatus($input) {
       
       $data = $returnArr = $arrayReturn = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details here
       $resultArrs     = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
       if(!empty($resultArrs)){
            
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArrs[0]) ? $resultArrs[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            #get Candidate Comments here
            $returnArr    = $this->candidatesRepository->getCandidatePersonalStatus($companyId,$referenceId,$candidateId);
            if($returnArr){
                $data = $returnArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
       
    }
    
    public function getQuestionTypes($input) {
        
       $returnArr    = $resultArr = $data = array();
       $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
       #get Question Types List here
       $resultArr    = $this->candidatesRepository->getQuestionTypes($companyCode);
       
       if(!empty($resultArr)){
            
            foreach ($resultArr as $value) {
                $record = array();
                $record['id']           = $value->idquestion_type;
                $record['name']         = $value->name;
                $record['description']  = $value->description;
                $returnArr[] = $record;
            }
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getQuestionLibraries($input) {
        
       $returnArr    = $resultArr = $data = array();
       $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
       #get Question Types List here
       $resultArr    = $this->candidatesRepository->getQuestionLibrariesList($companyCode);
       
       if(!empty($resultArr)){
            
            foreach ($resultArr as $value) {
                $record = array();
                $record['library_id']   = $value->idquestion_library;
                $record['library_name'] = $value->name;
                $returnArr[] = $record;
            }
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function addQuestion($input) {
        
        $returnArr      = $resultArr = $data = $qstInput = array();
        $optionsResArr  = $librariesResArr = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $optionsArr     = !empty($input['options']) ? $input['options'] : array();
        $librariesArr   = !empty($input['libraries']) ? $input['libraries'] : array();
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #form Question input params here
        $qstInput['question']       = !empty($input['question']) ? $input['question'] : '';
        $qstInput['qst_type']       = !empty($input['question_type']) ? $input['question_type'] : '';
        $qstInput['qst_value']      = !empty($input['question_value']) ? $input['question_value'] : '';
        $qstInput['qst_notes']      = !empty($input['question_notes']) ? $input['question_notes'] : '';
        $qstInput['is_ans_req']     = !empty($input['is_answer_required']) ? $input['is_answer_required'] : 0;
        $qstInput['has_multi_ans']  = !empty($input['has_multiple_answers']) ? $input['has_multiple_answers'] : 0;
        #Add Question here
        $resultArr  = $this->candidatesRepository->addQuestion($qstInput, $companyId);
        $questionId = !empty($resultArr['id']) ? $resultArr['id'] : 0;
        
        if($questionId) {
            #add Question Options
            if(!empty($optionsArr)) {
                foreach ($optionsArr as $value) {
                    #form Question Option input
                    $optionInput = array();
                    $optionInput['option']         = isset($value['option']) ? $value['option'] : '';
                    $optionInput['is_correct_ans'] = isset($value['is_correct_answer']) ? $value['is_correct_answer'] : 0;
                    #add Question Option here
                    $optionsResArr[] = $this->candidatesRepository->addQuestionOption($optionInput, $questionId);
                }
            }
            #add to Question Bank 
            if(!empty($librariesArr)) {
                foreach ($librariesArr as $library) {
                    #form Question Bank input
                    $libraryInput = array();
                    $libraryInput['library_id']  = isset($library['library_id']) ? $library['library_id'] : 0;
                    $libraryInput['question_id'] = $questionId;
                    #add to Question Bank here
                    $librariesResArr[] = $this->candidatesRepository->addQuestionBank($libraryInput, $companyId);
                }
            }
        }
        #check result success status   
        if(!empty($resultArr)){
            $data = $resultArr;
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_comment.success')));
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function editQuestion($input) {
        
        $returnArr      = $resultArr = $data = $qstInput = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $questionId     = !empty($input['question_id']) ? $input['question_id'] : 0;
        $optionsArr     = !empty($input['options']) ? $input['options'] : array();
        $librariesArr   = !empty($input['libraries']) ? $input['libraries'] : array();
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #form Question input params here
        $qstInput['question']      = !empty($input['question']) ? $input['question'] : '';
        $qstInput['qst_type']      = !empty($input['question_type']) ? $input['question_type'] : '';
        $qstInput['qst_notes']     = !empty($input['question_notes']) ? $input['question_notes'] : '';
        $qstInput['qst_value']     = !empty($input['question_value']) ? $input['question_value'] : '';
        $qstInput['is_ans_req']    = !empty($input['is_answer_required']) ? $input['is_answer_required'] : 0;
        $qstInput['has_multi_ans'] = !empty($input['has_multiple_answers']) ? $input['has_multiple_answers'] : 0;
        #check if Question insert or update
        if(!empty($questionId)){
            #edit Question here
            $resultArr  = $this->candidatesRepository->editQuestion($qstInput, $questionId);
            #edit Question Options
            if(!empty($optionsArr)) {
                
                #insert multiple answers rows
                $this->candidatesRepository->editQuestionOptionInactiveAll($questionId);
                foreach ($optionsArr as $value) {
                    $optionInput = array();
                    $optionId    = !empty($value['option_id']) ? $value['option_id'] : 0;
                    $optionInput['option']         = $value['option'];
                    $optionInput['is_correct_ans'] = isset($value['is_correct_answer']) ? $value['is_correct_answer'] : 0;
                    #check option id
                    if($optionId){
                        $optionInput['status'] = self::STATUS_ACTIVE;
                        $this->candidatesRepository->editQuestionOption($optionInput, $optionId);
                    } else {
                        $this->candidatesRepository->addQuestionOption($optionInput, $questionId);
                    }
                }
            }
            #edit Question Bank Details
            if(!empty($librariesArr)) {
                #insert multiple answers rows
                $this->candidatesRepository->editQuestionBankInactiveAll($questionId);
                foreach ($librariesArr as $value) {
                    #form Question Bank input
                    $libraryInput = array();
                    $qstBankId    = !empty($value['qst_bank_id']) ? $value['qst_bank_id'] : 0;
                    $libraryInput['library_id']   = isset($value['library_id']) ? $value['library_id'] : 0;
                    $libraryInput['question_id']  = $questionId;
                    #check option id
                    if($qstBankId){
                        $libraryInput['status'] = self::STATUS_ACTIVE;
                        $this->candidatesRepository->editQuestionBank($libraryInput, $qstBankId);
                    } else {
                        $this->candidatesRepository->addQuestionBank($libraryInput, $companyId);
                    }
                }
            }
        }  
        #check result success status
        if(!empty($resultArr)){
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.edit_configuration.success')));
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function viewQuestion($input) {
        
        $resultArr   = $data = $qstInput = $optionsArr = $librariesArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $questionId  = !empty($input['question_id']) ? $input['question_id'] : 0;
        #get Question Details here
        $questionResArr  = $this->candidatesRepository->getQuestion($questionId);
        #check if Question result
        if(!empty($questionResArr[0])){
            
            $qstObj  = $questionResArr[0];
            $resultArr['question_id']        = $questionId;
            $resultArr['question']           = !empty($qstObj->question) ? $qstObj->question : '';
            $resultArr['question_notes']     = !empty($qstObj->question_notes) ? $qstObj->question_notes : '';
            $resultArr['question_value']     = !empty($qstObj->question_value) ? $qstObj->question_value : 0;
            $resultArr['question_type']      = !empty($qstObj->question_type) ? $qstObj->question_type : 0;
            #get Question Options here
            $optionsResArr   = $this->candidatesRepository->getQuestionOptions($questionId);
            foreach ($optionsResArr as $value) {
                $optionsArr[] = (array) $value;
            }
            #get Question Libraries here
            $librariesResArr = $this->candidatesRepository->getQuestionLibraries($questionId);
            foreach ($librariesResArr as $value) {
                $librariesArr[] = (array) $value;
            }
            $resultArr['options']   = $optionsArr;
            $resultArr['libraries'] = $librariesArr;  
            #check result success status
            if(!empty($resultArr)){
                $data = $resultArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.edit_configuration.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function addEditExam($input) {
        
        $resultArr = $data = $examInput = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #form Exam input params here
        $examId   = !empty($input['exam_id']) ? $input['exam_id'] : 0;
        $examInput['exam_name']  = !empty($input['exam_name']) ? $input['exam_name'] : '';
        $examInput['exam_type']  = !empty($input['exam_type']) ? $input['exam_type'] : '';
        $examInput['exam_dura']  = !empty($input['exam_duration']) ? $input['exam_duration'] : '';
        $examInput['desc_url']   = !empty($input['description_url']) ? $input['description_url'] : '';
        $examInput['work_exp']   = !empty($input['work_experience']) ? $input['work_experience'] : '';
        
        if(!empty($examId)){
            #edit Exam here
            $resultArr  = $this->candidatesRepository->editExam($examInput, $examId, $userId);
        } else {
            #add Exam here
            $resultArr = $this->candidatesRepository->addExam($examInput, $companyId, $userId);
            $examId    = !empty($resultArr['id']) ? $resultArr['id'] : 0;
            $data      = $resultArr;
        }
        #check result success status   
        if(!empty($resultArr)){
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_comment.success')));
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function editExamSettings($input) {
        
        $resultArr = $data = $examInput = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        #get company details here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #form Exam input params here
        $examId   = !empty($input['exam_id']) ? $input['exam_id'] : 0;
        if(!empty($examId)){
            
            $examInput['is_active']  = !empty($input['is_active']) ? $input['is_active'] : 0;
            $examInput['exam_url']   = !empty($input['exam_url']) ? $input['exam_url'] : '';
            $examInput['password']   = !empty($input['password']) ? $input['password'] : '';
            $examInput['min_marks']  = !empty($input['min_marks']) ? $input['min_marks'] : 0;
            $examInput['exam_dura']  = !empty($input['exam_duration']) ? $input['exam_duration'] : 0;
            $examInput['str_date']   = !empty($input['start_date']) ? $input['start_date'] : '';
            $examInput['end_date']   = !empty($input['end_date']) ? $input['end_date'] : '';
            $examInput['auto_scr']   = !empty($input['is_auto_screening']) ? $input['is_auto_screening'] : 0;
            $examInput['full_scr']   = !empty($input['enable_full_screen']) ? $input['enable_full_screen'] : 0;
            $examInput['shuffle']    = !empty($input['shuffle_questions']) ? $input['shuffle_questions'] : 0;
            $examInput['reminder']   = !empty($input['reminder_emails']) ? $input['reminder_emails'] : 0;
            $examInput['confirm']    = !empty($input['confirmation_email']) ? $input['confirmation_email'] : 0;
            $examInput['pass_protect']   = !empty($input['password_protected']) ? $input['password_protected'] : 0;
            
            $resultArr  = $this->candidatesRepository->editExamSettings($examInput, $examId, $userId);
            #check result success status   
            if(!empty($resultArr)){
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_candidate_comment.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getQuestionsList($input) {
        
       $returnArr    = $data = array();
       $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
       $pageNo       = !empty($input['page_no']) ? $input['page_no'] : 0;
       #get company details here
       $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
       $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
       #get Question Types List here
       $questionResArr    = $this->candidatesRepository->getQuestionsList($companyId, $pageNo);
        #check if Question result
        if(!empty($questionResArr)){
            
            foreach ($questionResArr as $qstObj) {
                $resultArr = array();
                $resultArr['question_id']        = !empty($qstObj->idquestion) ? $qstObj->idquestion : 0;
                $resultArr['question']           = !empty($qstObj->question) ? $qstObj->question : '';
                $resultArr['question_value']     = !empty($qstObj->question_value) ? $qstObj->question_value : 0;
                $resultArr['question_type']      = !empty($qstObj->question_type) ? $qstObj->question_type : '';
                $returnArr[] = $resultArr;
            }
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    

    
    
    public function getCompanyAssessmentsList($input) {
        
       $returnArr    = $data = array();
       $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
       $name  = !empty($input['name']) ? $input['name'] : '';
       #get company details here
       $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
       $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
       #get Question Types List here
       $resultArr    = $this->candidatesRepository->getCompanyAssessmentsList($companyId,$name);
        #check if Question result
        if(!empty($resultArr)){
            
            foreach ($resultArr as $value) {
                $record = array();
                $record['id']           = $value->idexam;
                $record['name']         = $value->name;
                $returnArr[] = $record;
            }
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            if($returnArr){
                $data = $returnArr;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    }
    public function addEditExamQuestion($input) {
        
        $returnArr      = $data = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $examId         = !empty($input['exam_id']) ? $input['exam_id'] : 0;
        $questionId     = !empty($input['question_id']) ? $input['question_id'] : 0;
        $questionValue  = !empty($input['question_value']) ? $input['question_value'] : 0;
        $examQuestionId = !empty($input['exam_question_id']) ? $input['exam_question_id'] : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
       
        if((!empty($examId) && !empty($questionId)) || !empty($examQuestionId)){
  
            if($examQuestionId){
                #remove Exam Question here
                $questionResArr   = $this->candidatesRepository->removeExamQuestion($examQuestionId, $userId);
                $responseMessage  = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                #add Exam Question here
                $questionResArr    = $this->candidatesRepository->addExamQuestion($examId, $questionId, $userId, $questionValue);
                $responseMessage   = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
                $data = $questionResArr;
            }

            if($questionResArr){
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function viewExamQuestion($input) {
        
        $resultArr      = $data = $examQstArr = $questionResArr = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $examId         = !empty($input['exam_id']) ? $input['exam_id'] : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #get Exam Details here                
        $questionResArr   = $this->candidatesRepository->getExamDetails($examId);
        
        if(!empty($questionResArr[0])){
            
            $qstObj  = $questionResArr[0];
            $resultArr['exam_id']         = !empty($qstObj->idexam) ? $qstObj->idexam : '';
            $resultArr['exam_name']       = !empty($qstObj->exam_name) ? $qstObj->exam_name : '';
            $resultArr['exam_type']       = !empty($qstObj->exam_type) ? $qstObj->exam_type : '';
            $resultArr['max_duration']    = !empty($qstObj->max_duration) ? $qstObj->max_duration : '';
            $resultArr['experience_name'] = !empty($qstObj->experience_name) ? $qstObj->experience_name : '';
            #get Exam Question List here
            $examQstResArr   = $this->candidatesRepository->getExamQuestionList($examId);
        
            if(!empty($examQstResArr)){
                
                foreach ($examQstResArr as $value) {
                    $record = array();
                    $record['exam_question_id'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $record['question_id']      = !empty($value->question_id) ? $value->question_id : 0;
                    $record['question']         = !empty($value->question) ? $value->question : '';
                    $record['question_value']   = !empty($value->question_value) ? $value->question_value : 0;
                    $record['question_type']  = !empty($value->question_type) ? $value->question_type : '';
                    $examQstArr[]  = $record;
                }
            }
        
            if($resultArr){
                
                $resultArr['exam_question_list'] = $examQstArr;
                $data = $resultArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getExamDetails($input) {
        
        $returnArr      = $data = $resultArr = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $examId         = !empty($input['exam_id']) ? $input['exam_id'] : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
       
        $questionResArr   = $this->candidatesRepository->getExamDetails($examId);
        
        if(!empty($questionResArr[0])){
            
            $qstObj  = $questionResArr[0];
            $resultArr['exam_id']        = !empty($qstObj->idexam) ? $qstObj->idexam : '';
            $resultArr['exam_name']      = !empty($qstObj->exam_name) ? $qstObj->exam_name : '';
            $resultArr['exam_url']       = !empty($qstObj->exam_url) ? $qstObj->exam_url : '';
            $resultArr['description_url']      = !empty($qstObj->description_url) ? $qstObj->description_url : '';
            $resultArr['work_experience']      = !empty($qstObj->work_experience) ? $qstObj->work_experience : '';
            $resultArr['start_date_time']      = !empty($qstObj->start_date_time) ? $qstObj->start_date_time : '';
            $resultArr['end_date_time']        = !empty($qstObj->end_date_time) ? $qstObj->end_date_time : '';
            $resultArr['is_active']            = !empty($qstObj->is_active) ? $qstObj->is_active : '';
            $resultArr['is_auto_screening']    = !empty($qstObj->is_auto_screening) ? $qstObj->is_auto_screening : '';
            $resultArr['password_protected']   = !empty($qstObj->password_protected) ? $qstObj->password_protected : '';
            $resultArr['password']             = !empty($qstObj->password) ? $qstObj->password : '';
            $resultArr['min_marks']            = !empty($qstObj->min_marks) ? $qstObj->min_marks : '';
            $resultArr['enable_full_screen']   = !empty($qstObj->enable_full_screen) ? $qstObj->enable_full_screen : '';
            $resultArr['shuffle_questions']    = !empty($qstObj->shuffle_questions) ? $qstObj->shuffle_questions : '';
            $resultArr['reminder_emails']      = !empty($qstObj->reminder_emails) ? $qstObj->reminder_emails : '';
            $resultArr['exam_type_name']       = !empty($qstObj->exam_type) ? $qstObj->exam_type : '';
            $resultArr['experience_name']      = !empty($qstObj->experience_name) ? $qstObj->experience_name : '';
        
            if($resultArr){
                $data = $resultArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function deleteQuestion($input) {
        $returnArr      = $resultArr = $data = $qstInput = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $questionId     = !empty($input['question_id']) ? $input['question_id'] : 0;
        if(!empty($questionId)){
            $resultArr  = $this->candidatesRepository->deleteQuestion($questionId);
        }  
        if(!empty($resultArr)){
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.edit_configuration.success')));
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.add_edit_question.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    } 
    
    public function getCompanyAssessmentsAll($input) {
        
       $returnArr    = $data = array();
       $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
       #get company details here
       $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
       $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
       #get Question Types List here
       $resultArr    = $this->candidatesRepository->getCompanyAssessmentsAll($companyId);
       //print_r($resultArr); die;
        #check if Question result
        if(!empty($resultArr)){
            foreach($resultArr as $res){
                
                    $status = !empty($res->is_active) ? "Active" : "Inactive";
                    $returnArr[]  = array(
                            'idexam'         => $res->idexam,
                            'max_duration'   => $res->max_duration,
                            'name'           => $res->name,
                            'idexam_type'    => $res->idexam_type,
                            'experience'     => $res->exp_name,
                            'is_active'      => $status,
                            'qcount'         => $res->qcount,
                            'created_by'     => $res->firstname,
                            'created_at'     => date('M j Y', strtotime($res->created_at))
                    );
                }    
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                if($returnArr){
                    $data = $returnArr;
                    $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
                } else {
                    $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
                }
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data); 
            
       }
       
    public function getAssessment($input) {
        
        $resultArr      = $data = $examQstArr = $questionResArr = $elements = array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $examId         = !empty($input['assessment_id']) ? $input['assessment_id'] : 0;
        #get Logged In User details here
        $this->loggedinUser = $this->referralsGateway->getLoggedInUser(); 
        $userId   = $this->loggedinUser->id;
        #get Exam Details here                
        $questionResArr   = $this->candidatesRepository->getExamDetails($examId);
       // print_r($questionResArr).exit;

        
        $pageFlow = array("nextPage" => true,"label" => "mwForm.pageFlow.goToNextPage");
        if(!empty($questionResArr[0])){
            
            $qstObj  = $questionResArr[0];
            $resultArr['exam_id']         = !empty($qstObj->idexam) ? $qstObj->idexam : '';
            $resultArr['exam_name']       = !empty($qstObj->exam_name) ? $qstObj->exam_name : '';
            $resultArr['exam_type']       = !empty($qstObj->exam_type) ? $qstObj->exam_type : '';
            $resultArr['max_duration']    = !empty($qstObj->max_duration) ? $qstObj->max_duration : '';
            $resultArr['experience_name'] = !empty($qstObj->experience_name) ? $qstObj->experience_name : '';
            $resultArr['max_duration'] = !empty($qstObj->max_duration) ? $qstObj->max_duration : '';
            //$resultArr['pageFlow'] = $pageFlow;
            #get Exam Question List here
            $examQstResArr   = $this->candidatesRepository->getExamQuestionList($examId);
        
            if(!empty($examQstResArr)){
                
                foreach ($examQstResArr as $value) {
                    $record = array();
                    $record['id'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $record['number'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $record['exam_question_id'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $record['question_id']      = $questionId = !empty($value->question_id) ? $value->question_id : 0;
                    $record['question']         = !empty($value->question) ? $value->question : '';
                    $record['question_value']   = !empty($value->question_value) ? $value->question_value : 0;
                    $record['question_type']  = !empty($value->question_type) ? $value->question_type : '';
                    $record['name'] = '';
                    $record['description'] = '';
                    $record['pageFlow'] = $pageFlow;
                    
                    $elements['id'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $elements['exam_question_id'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $elements['orderNo'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;
                    $elements['type']  = !empty($value->question_type) ? $value->question_type : '';
                    

                   
                    $question['id'] = !empty($value->exam_question_id) ? $value->exam_question_id : 0;        
                    $question['text'] = !empty($value->question) ? $value->question : '';        
                    $question['type'] = !empty($value->question_type) ? $value->question_type : '';        
                    $question['required'] = 'true';        
                    $qstOptionsResArr = $this->candidatesRepository->getQuestionOptions($questionId);
                   // print_r($qstOptionsResArr).exit;
                    
                    $qstOptionsResArr = $this->candidatesRepository->getQuestionOptions($questionId);
                    
                    if(!empty($qstOptionsResArr)){
                        foreach ($qstOptionsResArr as $optValue) {
                            $optrecord = array();
                            $optrecord['id'] = !empty($optValue->option_id) ? $optValue->option_id : 0;
                            $optrecord['orderNo'] = !empty($optValue->option_id) ? $optValue->option_id : 0;
                            $optrecord['value'] = !empty($optValue->option) ? $optValue->option : 0;
                            $optrecord['option_id'] = !empty($optValue->option_id) ? $optValue->option_id : 0;
                            //$optrecord['option'] = !empty($optValue->option) ? $optValue->option : 0;
                            $optrecord['pageFlow'] = $pageFlow;
                            $qstOptArray[]  = $optrecord;
                        }
                   
                    $question['offeredAnswers'] = $qstOptArray;
                    $elements['question'] = $question;
                    $record['elements'] = array($elements);
                    $examQstArr[]  = $record;
                    }
                }
            }
        
            if($resultArr){
                
                $resultArr['pages'] = $examQstArr;
                $data = $resultArr;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
}

?>
