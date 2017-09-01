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

    protected $enterpriseRepository, $commonFormatter, $authorizer, $appEncodeDecode,$neoEnterpriseRepository,$userFileUploader, $neoUserRepository;
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
                                NeoUserRepository $neoUserRepository,
                                PostGateway $postGateway,
                                UserGateway $userGateway
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
        $this->neoUserRepository        = $neoUserRepository;
        $this->postGateway              = $postGateway;
        $this->userGateway              = $userGateway;
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
    public function validategetCompanyEmployeesInput($input) {
        return $this->doValidation('get_company_employees', 'MINTMESH.user.valid');
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
    
    public function getCandidateEmailTemplates($param) {
        
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        
        $returnArr = $this->candidatesRepository->getCandidateEmailTemplates($param);
        
        //$returnArrNew = array();
        foreach($returnArr as  &$resVal){
            $arrayReplace = array( "%fname%" => "sreenivas", "%lname%" => "reddy","%company%" => "Epi","%jobtitle%" => "sr Developer");
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
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.not_parsed_resumes.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    public function getCandidateFullDetails($candidateEmail = '') {
        
        #variable declaration here
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
        }
        return $returnArr;
    }
    
    public function getCandidateDetails($input) {
        
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referredId  = !empty($input['referred_id']) ? $input['referred_id'] : '';
        
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        #get candidate details
        $resultArr      = $this->neoCandidatesRepository->getCandidateDetails($companyCode, $referredId);
        
        $relation   = $resultArr[0];
        $candidate  = $resultArr[1];
        
        $candidateEmail = $candidate->emailid;
        //$candidateArr  = $this->getCandidateFullDetails($candidateEmail);
        
        #get referred by name here
        $candidateName  = $this->postGateway->getCandidateFullNameByEmail($candidateEmail, $relation->referred_by, $companyId);    
        $referredByName = $this->postGateway->getReferredbyUserFullName($relation->referred_by, $companyId);    
        
        $returnArr['name']          = $candidateName;
        $returnArr['emailid']       = $candidateEmail;//'nitinranganath@gmail.com';
        $returnArr['phone']         = !empty($candidate->phone) ? $candidate->phone : '';//'+91 9852458752';
        $returnArr['location']      = !empty($candidate->location) ? $candidate->location : '';//'Hyderabad, Telangana';
        $returnArr['qualification'] = 'B Tech (CSC) From JNTU, Hyderabad';
        $returnArr['certification'] = 'Android Developer Certification from Google .Inc';
        $returnArr['referred_by']   = $referredByName;
        $returnArr['current_company_name']      = 'EnterPi Software Solutions Pvt Ltd';
        $returnArr['current_company_details']   = 'May 2015 - Present(2 years 3 months)';
        $returnArr['current_company_location']  = 'Hyderabad Area, India';
        $returnArr['current_company_position']  = 'Sr Android Engineer';
        $returnArr['previous_company_name']     = 'HTC Global Services (India) Private Ltd';
        $returnArr['previous_company_details']  = 'May 2013 - May 2015 Present(2 years)';
        $returnArr['previous_company_location'] = 'Bangalore Area, India';
        $returnArr['previous_company_position'] = 'Jr. Android Engineer';
        $returnArr['skills']                    = array("Java & XML, C, C++", "Building to Devices", "Cocoa Touch", "Develop software solutions by studying information needs, conferring with users.", "Distubuting an App (prefearably for an app on the App Store");

        #check get career settings details not empty
        if($returnArr){
            $data = $returnArr;//return career settings details
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
    
    
    
     public function getCompanyEmployees($param) {
        
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        
        $returnArr = $this->candidatesRepository->getCompanyEmployees($param);
        
       
        
        
        #check get career settings details not empty
        if($returnArr){
            $data = $returnArr;//return career settings details
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
    
    

     public function addCandidateSchedule($input) {
        
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
         $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        if($this->loggedinUserDetails){
            $userId             = $this->loggedinUserDetails->id;
        } 
        
        $returnArr = $this->candidatesRepository->addCandidateSchedule($input,$userId);
        #check get career settings details not empty
        if($returnArr){
            //$data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.user.create_success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
     public function addCandidateEmail($input) {
        
        $data = $returnArr = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        if($this->loggedinUserDetails){
            
            
            $arrayuser['id'] = $this->loggedinUserDetails->id;
            $arrayuser['firstname'] = $this->loggedinUserDetails->firstname;
            $arrayuser['lastname'] = $this->loggedinUserDetails->lastname;
            $arrayuser['middlename'] = $this->loggedinUserDetails->middlename;
        } 
        
        $returnArr = $this->candidatesRepository->addCandidateEmail($input,$arrayuser);
        #check get career settings details not empty
        if($returnArr){
            //$data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.resendActivationLink.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.resendActivationLink.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
    public function addCandidateComment($input) {
        
        $data = $returnArr = $arrayuser = array();
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $referenceId = !empty($input['reference_id']) ? $input['reference_id'] : '';
        $candidate_id = !empty($input['candidate_id']) ? $input['candidate_id'] : '';
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        
        
        if($this->loggedinUserDetails){
            $userId             = $this->loggedinUserDetails->id;
        } 
        
        $returnArr = $this->candidatesRepository->addCandidateComment($input,$userId);
        #check get career settings details not empty
        if($returnArr){
           // $data = $returnArr;//return career settings details
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.user.create_success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.user.create_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        
    }
    
    
    public function getCandidateActivities($input) {
        $returnArr = array();
        //$returnArr = $this->candidatesRepository->getCandidateActivities($input);
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
        

        #check get career settings details not empty
        if($returnArr){
            //$data = $returnArr;//return career settings details
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
       
}

?>
