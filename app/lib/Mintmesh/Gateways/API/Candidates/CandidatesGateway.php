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
    
    public function getCandidateEmailTemplates($input) {
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        $candidatefirstname = $candidatelastname = $service_name = $company = '';
        if($resultArr){
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $postDetails       = isset($resultArr[2]) ? $resultArr[2] : '';
            $service_name = $postDetails->service_name;
            $company = $postDetails->company;
            $candidateEmail = $candidate->emailid; 
            $candidatefirstname = !empty($candidate->firstname) ? $candidate->firstname : '';
            $candidatelastname = !empty($candidate->lastname) ? $candidate->lastname : '';
            $input['to'] = $candidate->emailid;
            
        }
        
        $returnArr = $this->candidatesRepository->getCandidateEmailTemplates($input);
        
        //$returnArrNew = array();
        foreach($returnArr as  &$resVal){
            $arrayReplace = array( "%fname%" => $candidatefirstname, "%lname%" => $candidatelastname,"%company%" => $company,"%jobtitle%" => $service_name);
            $body_text = strtr($resVal->body, $arrayReplace);
            $subject = strtr($resVal->subject, $arrayReplace);
            //$arrayReplace = array('{%fname%}', '{%lanem%}', '{%Name%}');
            //$arrayReplaceBy = array('Sreenivas', 'Reddy', 'Thanks');
            //$body_text = str_replace($arrayReplace, $arrayReplaceBy, $resVal->body);
            $resVal->subject =  $subject;
            $resVal->body =  $body_text;
        }
        
        
        #check get career settings details not empty
        if($returnArr){
            $data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
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
            
            $qualification  = $candidateName = $referredByName = $skills = '';
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
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

            $returnArr['candidate_id']  = $candidateId;
            $returnArr['name']          = $candidateName;
            $returnArr['emailid']       = $candidateEmail;//'nitinranganath@gmail.com';
            $returnArr['phone']         = !empty($candidateArr['phone']) ? $candidateArr['phone'] : '';//'+91 9852458752';
            #candidate qualification details form here
            $returnArr['location']      = !empty($candidateArr['location']) ? $candidateArr['location'] : '';//'Hyderabad, Telangana';
            $returnArr['qualification'] = !empty($candidateArr['qualification']) ? $candidateArr['qualification'] : '' ;//$qualification;//'B Tech (CSC) From JNTU, Hyderabad';
            $returnArr['certification'] = !empty($candidateArr['Certification']) ? $candidateArr['Certification'] : '' ;//'Android Developer Certification from Google .Inc';
            $returnArr['skills']        = !empty($skills) ? array($skills) : array();//array("Java & XML, C, C++", "Building to Devices", "Cocoa Touch");
            #referral details form here
            $returnArr['document_id']   = !empty($relation->document_id) ? $relation->document_id : 0;
            $returnArr['resume_name']   = !empty($relation->resume_original_name) ? $relation->resume_original_name : Lang::get('MINTMESH.candidates.awaiting_resume');
            $returnArr['resume_path']   = !empty($relation->resume_path) ? $relation->resume_path : '';
            $returnArr['referred_by']   = $referredByName;
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
            if($returnArr){
                $data = $returnArr;
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
            } else {
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
            }
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    public function addCandidateSchedule($input) {
        
        $data = $returnArr = array();
        $candidatefirstname = $candidatelastname = $service_name = $company = $candidateEmail = '';
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $input['interview_date'] = date('Y-m-d', strtotime($input['interview_date']));
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $postDetails       = isset($resultArr[2]) ? $resultArr[2] : '';
            $service_name = $postDetails->service_name;
            $company = $postDetails->company;
            $candidateEmail = $candidate->emailid; 
            $input['to'] = $candidate->emailid;
            $candidateId = $candidate->getID();
            $candidatefirstname = !empty($candidate->firstname) ? $candidate->firstname : '';
            $candidatelastname = !empty($candidate->lastname) ? $candidate->lastname : '';
           
        }
        if($companyCode){
         $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
         $company_logo      = !empty($companyDetails[0]->logo)?$companyDetails[0]->logo:''; 
         $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        }
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        if($this->loggedinUserDetails){
            $userId             = $this->loggedinUserDetails->id;
            $arrayuser['id'] = $this->loggedinUserDetails->id;
            $arrayuser['firstname'] = $this->loggedinUserDetails->firstname;
            $arrayuser['lastname'] = $this->loggedinUserDetails->lastname;
            $arrayuser['middlename'] = $this->loggedinUserDetails->middlename;
            $arrayuser['emailid'] = $this->loggedinUserDetails->emailid;
        } 
        $subject = ' Interview with '.$company;
        $dataSet['message'] = 'You are invited to an interview with '.$company; 
        $dataSet['interview_when'] = date('D j M Y', strtotime($input['interview_date'])).' '.$input['interview_from_time'].date('A', strtotime($input['interview_date'])).'-'.$input['interview_to_time'].date('A', strtotime($input['interview_date']));
        $dataSet['interview_timezone'] = $input['interview_time_zone'];
        $dataSet['interview_who'] = $candidatefirstname.' '.$candidatelastname;
        
        $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.candidate_interview_schedule');
        $this->userEmailManager->emailId = $candidateEmail;
        $this->userEmailManager->dataSet = $dataSet;
        $this->userEmailManager->subject = $subject;
        
        $email_sent = $this->userEmailManager->sendMail();
        //log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
            $returnArr = $this->candidatesRepository->addCandidateSchedule($input,$userId,$referenceId,$candidateId,$companyId);
        }
        $emailLog = array(
            'emails_types_id'   => 10,
            'from_user'         => $arrayuser['id'],
            'from_email'        => $arrayuser['emailid'],
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($candidateEmail)),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $_SERVER['REMOTE_ADDR']
        );
        $this->userRepository->logEmail($emailLog); 
        if(!empty($input['attendees'])){
         $emails = explode(',', $this->config->get('attendees'));
	 foreach ($emails as $email) {
            if ($email && preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $email)) {
                    $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.candidate_interview_schedule');
        $this->userEmailManager->emailId = $email;
        $this->userEmailManager->dataSet = $dataSet;
        $this->userEmailManager->subject = $subject;
        $email_sent = $this->userEmailManager->sendMail();
        //log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
        }
        $emailLog = array(
            'emails_types_id'   => 10,
            'from_user'         => $arrayuser['id'],
            'from_email'        => $arrayuser['emailid'],
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($candidateEmail)),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $_SERVER['REMOTE_ADDR']
        );
        $this->userRepository->logEmail($emailLog);
            }
        
         } 
        }
        
        
        #check get career settings details not empty
        if($emailStatus == 1){
            
            //$data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
     public function addCandidateEmail($input) {
        
        $data = $returnArr = array();
        $candidatefirstname = $candidatelastname = $service_name = $company = $candidateEmail = '';
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        $candidateEmail = '';
        if($resultArr){
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $relation       = isset($resultArr[1]) ? $resultArr[1] : '';
            $candidateEmail = $candidate->emailid; 
            $input['to'] = $candidate->emailid;
            $candidateId = $candidate->getID();
            $candidatefirstname = !empty($candidate->firstname) ? $candidate->firstname : '';
            $candidatelastname = !empty($candidate->lastname) ? $candidate->lastname : '';
            $input['to_name'] = $candidatefirstname.' '.$candidatelastname;
            
        }
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
       
        if($this->loggedinUserDetails){
            $arrayuser['id'] = $this->loggedinUserDetails->id;
            $arrayuser['firstname'] = $this->loggedinUserDetails->firstname;
            $arrayuser['lastname'] = $this->loggedinUserDetails->lastname;
            $arrayuser['middlename'] = $this->loggedinUserDetails->middlename;
            $arrayuser['emailid'] = $this->loggedinUserDetails->emailid;
        } 
        $company_logo = '';
        if($companyCode){
         $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
         $company_logo      = !empty($companyDetails[0]->logo)?$companyDetails[0]->logo:''; 
         $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        }
       
        
        
        $subject = $input['subject'];
             if(!empty($input['custom_subject'])){
                 $subject = $input['custom_subject'];
             }
        $dataSet['body'] = $input['body'];
        $dataSet['company_logo'] = $company_logo;
        $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.candidate_invitation');
        $this->userEmailManager->emailId = $candidateEmail;
        $this->userEmailManager->dataSet = $dataSet;
        $this->userEmailManager->subject = $subject;
        
        $email_sent = $this->userEmailManager->sendMail();
        //log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
            $returnArr = $this->candidatesRepository->addCandidateEmail($input,$arrayuser,$companyId,$referenceId,$candidateId);
        }
        $emailLog = array(
            'emails_types_id'   => 9,
            'from_user'         => $arrayuser['id'],
            'from_email'        => $arrayuser['emailid'],
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($candidateEmail)),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $_SERVER['REMOTE_ADDR']
        );
        $this->userRepository->logEmail($emailLog);
        
        #check get career settings details not empty
        
        if($emailStatus == 1){
            //$data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
    public function addCandidateComment($input) {
        
        $data = $returnArr = $arrayuser = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $comment = !empty($input['comment']) ? $input['comment'] : '';
        
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        if($this->loggedinUserDetails){
            $userId             = $this->loggedinUserDetails->id;
        }
        
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
        }
        $returnArr = $this->candidatesRepository->addCandidateComment($companyId,$comment,$referenceId,$candidateId,$userId);
        #check get career settings details not empty
        if($returnArr){
           // $data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
    public function getCandidateActivities($input) {
        //echo '<pre>'; print_r($input); die;
        $returnArr = $data = $arrayReturn = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        if($resultArr){
            $neoInput       = $refInput = array();
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateEmail = $candidate->emailid;
            $candidateId    = $candidate->getID();
            $candidateId = $candidate->getID();
        }
        
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        
        $returnArr = $this->candidatesRepository->getCandidateActivities($companyId,$referenceId,$candidateId);
        if($returnArr){
            foreach($returnArr as $res){
                $timelinedate = '';
                $createdat = $res->created_at;
                $timeZone   = !empty($input['time_zone']) ? $input['time_zone'] : 0;
                $createdAt= date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($createdat, $timeZone)));
                $timelinedate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $arrayReturn[] = array(
                        'activity_id'       => $res->id,
                        'activity_type'     => $res->module_name,
                        'activity_status'   => $res->activity_text,
                        'activity_message'  => '',
                        'activity_by'       => 'by '.$res->created_by,
                        'activity_on'       => $timelinedate
                    
                );
            }
        }
      /*  
        $returnArr[0] = array('activity_id'       => '5002',
                        'activity_type'     => 'CANDIDATE_STATUS',
                        'activity_status'   => 'pending',
                        'activity_message'  => 'status changed to',
                        'activity_by'       => 'ramesh s',
                        'activity_on'       => '1 hour ago'
                        );
        $returnArr[1] = array('activity_id'       => '5012',
                        'activity_type'     => 'CANDIDATE_EMAILS',
                        'activity_message'  => 'sent email',
                        'activity_by'       => 'raju',
                        'activity_on'       => 'jul 10,2017'
                        );
        $returnArr[2] = array('activity_id'       => '5302',
                        'activity_type'     => 'CANDIDATE_COMMENTS',
                        'activity_message'  => 'given assignment for machine test',
                        'activity_by'       => 'karthik j',
                        'activity_on'       => '2 days ago'
                        );
        $returnArr[3] = array('activity_id'       => '4002',
                        'activity_type'     => 'CANDIDATE_STATUS',
                        'activity_status'   => 'interview',
                        'activity_message'  => 'status changed to',
                        'activity_by'       => 'gopi v',
                        'activity_on'       => '2 hour ago'
                        );
        */

        #check get career settings details not empty
        if($arrayReturn){
            $data = $arrayReturn;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
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
            }
            #check Candidate Refer status
            if($returnArr){
                $data = $returnArr;
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.apply_job.ref_success')));
            } else {
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array('msg' => array(Lang::get('MINTMESH.apply_job.failure')));
            }
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
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
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    
    
     public function getCandidateComments($param) {
        
        $data = $returnArr = $arrayReturn = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        $returnArr = $this->candidatesRepository->getCandidateComments($companyId,$referenceId,$candidateId);
        if($returnArr){
            foreach($returnArr as $res){
                $timelinedate = '';
                $createdat = $res->created_at;
                $timeZone   = !empty($param['time_zone']) ? $param['time_zone'] : 0;
                $createdAt= date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($createdat, $timeZone)));
                $timelinedate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $arrayReturn[] = array(
                        'id'       => $res->id,
                        'comment'     => $res->comment,
                        'created_by'       => 'by '.$res->created_by,
                        'created_at'       => $timelinedate
                );
            }    
        }
        #check get career settings details not empty
        if($arrayReturn){
            $data = $arrayReturn;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
    public function getCandidateSentEmails($param) {
        
        $data = $returnArr = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        
        $returnArr = $this->candidatesRepository->getCandidateSentEmails($referenceId,$candidateId,$companyId);
        
       if($returnArr){
            foreach($returnArr as $res){
                $timelinedate = '';
                $createdat = $res->created_at;
                $timeZone   = !empty($param['time_zone']) ? $param['time_zone'] : 0;
                $createdAt= date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($createdat, $timeZone)));
                $timelinedate = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $subject = $res->subject;
                if(!empty($res->custom_subject)){
                     $subject = $res->custom_subject;
                }
                $arrayReturn[] = array(
                        'id'       => $res->id,
                        'to_name'       => $res->to_name,
                        'from'     => $res->from,
                        'subject'     => $subject,
                        'body'     => $res->body,
                        'created_by'       => 'by '.$res->created_by,
                        'created_at'       => $timelinedate
                    
                );
            }    
        }
        
        
        #check get career settings details not empty
        if($arrayReturn){
            $data = $arrayReturn;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    public function getCandidateReferralList($input) {
        
        $data = $returnArr = $resultArr = $referralArr = array();
        $companyCode  = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId  = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidateId  = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        $search       = !empty($input['search']) ? $input['search'] : '';
        #get Candidate Details
        $resultArr  = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $candidateId, $referenceId);
        
        if(!empty($resultArr)){
            
            $candidate      = isset($resultArr[0]) ? $resultArr[0] : '';
            $candidateId    = $candidate->getID();
            #get Candidate Referral List here
            $referralArr = $this->neoCandidatesRepository->getCandidateReferralList($companyCode, $candidateId, $search);
        
            foreach ($referralArr as $val) {
                
                $record   = array();
                $postVal  = isset($val[0]) ? $val[0] : '';
                $refVal   = isset($val[1]) ? $val[1] : '';
                $record['reference_id']   = $refVal->getID();
                $record['post_name']      = isset ($postVal->service_name) ? $postVal->service_name : '';
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
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
       
}

?>
