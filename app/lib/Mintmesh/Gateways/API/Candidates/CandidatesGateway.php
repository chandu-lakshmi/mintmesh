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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
        
        $data   = $returnArr = $resultArr = $contactArr = array();
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
            $returnArr['referral_status']   = !empty($relation->referral_status) ? $relation->referral_status : 'New';
            #candidate professional details form here
            $returnArr['current_company_name']      = '';//'EnterPi Software Solutions Pvt Ltd';
            $returnArr['current_company_details']   = '';//'May 2015 - Present(2 years 3 months)';
            $returnArr['current_company_location']  = '';//'Hyderabad Area, India';
            $returnArr['current_company_position']  = '';//'Sr Android Engineer';
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
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
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.success')));
            } else {
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.failure')));
            }
            
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.failure')));
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
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_success')));
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
                    $timeZone     = !empty($input['time_zone']) ? $input['time_zone'] : 0;
                    $timelinedate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                    $returnArr[]  = array(
                            'activity_id'       => $activity->id,
                            'activity_type'     => $activity->module_name,
                            'activity_status'   => $activity->activity_text,
                            'activity_message'  => '',
                            'activity_comment'  => $activity->comment,
                            'activity_by'       => 'by '.$activity->created_by,
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
            $neoInput['resume_original_name']   = '';
            $neoInput['created_at']             = gmdate('Y-m-d H:i:s'); 
                
            foreach ($post_ids as $postId) {
                
                $refInput       = array();
                $postDetails    = $this->neoPostRepository->getPosts($postId);
                $refInput['post_id']       = $postId;
                $neoInput['referred_for']  = !empty($postDetails->created_by) ? $postDetails->created_by : '';
                $returnArr = $this->neoPostRepository->referCandidate($neoInput, $refInput);
                $this->candidatesRepository->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText) ;
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.apply_job.referrer_invalid')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateTagJobsList($input) {
        
        $data = $returnArr = $resultArr =  array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidate_id = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $search       = !empty($input['search']) ? $input['search'] : '';
        #get Tag Jobs List here
        $resultArr = $this->neoCandidatesRepository->getCandidateTagJobsList($companyCode, $search);
        
        if(!empty($resultArr)){
            
            foreach ($resultArr as $val) {
                $post = array();
                $val  = isset($val[0]) ? $val[0] : '';
                $post['post_id']   = $val->getID();
                $post['post_name'] = isset ($val->service_name) ? $val->service_name : '';
                $returnArr[] = $post;
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getCandidateReferralList($input) {
        
        $data = $returnArr = $resultArr = $referralArr = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
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
                $timeline       = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                #form return result here 
                $record['reference_id']   = $refVal->getID();
                $record['post_name']      = isset ($postVal->service_name) ? $postVal->service_name : '';
                $record['referred_by']    = $referredByName;
                $record['referred_on']    = $timeline;
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
                            'attendees'             => $res->attendees,
                            'interview_date'        => date('D j M Y', strtotime($res->interview_date)),
                            'interview_from_time'   => $res->interview_from_time.date('A', strtotime($res->interview_from_time)),
                            'interview_to_time'     => $res->interview_to_time.date('A', strtotime($res->interview_to_time)),
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
        $refComment   = !empty($input['referral_comment']) ? $input['referral_comment'] : '';
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
             
            if($referenceId){
                $refStatus    = $this->neoCandidatesRepository->editCandidateReferralStatus($referenceId, $refStatus, $userEmailId);
                $activityId   = $this->candidatesRepository->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText, $refComment) ;
                #return response form here
                $createdAt    = gmdate('Y-m-d H:i:s');;
                $timelineDate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $returnArr['timeline']  = array(
                            'activity_id'       => $activityId,
                            'activity_type'     => 'candidate_status',
                            'activity_status'   => $activityText,
                            'activity_message'  => '',
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.apply_job.referrer_invalid')));
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
                    $arrayNewComment['timeline']  = array(
                            'activity_id'       => 0,
                            'activity_type'     => 'candidate_comments',
                            'activity_status'   => 'Comment Added',
                            'activity_message'  => '',
                            'activity_comment'  => '',
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
            if(!empty($email->custom_subject)){
                $subject = $returnArr[0]->custom_subject;
            }
            $arrayNewEmail['email'] = array(
               'id'            => $returnArr[0]->id,
               'to_name'       => $returnArr[0]->to_name,
               'from_name'     => $returnArr[0]->from,
               'subject'       => $subject,
               'body'          => $returnArr[0]->body,
               'created_by'    => $returnArr[0]->created_by,
               'created_at'    => $timelinedate
            );
             $arrayNewEmail['timeline']  = array(
                            'activity_id'       => 0,
                            'activity_type'     => 'candidate_emails',
                            'activity_status'   => 'Email Sent',
                            'activity_message'  => '',
                            'activity_comment'  => '',
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
                        'attendees'             => $returnArr[0]->attendees,
                        'interview_date'        => date('D j M Y', strtotime($returnArr[0]->interview_date)),
                        'interview_from_time'   => $returnArr[0]->interview_from_time.date('A', strtotime($returnArr[0]->interview_from_time)),
                        'interview_to_time'     => $returnArr[0]->interview_to_time.date('A', strtotime($returnArr[0]->interview_to_time)),
                        'interview_time_zone'   => $returnArr[0]->interview_time_zone,
                        'interview_location'    => $returnArr[0]->interview_location,
                        'notes'                 => $returnArr[0]->notes,
                        'created_by'            => 'by '.$returnArr[0]->created_by,
                        'created_at'            => $timelinedate
                );
               
                $arrayNewSchedules['timeline']  = array(
                            'activity_id'       => 0,
                            'activity_type'     => 'candidate_schedules',
                            'activity_status'   => $returnArr[0]->schedule_for." Schedule",
                            'activity_message'  => '',
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
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
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
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
                //$data = $returnArr;
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
   
}

?>
