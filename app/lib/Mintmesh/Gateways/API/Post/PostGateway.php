<?php

namespace Mintmesh\Gateways\API\Post;

/**
 * This is the Post Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Repositories\API\Enterprise\EnterpriseRepository;
use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Repositories\API\Enterprise\NeoEnterpriseRepository;
use Mintmesh\Repositories\API\Payment\PaymentRepository;
use Mintmesh\Gateways\API\Payment\PaymentGateway;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
//use Mintmesh\Repositories\API\Globals\NeoGlobalRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
use Mintmesh\Services\Emails\API\User\UserEmailManager;
use API\User\UserController;
use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Mintmesh\Services\Validators\API\Post\PostValidator;
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
use Mintmesh\Services\Parser\DocxConversion;
use Mintmesh\Services\Parser\PdfParser;
use Mintmesh\Services\Parser\ParserManager;
use lib\Parser\MyEncrypt;
use job2;
use Mintmesh\Services\IntegrationManager\IntegrationManager;

class PostGateway {

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
    const COMPANY_RESUME_AI_PARSED_STATUS = 2;

    protected $enterpriseRepository, $commonFormatter, $authorizer, $appEncodeDecode,$neoEnterpriseRepository,$userFileUploader,$job2,$paymentRepository;
    protected $createdNeoUser, $postValidator, $referralsRepository, $enterpriseGateway, $userGateway, $contactsRepository, $userEmailManager,$paymentGateway;

    public function __construct(NeoPostRepository $neoPostRepository, 
                                UserRepository $userRepository, 
                                NeoUserRepository $neoUserRepository, 
//                                NeoGlobalRepository $neoGlobalRepository, 
                                UserGateway $userGateway, 
                                UserController $userController, 
                                ReferralsGateway $referralsGateway, 
                                EnterpriseGateway $enterpriseGateway,
                                PaymentRepository $paymentRepository,
                                PaymentGateway $paymentGateway,
                                Authorizer $authorizer, 
                                CommonFormatter $commonFormatter, 
                                APPEncode $appEncodeDecode, 
                                postValidator $postValidator, 
                                NeoEnterpriseRepository $neoEnterpriseRepository, 
                                referralsRepository $referralsRepository,
                                ContactsRepository $contactsRepository,
                                EnterpriseRepository $enterpriseRepository,
                                UserFileUploader $userFileUploader,
                                UserEmailManager $userEmailManager,
                                job2 $job2
                                
    ) {
        $this->neoPostRepository = $neoPostRepository;
//        $this->neoGlobalRepository = $neoGlobalRepository;
        $this->userController = $userController;
        $this->userRepository = $userRepository;
        $this->neoEnterpriseRepository = $neoEnterpriseRepository;
        $this->neoUserRepository = $neoUserRepository;
        $this->referralsRepository = $referralsRepository;
        $this->userGateway = $userGateway;
        $this->paymentRepository = $paymentRepository ;
        $this->paymentGateway = $paymentGateway ;
        $this->referralsGateway = $referralsGateway;
        $this->enterpriseGateway = $enterpriseGateway;
        $this->authorizer = $authorizer;
        $this->postValidator = $postValidator;
        $this->commonFormatter = $commonFormatter;
        $this->appEncodeDecode = $appEncodeDecode;
        $this->contactsRepository = $contactsRepository;
        $this->enterpriseRepository = $enterpriseRepository;
        $this->userFileUploader = $userFileUploader;
        $this->userEmailManager = $userEmailManager;
        $this->job2 = $job2 ;
            
    }
    
    public function doValidation($validatorFilterKey, $langKey) {
        //validator passes method accepts validator filter key as param
        if ($this->postValidator->passes($validatorFilterKey)) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get($langKey)));
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $data = array();
        } else {
            /* Return validation errors to the controller */
            $message = $this->postValidator->getErrors();
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $data = array();
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }

    //validation on job posting
    public function validatePostJobInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->postValidator->passes('job_post')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.post.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->postValidator->getErrors(), array());
    }

    //validation on job posting
    public function validatejobsListInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->postValidator->passes('jobs_list')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.post.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->postValidator->getErrors(), array());
    }

    //validation on job details
    public function validatejobDetailsInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->postValidator->passes('job_details')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.post.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->postValidator->getErrors(), array());
    }

    //validation on referral details
    public function jobReferralDetailsInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->postValidator->passes('referral_details')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.post.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->postValidator->getErrors(), array());
    }

    //validation on process job input
    public function processJobInput($input) {
        return $this->enterpriseGateway->doValidation('process_job', 'MINTMESH.user.valid');
    }
    
    //validation on awaiting Action Input
    public function awaitingActionInput($input) {
        return $this->enterpriseGateway->doValidation('awaiting_action', 'MINTMESH.user.valid');
    }
    //validation on awaiting Action Input
    public function jobRewardsInput($input) {
        return $this->enterpriseGateway->doValidation('job_rewards', 'MINTMESH.user.valid');
    }
    
    //validation on add Campaign Input
    public function addCampaignInput($input) {
        return $this->doValidation('add_campaign', 'MINTMESH.user.valid');
    }
    //validation on add Campaign Input
    public function viewCampaignInput($input) {
        return $this->doValidation('view_campaign', 'MINTMESH.user.valid');
    }
    
    //validation on multiple awaiting action
    public function MultipleAwaitingActionInput($input) {
        return $this->doValidation('multiple_awaiting_action', 'MINTMESH.user.valid');
    }
    
    //validation on applying job input
    public function applyJobInput($input) {
        return $this->doValidation('apply_job', 'MINTMESH.user.valid');
    }
    //validation on applying jobs list input
    public function applyJobsListInput($input) {
        return $this->doValidation('apply_jobs_list', 'MINTMESH.user.valid');
    }
    
    //validation on posting job from campaigns input
    public function validatejobPostFromCampaignsInput($input) {
        return $this->doValidation('job_post_from_campaigns', 'MINTMESH.user.valid');
    }
    
    //validation on validate Get Jobs List Input
    public function validateGetJobsListInput($input) {
        return $this->doValidation('get_jobs_list', 'MINTMESH.user.valid');
    }
    
    //validation on validate Get Job Details Input
    public function validateGetJobDetailsInput($input) {
        return $this->doValidation('get_job_details', 'MINTMESH.user.valid');
    }
    
    //validation on campaign jobs list
    public function campaignJobsListInput($input) {
        return $this->doValidation('campaign_jobs_list', 'MINTMESH.user.valid');
    }
    //validation on campaign jobs list
    public function validateUploadResumeInput($input) {
        return $this->doValidation('upload_resume', 'MINTMESH.user.valid');
    }
    //validation on Not Parsed Resumes
    public function validateNotParsedResumes($input) {
        return $this->doValidation('not_parsed_resumes', 'MINTMESH.user.valid');
    }
    
    public function postJob($input) {
        
        $objCompany  = new \stdClass();
        $bucket_id   = explode(',', $input['bucket_id']);
        $rewardsAry  = !empty($input['rewards']) ? $input['rewards'] :array();
        $this->loggedinEnterpriseUserDetails    = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $fromId     = $this->neoLoggedInEnterpriseUserDetails->id;
        $emailId    = $this->loggedinEnterpriseUserDetails->emailid;
        $fromName   = $this->loggedinEnterpriseUserDetails->firstname;
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($input['company_code']);
        if ($this->loggedinEnterpriseUserDetails) {
            $relationAttrs = $neoInput = $excludedList = $getSkillsAry = $usersAry = array();
            $neoInput['service_scope']      = "find_candidate";
            $neoInput['service_from_web']   = 1;
            $neoInput['service_name']       = !empty($input['job_title'])?$input['job_title']:'';
            $neoInput['looking_for']        = !empty($input['job_title'])?$input['job_title']:'';
            $neoInput['job_function']       = !empty($input['job_function'])?$input['job_function']:'';
            $neoInput['service_location']   = !empty($input['location'])?$input['location']:'';
            $neoInput['service_country']    = !empty($input['country_code'])?$input['country_code']:'';
            $neoInput['industry']           = !empty($input['industry'])?$input['industry']:'';
            $neoInput['employment_type']    = !empty($input['employment_type'])?$input['employment_type']:'';
            $neoInput['experience_range']   = !empty($input['experience_range'])?$input['experience_range']:'';
            $neoInput['service']            = !empty($input['job_description'])?$input['job_description']:'';
            $neoInput['position_id']        = !empty($input['position_id'])?$input['position_id'] : "";
            $neoInput['requistion_id']      = !empty($input['requistion_id'])?$input['requistion_id'] : "";
            $neoInput['no_of_vacancies']    = !empty($input['no_of_vacancies'])?$input['no_of_vacancies'] :0;
            $neoInput['service_period']     = !empty($input['job_period'])?$input['job_period'] : "";
            $neoInput['service_type']       = !empty($input['job_type'])?$input['job_type'] : "";
            $neoInput['post_type']          = !empty($input['post_type'])?$input['post_type'] : "";
            $neoInput['free_service']       = !empty($input['free_job']) ? 1 : 0;
            $neoInput['service_currency']   = !empty($input['job_currency'])?$input['job_currency'] : "";
            $neoInput['service_cost']       = !empty($input['job_cost'])?$input['job_cost'] : "";
            $neoInput['bucket_id']          = !empty($input['bucket_id'])?$input['bucket_id'] : "";
            $neoInput['company']            = !empty($input['company_name'])?$input['company_name'] : "";
            $neoInput['job_description']    = !empty($input['job_description'])?$input['job_description'] : "";
            $neoInput['status']             = Config::get('constants.POST.STATUSES.ACTIVE');
            $neoInput['created_by']         = $emailId;
            #form jobs skills here
            $neoInput['skills']             =  $this->userGateway->getSkillsFromJobTitle($neoInput['service_name'], $neoInput['job_description']);
            
            $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($input['company_code']);
            
            $relationAttrs['created_at']    = gmdate("Y-m-d H:i:s");
            $relationAttrs['company_name']  = $neoInput['company'];
            $relationAttrs['company_code']  = !empty($input['company_code'])?$input['company_code']:'';
            $objCompany->fullname   = $relationAttrs['company_name'];
            $createdPost = $this->neoPostRepository->createPostAndUserRelation($fromId,$neoInput, $relationAttrs);
            if (isset($createdPost[0]) && isset($createdPost[0][0])) {
                $postId = $createdPost[0][0]->getID();
                $postType = $createdPost[0][0]->post_type;
            } else {
                $postId = 0;
            }
            #creating rewards data here
            foreach ($rewardsAry as $rewards) {
                $rewardsAttrs = array();
                $rewardsAttrs['post_id']        = $postId;
                $rewardsAttrs['rewards_type']   = !empty($rewards['rewards_type'])?$rewards['rewards_type']:'free';
                $rewardsAttrs['type']           = !empty($rewards['type'])?$rewards['type']:'';
                $rewardsAttrs['currency_type']  = !empty($rewards['currency_type'])?$rewards['currency_type']:0;
                $rewardsAttrs['rewards_value']  = !empty($rewards['rewards_value'])?$rewards['rewards_value']:0;
                $rewardsAttrs['created_at']     = gmdate("Y-m-d H:i:s");
                $rewardsAttrs['created_by']     = $emailId;
                $createdRewards = $this->neoPostRepository->createRewardsAndPostRelation($postId, $rewardsAttrs); 
            }
            #map post and company
            $postCompanyrelationAttrs['created_at']     = gmdate("Y-m-d H:i:s");
            $postCompanyrelationAttrs['user_emailid']   = $this->loggedinEnterpriseUserDetails->emailid;
            if (!empty($input['company_code'])) {
                $createdrelation = $this->neoPostRepository->createPostAndCompanyRelation($postId, $input['company_code'], $postCompanyrelationAttrs);
            }
            #map industry if provided
            if (!empty($input['industry'])) {
                $iResult = $this->referralsRepository->mapIndustryToPost($input['industry'], $postId, Config::get('constants.REFERRALS.ASSIGNED_INDUSTRY'));
            }
            #map job_function if provided
            if (!empty($input['job_function'])) {
                $jfResult = $this->referralsRepository->mapJobFunctionToPost($input['job_function'], $postId, Config::get('constants.REFERRALS.ASSIGNED_JOB_FUNCTION'));
            }
            #map employment type if provided
            if (!empty($input['employment_type'])) {
                $emResult = $this->referralsRepository->mapEmploymentTypeToPost($input['employment_type'], $postId, Config::get('constants.REFERRALS.ASSIGNED_EMPLOYMENT_TYPE'));
            }
            #map experience range if provided
            if (!empty($input['experience_range'])) {
                $eResult = $this->referralsRepository->mapExperienceRangeToPost($input['experience_range'], $postId, Config::get('constants.REFERRALS.ASSIGNED_EXPERIENCE_RANGE'));
            }
            if (!empty($bucket_id)) {
                $inviteCount = 0;
                $notificationMsg = Lang::get('MINTMESH.notifications.messages.27');
                #for reply emailid 
                $replyToName = Config::get('constants.MINTMESH_SUPPORT.REFERRER_NAME');
                $replyToHost = Config::get('constants.MINTMESH_SUPPORT.REFERRER_HOST');
                
                foreach ($bucket_id as $key => $value) {
                    
                    $input['bucket_id'] = $value;
                    $neoCompanyBucketContacts = $this->enterpriseGateway->enterpriseContactsList($input);
                    $contactList = $neoCompanyBucketContacts['data'];
                        
                    foreach ($contactList['Contacts_list'] as $contact => $contacts) {
                        
                        #check the condition for duplicat job post here
                        if(!in_array($contacts->emailid, $usersAry) && $contacts->status != 'Separated'){
                            
                            $usersAry[] = $contacts->emailid;
                            #creating included Relation between Post and Contacts 
                            $pushData['postId']         = $postId;
                            $pushData['bucket_id']      = $input['bucket_id'];
                            $pushData['contact_emailid']= $contacts->emailid;
                            $pushData['company_code']   = $input['company_code'];
                            $pushData['user_emailid']   = $this->loggedinEnterpriseUserDetails->emailid;
                            $pushData['notification_msg'] = $notificationMsg;
                            $pushData['notification_log'] = 1;//for log the notification or not
                            Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                            
                            #send push notifications to all the contacts
                            $notifyData   = array();
                            $notifyData['serviceId']            = $postId;
                            $notifyData['loggedinUserDetails']  = $this->loggedinEnterpriseUserDetails;
                            $notifyData['neoLoggedInUserDetails'] = $objCompany;//obj
                            $notifyData['includedList']     = array($contacts->emailid);
                            $notifyData['excludedList']     = $excludedList;
                            $notifyData['service_type']     = '';
                            $notifyData['service_location'] = '';
                            $notifyData['notification_type']  = 27;
                            $notifyData['service_name']       = $neoInput['service_name'];
                            Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $notifyData, 'Notification');

                            #send email notifications to all the contacts
                            $refId = $refCode = 0;
                            $emailData  = array();
                            $refId = $this->neoPostRepository->getUserNodeIdByEmailId($contacts->emailid);
                            $refCode                        = MyEncrypt::encrypt_blowfish($postId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));
                            $replyToData                    = '+ref='.$refCode;
                            $emailData['company_name']      = $input['company_name'];
                            $emailData['company_code']      = $input['company_code'];
                            $emailData['post_id']           = $postId;
                            $emailData['post_type']         = $postType;
                            $emailData['company_logo']      = $companyDetails[0]->logo;
                            $emailData['to_firstname']      = $contacts->firstname;
                            $emailData['to_lastname']       = $contacts->lastname;
                            $emailData['to_emailid']        = $contacts->emailid;
                            $emailData['from_userid']       = $fromId;
                            $emailData['from_emailid']      = $emailId;
                            $emailData['from_firstname']    = $fromName;
                            $emailData['ip_address']        = $_SERVER['REMOTE_ADDR'];
                            $emailData['ref_code']          = $refCode;
                            $emailData['reply_to']          = $replyToName.$replyToData.$replyToHost;
                          Queue::push('Mintmesh\Services\Queues\SendJobPostEmailToContactsQueue', $emailData, 'Notification');
                          $inviteCount+=1;
                        }
                    }
                }
                $this->neoPostRepository->updatePostInviteCount($postId, $inviteCount);
            }

            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.success')));
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.error')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, array());
    }

    public function getLoggedInEnterpriseUser() {
        return $this->userGateway->getLoggedInUser();
    }

    public function createPostContactsRelation($jobData = array()) {
        
        if (!empty($jobData['bucket_id']) && !empty($jobData['postId'])) {
            #get the $jobData here
            $encodeString       = Config::get('constants.MINTMESH_ENCCODE');
            $enterpriseUrl      = Config::get('constants.MM_ENTERPRISE_URL');
            $postId             = $jobData['postId'];
            $contactEmailid     = $jobData['contact_emailid'];
            $company_code       = $jobData['company_code'];
            $notificationMsg    = $jobData['notification_msg'];
            #get neo user node id
            $neoUser    = $this->neoEnterpriseRepository->getNodeByEmailId($contactEmailid);
            $contactId  = !empty($neoUser['id'])?$neoUser['id']:'';
            $refId      = $postId.'_'.$contactId;
            $refCode    = MyEncrypt::encrypt_blowfish($refId, $encodeString);
            $url = $enterpriseUrl . "/email/job-details/share?ref=" . $refCode."";; 
            $biltyUrl = $this->urlShortner($url);
            #form relation details here
            $relationAttrs = array();
            $relationAttrs['company_code']  = $jobData['company_code'];
            $relationAttrs['user_emailid']  = $jobData['user_emailid'];
            $relationAttrs['post_read_status'] = 0;
            $relationAttrs['bittly_url'] = $biltyUrl;
            
            try {
                $postDetails = $this->neoPostRepository->createPostContactsRelation($relationAttrs, $postId, $company_code, $contactEmailid, $jobData['bucket_id']);
                if(isset($postDetails[0]) && !empty($jobData['notification_log'])){
                    $notificationLog = array(
                                            'notifications_types_id' => 27,
                                            'from_email' => $jobData['user_emailid'],
                                            'to_email'   => $postDetails[0]['data']->emailid,
                                            'message' => $notificationMsg,
                                            'created_at' => date('Y-m-d H:i:s')
                                        ) ;
                    $this->userRepository->logNotification($notificationLog);
                }
            } catch (\RuntimeException $e) {
                return false;
            }
        }
        return true;
    }
    
    public function sendJobPostEmailToContacts ($emailData) {
        
        $dataSet    = array();
        $email_sent = '';
        $postId     = $emailData['post_id'];
        $refCode    = $emailData['ref_code'];
        $fullName   = $emailData['to_firstname'] . ' ' . $emailData['to_lastname'];
        $posts      = $this->neoPostRepository->getPosts($postId);
        $postDetails = $this->referralsGateway->formPostDetailsArray($posts);
        $freeService = $postDetails['free_service']; 
        #form email variables here
        $dataSet['name']                = $fullName;
        $dataSet['reply_emailid']       = $emailData['reply_to'];
        $dataSet['email']               = $emailData['to_emailid'];
        $dataSet['fromName']            = $emailData['from_firstname'];
        $dataSet['post_type']            = $emailData['post_type'];
        $dataSet['company_name']        = $emailData['company_name'];//Enterpi Software Solutions Pvt.Ltd.
        $dataSet['company_logo']        = $emailData['company_logo'];
        $dataSet['emailbody']           = 'just testing';
        $dataSet['send_company_name']   = $emailData['company_name'];
        $dataSet['reply_to']            = $emailData['reply_to'];
        $dataSet['app_id']              = '1268916456509673';
        #form job details here
        $dataSet['looking_for']         = $posts->service_name;//'Senior UI/UX Designer';
        $dataSet['job_function']        = $postDetails['job_function_name'];//'Design';
        $dataSet['experience']          = $postDetails['experience_range_name'];//'5-6 Years';
        $dataSet['vacancies']           = $posts->no_of_vacancies;//3;
        $dataSet['location']            = $posts->service_location;//'Hyderabad, Telangana';
        $dataSet['job_description']     = $posts->job_description;//'Job Description....';
        #form currency types and rewards
        $discovery   = $referral = $isPoints = 0;
        if (empty($freeService)){
            $postRewards = $this->getPostRewards($postId);
            foreach ($postRewards as $value) {
                $isPoints = FALSE;
                #checking rewards type here
                if($value['rewards_type']=='paid'){
                    $currency   = ($value['currency_type']==2)?'&#8377;':'&#x24;';//rupee:dollar
                    $reward     = $currency.$value['rewards_value'];
                }else if($value['rewards_type'] == 'points'){
                    $reward     = $value['rewards_value'];
                    $isPoints   = TRUE;
                } else {
                    $reward     = '';
                }
               #checking rewards name here     
               if($value['rewards_name']=='Discovery'){
                   $discovery   = $reward;
                   $isDisPoints = $isPoints;
                   $dataSet['dis_points']  = $isDisPoints;
                } elseif ($value['rewards_name']=='Referral') {  
                   $referral    = $reward;
                   $isRefPoints = $isPoints;
                   $dataSet['ref_points']  = $isRefPoints;
                }   
            }
        }
        $dataSet['free_service']= $freeService;
        $dataSet['discovery']   = $discovery;
        $dataSet['referral']    = $referral;
        $dataSet['job_details_link']    = Config::get('constants.MM_ENTERPRISE_URL') . "/email/job-details/share?ref=" . $refCode."";
        $bitlyUrl = $this->urlShortner($dataSet['job_details_link']);
        $dataSet['bittly_link']    = $bitlyUrl;
        #redirect email links
        $dataSet['apply_link']          = Config::get('constants.MM_ENTERPRISE_URL') . "/email/candidate-details/web?ref=" . $refCode."&flag=0&jc=0";
        $dataSet['refer_link']          = Config::get('constants.MM_ENTERPRISE_URL') . "/email/referral-details/web?ref=" . $refCode."&flag=0&jc=0";
        $dataSet['view_jobs_link']      = Config::get('constants.MM_ENTERPRISE_URL') . "/email/all-jobs/web?ref=" . $refCode."";
        $dataSet['drop_cv_link']        = Config::get('constants.MM_ENTERPRISE_URL') . "/email/referral-details/web?ref=" . $refCode."&flag=1&jc=0";
        #set email required params
        $this->userEmailManager->templatePath   = Lang::get('MINTMESH.email_template_paths.contacts_job_invitation');
        $this->userEmailManager->emailId        = $emailData['to_emailid'];//target email id
        $this->userEmailManager->dataSet        = $dataSet;
        $this->userEmailManager->subject        = $dataSet['looking_for'];
        $this->userEmailManager->name           = $fullName;
        $email_sent = $this->userEmailManager->sendMail();
        #for email logs
        $fromUserId  = $emailData['from_userid'];
        $fromEmailId = $emailData['from_emailid'];
        $companyCode = $emailData['company_code'];
        $ipAddress   = $emailData['ip_address'];
        #log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
        }
        $emailLog = array(
            'emails_types_id'   => 6,
            'from_user'         => $fromUserId,
            'from_email'        => $fromEmailId,
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($emailData['to_emailid'])),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $ipAddress
        );
        $this->userRepository->logEmail($emailLog);
    }

    public function jobsList($input) {
        
        $totalCount = 0;
        $timeZone   = !empty($input['time_zone']) ? $input['time_zone'] : 0;  
        $page       = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search_for = !empty($input['search_for']) ? $input['search_for'] : 0;
        $post_by    = !empty($input['post_by']) ? $input['post_by'] : 0;
        #get loggedin Enterprise User Details
        $this->loggedinEnterpriseUserDetails    = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $input['userEmail'] = $this->neoLoggedInEnterpriseUserDetails->emailid;
        #get User Permissions here
        $checkPermissions = $this->enterpriseRepository->getUserPermissions($this->loggedinEnterpriseUserDetails->group_id, $input);
        $checkPermissions = isset($checkPermissions['view_jobs']) ? $checkPermissions['view_jobs'] : '';
        #get jobs list from Neo4j DB
        $jobsList = $this->neoPostRepository->jobsList($input, $page, $search_for, $checkPermissions, $post_by);
        #get total records count from jobs list results
        $totalCount = isset($jobsList[0]) ? isset($jobsList[0][1]) ? $jobsList[0][1] : 0 : 0;
        if ($totalCount) {
            $returnPostsData = array();
            foreach ($jobsList as $post) {
                $flag = FALSE;//skip get industry and job function name
                $returnPosts = array();
                $postDetails = $this->referralsGateway->formPostDetailsArray($post[0], $flag);
                $invitedCount   = !empty($postDetails['invited_count']) ? $postDetails['invited_count'] : 0;
                $referralCount  = !empty($postDetails['total_referral_count']) ? $postDetails['total_referral_count'] : 0;
                $acceptedCount  = !empty($postDetails['referral_accepted_count']) ? $postDetails['referral_accepted_count'] : 0;
                $declinedCount  = !empty($postDetails['referral_declined_count']) ? $postDetails['referral_declined_count'] : 0;
                $hiredCount     = !empty($postDetails['referral_hired_count']) ? $postDetails['referral_hired_count'] : 0;
                $pendingCount   = $referralCount - ($acceptedCount + $declinedCount);
                $returnPosts['campaign_name'] = '';
                if(($postDetails['post_type']) == 'campaign'){
                   $campaignName = $this->neoPostRepository->getPostCampaign($postDetails['post_id']);
                   if(isset($campaignName[0]) && isset($campaignName[0][0])){
                        $returnPosts['campaign_name'] = $campaignName[0][0]->campaign_name;
                   }
                }
                $returnPosts['post_type']   = $postDetails['post_type'];
                $returnPosts['id']          = $postDetails['post_id'];
                $returnPosts['location']    = !empty($postDetails['service_location']) ? $postDetails['service_location'] : 'See Job Description';
                $returnPosts['job_title']   = !empty($postDetails['service_name']) ? $postDetails['service_name'] : 'See Job Description';
                $returnPosts['free_service']        = $postDetails['free_service'];
                $returnPosts['status']              = $postDetails['status'];
                $returnPosts['no_of_vacancies']     = !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:0;
                $returnPosts['experience']['id']    = $postDetails['experience_range'];
                $returnPosts['employment']['id']    = $postDetails['employment_type'];
                $returnPosts['experience']['name']  = isset($postDetails['experience_range_name']) ? $postDetails['experience_range_name'] : "";
                $returnPosts['employment']['name']  = isset($postDetails['employment_type_name']) ? $postDetails['employment_type_name'] : "";
                if ($returnPosts['free_service'] == 0) {
                    $returnPosts['service_cost'] = $postDetails['service_cost'];
                    $returnPosts['service_currency'] = $postDetails['service_currency'];
                }
                $returnPosts['created_at']      = !empty($postDetails['created_at']) ? date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($postDetails['created_at'], $timeZone))) : '';
                $returnPosts['hired_count']     = $hiredCount;
                $returnPosts['invited_count']   = $invitedCount;
                $returnPosts['referral_count']  = $referralCount;
                $returnPosts['accepted_count']  = $acceptedCount;
                $returnPosts['pending_count']   = max($pendingCount, 0);
                $returnPosts['rewards']         = $this->getPostRewards($postDetails['post_id']);
                $returnPostsData[] = $returnPosts;
            }
        }

        if (!empty($returnPostsData)) {
            $data = array("posts" => array_values($returnPostsData), "total_count" => $totalCount);
            $message = array('msg' => array(Lang::get('MINTMESH.post.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.post.no_posts')));
            $data = array();
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }

    public function jobDetails($input) {
        $this->loggedinEnterpriseUserDetails = $this->getLoggedInEnterpriseUser();
        $posts = $this->neoPostRepository->jobsDetails($input['id'], $input['company_code']);
        $timeZone = !empty($input['time_zone'])?$input['time_zone']:0;   
        $checkPermissions = $this->enterpriseRepository->getUserPermissions($this->loggedinEnterpriseUserDetails->group_id,$input);
        if (!empty(count($posts))) {
            $returnPosts = array();
            $returnPostsData = array();
            $buckets = array();
            $postDetails = $this->referralsGateway->formPostDetailsArray($posts[0][0]);
                $companyDetails = $posts[0][1];
                $returnPosts['id']  = $postDetails['post_id'];
                if(!empty($postDetails['bucket_id'])){
                $bucket_id = explode(',', $postDetails['bucket_id']);
                foreach ($bucket_id as $bucket){
                $bucketDetails     =  $this->neoPostRepository->bucket($bucket);
                $buckets[]       =     $bucketDetails;
                }
                }else{
                    if($postDetails['post_type'] == 'campaign'){
                    $campaignDetails = $this->neoPostRepository->getPostCampaign($returnPosts['id']);
                    $bucket_id = explode(',', $campaignDetails[0][0]->bucket_id);
                    foreach($bucket_id as $buckets_id){
                    $bucketDetails     =  $this->neoPostRepository->bucket($buckets_id);
                    $buckets[]         =  $bucketDetails;
                    }
                } 
                }
                $invitedCount   = !empty($postDetails['invited_count']) ? $postDetails['invited_count'] : 0;
                $referralCount  = !empty($postDetails['total_referral_count']) ? $postDetails['total_referral_count'] : 0;
                $acceptedCount  = !empty($postDetails['referral_accepted_count']) ? $postDetails['referral_accepted_count'] : 0;
                $declinedCount  = !empty($postDetails['referral_declined_count']) ? $postDetails['referral_declined_count'] : 0;
                $hiredCount     = !empty($postDetails['referral_hired_count']) ? $postDetails['referral_hired_count'] : 0;
                $pendingCount   = $referralCount - ($acceptedCount + $declinedCount);

                $returnPosts['location']    = !empty($postDetails['service_location']) ? $postDetails['service_location'] : 'See Job Description';
                $returnPosts['post_type']   = $postDetails['post_type'];
                $returnPosts['job_title']   = $postDetails['service_name'];
                $returnPosts['created_at']  =  !empty($postDetails['created_at'])?date("D M d, Y H:i:s", strtotime($this->appEncodeDecode->UserTimezone($postDetails['created_at'],$timeZone))):'';
                $returnPosts['position_id'] = !empty($postDetails['position_id'])? $postDetails['position_id']:'';
                $returnPosts['status']      = $postDetails['status'];
                $closeJobs = !empty($checkPermissions['close_jobs'])?$checkPermissions['close_jobs']:'';
                if($postDetails['created_by'] == $this->loggedinEnterpriseUserDetails->emailid || $closeJobs == '1'){
                    $returnPosts['is_close']     = '1';
                }else{
                    $returnPosts['is_close']     = '0';
                }
                $returnPosts['hired_count']     = $hiredCount;
                $returnPosts['invited_count']   = $invitedCount;
                $returnPosts['referral_count']  = $referralCount;
                $returnPosts['accepted_count']  = $acceptedCount;
                $returnPosts['pending_count']   = max($pendingCount,0);
                $returnPosts['bucket_name']     = !empty($buckets)?$buckets:'';
                $returnPosts['currency']        = $postDetails['service_currency'];
                $returnPosts['cost']            = $postDetails['service_cost'];
                $returnPosts['free_service']    = $postDetails['free_service'];
                $returnPosts['no_of_vacancies'] = !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:0;
                $returnPosts['job_function']    = isset($postDetails['job_function_name']) ? $postDetails['job_function_name'] : "";
                $returnPosts['industry_name']   = $postDetails['industry_name'];
                $returnPosts['requistion_id']   = $postDetails['requistion_id'];
                $returnPosts['job_description'] = isset($postDetails['job_description']) ? $postDetails['job_description'] : "";
                $returnPosts['employment_type'] = isset($postDetails['employment_type_name']) ? $postDetails['employment_type_name'] : "";
                $returnPosts['experience_range']    = isset($postDetails['experience_range_name']) ? $postDetails['experience_range_name'] : "";
                $returnPosts['company_description'] = isset($companyDetails->description) ? $companyDetails->description : "";
                $returnPosts['company_logo'] = isset($companyDetails->logo) ? $companyDetails->logo : "";
                $returnPosts['rewards']      = $this->getPostRewards($returnPosts['id']);
                $returnPostsData[] = $returnPosts;
        }

        if (!empty($returnPostsData)) {
            $data = array("posts" => array_values($returnPostsData));
            $message = array('msg' => array(Lang::get('MINTMESH.post.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.post.no_posts')));
            $data = array();
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }

    public function jobreferralDetails($input) {
        $this->loggedinEnterpriseUserDetails = $this->getLoggedInEnterpriseUser();
        $company = $this->enterpriseRepository->getUserCompanyMap($this->loggedinEnterpriseUserDetails->id);
        $page   = !empty($input['page_no']) ? $input['page_no'] : 0;
        $status = !empty($input['status'])?$input['status']:'';
        //get job Referral Details here
        $referredByDetails = $this->neoPostRepository->jobReferralDetails($input, $page);
        if (!empty(count($referredByDetails))) {
            $returnReferralCountDetails = $returnReferralDetails = $returnDetails  = array();
            
            foreach ($referredByDetails as $post) {
                $userDetails    = $this->referralsGateway->formPostDetailsArray($post[0]);
                $postRelDetails = $this->referralsGateway->formPostDetailsArray($post[1]);
                $postDetails    = $this->referralsGateway->formPostDetailsArray($post[2]);
                $referralName = '';
                $nonMMUser    = new \stdClass();
                
                if(!empty($userDetails['emailid']) && !empty($postRelDetails['referred_by'])){
                    $uDetails = $this->enterpriseRepository->getContactByEmailId($userDetails['emailid'],$company->company_id);
                    if(!empty($uDetails)){
                         $userName = $uDetails[0]->firstname.' '.$uDetails[0]->lastname;
                    }
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($userDetails['emailid']);
                    $neoReferralName = !empty($neoUserDetails['fullname'])?$neoUserDetails['fullname']:$neoUserDetails['firstname'];
                    if(empty($userDetails['firstname']) && empty($userDetails['fullname'])){
                          $nonMMUser    = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                          $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                    }else{
                          $referralName = !empty($userName)?$userName:$neoReferralName;
                    }

                    $returnReferralDetails['from_user']         = $neoUserDetails['emailid'];
                    $returnReferralDetails['referred_by_phone'] = '0';
                    $returnReferralDetails['dp_image']          = $neoUserDetails['dp_renamed_name'];

                    if ($neoUserDetails['completed_experience'] == '1')
                    {
                        $title = $this->neoPostRepository->getJobTitle($neoUserDetails['emailid']);
                        foreach ($title as $t) 
                        {
                            $jobTitle = $this->referralsGateway->formPostDetailsArray($t[0]);
                            $returnReferralDetails['designation'] = $jobTitle['name'];
                        }
                    } 
                    else {
                        $returnReferralDetails['designation'] = '';
                    }
                }
                else{
                    
                     if(empty($userDetails['firstname']) && !empty($postRelDetails['referred_by'])){
                        $nonMMUser     = $this->contactsRepository->getImportRelationDetailsByPhone($postRelDetails['referred_by'], $userDetails['phone']);
                        $referralName  = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                     } 
                     $returnReferralDetails['from_user'] = $userDetails['phone'];
                     $returnReferralDetails['referred_by_phone'] = '1';
                     $returnReferralDetails['designation'] = '';
                     unset($returnReferralDetails['dp_image']);
                }
                $referrerDetails = $this->enterpriseRepository->getContactByEmailId($postRelDetails['referred_by'],$company->company_id);
                if(!empty($referrerDetails)){
                $referrerName = $referrerDetails[0]->firstname.' '.$referrerDetails[0]->lastname;}
                $neoReferrerDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                $neoReferrerName = !empty($neoReferrerDetails['fullname'])?$neoReferrerDetails['fullname']:$neoReferrerDetails['firstname'];
                if ($neoReferrerDetails['completed_experience'] == '1') {
                    $title = $this->neoPostRepository->getJobTitle($neoReferrerDetails['emailid']);
                    foreach ($title as $t) {
                        $jobTitle = $this->referralsGateway->formPostDetailsArray($t[0]);
                        $returnReferralDetails['ref_designation'] = $jobTitle['name'];
                    }
                } else {
                    $returnReferralDetails['ref_designation'] = '';
                }
                $cvPath = !empty($userDetails['cv_path'])?$userDetails['cv_path']:'';
                $returnReferralDetails['status']                = $postRelDetails['one_way_status'];   
                $timeZone = !empty($input['time_zone'])?$input['time_zone']:0;   
                $createdAt        = $postRelDetails['created_at'];		
                $returnReferralDetails['created_at']            = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                $returnReferralDetails['updated_at']            = !empty($postRelDetails['p1_updated_at'])?date("D M d, Y h:i A", strtotime($this->appEncodeDecode->UserTimezone($postRelDetails['p1_updated_at'],$timeZone))):'';
                $returnReferralDetails['referred_by']           = $neoReferrerDetails['emailid'];
                $returnReferralDetails['resume_path']           = !empty($postRelDetails['resume_path'])?$postRelDetails['resume_path']:$cvPath;
                $returnReferralDetails['resume_original_name']  = $postRelDetails['resume_original_name'];
                $returnReferralDetails['relation_count']        = $postRelDetails['relation_count'];
                $returnReferralDetails['referred_by_name']      = !empty($referrerName)?$referrerName:$neoReferrerName;
                $returnReferralDetails['referred_by_dp_image']  = $neoReferrerDetails['dp_renamed_name'];
                $returnReferralDetails['confidence_score']      = !empty($postRelDetails['overall_score'])?$postRelDetails['overall_score']:0;
                $returnReferralDetails['name']                  = !empty($referralName)?$referralName:'The contact';
                $returnReferralDetails['document_id']           = !empty($postRelDetails['document_id']) ? $postRelDetails['document_id'] : 0;
                // awaiting Action Details
                if($status == 'ACCEPTED'){ 
                    if(!empty($postRelDetails['awaiting_action_by'])){
                        $awaitingActionUser = $this->neoUserRepository->getNodeByEmailId($postRelDetails['awaiting_action_by']);
                        $returnReferralDetails['awaiting_action_by'] = $awaitingActionUser['fullname'];
                    }  else {
                        $returnReferralDetails['awaiting_action_by'] = '';
                    }
                    $returnReferralDetails['awaiting_action_updated_at'] = !empty($postRelDetails['awaiting_action_updated_at'])?date("D M d, Y h:i A", strtotime($this->appEncodeDecode->UserTimezone($postRelDetails['awaiting_action_updated_at'],$timeZone))):'';
                    $returnReferralDetails['awaiting_action_status']     = !empty($postRelDetails['awaiting_action_status'])?$postRelDetails['awaiting_action_status']:'ACCEPTED';
                }
                $returnDetails[] = $returnReferralDetails;
            } 
            //post count details
            $referralCount = !empty($postDetails['total_referral_count']) ? $postDetails['total_referral_count'] : '0';
            $acceptedCount = !empty($postDetails['referral_accepted_count']) ? $postDetails['referral_accepted_count'] : '0';
            $declinedCount = !empty($postDetails['referral_declined_count']) ? $postDetails['referral_declined_count'] : '0';
            $hiredCount    = !empty($postDetails['referral_hired_count']) ? $postDetails['referral_hired_count'] : 0;
            $pendingCount  = $referralCount - ($acceptedCount + $declinedCount);
            
            $returnReferralCountDetails['hired_count']      = $hiredCount;
            $returnReferralCountDetails['referral_count']   = $referralCount;
            $returnReferralCountDetails['accepted_count']   = $acceptedCount;
            
            $returnReferralCountDetails['pending_count']    = max($pendingCount, 0);
            $returnReferralCountDetails['invited_count']    = !empty($postDetails['invited_count']) ? $postDetails['invited_count'] : '0';
        }
        if (!empty($returnDetails)) {
            $data = array("referrals" => array_values($returnDetails),"countDetails" => $returnReferralCountDetails);
            $message = array('msg' => array(Lang::get('MINTMESH.referral_details.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referral_details.no_referrals')));
            $data = array();
        }

        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }

    public function processJob($input) {
        
        $objCompany = new \stdClass();
        $one_way_status = FALSE;
        $returnReferralDetails = $data = array();
        $this->loggedinEnterpriseUserDetails    = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $company = $this->enterpriseRepository->getUserCompanyMap($this->loggedinEnterpriseUserDetails->id);
        $objCompany->fullname = $company->name;
        $parse    = 1;
        $postId   = $input['post_id'];
        $status   = $input['status'];
        $postWay  = 'one';
        $referral = $input['from_user'];
        $referredBy    = $input['referred_by'];
        $relationCount = $input['relation_count'];
        $userEmail = $this->neoLoggedInEnterpriseUserDetails->emailid;
        $phoneNumberReferred = !empty($input['referred_by_phone']) ? 1 : 0;
        $checkCandidate = $this->neoPostRepository->checkCandidate($referral,$postId);
        $result = $this->neoPostRepository->statusDetails($postId, $referredBy, $referral, $status, $postWay, $relationCount, $phoneNumberReferred);
        if (count($result)) {

            if ($status != 'DECLINED') {

                $relationId         = !empty($result[0][1]) ? $result[0][1]->getID() : 0;
                $is_self_referred   = ($referral == $referredBy) ? 1 : 0;
                $one_way_status     = !empty($result[0][1]->one_way_status)?$result[0][1]->one_way_status:false;
                $relReferredBy      = !empty($result[0][1])?$result[0][1]->referred_by:'';
                
               if($status =='ACCEPTED'){
                    if(!empty($result[0][0]) && empty($result[0][0]->free_service)){
                       //free service
                    
                        $postRewards = $this->getPostRewards($postId);
                        foreach ($postRewards as $value) {
                            
                            $rewardsName  = !empty($value['rewards_name'])?$value['rewards_name']:'';
                            $rewardsType  = !empty($value['rewards_type'])?$value['rewards_type']:'';
                            $rewardsValue = !empty($value['rewards_value'])?$value['rewards_value']:0;
                            if($rewardsName  =='Discovery'){
                                
                                if($rewardsType =='paid')//if($rewardsType =='points')
                                {
                                    $rewardsType = !empty($value['currency_type'])?$value['currency_type']:1;
                                    $relReferredBy;
                                    //update user cash
                                    $transactionInput = array();
                                    $transactionInput['from_user']              = $this->appEncodeDecode->filterString(strtolower($userEmail));
                                    $transactionInput['to_user']                = $this->appEncodeDecode->filterString(strtolower($relReferredBy)); 
                                    $transactionInput['for_user']               = $this->appEncodeDecode->filterString(strtolower($referral)); 
                                    $transactionInput['amount']                 = $rewardsValue;
                                    $transactionInput['payment_reason']         = 1;
                                    $transactionInput['payment_type']           = $rewardsType;
                                    $transactionInput['mm_transaction_id']      = $t_id = $this->paymentGateway->generateTansactionId($input['referred_by']);
                                    $transactionInput['comission_percentage']   = 0;
                                    $transactionInput['payed_for_id']           = $postId;
                                    $transactionInput['relation_id']            = !empty($result[0][1])?$result[0][1]->getID():0;
                                    $transactionInput['status']                 = Config::get('constants.PAYMENTS.STATUSES.SUCCESS');
                                    $payment_transaction                        = $this->paymentRepository->insertTransaction($transactionInput);
                                    
                                    
                                    //get user country 
                                    $userDetails = $this->neoUserRepository->getNodeByEmailId($relReferredBy) ;
                                    $userPhoneCountry  = !empty($userDetails->phone_country_name)?$userDetails->phone_country_name:self::DEFAULT_USER_COUNTRY;
                                    $amount = $rewardsValue;
                                    $convertedAmount = 0;
                                    if(!empty($userPhoneCountry)){
                                        if (strtolower($userPhoneCountry) =="india")//if india
                                        {
                                            if ($rewardsType == 1)//if dollar then convert 
                                            {
                                                //change from dollar to rs
                                                $rsRate = Config::get('constants.PAYMENTS.CONVERSION_RATES.USD_TO_INR');
                                                $convertedAmount = $this->paymentGateway->convertUSDToINR($amount);
                                            }
                                            else
                                            {
                                                $convertedAmount = $amount ;
                                            }
                                        }
                                        else //if USA
                                        {
                                            //get balance cash info
                                            if ($rewardsType == 2)//if dollar then convert 
                                            {
                                                //change from rs to USD

                                                $usdRate = Config::get('constants.PAYMENTS.CONVERSION_RATES.INR_TO_USD');
                                                $convertedAmount = $this->paymentGateway->convertINRToUSD($amount);
                                            }
                                            else
                                            {
                                                $convertedAmount = $amount ;
                                            }
                                        }
                                        

                                        //get balance cash info
                                        $balanceCashInfo = $this->paymentRepository->getbalanceCashInfo($relReferredBy);

                                        if (!empty($balanceCashInfo))
                                        {
                                            //edit balance cash info
                                            $balanceCash = $convertedAmount+$balanceCashInfo->balance_cash ;
                                            $bid = $balanceCashInfo->id ;
                                            $inp = array();
                                            $inp['balance_cash'] = $balanceCash ;
                                            $u = $this->paymentRepository->editBalanceCash($bid, $inp);
                                        }
                                        else //insert balance cash info
                                        {
                                            $sqlUser = $this->userRepository->getUserByEmail($relReferredBy);
                                            //insert balance cash info
                                            $inp = array();
                                            $inp['user_id'] = !empty($sqlUser->id)?$sqlUser->id:0;
                                            $inp['user_email'] = $relReferredBy ;
                                            $inp['balance_cash'] = $convertedAmount ;
                                            $inp['currency'] = ($rewardsType == 1)?'USD':'INR';
                                            $i = $this->paymentRepository->insertBalanceCash($inp);
                                        }
                                    }

                                } else if($rewardsType == 'points')
                                {
                                    //update user points
                                    $this->userRepository->logLevel(1, $relReferredBy, $userEmail, $referral, $rewardsValue);
                                }

                            }

                        }

                    }
                     $posts      = $this->neoPostRepository->getPosts($postId);
                     $hcm_type = !empty($posts->hcm_type) ? $posts->hcm_type : '';
                     if($hcm_type == "ICIMS"){
                   
                    $icimsArray = array();
                    $icimsArray['to_emailid'] = $referral;
                    $icimsArray['from_emailid'] = $userEmail;
                    $icimsArray['company_code'] = $company->company_id;
                    $icimsArray['post_type'] = $posts->post_type;
                    $icimsArray['subject'] = $posts->service_name;
                    $sqlUser = $this->userRepository->getUserByEmail($relReferredBy);
                    $icimsArray['from_userid'] = !empty($sqlUser->id)?$sqlUser->id:0;
                    
                    $this->sendPortalUrlLinkToIcims($postId, $icimsArray, $company, $this->loggedinEnterpriseUserDetails);
                     }
               }

                $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus($relationId, '', $is_self_referred, $userEmail);
                //send notification to the person who referred to the post
                $sqlUser = $this->userRepository->getUserByEmail($referredBy);
                if(empty($sqlUser)){
                    $sqlUser = $this->enterpriseRepository->getContactByEmailId($referredBy,$company->company_id);
                    $referred_byDetails = $this->enterpriseRepository->getContactById($sqlUser[0]->id);
                    $referred_by_details = $referred_byDetails[0];
                }else{
                $referred_by_details = $this->userRepository->getUserById($sqlUser->id);
                }
                $referred_by_neo_user = $this->neoUserRepository->getNodeByEmailId($referredBy);
                //add credits                

                if ($referral == $referredBy) {
                    //send notification to via person
                    $notificationId = 24; //indicates self referral accepted notify to P2
                } else {
                    //send notification to via person
                    $notificationId = 12; //indicates referral accepted notify to P2
                    //P3 got the notification
                    $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $referral, 11, array('extra_info' => $postId), array('other_user' => $userEmail), 1);
                    //send battle card to u1 containing u3 details
                    $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $this->loggedinEnterpriseUserDetails->emailid, 20, array('extra_info' => $postId), array('other_user' => $referral), 1);
                }
                
                //}
            } else {
                $notificationId = ($referral == $referredBy) ? 25 : 15; //indicates referral declined notify to P2
            }
            //send notification
            $this->userGateway->sendNotification($this->loggedinEnterpriseUserDetails, $objCompany, $referredBy, $notificationId, array('extra_info' => $postId), array('other_user' => $referral), $parse);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            $data = array("one_way_status" => $one_way_status);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    public function sendPortalUrlLinkToIcims($postId, $emailData, $company, $userDetails) {
       
        $integrationManager = new IntegrationManager();
        $posts      = $this->neoPostRepository->getPosts($postId);
        $postDetails = $this->referralsGateway->formPostDetailsArray($posts);
        $freeService = $postDetails['free_service']; 
        $companyDetails = $company;
         $companyName = $companyDetails->name; //'company68';
        $companyCode = $companyDetails->code; //510632;
        $companyLogo = $companyDetails->logo; //510632;
        $userEmailId = !empty($userDetails->emailid) ? $userDetails->emailid : ''; //'gopi68@mintmesh.com';
        $userFirstname = !empty($userDetails->firstname) ? $userDetails->firstname : ''; //'gopi68@mintmesh.com';
         #form email variables here
        $dataSet['name']                = $userFirstname;
        $dataSet['email']               = $emailData['to_emailid'];
        $dataSet['fromName']            = $userFirstname;
        $dataSet['post_type']            = $emailData['post_type'];
        $dataSet['company_name']        = $companyName;//Enterpi Software Solutions Pvt.Ltd.
        $dataSet['company_logo']        = $companyLogo;
        $dataSet['emailbody']           = 'just testing';
        $dataSet['send_company_name']   = $companyName;
        $dataSet['app_id']              = '1268916456509673';
        #form job details here
        $dataSet['looking_for']         = $posts->service_name;//'Senior UI/UX Designer';
        $dataSet['job_function']        = $postDetails['job_function_name'];//'Design';
        $dataSet['experience']          = $postDetails['experience_range_name'];//'5-6 Years';
        $dataSet['vacancies']           = $posts->no_of_vacancies;//3;
        $dataSet['location']            = $posts->service_location;//'Hyderabad, Telangana';
        $dataSet['job_description']     = $posts->job_description;//'Job Description....';
        $dataSet['portalUrl'] = $posts->portalUrl;
        
        $this->userEmailManager->templatePath   = Lang::get('MINTMESH.email_template_paths.contacts_job_active_invitation');
        $this->userEmailManager->emailId        = $emailData['to_emailid'];//target email id
        $this->userEmailManager->dataSet        = $dataSet;
        $this->userEmailManager->subject        = $posts->service_name;
        $this->userEmailManager->name           = "Test";
        $email_sent = $this->userEmailManager->sendMail();
        #for email logs
        $fromUserId  = $emailData['from_userid'];
        $fromEmailId = $emailData['from_emailid'];
        $companyCode = $companyCode;
        $ipAddress   = '192.168.1.1';
        #log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
        }
        $emailLog = array(
            'emails_types_id'   => 6,
            'from_user'         => $fromUserId,
            'from_email'        => $fromEmailId,
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($emailData['to_emailid'])),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $ipAddress
        );
        $this->userRepository->logEmail($emailLog);
    }
    public function awaitingAction($input) {
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId   = $this->loggedinUserDetails->emailid;
        $userFirstName = $this->loggedinUserDetails->firstname;
        $data = $response = array();
        $timeZone = !empty($input['time_zone'])?$input['time_zone']:0;   
        $postId = $input['post_id'];
        $status = $input['awaiting_action_status'];
        $referral = $input['from_user'];
        $referredBy = $input['referred_by'];
        $relationCount = $input['relation_count'];
        $nonMintmesh = !empty($input['referred_by_phone']) ? 1 : 0;
        
        $companyDetils = $this->neoPostRepository->getPostCompany($postId); 
        $companyDetils->companyCode;
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyDetils->companyCode);
        $companyId   = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        $companyName = isset($companyDetails[0]) ? $companyDetails[0]->name : '';
        #get Contact Current Status By EmailId
        $currentStatus = $this->enterpriseRepository->checkContactCurrentStatusByEmailId($companyId ,$referredBy);
        $result = $this->neoPostRepository->updateAwaitingActionDetails($userEmailId, $postId, $referredBy, $referral, $status, $relationCount, $nonMintmesh);
        if (!empty($result)) {
            #send notification
            $objCompany = new \stdClass();
            $objCompany->fullname = $companyName;
            if($status == 'INTERVIEWED'){
                $notificationId = 29;
            } else if($status == 'OFFERED'){
                $notificationId = 30;
            } else if($status == 'HIRED'){
                $notificationId = 31;
            } else {
                $notificationId = '';
            }
            if(!empty($currentStatus)){
                #send notification to p2
                $this->userGateway->sendNotification($this->loggedinUserDetails, $objCompany, $referredBy, $notificationId, array('extra_info' => $postId), array('other_user' => $referral), 1);
            }
            $postDetails     = !empty($result[0][0])?$result[0][0]:'';
            $postDetails     = $this->referralsGateway->formPostDetailsArray($postDetails);
            $relationDetails = !empty($result[0][1])?$result[0][1]:'';
            $relationDetails = $this->referralsGateway->formPostDetailsArray($relationDetails);
            $response['hired_count']                =  !empty($postDetails['referral_hired_count'])?$postDetails['referral_hired_count']:0;
            $response['awaiting_action_status']     =  !empty($relationDetails['awaiting_action_status'])?$relationDetails['awaiting_action_status']:'ACCEPTED';
            $response['awaiting_action_by']         =  !empty($relationDetails['awaiting_action_by'])?$userFirstName:'';
            $response['awaiting_action_updated_at'] =  !empty($relationDetails['awaiting_action_updated_at'])?$relationDetails['awaiting_action_updated_at']:date("d-m-Y");
            $response['awaiting_action_updated_at'] =  date("D M d, Y h:i A", strtotime($this->appEncodeDecode->UserTimezone($response['awaiting_action_updated_at'],$timeZone)));
            
            if (!empty($response)) {   
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                $data = $response;
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
            }
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message,$data);
    }
    
    public function getPostRewards($postId='') {
        $aryRewards  = $rewards = array();
        $postRewards = $this->neoPostRepository->getPostRewards($postId);
            foreach ($postRewards as $value) { 
               $relObj   = $value[1];//POST_REWARDS relation
               $valueObj = $value[2];//REWARDS node
               $rewards['rewards_name']     = !empty($relObj->rewards_mode)?ucfirst($relObj->rewards_mode):'';
               $rewards['currency_type']    = !empty($valueObj->currency_type)?$valueObj->currency_type:0;
               $rewards['rewards_type']     = !empty($valueObj->rewards_type)?$valueObj->rewards_type:'';
               $rewards['rewards_value']    = !empty($valueObj->rewards_value)?$valueObj->rewards_value:'';
               $aryRewards[] = $rewards;
            }
        return $aryRewards;
    }
    
    public function jobRewards($input) {
        $this->loggedinEnterpriseUserDetails = $this->getLoggedInEnterpriseUser();
        $company = $this->enterpriseRepository->getUserCompanyMap($this->loggedinEnterpriseUserDetails->id);
        $postReferrals  = $dscRewards = $refRewards = $data = array();
        $aryRefRewards  = $aryDscRewards = $postDetails = array();
        $currencyType   = $totalCash = $totalPoints = 0;
        $postId         = !empty($input['post_id'])?$input['post_id']:0;
         $timeZone = !empty($input['time_zone'])?$input['time_zone']:0;   
        //get the post referrals data here
        $postReferrals  = $this->neoPostRepository->getJobReferrals($postId);
        $postDetails    = !empty($postReferrals[0])?!empty($postReferrals[0][2])?$postReferrals[0][2]:$postDetails:$postDetails;
        $postDetails    = $this->referralsGateway->formPostDetailsArray($postDetails);
        //get the service location(post country) here
        $jobCountry     = !empty($postDetails['service_country'])?$postDetails['service_country']:'';
        $freeService    = !empty($postDetails['free_service'])?$postDetails['free_service']:'0';
        //based on service location currency will decide here
        $jobCountry     = ($jobCountry == 'IN')?'india':'';
        //get the post reward details here
        $postRewards    = $this->referralsGateway->getPostRewards($postId, $jobCountry);
        //separate the rewards result discovery and referrals here
        $dscRewards     = !empty($postRewards['discovery'])?$postRewards['discovery']:array();
        $refRewards     = !empty($postRewards['referral'])?$postRewards['referral']:array();
        $currencyType   = !empty($postRewards['currency_type'])?$postRewards['currency_type']:0;
        
        foreach ($postReferrals as $value) {
                $userDetails    = $this->referralsGateway->formPostDetailsArray($value[0]);
                $postRelDetails = $this->referralsGateway->formPostDetailsArray($value[1]);              
                $oneWayStatus         = !empty($postRelDetails['one_way_status'])?$postRelDetails['one_way_status']:'';
                $awaitingActionStatus = !empty($postRelDetails['awaiting_action_status'])?$postRelDetails['awaiting_action_status']:'';
                //checking free service and referral acceptance here
                if($freeService !=1 && $oneWayStatus == 'ACCEPTED'){
                    $referralName  = $rewardsVal = '';
                    $returnRewards = array();
                    $nonMMUser     = new \stdClass();
                    //form the referral details here
                    if(!empty($userDetails['emailid']) && !empty($postRelDetails['referred_by'])){//with referral emailid
                         $uDetails = $this->enterpriseRepository->getContactByEmailId($userDetails['emailid'],$company->company_id);
                        if(!empty($uDetails)){
                         $userName = $uDetails[0]->firstname.' '.$uDetails[0]->lastname;
                        }
                        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($userDetails['emailid']);
                        $neoReferralName = !empty($neoUserDetails['fullname'])?$neoUserDetails['fullname']:$neoUserDetails['firstname'];
                        if(empty($userDetails['firstname'])){
                              $nonMMUser    = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                              $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                        }else{
                              $referralName = !empty($userName)?$userName:$neoReferralName;
                        }
                        $returnReferralDetails['from_user']         = $neoUserDetails['emailid'];
                        $returnReferralDetails['referred_by_phone'] = '0';
                        $returnReferralDetails['dp_image']          = $neoUserDetails['dp_renamed_name'];
                        //form the referral designation here
                        $returnReferralDetails['designation']       = '';
                        if ($neoUserDetails['completed_experience'] == '1')
                        {
                            $title = $this->neoPostRepository->getJobTitle($neoUserDetails['emailid']);
                            foreach ($title as $t) 
                            {
                                $jobTitle = $this->referralsGateway->formPostDetailsArray($t[0]);
                                $returnReferralDetails['designation'] = $jobTitle['name'];
                            }
                        } 
                    }
                    else{//with phone number
                         if(empty($userDetails['firstname']) && !empty($postRelDetails['referred_by'])){
                            $nonMMUser     = $this->contactsRepository->getImportRelationDetailsByPhone($postRelDetails['referred_by'], $userDetails['phone']);
                            $referralName  = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                         } 
                         $returnReferralDetails['from_user']         = $userDetails['phone'];
                         $returnReferralDetails['referred_by_phone'] = '1';
                         $returnReferralDetails['designation']       = '';
                         unset($returnReferralDetails['dp_image']);
                    }
                    $referrerDetails = $this->enterpriseRepository->getContactByEmailId($postRelDetails['referred_by'],$company->company_id);
                    if(!empty($referrerDetails)){
                    $referrerName = $referrerDetails[0]->firstname.' '.$referrerDetails[0]->lastname;}
                    $neoReferrerDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                    $neoReferrerName = !empty($neoReferrerDetails['fullname'])?$neoReferrerDetails['fullname']:$neoReferrerDetails['firstname'];
                    //get the referral details with emailid
                    //form the referral designation here
                    $returnReferralDetails['ref_designation']       = '';
                    if ($neoReferrerDetails['completed_experience'] == '1') {
                        $title = $this->neoPostRepository->getJobTitle($neoReferrerDetails['emailid']);
                        foreach ($title as $t) {
                            $jobTitle = $this->referralsGateway->formPostDetailsArray($t[0]);
                            $returnReferralDetails['ref_designation'] = $jobTitle['name'];
                        }
                    } 
                    $createdAt = $postRelDetails['created_at']; 
                    $returnReferralDetails['created_at']            = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                    $returnReferralDetails['referred_by']           = $neoReferrerDetails['emailid'];
                    $returnReferralDetails['referred_by_name']      = !empty($referrerName)?$referrerName:$neoReferrerName;
                    $returnReferralDetails['referred_by_dp_image']  = $neoReferrerDetails['dp_renamed_name'];
                    $returnReferralDetails['name']                  = !empty($referralName)?$referralName:'The contact';
                    //form the discovery rewards here
                    $dscRewardsType = !empty($dscRewards['rewards_type'])?$dscRewards['rewards_type']:'';
                    $aryDscRewards['name'] = 'discovery';
                    if($dscRewardsType == 'paid' && $oneWayStatus == 'ACCEPTED')//check if referral accepted or not
                    {
                        $aryDscRewards['rewards_type']  = $dscRewardsType; 
                        $aryDscRewards['rewards_value'] = $dscRewards['rewards_value']; 
                        $totalCash += $aryDscRewards['rewards_value'];//calculating cash

                    } else if($dscRewardsType == 'points' && $oneWayStatus == 'ACCEPTED')//check if referral accepted or not
                    {
                        $aryDscRewards['rewards_type']   = $dscRewardsType; 
                        $aryDscRewards['rewards_value']  = $dscRewards['rewards_value']; 
                        $totalPoints += $aryDscRewards['rewards_value'];//calculating points
                    } else {    
                        $aryDscRewards['rewards_type']  = 0; 
                        $aryDscRewards['rewards_value'] = 0;
                    }
                    //form the referral rewards here
                    $refRewardsType = !empty($refRewards['rewards_type'])?$refRewards['rewards_type']:'';
                    $aryRefRewards['name'] = 'referral';
                    if($refRewardsType == 'paid' && $awaitingActionStatus == 'HIRED')//check if referral hired or not
                    {    
                        $aryRefRewards['rewards_type']  = $refRewardsType; 
                        $aryRefRewards['rewards_value'] = $refRewards['rewards_value']; 
                        $totalCash += $aryRefRewards['rewards_value'];//calculating cash

                    } else if($refRewardsType == 'points' && $awaitingActionStatus == 'HIRED')//check if referral hired or not
                    {
                        $aryRefRewards['rewards_type']      = $refRewardsType; 
                        $aryRefRewards['rewards_value']     = $refRewards['rewards_value']; 
                        $totalPoints += $aryRefRewards['rewards_value'];//calculating points
                    } else {
                        $aryRefRewards['rewards_type']  = 0; 
                        $aryRefRewards['rewards_value'] = 0;
                    }

                    $returnRewards[] = $aryDscRewards;
                    $returnRewards[] = $aryRefRewards;
                    $returnReferralDetails['rewards'] = $returnRewards;//return discovery rewards
                    $returnDetails[] = $returnReferralDetails;
                }
            }
            
            if (!empty($returnDetails)) {
            $data    = array("referrals" => $returnDetails,  "currency_type" => $currencyType ,"total_cash" => $totalCash,"total_points" => $totalPoints);
            $message = array('msg' => array(Lang::get('MINTMESH.rewards.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.rewards.no_rewards')));
        } 
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function createRelationBwPostAndContacts($postId='', $input=array(), $bucketIds=array(), $loggedInUser='', $objCompany){
        
        $usersAry = array();
        $inviteCount = $this->neoPostRepository->getPostInviteCount($postId);        
        $notificationMsg = Lang::get('MINTMESH.notifications.messages.27');
        foreach ($bucketIds as $key => $value) {
            
            $input['bucket_id'] = $value;
            $neoCompanyBucketContacts = $this->enterpriseGateway->enterpriseContactsList($input);
            $contactList = $neoCompanyBucketContacts['data'];

            foreach ($contactList['Contacts_list'] as $contact => $contacts) {
                
                $relation = $this->neoPostRepository->checkPostContactsRelation($postId, $contacts->emailid);
                #check the condition for duplicat job post here
                if(empty($relation) && !in_array($contacts->emailid, $usersAry) && $contacts->status != 'Separated'){
                    
                   $usersAry[] =  $contacts->emailid;
                   $neoUser = $this->neoEnterpriseRepository->getNodeByEmailId($contacts->emailid);
                   $pushData['postId']         = $postId;
                   $pushData['bucket_id']      = $input['bucket_id'];
                   $pushData['company_code']   = $input['company_code'];
                   $pushData['user_emailid']   = $loggedInUser->emailid;
                   $pushData['contact_id']   = $neoUser['id'];
                   $pushData['contact_emailid']  = $contacts->emailid;
                   $pushData['notification_msg'] = $notificationMsg;
                   $pushData['notification_log'] = 0;//for log the notification or not
   //                $this->createPostContactsRelation($pushData) ;
                   Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                   $inviteCount+=1;
                }
            }
        }
        $this->neoPostRepository->updatePostInviteCount($postId, $inviteCount);       
    }
    
    public function createRelationBwCampaignAndContacts($campaignId='', $input=array(), $bucketIds=array(), $loggedInUser='',$objCompany){
        $relation = 0;
        $usersAry = array();
        foreach ($bucketIds as $key => $value) {
            $input['bucket_id'] = $value;
            $neoCompanyBucketContacts = $this->enterpriseGateway->enterpriseContactsList($input);
            $contactList = $neoCompanyBucketContacts['data'];

            foreach ($contactList['Contacts_list'] as $contact => $contacts) {
                #check the condition for duplicat Campaign post here
                $checkCampaignContactRelation = $this->neoPostRepository->checkCampaignContactsRelation($campaignId, $contacts->emailid);
                    
                if(empty($checkCampaignContactRelation) && !in_array($contacts->emailid, $usersAry) && $contacts->status != 'Separated'){
                    
                    $usersAry[] = $contacts->emailid;
                    $pushData['campaign_id']        = $campaignId;
                    $pushData['bucket_id']          = $input['bucket_id'];
                    $pushData['contact_emailid']    = $contacts->emailid;
                    $pushData['contact_name']       = !empty($contacts->fullname)?$contacts->fullname:$contacts->firstname;
                    $pushData['company_code']       = $input['company_code'];
                    $pushData['company_name']       = $input['company_name'];
                    $pushData['company_logo']       = $input['company_logo'];
                    $pushData['campaign_name']      = $input['campaign_name'];
                    $pushData['campaign_type']      = $input['campaign_type'];
                    $pushData['campaign_location']      = $input['campaign_location'];
                    $pushData['campaign_start_date']    = $input['campaign_start_date'];
                    $pushData['campaign_end_date']      = $input['campaign_end_date'];
                    $pushData['user_name']      = $input['user_name'];
                    $pushData['user_emailid']   = $loggedInUser->emailid;
                    $pushData['user_id']        = $loggedInUser->id;
                    $pushData['time_zone']      = $input['time_zone'];
                    $pushData['ip_address']     = $_SERVER['REMOTE_ADDR'];
                    Queue::push('Mintmesh\Services\Queues\CreateCampaignContactsRelationQueue', $pushData, 'default');
                    
                    #send push notifications to all the contacts
                    $notifyData   = $excludedList = array();
                    $notifyData['serviceId']            = $campaignId;
                    $notifyData['loggedinUserDetails']  = $loggedInUser;
                    $notifyData['neoLoggedInUserDetails'] = $objCompany;//obj
                    $notifyData['includedList']     = array($contacts->emailid);
                    $notifyData['excludedList']     = $excludedList;
                    $notifyData['service_type']     = '';
                    $notifyData['service_location'] = '';
                    $notifyData['notification_type'] = 28;
                    $notifyData['service_name']      = $input['campaign_name'];
                    Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $notifyData, 'Notification');
                }
            }
        }
    }
    
    public function createCampaignContactsRelation($relationInput = array()) {
        
        
        $relationAttrs  = array();
        $encodeString   = Config::get('constants.MINTMESH_ENCCODE');
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        $campaignId     = $relationInput['campaign_id'];
        $contactEmailid = $relationInput['contact_emailid'];        
        $relationAttrs['company_code']  = $relationInput['company_code'];
        $relationAttrs['created_by']    = $relationInput['user_emailid'];
        $relationAttrs['created_at']    = gmdate("Y-m-d H:i:s");
        $refId = $this->neoPostRepository->getUserNodeIdByEmailId($contactEmailid);
        $refCode                        = MyEncrypt::encrypt_blowfish($campaignId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));
        $url = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode.""; 
        $biltyUrl = $this->urlShortner($url);
        $relationAttrs['bittly_url']    = $biltyUrl;
      
        try {
            $this->neoPostRepository->createCampaignContactsRelation($relationAttrs, $campaignId, $contactEmailid);
            #send email notifications to all the contacts
            $refId  = 0;
            $emailData  = array();
            $emailData['company_name']      = $relationInput['company_name'];
            $emailData['company_code']      = $relationInput['company_code'];
            $emailData['campaign_id']       = $campaignId;
            $emailData['campaign_name']     = $relationInput['campaign_name'];
            $emailData['campaign_type']     = $relationInput['campaign_type'];
            $emailData['campaign_location']     = $relationInput['campaign_location'];
            $emailData['campaign_start_date']     = $relationInput['campaign_start_date'];
            $emailData['campaign_end_date']     = $relationInput['campaign_end_date'];
            $emailData['company_logo']      = $relationInput['company_logo'];
            $emailData['to_emailid']        = $contactEmailid;
            $emailData['contact_name']        = $relationInput['contact_name'];
            $emailData['from_emailid']      = $relationInput['user_emailid'];
            $emailData['from_userid']      = $relationInput['user_id'];
            $emailData['time_zone']      = $relationInput['time_zone'];
            $emailData['user_name']      = $relationInput['user_name'];
            $emailData['ref_code']          = $refCode;
            $emailData['ip_address']     = $relationInput['ip_address'] ;
            $emailData['bittly_url']     = $biltyUrl ;
            $this->sendCampaignEmailToContacts($emailData); 
        

        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }

    public function sendCampaignEmailToContacts ($emailData) {
        
        $dataSet    = array();
        $email_sent = '';
        $campaignId     = $emailData['campaign_id'];
        $refCode    = $emailData['ref_code']; 
        #form email variables here
        $dataSet['name']                = $emailData['user_name'];
        $dataSet['campaign_name']       = $emailData['campaign_name'];
        $dataSet['campaign_type']       = $emailData['campaign_type'];
        $dataSet['send_company_name']   = $emailData['company_name'];
        $startDate = $this->appEncodeDecode->UserTimezone($emailData['campaign_start_date'],$emailData['time_zone']); 
        $endDate = $this->appEncodeDecode->UserTimezone($emailData['campaign_end_date'],$emailData['time_zone']); 
        $dataSet['campaign_start_date']  = \Carbon\Carbon::parse($startDate)->format('dS M Y');
        $dataSet['campaign_end_date']  = \Carbon\Carbon::parse($endDate)->format('dS M Y');
        $dataSet['campaign_start_time']= \Carbon\Carbon::parse($startDate)->format('h:i A');
        $dataSet['campaign_end_time'] = \Carbon\Carbon::parse($endDate)->format('h:i A');
        $dataSet['campaign_location']       = $emailData['campaign_location'];
        $dataSet['company_name']        = $emailData['company_name'];//Enterpi Software Solutions Pvt.Ltd.
        $dataSet['company_logo']        = $emailData['company_logo'];
        $dataSet['app_id']              = '1268916456509673';
        #redirect email links
          $dataSet['view_jobs_link']          = Config::get('constants.MM_ENTERPRISE_URL') . "/email/all-campaigns/share?ref=" . $refCode."";
          $dataSet['bittly_link']    = $emailData['bittly_url'];;
          $dataSet['view_jobs_link_web']      = Config::get('constants.MM_ENTERPRISE_URL') . "/email/all-campaigns/web?ref=" . $refCode."";
        #set email required params
        $this->userEmailManager->templatePath   = Lang::get('MINTMESH.email_template_paths.contacts_campaign_invitation');
        $this->userEmailManager->emailId        = $emailData['to_emailid'];//target email id
        $this->userEmailManager->dataSet        = $dataSet;
        $this->userEmailManager->subject        = $dataSet['campaign_type'];
        $this->userEmailManager->name           = $emailData['contact_name'];
        $email_sent = $this->userEmailManager->sendMail();
        #for email logs
        $fromUserId  = $emailData['from_userid'];
        $fromEmailId = $emailData['from_emailid'];
        $companyCode = $emailData['company_code'];
        $ipAddress   = $emailData['ip_address'];
        #log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
        }
        $emailLog = array(
            'emails_types_id'   => 6,
            'from_user'         => $fromUserId,
            'from_email'        => $fromEmailId,
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($emailData['to_emailid'])),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $ipAddress
        );
        $this->userRepository->logEmail($emailLog);
    }
    public function addCampaign($input) {
        //variable declaration here
        $campaignId     = '';
        $objCompany     = new \stdClass();
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        $postCampaign   = $postContacts = $campaignContacts = $campaign = $createdCampaign = $campSchedule = $data = array();
        $loggedInUser   = $this->referralsGateway->getLoggedInUser();
        $this->neoLoggedInUserDetails   = $this->neoUserRepository->getNodeByEmailId($loggedInUser->emailid);
        $userId = $this->neoLoggedInUserDetails->id;
        $timeZone = !empty($input['time_zone'])?$input['time_zone']:0;  
        $company        = $this->enterpriseRepository->getUserCompanyMap($loggedInUser['id']);
        $companyId      = $company->company_id;
        $companyCode    = $company->code;
        $userEmailId    = $loggedInUser->emailid;
        $objCompany->fullname   = $company->name;
        //explode jobs and buckets here
        $campPostIds    = !empty($input['job_ids'])?$input['job_ids']:'';
        $campBucketIds  = !empty($input['selectedBuckets'])?explode(',',$input['selectedBuckets']):'';
        $campaignId     = !empty($input['campaign_id'])?$input['campaign_id']:'';
        $requestType    = !empty($input['request_type'])?$input['request_type']:'add';
        
        $campaign['campaign_name']      = !empty($input['campaign_name'])?$input['campaign_name']:'';
        $campaign['campaign_type']      = !empty($input['campaign_type'])?$input['campaign_type']:'';//mass recruitment | military veterans | campus hires
        $campaign['location_type']      = !empty($input['location_type'])?$input['location_type']:'';//online | onsite 
        $campaign['bucket_id']          = !empty($input['selectedBuckets'])?$input['selectedBuckets']:''; 
        
        if(strtolower($campaign['location_type']) == 'onsite'){
            $campaign['address']        = !empty($input['address'])?$input['address']:'';
            $campaign['city']           = !empty($input['city'])?$input['city']:'';
            $campaign['zip_code']       = !empty($input['zip_code'])?$input['zip_code']:'';
            $campaign['state']  = !empty($input['state'])?$input['state']:'';
            $campaign['country']  = !empty($input['country'])?$input['country']:'';
            $campaign['latitude']  = !empty($input['latitude'])?$input['latitude']:'';
            $campaign['longitude']  = !empty($input['longitude'])?$input['longitude']:'';
        }    
        $campSchedule = !empty($input['schedule'])?$input['schedule']:array();
         
        $campaign['company_code'] = $companyCode;
        $campaign['status']       = Config::get('constants.POST.STATUSES.ACTIVE');
         //upload the file
         if (isset($input['ceos_file_name']) && !empty($input['ceos_file_name'])) {
                //upload the file
                $this->userFileUploader->source =  $input['ceos_file_name'];
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET_CAMPAIGN_IMAGES');
                $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['ceos_file_name']);
                $campaign['ceos_file'] = $renamedFileName;
                $campaign['ceos_name'] = !empty($input['ceos_org_name'])?$input['ceos_org_name']:'';
            }
            if (isset($input['emp_file_name']) && !empty($input['emp_file_name'])) {
                //upload the file
                $this->userFileUploader->source =  $input['emp_file_name'];
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET_CAMPAIGN_IMAGES');
                $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['emp_file_name']);
                $campaign['emp_file'] = $renamedFileName;
                $campaign['emp_name'] = !empty($input['emp_org_name'])?$input['emp_org_name']:'';
            }
            if(isset($input['ceos_file_name_s3']) && !empty($input['ceos_file_name_s3'])){
                $campaign['ceos_file'] = $input['ceos_file_name_s3'];
                $campaign['ceos_name'] = !empty($input['ceos_org_name_s3'])?$input['ceos_org_name_s3']:'';
            }
            if(isset($input['emp_file_name_s3']) && !empty($input['emp_file_name_s3'])){
                $campaign['emp_file'] = $input['emp_file_name_s3'];
                $campaign['emp_name'] = !empty($input['emp_org_name_s3'])?$input['emp_org_name_s3']:'';
            }
        if($requestType == 'edit'){            
            $campaign['ceos_name'] = !empty($campaign['ceos_name'])?$campaign['ceos_name']:'';
            $campaign['ceos_file'] = !empty($campaign['ceos_file'])?$campaign['ceos_file']:'';
            $campaign['emp_name'] = !empty($campaign['emp_name'])?$campaign['emp_name']:'';
            $campaign['emp_file'] = !empty($campaign['emp_file'])?$campaign['emp_file']:'';
      
            //updating Campaign details here
           $editedCampaign = $this->neoPostRepository->editCampaignAndCompanyRelation($companyCode, $campaignId, $campaign, $userEmailId);
        }  else {
            //creating Campaign And Company Relation here
            $createdCampaign = $this->neoPostRepository->createCampaignAndCompanyRelation($companyCode, $campaign, $userEmailId);
            if (isset($createdCampaign[0]) && isset($createdCampaign[0][0])) {
                $campaignId = $createdCampaign[0][0]->getID(); 
                $data['campaign_name']    = $createdCampaign[0][0]->campaign_name;
                $data['campaign_type']    = $createdCampaign[0][0]->campaign_type;
                $data['total_vacancies']  = $createdCampaign[0][0]->total_vacancies;
                $data['location_type']    = $createdCampaign[0][0]->location_type;
                $data['camp_ref']         = $refCode = MyEncrypt::encrypt_blowfish($campaignId.'_'.$userId,Config::get('constants.MINTMESH_ENCCODE'));
                $url = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode.""; 
                $biltyUrl = $this->urlShortner($url);
                $data['bittly_url'] = $biltyUrl;
                if(!empty($createdCampaign[0][0]->latitude)){
                    $data['latitude'] = $createdCampaign[0][0]->latitude;
                }
                if(!empty($createdCampaign[0][0]->longitude)){
                    $data['longitude'] = $createdCampaign[0][0]->longitude;
                }
                if(strtolower($data['location_type']) == 'onsite'){
                $location = array();
                $location['address']          = $createdCampaign[0][0]->address;
                $location['state']            = $createdCampaign[0][0]->state;
                $location['country']          = $createdCampaign[0][0]->country;
                $location['city']             = $createdCampaign[0][0]->city;
                $location['zip_code']         = $createdCampaign[0][0]->zip_code;
                $data['location'] = $location;
            }
            }
        }
        //checking if campaign created or not
        if($campaignId){
            if(!empty($campSchedule)){
                    //create Campaign Schedule Relation here
                    foreach($campSchedule as $schedule){
                        $scheduleAttrs  = array();
                        $scheduleId     = !empty($schedule['schedule_id'])?$schedule['schedule_id']:'';//if update
                        $scheduleAttrs['start_date'] = $schedule['start_on_date'];
                        $scheduleAttrs['start_time'] = $schedule['start_on_time'];
                        $gmtstart_date = $schedule['start_on_date']." " .$schedule['start_on_time'];    
                        $scheduleAttrs['gmt_start_date'] = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezoneGmt($gmtstart_date,$timeZone)));  
                        $scheduleAttrs['end_date']   = $schedule['end_on_date'];
                        $scheduleAttrs['end_time']   = $schedule['end_on_time'];
                        $gmtend_date = $schedule['end_on_date']." " .$schedule['end_on_time'];
                        $scheduleAttrs['gmt_end_date'] = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezoneGmt($gmtend_date,$timeZone)));   
                        $scheduleAttrs['company_code'] = $companyCode;
                        
                        if(!empty($scheduleId)){
                            //update Campaign Schedule here
                            $campaignSchedule = $this->neoPostRepository->updateCampaignScheduleRelation($scheduleId, $campaignId, $scheduleAttrs, $userEmailId);
                        }  else {
                            //create Campaign Schedule here
                            $campaignSchedule = $this->neoPostRepository->createCampaignScheduleRelation($campaignId, $scheduleAttrs, $userEmailId);
                            foreach ($campaignSchedule as $k => $value){
                                $value = $value[0];
                                $schedule['schedule_id']    = $value->getID();
                                $gmtstart_date = $value->start_date." " .$value->start_time;
                                $gmt_start_on_date = $this->appEncodeDecode->UserTimezone($gmtstart_date,$input['time_zone']);
                                $schedule['gmt_start_on_date'] = !empty($gmt_start_on_date)?$gmt_start_on_date:'';
                                $gmtend_date = $value->end_date." " .$value->end_time;
                                $gmt_end_on_date = $this->appEncodeDecode->UserTimezone($gmtend_date,$input['time_zone']);
                                $schedule['gmt_end_on_date'] = !empty($gmt_end_on_date)?$gmt_end_on_date:'';
                                $schedule['start_on_date']  = date('Y-m-d', strtotime($value->start_date));
                                $schedule['start_on_time']  = $value->start_time;
                                $schedule['end_on_date']    = date('Y-m-d', strtotime($value->end_date));
                                $schedule['end_on_time']    = $value->end_time;
                                $campSchedule[] = $schedule; 
                            }
                            }
                    }
            }
            //checking if user selected at least one job or not
            if(!empty($campPostIds)){
                $postCampaign['company_code'] = $companyCode;
                $postCampaign['created_at']   = gmdate("Y-m-d H:i:s");
                $postCampaign['created_by']   = $userEmailId;
                foreach ($campPostIds as $key => $postId) {
                    $postCampaignRes = '';
                    #check Post And Campaign Relation
                    $postCampaignRes = $this->neoPostRepository->checkPostAndCampaignRelation($postId, $campaignId);
                    if(empty($postCampaignRes)){
                    //creating Campaign And Post Relation here
                    $postCampaignRes = $this->neoPostRepository->createPostAndCampaignRelation($postId, $campaignId, $postCampaign);
                    }
                    //creating Post and contacts Relation here
                    if(!empty($postCampaignRes) && !empty($campBucketIds)){
                        $this->neoPostRepository->changePostStatus($postId);
                        $postContacts['company_id']   = $companyId;    
                        $postContacts['company_code'] = $companyCode;
                        $this->createRelationBwPostAndContacts($postId, $postContacts, $campBucketIds, $loggedInUser, $objCompany);
                    }
                     $vacancies = 0;
                     foreach($postCampaignRes as $posts){
                        $post = array();
                        $postDetails = $this->referralsGateway->formPostDetailsArray($posts[0]);
                        $post['post_id'] = $postDetails['post_id'];
                        $post['name'] = $postDetails['service_name'];
                        $post['no_of_vacancies'] = $postDetails['no_of_vacancies'];
                        $postAry[] = $post;
                        $vacancies += !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:'';;
                        }
                        $data['total_vacancies'] = $vacancies;
                    }

            }
            //checking if user selected at least one bucket or not
            if(!empty($campBucketIds)){
                $campaignContacts['company_code'] = $companyCode;
                $campaignContacts['user_name']    = !empty($loggedInUser->fullname)?$loggedInUser->fullname:$loggedInUser->firstname;
                $campaignContacts['time_zone'] = $input['time_zone'];
                $campaignContacts['company_id']   = $companyId;
                $campaignContacts['company_name'] = $company->name;
                $campaignContacts['company_logo']    = $company->logo;
                $campaignContacts['campaign_name'] = !empty($createdCampaign[0][0]->campaign_name)?$createdCampaign[0][0]->campaign_name:$editedCampaign[0][0]->campaign_name; 
                $campaignContacts['campaign_type'] = !empty($createdCampaign[0][0]->campaign_type)?$createdCampaign[0][0]->campaign_type:$editedCampaign[0][0]->campaign_type; 
                if((isset($createdCampaign[0][0]) && $createdCampaign[0][0]->location_type === 'onsite')){
                    
                   $campaignContacts['campaign_location'] = !empty($createdCampaign[0][0]->zip_code)?$createdCampaign[0][0]->address.', '.$createdCampaign[0][0]->city.', '.$createdCampaign[0][0]->zip_code.', '.$createdCampaign[0][0]->state.', '.$createdCampaign[0][0]->country:$createdCampaign[0][0]->address.', '.$createdCampaign[0][0]->city.', '.$createdCampaign[0][0]->state.', '.$createdCampaign[0][0]->country;
                }else if(isset($editedCampaign[0][0]) && $editedCampaign[0][0]->location_type === 'onsite'){
                    $campaignContacts['campaign_location'] = !empty($editedCampaign[0][0]->zip_code)?$editedCampaign[0][0]->address.', '.$editedCampaign[0][0]->city.', '.$editedCampaign[0][0]->zip_code.', '.$editedCampaign[0][0]->state.', '.$editedCampaign[0][0]->country:$editedCampaign[0][0]->address.', '.$editedCampaign[0][0]->city.', '.$editedCampaign[0][0]->state.', '.$editedCampaign[0][0]->country;
                }
                else{
                                        $campaignContacts['campaign_location'] = 'online'; 
                }
                $campaignContacts['campaign_start_date'] = $campaignSchedule[0][0]->gmt_start_date;
                $campaignContacts['campaign_end_date'] = $campaignSchedule[0][0]->gmt_end_date;
                //creating Campaign And contacts Relation here
                $this->createRelationBwCampaignAndContacts($campaignId, $campaignContacts, $campBucketIds, $loggedInUser, $objCompany);
            }
            if(isset($createdCampaign[0][0]->ceos_file)){
                $data['ceos_file'] = !empty($createdCampaign[0][0]->ceos_file)?$createdCampaign[0][0]->ceos_file:'';
            }
            if(isset($createdCampaign[0][0]->emp_file)){
                $data['emp_file'] = !empty($createdCampaign[0][0]->emp_file)?$createdCampaign[0][0]->emp_file:'';
            }
            if(isset($editedCampaign[0][0]->ceos_file)){
                $data['ceos_file'] = !empty($editedCampaign[0][0]->ceos_file)?$editedCampaign[0][0]->ceos_file:'';
            }
            if(isset($editedCampaign[0][0]->emp_file)){
                $data['emp_file'] = !empty($editedCampaign[0][0]->emp_file)?$editedCampaign[0][0]->emp_file:'';
            }
            foreach($campBucketIds as $buckets){
                $bucket = '';
                $bucket = (int)$buckets[0];
                $bucketAry[] = $bucket;
            }
            $data['bucket_ids']         = $bucketAry;
            $data['schedule']     = $campSchedule;
            $data['job_details']   = $postAry;
            if($requestType == 'edit'){
                $data = array(); 
            }
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.campaign.success')));
        } else {
            $data = array();
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.campaign.error')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function campaignsList($input) {
        
        $campaign = $returnDetails = $ids =array();
        $page = !empty($input['page_no']) ? $input['page_no'] : 0;
        $input['time_zone'] = !empty($input['time_zone'])?$input['time_zone']:0;  
        $this->loggedinUserDetails      = $this->referralsGateway->getLoggedInUser();
        $this->neoLoggedInUserDetails   = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $company = $this->enterpriseRepository->getUserCompanyMap( $this->loggedinUserDetails->id);
        
        $input['company_code']  = $company->code;
        $checkPermissions       = $this->enterpriseRepository->getUserPermissions($this->loggedinUserDetails->group_id, $input);
        $permission             = !empty($checkPermissions['run_campaign'])?$checkPermissions['run_campaign']:0;
         if(!empty($input['filter'])){
            $filter = explode(',', $input['filter']);
            $campaigns              = $this->neoPostRepository->campaignsList($this->neoLoggedInUserDetails->emailid, $input, $page, $permission,$filter);
            $totalCampaigns              = $this->neoPostRepository->campaignsList($this->neoLoggedInUserDetails->emailid, $input, '', $permission,$filter);

         }else{
             $campaigns              = $this->neoPostRepository->campaignsList($this->neoLoggedInUserDetails->emailid, $input, $page, $permission,'');
             $totalCampaigns              = $this->neoPostRepository->campaignsList($this->neoLoggedInUserDetails->emailid, $input, '', $permission,'');

         }
        if(!empty(count($campaigns))){
            $totalCount         = $totalCampaigns->count();
            foreach($campaigns as $k=>$v){
                $campaign['id'] = $v[0]->getID();
                $postsRes       = $this->neoPostRepository->getCampaignPosts($campaign['id'],'','');
                $vacancies      = 0;
                foreach($postsRes as $posts){
                    $postDetails = $this->referralsGateway->formPostDetailsArray($posts[0]);
                    $vacancies  += !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:0;
                }
                $campaign['total_vacancies']= $vacancies;
                $campaign['campaign_name']  = !empty($v[0]->campaign_name)?$v[0]->campaign_name:'';
                $campaign['campaign_type']  = !empty($v[0]->campaign_type)?$v[0]->campaign_type:'';
                $status = !empty($v[0]->status)?$v[0]->status:'';
                if($status == 'ACTIVE'){
                    $campaign['status'] = 'OPEN'; 
                }else{
                    $campaign['status'] = 'CLOSED'; 
                }
                $scheduleTimes = $this->neoPostRepository->getCampaignSchedule($campaign['id']);
                if(!empty($scheduleTimes[0]) && !empty($scheduleTimes[0][0])){
                    $scheduleTimes                  = $scheduleTimes[0][0];
                    $gmtstart_date                  = $scheduleTimes->start_date." " .$scheduleTimes->start_time;
                    $campaign['gmt_start_on_date']  = !empty($gmtstart_date)?$gmtstart_date:'';
                    $gmtend_date                    = $scheduleTimes->end_date." " .$scheduleTimes->end_time;
                    $campaign['gmt_end_on_date']    = !empty($gmtend_date)?$gmtend_date:'';
                    $startdate                      = $scheduleTimes->start_date;
                    $campaign['start_on_date']      = !empty($startdate)?date('Y-m-d', strtotime($startdate)):'';
                    $enddate                        = $scheduleTimes->end_date;
                    $campaign['end_on_date']        = !empty($enddate)?date('Y-m-d', strtotime($enddate)):'';
                }
                $returnDetails[] = $campaign;
            }
            if(!empty($returnDetails)){
                $data = array("campaigns" => array_values($returnDetails), "count" => count($campaigns), "total_count" => $totalCount);
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.campaigns.success')));
            }else{
                $data = array();
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.campaigns.failure')));
            }
        }else{
            $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.campaigns.no_campaigns')));
        }
        return $this->commonFormatter->formatResponse($responseCode,$responseMsg, $message, $data);
    }
    
    public function viewCampaign($input) {
        
        $data = $campSchedule = $scheduleRes = $postAry = $bucketAry  = array();
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        $loggedInUser   = $this->referralsGateway->getLoggedInUser();
        $this->neoLoggedInUserDetails   = $this->neoUserRepository->getNodeByEmailId($loggedInUser->emailid);
        $userId = $this->neoLoggedInUserDetails->id;
        $input['time_zone'] = !empty($input['time_zone'])?$input['time_zone']:0;
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        $campaignId     = !empty($input['campaign_id'])?$input['campaign_id']:'';
        $refCode        = MyEncrypt::encrypt_blowfish($campaignId.'_'.$userId,Config::get('constants.MINTMESH_ENCCODE'));
        $campRes        = $this->neoPostRepository->getCampaignById($campaignId);
        if($campRes){
            //form response details here
            $returnData['campaign_name']    = $campRes->campaign_name;
            $returnData['campaign_type']    = $campRes->campaign_type;
            $returnData['total_vacancies']  = $campRes->total_vacancies;
            $returnData['location_type']    = $campRes->location_type;
            $returnData['camp_ref']         = $refCode;
            if($campRes->location_type == 'ACTIVE'){
               $returnData['status'] = 'OPEN'; 
            }else{
                    $returnData['status'] = 'CLOSED'; 
            }
            $filesAry[0]['ceos_file_name'] = !empty($campRes->ceos_file)?$campRes->ceos_file:'';  
            $filesAry[0]['ceos_org_name'] = !empty($campRes->ceos_name)?$campRes->ceos_name:'';
            $filesAry[1]['emp_file_name'] = !empty($campRes->emp_file)?$campRes->emp_file:'';  
            $filesAry[1]['emp_org_name'] = !empty($campRes->emp_name)?$campRes->emp_name:'';
            if((!empty($campRes->ceos_file) && !empty($campRes->ceos_name)) || (!empty($campRes->emp_file) && !empty($campRes->emp_name))){
            $returnData['files'] = $filesAry;
            }
            if(!empty($campRes->latitude)){
            $returnData['latitude'] = $campRes->latitude;
            }
            if(!empty($campRes->longitude)){
            $returnData['longitude'] = $campRes->longitude;
            }
            //location Details here
            if(strtolower($returnData['location_type']) == 'onsite'){
                $location = array();
                $location['address']          = $campRes->address;
                $location['state']            = $campRes->state;
                $location['country']          = $campRes->country;
                $location['city']             = $campRes->city;
                $location['zip_code']         = $campRes->zip_code;
                $returnData['location'] = $location;
            }
            //get Campaign Schedule here
            $scheduleRes   = $this->neoPostRepository->getCampaignSchedule($campaignId);
            foreach ($scheduleRes as $k => $value){
                $value = $value[0];
                $schedule['schedule_id']    = $value->getID();
                $gmtstart_date = $value->start_date." " .$value->start_time;
                $schedule['gmt_start_on_date'] = !empty($value->gmt_start_date)?date("D M d, Y H:i:s A", strtotime($this->appEncodeDecode->UserTimezone($value->gmt_start_date,$input['time_zone']))):'';
                $gmtend_date = $value->end_date." " .$value->end_time;
                $schedule['gmt_end_on_date'] = !empty($value->gmt_end_date)?date("D M d, Y H:i:s A", strtotime($this->appEncodeDecode->UserTimezone($value->gmt_end_date,$input['time_zone']))):'';
                $currentDate = gmdate("Y-m-d H:i:s");
                if($value->gmt_end_date < $currentDate ){
                    $schedule['status']    = 'CLOSED';
                }else{
                     $schedule['status']    = 'OPEN';
                }
                $schedule['start_on_date']  = date('Y/m/d', strtotime($this->appEncodeDecode->UserTimezone($value->gmt_start_date,$input['time_zone'])));
                $schedule['start_on_time']  = date('H:i', strtotime($this->appEncodeDecode->UserTimezone($value->gmt_start_date,$input['time_zone'])));;
                $schedule['end_on_date']    = date('Y/m/d', strtotime($this->appEncodeDecode->UserTimezone($value->gmt_end_date,$input['time_zone'])));
                $schedule['end_on_time']    = date('H:i', strtotime($this->appEncodeDecode->UserTimezone($value->gmt_end_date,$input['time_zone'])));;
                $campSchedule[] = $schedule; 
            }
            //get Campaign Posts here
            $postsRes   = $this->neoPostRepository->getCampaignPosts($campaignId,'','');
            $vacancies = 0;
            foreach($postsRes as $posts){
                $post = array();
                $postDetails = $this->referralsGateway->formPostDetailsArray($posts[0]);
                $post['post_id'] = $postDetails['post_id'];
                $post['name'] = $postDetails['service_name'];
                $post['no_of_vacancies'] = !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:'';
                $postAry[] = $post;
                $vacancies += !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:'';;
            }
            $returnData['total_vacancies'] = $vacancies;
            //get Campaign Buckets here
            $bucketsRes    = !empty($campRes->bucket_id)?explode(',',$campRes->bucket_id):'';
            if(!empty($bucketsRes)){
                foreach($bucketsRes as $buckets){
                    $bucket = '';
                    $bucket = (int)$buckets;
                    $bucketAry[] = $bucket;
                }
            }
            //form response details here
            $returnData['schedule']     = $campSchedule;
            $returnData['job_details']      = $postAry;
            $returnData['bucket_ids']   = $bucketAry;
            $url = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode.""; 
            $biltyUrl = $this->urlShortner($url);
            $returnData['bittly_url'] = $biltyUrl;
            $data = $returnData;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.campaign.success')));
        } else {
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.campaign.error')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    private function urlShortner($url){
        
        $bitly = Config::get('constants.BITLY_URL').Config::get('constants.BITLY_ACCESS_TOKEN').'&longUrl='.$url;
        try{
            $fp   = '';
            $type = self::CURL_CALL_TYPE;
            $response = $this->referralsGateway->curlCall($bitly, $fp, $type);
            $b = (array) json_decode($response, TRUE);
            if(!empty($b['data']['link_save']['link'])){
                $url = $b['data']['link_save']['link'];
            }else{
               // \Log::info("<<<<<< Short Url not created >>>>>>");
            }
        } catch (\RuntimeException $e) {
            return $url;
        }    
       return $url;
    }
       
    public function getCompanyAllReferrals($input) {
        
        $data = $ReferralsRes  = $returnData = array();
        $page           = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search         = !empty($input['search']) ? $input['search'] : '';
        $filter         = !empty($input['filter']) ? explode(',', $input['filter']) : '';
        $input['time_zone'] = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        
        $loggedInUser   = $this->referralsGateway->getLoggedInUser();
        $company        = $this->enterpriseRepository->getUserCompanyMap($loggedInUser->id);
        $emailId        = $loggedInUser->id;
        $company        = $this->enterpriseRepository->getUserCompanyMap($loggedInUser->id);
        $companyCode    = !empty($company->code)?$company->code:'';
        #get the Company All Referrals list here
        $ReferralsRes   = $this->neoPostRepository->getCompanyAllReferrals($emailId, $companyCode, $search, $page, $filter);
        
        if(isset($ReferralsRes[0])){
            #get the total Records count
            $totalRec     = !empty($ReferralsRes) ? $ReferralsRes->count() : 0;
            $totalRecords = !empty($ReferralsRes[0][3]) ? $ReferralsRes[0][3] : 0;  
            
            foreach($ReferralsRes as $result){
                $record     = array();
                $post       = $result[0];//post details
                $user       = $result[1];//user details
                if(!empty($user->emailid) && isset($user->emailid)){
                    $record['referred_by_phone']= 0;
                    $record['from_user'] = $result[1]->emailid;
                   
                }else{
                    $record['referred_by_phone']= 1;
                    $record['from_user'] = $user->phone;
                }
                $relation   = $result[2];//relation details
                #form the referrals here
                $record['id']               = $result[2]->getID();
                $record['post_id']          = $result[0]->getID();
                $record['referred_by']      = $result[2]->referred_by;
                $record['relation_count']   = $result[2]->relation_count;
                if($relation->one_way_status != 'UNSOLICITED'){
                    $record['service_name']     = $post->service_name;
                }else{
                    $record['service_name']   = 'Not Tagged';  
                }
                $record['document_id']      = !empty($relation->document_id) ? $relation->document_id : 0;
                $record['one_way_status']   = $relation->one_way_status;
                $record['resume_path']      = $relation->resume_path;
                $record['resume_name']      = $relation->resume_original_name;
                $record['created_at']       = date('M d, Y',strtotime($this->appEncodeDecode->UserTimezone($relation->created_at,$input['time_zone'])));
                $record['awt_status']       = $relation->awaiting_action_status;
                #get the user details here
                $referralName = $userName = '';
                $nonMMUser    = new \stdClass();
                if(!empty($user->emailid) && !empty($relation->referred_by)){
                    $userDetails = $this->enterpriseRepository->getContactByEmailId($user->emailid,$company->company_id);
                    if(!empty($userDetails)){
                        $userName = $userDetails[0]->firstname.' '.$userDetails[0]->lastname;
                    }
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($user->emailid);
                    $neoReferralName = !empty($neoUserDetails['fullname'])?$neoUserDetails['fullname']:$neoUserDetails['firstname'];
                    if(empty($neoUserDetails['firstname']) && empty($neoUserDetails['fullname'])){
                        $nonMMUser    = $this->contactsRepository->getImportRelationDetailsByEmail($relation->referred_by, $user->emailid);
                        $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                    }else{
                        $referralName = !empty($userName)?$userName:$neoReferralName;
                    } 
                }  else {
                    if(empty($user->firstname) && !empty($relation->referred_by)){
                        $nonMMUser     = $this->contactsRepository->getImportRelationDetailsByPhone($relation->referred_by, $user->phone);
                        $referralName  = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                    }
                }
                $referrerDetails = $this->enterpriseRepository->getContactByEmailId($relation->referred_by,$company->company_id);
                if(!empty($referrerDetails)){
                    $referrerName = $referrerDetails[0]->firstname.' '.$referrerDetails[0]->lastname;}
                else{
                    $neoReferrerDetails = $this->neoUserRepository->getNodeByEmailId($relation->referred_by);
                    $neoReferrerName = !empty($neoReferrerDetails['fullname'])?$neoReferrerDetails['fullname']:$neoReferrerDetails['firstname'];
                }
                $record['fullname']    = !empty($referralName)?$referralName:'The contact';
                $record['referred_by_name'] = !empty($referrerName)?$referrerName:$neoReferrerName;
                $returnData[] = $record;
            }
            $data = array("referrals" => array_values($returnData), 'count'=>$totalRec, 'total_records'=>$totalRecords);
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.candidates.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.candidates.no_candidates')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    
    public function MultipleAwaitingAction($input){
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $inputData = array();
        foreach($input['id'] as $k=>$v){
           $returnDetails = $this->neoPostRepository->getReferralDetails($v);
           if(!empty($returnDetails[0][0]->phone) && isset($returnDetails[0][0]->phone)){
            $inputData['referred_by_phone']= 1;
           }else{
            $inputData['referred_by_phone']= 0;
           }
            $userEmailId   = $this->loggedinUserDetails->emailid;
            $userFirstName = $this->loggedinUserDetails->firstname;
            $postId = $returnDetails[0][2]->getID();
            $status = $input['awaiting_action_status'];
            $referral = $returnDetails[0][0]->emailid;
            $referredBy = $returnDetails[0][1]->referred_by;
            $relationCount = $returnDetails[0][1]->relation_count;
            $nonMintmesh = $inputData['referred_by_phone'];
            $result = $this->neoPostRepository->updateAwaitingActionDetails($userEmailId, $postId, $referredBy, $referral, $status, $relationCount, $nonMintmesh);
            $status = $result[0][1]->awaiting_action_status;
        }     
        if($result){
            $data = array("awt_status" => $status);
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
        }else{
            $data = array();
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.referrals.no_referrals_found')));
        }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage,$data);
    }
    
    public function applyJob($input){
        
     if(!empty($input['post_id']) && isset($input['post_id']) && !empty($input['reference_id']) && isset($input['reference_id'])){
        
        $documentId = 0; 
        $input['time_zone'] = !empty($input['timeZone'])?$input['timeZone']:0; 
        $input['referred_by_id'] = $reference_id = $input['reference_id'];
        $postId =  $input['post_id'];
        $companyDetils = $this->neoPostRepository->getPostCompany($postId); 
        #check user Separated Status here
        $separatedStatus = $this->checkReferredUserSeparatedStatus($reference_id, $companyDetils->companyCode);
        if($separatedStatus){
                $from =  $this->appEncodeDecode->filterString(strtolower($input['emailid']));
                $checkCand_Not_exist = $this->job2->checkCandidate($input,$from);
                if($checkCand_Not_exist){ 
                    $checkRel = $this->job2->checkRel($input);
                    if(!empty($checkRel[0]) && isset($checkRel[0][0])){
                        $neoInput['uploaded_by_p2'] = '1';
                        $neoInput['referred_for'] = $checkRel[0][0]->user_emailid;
                        $neoInput['referred_by'] = $checkRel[0][1]->emailid;
                        $checkUser = $this->job2->checkUser($from);
                        if(!empty($checkUser[0]) && isset($checkUser[0][0])){
                        $neoInput['referral'] = $checkUser[0][0];
                        }else{
                        $input['referral_name'] = $input['fullname'];
                        $createUser = $this->job2->createUser($from,$input);
                        $neoInput['referral'] = $createUser[0][0];
                        }
                         if (isset($input['cv']) && !empty($input['cv'])) {
                            
                            $resumeFile = $input['cv'];
                            $originalFileName =  !empty($input['resume_original_name']) ? $input['resume_original_name'] : '';
                            #get the user id by email for doc id
                            $referredByEmail = !empty($checkRel[0][1]->emailid) ? $checkRel[0][1]->emailid : '';
                            $sqlUser    = $this->userRepository->getUserByEmail($referredByEmail);
                            $refUserId  = !empty($sqlUser->id)?$sqlUser->id:0;
                            #get company details by code
                            $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyDetils->companyCode);
                            $companyId   = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
                            $source = self::SOURCE_FROM_EMAIL_UPLOAD;
                            $renamedFileName = '';
                            #insert company resumes in company resumes table
                            $insertResult = $this->enterpriseRepository->insertInCompanyResumes($companyId, $originalFileName, $refUserId, $source);
                            $documentId   = $insertResult->id;
                            
                            #file move to s3 folder
                            $fileName = $this->moveResume($resumeFile, $companyId, $documentId);
                            if($fileName){
                                #form s3 path here
                                $s3Path = Config::get('constants.S3_DOWNLOAD_PATH').$companyId.'/'.$fileName;
                                #updte s3 path in company resumes table
                                $updateResult = $this->enterpriseRepository->updateCompanyResumes($documentId, $s3Path);
                                $renamedFileName = $s3Path;
                            }    
                            $neoInput['document_id'] = $documentId;
                            $neoInput['resume_path'] = $renamedFileName;
                        }
                        $neoInput['resume_original_name'] = $input['resume_original_name'];
                        $neoInput['created_at']     = gmdate('Y-m-d H:i:s'); 
                        $neoInput['awaiting_action_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                        $neoInput['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                        $neoInput['relation_count'] = '1';
                        $neoInput['completed_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                        $neoInput['awaiting_action_by'] = $neoInput['referred_for'];
                        if($input['flag'] == '1'){
                            $neoInput['one_way_status'] = Config::get('constants.REFERRALS.STATUSES.UNSOLICITED');
                        }
                        else{
                            $neoInput['one_way_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                        }
                        $referredCandidate = $this->neoPostRepository->referCandidate($neoInput, $input);
                        if($referredCandidate){
                            #update got referred relation id company resumes table
                            if(isset($referredCandidate[0]) && !empty($referredCandidate[0][2]) && !empty($documentId)){
                                $gotReferredId = $referredCandidate[0][2];
                                $this->enterpriseRepository->updateCompanyResumesWithGotReferredId($documentId, $gotReferredId);
                            }
                            if(!empty($input['department']) && isset($input['department'])){
                             #map job_function if provided
                             $userId = $referredCandidate[0][1]->getID();
                             $jfResult = $this->neoPostRepository->mapJobFunctionToUser($input['department'], $userId, Config::get('constants.REFERRALS.ASSIGNED_JOB_FUNCTION'));
                            }
                            $responseCode   = self::SUCCESS_RESPONSE_CODE;
                            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                            $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.success')));
                        }else{
                            $responseCode   = self::ERROR_RESPONSE_CODE;
                            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                            $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.failure')));
                        }
                    }else{
                        $responseCode   = self::ERROR_RESPONSE_CODE;
                        $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                        $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.referrer_invalid')));
                    }

                }else{
                    $responseCode   = self::ERROR_RESPONSE_CODE;
                    $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.referred')));
                }
            }else{
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.user_separated')));
            }
     }else{
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.invalid')));
     }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, array());
        
    }
    
    public function decryptRef($input){
       $data = array();
       $input['all_jobs'] = !empty($input['all_jobs'])?$input['all_jobs']:'';
       if(!empty($input['ref']) && isset($input['ref'])){
        $mail_parse_ref = isset($input['ref'])?MyEncrypt::decrypt_blowfish($input['ref'],Config::get('constants.MINTMESH_ENCCODE')):0;
        $mail_parse_ref_val = array_map('intval',explode('_',$mail_parse_ref));	
	$post_id = isset($mail_parse_ref_val[0])?$mail_parse_ref_val[0]:0;
        $referred_by_id = isset($mail_parse_ref_val[1])?$mail_parse_ref_val[1]:0;
        if($post_id != 0 && $referred_by_id != 0){
        $userDetails = $this->neoEnterpriseRepository->getNodeById($referred_by_id);
        $companyDetails     = $this->neoPostRepository->getPostCompany($post_id);
        $input['post_id'] = $post_id;
        $postStatus = $this->job2->getPost($input);
        $postDetails    = $this->referralsGateway->formPostDetailsArray($postStatus);
        $companyLogo = !empty($companyDetails->logo)?$companyDetails->logo:'';
        if($input['all_jobs'] == 0){
        $jobTitle = $companyDetails->name .' looking for '.$postStatus->looking_for;
        $jobDescription = 'Experience: '.$postDetails['experience_range_name'].', Location: '.$postStatus->service_location;
         $url = Config::get('constants.MM_ENTERPRISE_URL') . "/email/job-details/share?ref=" . $input['ref']."";
         $bitlyUrl = $this->urlShortner($url);
         $bittly    = $bitlyUrl;

        }else if($input['all_jobs'] == 1){
            $jobTitle = 'These jobs are available in '.$companyDetails->name;
            $jobDescription = $postStatus->looking_for;
            $url = Config::get('constants.MM_ENTERPRISE_URL') . "/email/all-jobs/share?ref=" . $input['ref']."";
            $bitlyUrl = $this->urlShortner($url);
            $bittly    = $bitlyUrl;

        }
        else{
            $jobTitle = $jobDescription = $url = '';
        }
        $data = array("post_id" => $input['post_id'],"reference_id"=>$referred_by_id,"emailid" => $userDetails->emailid,"post_status"=>$postStatus->status,"company_logo"=>$companyLogo,"company_name"=>$companyDetails->name,"title"=>$jobTitle,"description" => $jobDescription,"url"=>$url,"bittly_url"=>$bittly);
        }else{
            $data = array();
        }
        return $data;
       }
    }
    
    public function applyJobsList($input){
        $jobsListCount  = 0;
        $compName       = '';
        $resJobsList    = $refAry = $returnData =  $data = array();
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        $referenceId    = isset($input['reference_id'])?$input['reference_id']:'';
        $page           = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search_for     = !empty($input['search']) ? $input['search'] : 0;
        if(!empty($referenceId)){
            $refId          = MyEncrypt::decrypt_blowfish($referenceId, Config::get('constants.MINTMESH_ENCCODE'));
            $url = $enterpriseUrl . "/email/all-jobs/share?ref=" . $referenceId.""; 
            $biltyUrl       = $this->urlShortner($url);
            $bittly         = $biltyUrl;
            $refAry         = array_map('intval', explode('_', $refId));
            $post_id        = isset($refAry[0])?$refAry[0]:0;  
            $refById        = isset($refAry[1])?$refAry[1]:0;
            $neoInput['post_id'] = $post_id;
            $neoInput['referred_by_id'] = $refById;
            
            $checkRelation  = $this->job2->checkRel($neoInput);
            $companyCode    = !empty($checkRelation[0][0]->company_code)?$checkRelation[0][0]->company_code:'';
            if($companyCode){
                #check user Separated Status here
                $separatedStatus = $this->checkReferredUserSeparatedStatus($refById, $companyCode);
                if($separatedStatus){
                    $resJobsList      = $this->neoPostRepository->getApplyJobsList($companyCode, $refById,$page,$search_for,$input);
                    foreach ($resJobsList as $result){
                        $record         = array();
                        $postRes        = $result[0];//post details 
                        $postDetails    = $this->referralsGateway->formPostDetailsArray($postRes);
                        $compRes        = $result[1];//company details 
                        $jobsListCount  = !empty($result[2])?$result[2]:0;//count of result set

                        $postId   = $postRes->getID();
                        $compName = $compRes->name;
                        $compLogo = !empty($compRes->logo)?$compRes->logo:'';
                        //form the return jobs list here
                        $record['job_name']         = $postRes->service_name;
                        $record['experience']       = $postDetails['experience_range_name'];
                        $record['vacancies']        = $postRes->no_of_vacancies;
                        $record['location']         = $postRes->service_location;
                        $record['job_description']  = $postRes->job_description;
                        $record['status']           = $postRes->status;
                        $record['post_type']        = $postRes->post_type;
                        $record['ref_code']         = MyEncrypt::encrypt_blowfish($postId.'_'.$refById,Config::get('constants.MINTMESH_ENCCODE'));

                        $returnData[] = $record; 
                    }
                    if($returnData){
                    $data = array("jobs_list" => array_values($returnData),'count'=>$jobsListCount, 'company_name'=>$compName,'company_logo' => $compLogo,'bittly_url' => $bittly);
                    $responseCode   = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.success')));
                    }else{
                        $responseCode   = self::ERROR_RESPONSE_CODE;
                        $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                        $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.no_jobs')));
                    }
                } else {
                    $responseCode   = self::ERROR_RESPONSE_CODE;
                    $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.user_separated')));
                }
            } else {
                    $responseCode   = self::ERROR_RESPONSE_CODE;
                    $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.failure')));
                }    
        } 
        else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.failure')));
        }
        
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function jobPostFromCampaigns($input) {
        $this->loggedinEnterpriseUserDetails   = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $fromId     = $this->neoLoggedInEnterpriseUserDetails->id;
        $emailId    = $this->loggedinEnterpriseUserDetails->emailid;
        $fromName   = $this->loggedinEnterpriseUserDetails->firstname;
        $company = $this->enterpriseRepository->getUserCompanyMap( $this->loggedinEnterpriseUserDetails->id);
        $input['company_name'] = $company->name;
        $input['company_code'] = $company->code;
        if ($this->loggedinEnterpriseUserDetails) {
            $relationAttrs = $neoInput = array();
            $neoInput['service_scope']      = "find_candidate";
            $neoInput['service_from_web']   = 1;
            $neoInput['service_name']       = $input['job_title'];
            $neoInput['looking_for']        = $input['job_title'];
            $neoInput['job_function']       = $input['job_function'];
            $neoInput['service_location']   = $input['location'];
            $neoInput['service_country']    = !empty($input['country_code'])?$input['country_code']:'';
            $neoInput['industry']           = $input['industry'];
            $neoInput['employment_type']    = $input['employment_type'];
            $neoInput['experience_range']   = $input['experience_range'];
            $neoInput['service']            = $input['job_description'];
            $neoInput['position_id']        = !empty($input['position_id']) ? $input['position_id'] : "";
            $neoInput['requistion_id']      = !empty($input['requistion_id']) ? $input['requistion_id'] : "";
            $neoInput['no_of_vacancies']    = $input['vacancy'];
            $neoInput['service_period']     = $input['job_period'];
            $neoInput['service_type']       = $input['job_type'];
            $neoInput['free_service']       = 1;
            $neoInput['service_currency']   = !empty($input['job_currency']) ? $input['job_currency'] : "";
            $neoInput['service_cost']       = !empty($input['job_cost']) ? $input['job_cost'] : "";
            $neoInput['company']            = $input['company_name'];
            $neoInput['job_description']    = $input['job_description'];
            $neoInput['skills']             =  $this->userGateway->getSkillsFromJobTitle($neoInput['service_name'], $neoInput['job_description']);
            $neoInput['status']             = Config::get('constants.POST.STATUSES.PENDING');
            $neoInput['created_by']         = $emailId;
            $neoInput['post_type']          = 'campaign';          
            $relationAttrs['created_at']    = gmdate("Y-m-d H:i:s");
            $relationAttrs['company_name']  = $input['company_name'];
            $relationAttrs['company_code']  = $input['company_code'];
            $createdPost = $this->neoPostRepository->createPostAndUserRelation($fromId,$neoInput, $relationAttrs);
            if (isset($createdPost[0]) && isset($createdPost[0][0])) {
                $postId = $createdPost[0][0]->getID();
            } else {
                $postId = 0;
            }
            #map post and company
            $postCompanyrelationAttrs['created_at']     = gmdate("Y-m-d H:i:s");
            $postCompanyrelationAttrs['user_emailid']   = $this->loggedinEnterpriseUserDetails->emailid;
            if (!empty($input['company_code'])) {
                $createdrelation = $this->neoPostRepository->createPostAndCompanyRelation($postId, $input['company_code'], $postCompanyrelationAttrs);
            }
            #map industry if provided
            if (!empty($input['industry'])) {
                $iResult = $this->referralsRepository->mapIndustryToPost($input['industry'], $postId, Config::get('constants.REFERRALS.ASSIGNED_INDUSTRY'));
            }
            #map job_function if provided
            if (!empty($input['job_function'])) {
                $jfResult = $this->referralsRepository->mapJobFunctionToPost($input['job_function'], $postId, Config::get('constants.REFERRALS.ASSIGNED_JOB_FUNCTION'));
            }
            #map employment type if provided
            if (!empty($input['employment_type'])) {
                $emResult = $this->referralsRepository->mapEmploymentTypeToPost($input['employment_type'], $postId, Config::get('constants.REFERRALS.ASSIGNED_EMPLOYMENT_TYPE'));
            }
            #map experience range if provided
            if (!empty($input['experience_range'])) {
                $eResult = $this->referralsRepository->mapExperienceRangeToPost($input['experience_range'], $postId, Config::get('constants.REFERRALS.ASSIGNED_EXPERIENCE_RANGE'));
            }
            $data = array("post_id" => $postId,"name" => $createdPost[0][0]->service_name,"no_of_vacancies" => $createdPost[0][0]->no_of_vacancies);
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.success')));
        } else {
            $data = array();
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.error')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage,$data);
    }
    
    public function applyJobDetails($input){
        
        $jobsListCount  = 0;
        $compName       = '';
        $resJobsList    = $refAry = $returnData =  $data = array();
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        $referenceId    = isset($input['reference_id'])?$input['reference_id']:'';
        if(!empty($referenceId)){
            $refId          = MyEncrypt::decrypt_blowfish($referenceId, Config::get('constants.MINTMESH_ENCCODE'));
            $refAry         = array_map('intval', explode('_', $refId));
            $post_id        = isset($refAry[0])?$refAry[0]:0;  
            $refById        = isset($refAry[1])?$refAry[1]:0;  
            $neoInput['post_id'] = $post_id;
            $neoInput['referred_by_id'] = $refById;
            
            $checkRelation  = $this->job2->checkRel($neoInput);
            if($checkRelation){
                $companyDetails     = $this->neoPostRepository->getPostCompany($post_id);
                #check user Separated Status here
                $separatedStatus = $this->checkReferredUserSeparatedStatus($refById, $companyDetails->companyCode);
                if($separatedStatus){
                    $postDetails      = $this->neoPostRepository->getPosts($post_id);
                    $postResult = $this->referralsGateway->formPostDetailsArray($postDetails);
                    $postId = $postDetails->getID();
                    $url = $enterpriseUrl . "/email/job-details/share?ref=" . $referenceId.""; 
                    $biltyUrl   = $this->urlShortner($url);
                    $bittly     = $biltyUrl;
                    $record['job_name']         = $postDetails->service_name;
                    $record['experience']       = $postResult['experience_range_name'];
                    $record['vacancies']        = $postDetails->no_of_vacancies;
                    $record['location']         = $postDetails->service_location;
                    $record['job_description']  = $postDetails->job_description;
                    $record['status']           = $postDetails->status;
                    $record['post_type']        = $postDetails->post_type;
                    $record['job_function']     = $postResult['job_function_name'];
                    $record['rewards']          = $this->getPostRewards($postId);
                    $record['ref_code']         = MyEncrypt::encrypt_blowfish($postId.'_'.$refById,Config::get('constants.MINTMESH_ENCCODE'));
                    $returnData[] = $record;
                    $data = array("job_details" => array_values($returnData),'company_name'=>$companyDetails->name,'company_logo'=>$companyDetails->logo,'bittly_url'=>$bittly);
                    $responseCode   = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job_details.success')));
                } else {
                    $responseCode   = self::ERROR_RESPONSE_CODE;
                    $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job.user_separated')));
                }
            } 
            else {
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job_details.failure')));
            }
        } 
        else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job_details.failure')));
        }
        
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function campaignJobsList($input){
        
            $returnData = $data = array();
            $checkRelation  = $this->neoPostRepository->checkCampaignUserRelation($input);
            $reference_id   = !empty($input['reference_id']) ? $input['reference_id'] : 0;
            $userDetails    = $this->neoEnterpriseRepository->getNodeById($reference_id);
            $companyCode    = !empty($checkRelation->company_code)?$checkRelation->company_code:$userCompany;
            #check user Separated Status here
            $separatedStatus = $this->checkReferredUserSeparatedStatus($reference_id, $companyCode);
            if($separatedStatus){
                $page           = !empty($input['page_no']) ? $input['page_no'] : 0;
                $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
                $search_for     = !empty($input['search']) ? $input['search'] : 0;
                if($companyCode){
                    $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
                    $companyLogo = !empty($companyDetails[0]->logo)?$companyDetails[0]->logo:'';
                    $campaignDetails = $this->neoPostRepository->getCampaignById($input['campaign_id']);
                    $campaignSchedule = $this->neoPostRepository->getCampaignSchedule($input['campaign_id']);  
                    $refCode = MyEncrypt::encrypt_blowfish($input['campaign_id'].'_'.$input['reference_id'],Config::get('constants.MINTMESH_ENCCODE'));
                    $url = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode.""; 
                    $biltyUrl = $this->urlShortner($url);
                    $bittly   = $biltyUrl;
                    $startDate = $this->appEncodeDecode->UserTimezone($campaignSchedule[0][0]->gmt_start_date,$input['time_zone']); 
                    $endDate = $this->appEncodeDecode->UserTimezone($campaignSchedule[0][0]->gmt_end_date,$input['time_zone']); 
                    $start_date = \Carbon\Carbon::parse($startDate)->format('dS M Y');
                    $end_date = \Carbon\Carbon::parse($endDate)->format('dS M Y');
                    $start_time = \Carbon\Carbon::parse($startDate)->format('h:i A');
                    $end_time = \Carbon\Carbon::parse($endDate)->format('h:i A');
                    if($campaignDetails->location_type == 'onsite'){
                        $campaignLocation = $campaignDetails->address.','.$campaignDetails->city.','.$campaignDetails->state.','.$campaignDetails->country.','.$campaignDetails->zip_code;
                    }else{
                        $campaignLocation = 'online';
                    }
                    $status = Config::get('constants.REFERRALS.STATUSES.ACTIVE');
                    $campaignJobsList      = $this->neoPostRepository->getCampaignPosts($input['campaign_id'], $page, $search_for, $status);
                    foreach ($campaignJobsList as $result){
                        $record         = array();
                        $postRes        = $result[0];//post details 
                        $postDetails    = $this->referralsGateway->formPostDetailsArray($postRes);
                        $postId   = $postRes->getID();
    //                    //form the return jobs list here
                        $record['reference_id']         = $input['reference_id'];
                        $record['job_name']         = $postRes->service_name;
                        $record['experience']       = $postDetails['experience_range_name'];
                        $record['vacancies']        = $postRes->no_of_vacancies;
                        $record['location']         = $postRes->service_location;
                        $record['job_description']  = $postRes->job_description;
                        $record['status']           = $postRes->status;
                        $record['post_type']        = $postRes->post_type;
                        $record['post_id']          = $postId;
                        $record['ref_code']         = MyEncrypt::encrypt_blowfish($postId.'_'.$record['reference_id'],Config::get('constants.MINTMESH_ENCCODE'));
                          $returnData[] = $record; 
                    }
                    if($returnData){
                    $data = array("bittly_url"=>$bittly,"campaign_jobs_list" => array_values($returnData),"campaign_name" => $campaignDetails->campaign_name,"campaign_type" => $campaignDetails->campaign_type, "campaign_location" => $campaignLocation,"campaign_start_date" => $start_date,"campaign_start_time" => $start_time,"campaign_end_date"=>$end_date,"campaign_end_time" => $end_time,"company_name" =>$companyDetails[0]->name,"company_logo"=>$companyLogo,"user_emailid"=>$userDetails->emailid,"count"=>count($campaignJobsList));
                    $responseCode   = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.success')));
                    }else{
                        $responseCode   = self::ERROR_RESPONSE_CODE;
                        $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                        $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.no_jobs')));
                    }
                } else {
                    $responseCode   = self::ERROR_RESPONSE_CODE;
                    $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.failure')));
                }
            } else {
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_jobs_list.user_separated')));
            }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function getJobsList($input){
        
        $listCount      = $jobsCount = $unreadCount = 0;
        $totalCount     = $readCount = 0;
        $compName       = '';
        $companyCode    = $input['company_code'];
        $timeZone       = !empty($input['time_zone']) ? $input['time_zone'] : 0; 
        $page           = !empty($input['page_no'])?$input['page_no']:0;
        $search         = !empty($input['key'])?$input['key']:0;
        $resJobsList    = $refAry = $returnData =  $data = $companyDetails = $jobsListAry = $jobsCountAry = array();
        $encodeString   = Config::get('constants.MINTMESH_ENCCODE');
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        #get logged in user details here
        $this->user     = $this->getLoggedInEnterpriseUser();
        $userId         = !empty($this->user->id)?$this->user->id:'';
        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($this->user->emailid);
        #log user activity here
        $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=1);
        
        $userEmailId    = $neoUserDetails->emailid;
        $neoUserId      = $neoUserDetails->id;
        $userCountry    = $neoUserDetails->phone_country_name;
        
        if(!empty($companyCode)){
            #get company details here
            $companyData    = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
            $companyData    = isset($companyData[0])?$companyData[0]:0;
            $companyDetails['company_name']  = !empty($companyData->name)?$companyData->name:'';//company name  
            $companyDetails['company_logo']  = !empty($companyData->logo)?$companyData->logo:'';//company logo 
            
            $creditResult    = $this->userRepository->getCreditsCount($userEmailId);
            $referralCashRes = $this->paymentRepository->getPaymentTotalCash($userEmailId,1);
            #user rewards details
            $companyDetails['points']        = !empty($creditResult[0]->credits)?$creditResult[0]->credits:0;  
            $companyDetails['rewards']       = !empty($referralCashRes[0]->total_cash)?$referralCashRes[0]->total_cash:0;    
            $companyDetails['currency_type'] = (strtolower($userCountry) =="india")?2:1;
            #get jobs list with company code
            $jobsListAry    = $this->neoPostRepository->getJobsList($userEmailId, $companyCode, $page, $search);
            if(!empty($jobsListAry->count())){
                #form referral tab counts here
                $jobsCountAry   = $this->getUserJobsCount($userEmailId, $companyCode);
                $jobsCount      = !empty($jobsCountAry['jobs_count'])?$jobsCountAry['jobs_count']:0;
                $unreadCount    = !empty($jobsCountAry['unread_records'])?$jobsCountAry['unread_records']:0;
                $totalCount     = !empty($jobsCountAry['total_records'])?$jobsCountAry['total_records']:0;
                #form the return results here
                foreach ($jobsListAry as $value) {
                    $record   = $rewards = $postRewards = $vacancies = array();
                    $jobsList = !empty($value[0]['post'])?$value[0]['post']:'';
                    $jobRel   = !empty($value[0]['rel'])?$value[0]['rel']:'';
                    #check result set non empty
                    if(!empty($jobsList) && !empty($jobRel)){
                        #separate campaigns and post here
                        if(!empty($jobsList->campaign_name)){
                            $campaignJobs = array();
                            #campaigns list 
                            $campaignId = $jobsList->getID();
                            $created_at = !empty($jobsList->created_at) ? $jobsList->created_at :'';
                            if($created_at){
                               $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
                            }
                            $record['campaign_id']          = $campaignId;
                            $record['post_type']            = 'campaign';
                            $record['campaign_name']        = $jobsList->campaign_name;//'designers campaign';
                            $record['campaign_type']        = $jobsList->campaign_type;//'2-4 Years Exp';
                            $record['campaign_date']        = $created_at;//'2016-11-30 12:56:14';
                            $record['created_by']           = $jobsList->created_by;
                            #get campaign location
                            if($jobsList->location_type == 'online'){
                                $record['campaign_location'] = 'online'; 
                            }else{
                                $location = $jobsList->address.', '.$jobsList->city.', '.$jobsList->state.', '.$jobsList->country.', '.$jobsList->zip_code;
                                $record['campaign_location'] = str_replace(', ,', ',', $location);//remove double commas
                            }
                            #get campaign job title here
                            $postsRes = $this->neoPostRepository->getCampaignActivePosts($campaignId);

                            if(!empty($postsRes->count())){
                                //$jobsCount+= $postsRes->count();
                                foreach($postsRes as $posts){
                                    $postDetails    = $this->referralsGateway->formPostDetailsArray($posts[0]);
                                    $campaignJobs[] = !empty($postDetails['service_name'])?$postDetails['service_name']:'';
                                }
                            }
                            $record['campaign_jobs']  = $campaignJobs;
                            $postRead                       = !empty($jobRel->post_read_status)?$jobRel->post_read_status:0;
                            $record['campaign_read_status'] = $postRead; 

                            $refId      = $campaignId.'_'.$neoUserId;
                            $refCode    = MyEncrypt::encrypt_blowfish($refId, $encodeString);
                            $refUrl     = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode."";
                            $record['social_campaign_share']   = !empty($jobRel->bittly_url)?$jobRel->bittly_url:$refUrl;

                        }  else {
                            #jobs list
                            $postId                     = $jobsList->getID();
                            $created_at = !empty($jobsList->created_at) ? $jobsList->created_at :'';
                            if($created_at){
                               $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
                            }
                            $record['post_id']          = $postId;
                            $record['post_type']        = $jobsList->post_type;//'external';
                            $record['job_name']         = $jobsList->service_name;//'IOS DEVELOPER';
                            $record['job_location']     = $jobsList->service_location;//'bangalore, karnataka, india';
                            $record['job_date']         = $created_at;//'2016-12-28 12:26:42'; 
                            $record['created_by']       = $jobsList->created_by;
                            #get experience range name
                            $jobExperience = $this->referralsRepository->getExperienceRangeNameForPost($postId);
                            $record['job_experience']   = !empty($jobExperience)?$jobExperience:$jobsList->experience_range;
                            #get the post reward details here
                            $postRewards                = $this->referralsGateway->getPostRewards($postId, $userCountry, $isEnterprise=1);
                            $record['rewards']          = $postRewards;
                            $postRead                   = !empty($jobRel->post_read_status)?$jobRel->post_read_status:0;
                            $record['post_read_status'] = $postRead; 
                            
                            $refId      = $postId.'_'.$neoUserId;
                            $refCode    = MyEncrypt::encrypt_blowfish($refId, $encodeString);
                            $refUrl     = $enterpriseUrl . "/email/job-details/share?ref=" . $refCode."";
                            $record['social_job_share']   = !empty($jobRel->bittly_url)?$jobRel->bittly_url:$refUrl;
                        }
                     $returnData[] = $record;
                    }
                    
                }
                $data = array('company_details'=>$companyDetails, "jobs_list" => array_values($returnData),"unread_count" => $unreadCount, "jobs_count" => $jobsCount, "total_count" => $totalCount);
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job_details.success')));
            } 
            else {
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job_details.no_jobs')));
            }
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.apply_job_details.failure')));
        }
        
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
     public function decryptCampaignRef($input){
       $data = array();
       if(!empty($input['ref']) && isset($input['ref'])){
        $mail_parse_ref = isset($input['ref'])?MyEncrypt::decrypt_blowfish($input['ref'],Config::get('constants.MINTMESH_ENCCODE')):0;
        $mail_parse_ref_val = array_map('intval',explode('_',$mail_parse_ref));	
	$campaign_id = isset($mail_parse_ref_val[0])?$mail_parse_ref_val[0]:0;
        $referred_by_id = isset($mail_parse_ref_val[1])?$mail_parse_ref_val[1]:0;
        if($campaign_id != 0 && $referred_by_id != 0){
        $userDetails = $this->neoEnterpriseRepository->getNodeById($referred_by_id);
        $companyDetails     = $this->neoPostRepository->getCampaignCompany($campaign_id);
        $input['campaign_id'] = $campaign_id;
        $campaignStatus = $this->neoPostRepository->getCampaignById($input['campaign_id']);
        $scheduleTimes = $this->neoPostRepository->getCampaignSchedule($input['campaign_id']);
        $companyLogo = !empty($companyDetails->logo)?$companyDetails->logo:'';
        $campaignTitle = 'Here is a campaign at '.$companyDetails->name.' for '.$campaignStatus->campaign_name;
        $campaignDescription = 'Start date: '.$scheduleTimes[0][0]->start_date.' and End date: '.$scheduleTimes[0][0]->end_date;
        $url = Config::get('constants.MM_ENTERPRISE_URL') . "/email/all-campaigns/share?ref=" . $input['ref']."";
        $bitlyUrl = $this->urlShortner($url);
        $bittly    = $bitlyUrl;
        $data = array("campaign_id" => $input['campaign_id'],"reference_id"=>$referred_by_id,"emailid" => $userDetails->emailid,"campaign_status"=>$campaignStatus->status,"company_logo"=>$companyLogo,"company_name"=>$companyDetails->name,"title"=>$campaignTitle,"description"=>$campaignDescription,"url" => $url,"bittly_url"=>$bittly);
        }else{
            $data = array();
        }
        return $data;
       }
    }
    
     public function getJobDetails($input){
       
        $postId     =  $input['post_id'];
        $timeZone   = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        $returnData =  $data = $referrals = $record = $referralsAry = $companyAry = $returnCompany = array();
        $encodeString   = Config::get('constants.MINTMESH_ENCCODE');
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        #get logged in user details here
        $this->user     = $this->getLoggedInEnterpriseUser();
        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($this->user->emailid);
        $userId         = !empty($this->user->id)?$this->user->id:'';
        #log user activity here
        $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=2);
        $userEmailId    = $neoUserDetails->emailid;
        $neoUserId      = $neoUserDetails->id;
        $userCountry    = $neoUserDetails->phone_country_name;
        #get company Details
        $companyAry = $this->neoEnterpriseRepository->connectedCompanyDetails($userEmailId);
        $returnCompany['company_name'] = !empty($companyAry->name)?$companyAry->name:'';
        $returnCompany['company_logo'] = !empty($companyAry->logo)?$companyAry->logo:'';
        $returnCompany['company_code'] = !empty($companyAry->companyCode)?$companyAry->companyCode:0;
        $returnData['company_details'] = $returnCompany;
        #get job Details by post id here
        $postResultAry = $this->referralsRepository->getPostAndMyReferralDetails($postId, $userEmailId);
        
        if(!empty($postResultAry[0]) && !empty($postResultAry[0][0])){
            
            $jobData  = $postDetails = $this->referralsGateway->formPostDetailsArray($postResultAry[0][0]);
            $relData = !empty($postResultAry[0][4])?$postResultAry[0][4]:'';
            $jobDesc = $jobData['job_description'];
            $jobDesc = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($jobDesc))))));
            $created_at = !empty($jobData['created_at']) ? $jobData['created_at'] : '';
            if($created_at){
                $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($jobData['created_at'], $timeZone)));
            }
            
            $record['post_id']          = $jobData['post_id'];
            $record['job_name']         = $jobData['service_name'];
            $record['job_type']         = $jobData['post_type'];
            $record['job_location']     = $jobData['service_location'];
            $record['job_experience']   = $jobData['experience_range_name'];
            $record['job_function']     = $jobData['job_function_name'];
            $record['job_industry']     = $jobData['industry_name'];
            $record['employment_type']  = $jobData['employment_type_name'];
            $record['job_vacancies']    = $jobData['no_of_vacancies'];
            $record['position_id']      = $jobData['position_id'];
            $record['job_description']  = $jobDesc;
            $record['created_by']       = $jobData['created_by'];
            $record['created_at']       = $created_at;
            $record['company_name']     = !empty($jobData['company'])?$jobData['company']:'';
            #social job share link
            $refId      = $postId.'_'.$neoUserId;
            $refCode    = MyEncrypt::encrypt_blowfish($refId, $encodeString);
            $url = $enterpriseUrl . "/email/job-details/share?ref=" . $refCode."";
            $record['social_job_share'] = !empty($relData->bittly_url)?$relData->bittly_url:$url;
            #get the post reward details here
            $postRewards                = $this->referralsGateway->getPostRewards($postId, $userCountry, $isEnterprise=1);
            $record['rewards']          = $postRewards;
            
            $returnData['job_details']  = $record;
            #get post referral details here
            if(!empty($postResultAry[0][1]) && !empty($postResultAry[0][2])){   
             
                foreach ($postResultAry as $value){
                    #get referral properties
                    $nonMMUser  = $referral = array();
                    $relObj     = !empty($value[1])?$value[1]:'';
                    $valObj     = !empty($value[2])?$value[2]:'';
                    $nonMMlabel = !empty($value[3][0])?$value[3][0]:'';
                    $MMlabel    = !empty($value[3][1])?$value[3][1]:'';
                    #variable declaration here
                    $referral["is_mintmesh"]     =  $referral['is_self_referred']  = $isNonMintmesh = 0;
                    $referral['ref_status']      = 'PENDING';
                    $referral['ref_designation'] = '';
                    $defaultName = Lang::get('MINTMESH.user.non_mintmesh_user_name');

                    $referral['ref_id']    = $relObj->getId();
                    $refPhone   = !empty($valObj->phone)?$valObj->phone:'';
                    $refEmail   = !empty($valObj->emailid)?$valObj->emailid:'';
                    $referral['ref_phone'] = $refPhone;
                    $referral['ref_email'] = $refEmail;
                    $referral['ref_dp']    = !empty($valObj->dp_renamed_name)?$valObj->dp_renamed_name:'';
                    $referral['ref_dp']    = !empty($valObj->dp_renamed_name)?$valObj->dp_renamed_name:'';
                    $referral['rel_count'] = !empty($relObj->relation_count)?$relObj->relation_count:'';
                    $referral['from_user'] = !empty($relObj->referred_for)?$relObj->referred_for:'';
                    $referral['referred_by'] = !empty($relObj->referred_by)?$relObj->referred_by:'';
                    #get referral profession name
                    if (!empty($valObj->profession)){
                        $referral['ref_designation'] = $this->userRepository->getProfessionName($valObj->profession);
                    }
                    #get referral status
                    if (!empty($relObj->one_way_status)){
                        #if one way status accepted                        
                            $referral['ref_status'] = $relObj->one_way_status;
                    }
                    #add label if non mintmesh
                    if ($nonMMlabel =='NonMintmesh'){
                        $isNonMintmesh = 1 ;
                        $referral["user_referred_by"]  = 'phone' ;
                        $referral['referred_by_phone'] = 1 ;
                        $nonMMUser = $this->contactsRepository->getImportRelationDetailsByPhone($userEmailId, $refPhone);
                    }else if ($MMlabel =='Mintmesh'){
                        $referral["is_mintmesh"] = 1 ;
                    }else{
                        $isNonMintmesh = 1 ;
                        $referral["user_referred_by"] = 'emailid' ;
                        $nonMMUser = $this->contactsRepository->getImportRelationDetailsByEmail($userEmailId, $refEmail);
                        if(empty($nonMMUser)){
                            #In case of referral made from emailid
                           $nonMMUser =  $this->neoUserRepository->getNodeByEmailId($refEmail);
                        }
                    }
                    #form user name details for non mintmesh contacts
                    if (!empty($nonMMUser) || !empty($isNonMintmesh)){
                        if (!empty($nonMMUser->fullname)){
                            $nonMMUser->fullname = trim($nonMMUser->fullname);
                        }
                        $referral['firstname'] = !empty($nonMMUser->firstname)?$nonMMUser->firstname:$defaultName;
                        $referral['lastname']  = !empty($nonMMUser->lastname)?$nonMMUser->lastname:$defaultName;
                        $referral['fullname']  = !empty($nonMMUser->fullname)?$nonMMUser->fullname:$defaultName;
                    } else {
                        $referral['firstname'] = !empty($valObj->firstname)?$valObj->firstname:$defaultName;
                        $referral['lastname']  = !empty($valObj->lastname)?$valObj->lastname:$defaultName;
                        $referral['fullname']  = !empty($valObj->fullname)?$valObj->fullname:$defaultName;
                    }
                    #check if self referred
                    if ($refEmail == $userEmailId){
                        $referral['is_self_referred'] = 1;
                        $self_referred = 1 ;
                    }
                $referralsAry[] = $referral;
                }
            }
            $returnData['referrals'] = $referralsAry;
            $data = $returnData;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.referrals.success')));
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function companyCampaignsAutoConnectWithContact($relationInput){
        
        $relationAttrs  = array();
        $enterpriseUrl  = Config::get('constants.MM_ENTERPRISE_URL');
        $bucketId       = $relationInput['bucket_id'];
        $campaignId     = $relationInput['campaign_id'];
        $contactEmailid = $relationInput['contact_emailid'];
        
        $relationAttrs['company_code']  = $companyCode = $relationInput['company_code'];
        $relationAttrs['created_by']    = $emailId = $relationInput['user_emailid'];
        $relationAttrs['created_at']    = gmdate("Y-m-d H:i:s");
        
        $checkCampaign = $this->neoPostRepository->checkCampaignContactsRelation($campaignId, $contactEmailid);
        
        if(empty($checkCampaign)){
        
            $refId      = $this->neoPostRepository->getUserNodeIdByEmailId($contactEmailid);
            $refCode    = MyEncrypt::encrypt_blowfish($campaignId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));
            $url = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode.""; 
            $biltyUrl = $this->urlShortner($url);
            $relationAttrs['bittly_url']    = $biltyUrl;

            try {
                    $campaignData = $this->neoPostRepository->createCampaignContactsRelation($relationAttrs, $campaignId, $contactEmailid);
                } catch (\RuntimeException $e) {
                \Log::info("<<<< failed to Auto Connect Campaign Contacts Relation >>>>".print_r($relationAttrs,1));
            }
            if(!empty($campaignData)){
                #get Campaign Job Ids here
                $campaignJobIds = $this->neoPostRepository->getCampaignJobIds($campaignId);
                $notificationMsg    =  Lang::get('MINTMESH.notifications.messages.27');
                if(!empty($campaignJobIds)){
                    #creating included Relation between Post and Contacts 
                    $pushData['bucket_id']          = $bucketId;
                    $pushData['contact_emailid']    = $contactEmailid;
                    $pushData['company_code']       = $companyCode;
                    $pushData['user_emailid']       = $emailId;
                    $pushData['notification_msg']   = $notificationMsg;
                    $pushData['notification_log']   = 0;//for log the notification or not
                     \Log::info("<<<<<<<<<<<<<<<< In Campaign Jobs Auto Connect >>>>>>>>>>>>>".print_r($pushData,1));
                    foreach ($campaignJobIds as $jobs){
                        #creating relation with each job
                        $pushData['postId']  = $postId = !empty($jobs[0])?$jobs[0]:'';
                        $inviteCount = $this->neoPostRepository->getPostInviteCount($postId);
                        $relation    = $this->neoPostRepository->checkPostContactsRelation($postId, $contactEmailid);
                        #check the condition for duplicat job post here
                        if(empty($relation)){
                            //Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                            $this->createPostContactsRelation($pushData);
                            $inviteCount+=1;
                            $this->neoPostRepository->updatePostInviteCount($postId, $inviteCount);
                        }
                    }
                }
            }
        }
  
    }
    
    public function companyPostsAutoConnectWithContactQueue($pushData) {
        #form the details here
        $postId  = !empty($pushData['postId'])?$pushData['postId']:'';
        $contactEmailId = !empty($pushData['contact_emailid'])?$pushData['contact_emailid']:'';
        if(!empty($contactEmailId) && !empty($postId)){
            $inviteCount = $this->neoPostRepository->getPostInviteCount($postId);
            $relation    = $this->neoPostRepository->checkPostContactsRelation($postId, $contactEmailId);
            #check the condition for duplicat job post here
            if(empty($relation)){
                #creating relation with each job
                Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                $inviteCount+=1;
                $this->neoPostRepository->updatePostInviteCount($postId, $inviteCount);
            } 
        }
    }
    
    public function getUserJobsCount($userEmailId, $companyCode) {
        
        $returnAry = array();
        $unreadCount = $totalRecords = $readCount = $readCount = $jobsCount = 0;
        $companyJobsList = $this->neoPostRepository->getCompanyJobsList($userEmailId, $companyCode);
        
        foreach ($companyJobsList as $jobValue) {
            $jobRel   = !empty($jobValue[0]['rel'])?$jobValue[0]['rel']:'';
            $jobsList = !empty($jobValue[0]['post'])?$jobValue[0]['post']:'';
            #check result set non empty
            if(!empty($jobsList) && !empty($jobRel)){
                #get post read status
                $readStatus = !empty($jobRel->post_read_status)?$jobRel->post_read_status:'';
                if($readStatus){
                    if($readStatus == '1'){
                        $readCount += 1;
                    } 
                }
                #separate campaigns and post here
                if(!empty($jobsList->campaign_name)){
                    #campaigns list 
                    $campaignId = $jobsList->getID();
                    #get campaign job title here
                    $postsRes = $this->neoPostRepository->getCampaignActivePosts($campaignId);
                    if(!empty($postsRes->count())){
                        $jobsCount+= $postsRes->count();
                    }
                } else {
                    $jobsCount+=1;
                }
                $totalRecords+=1;
            }
        }
        #calculate unread count here
        $unreadCount = $totalRecords - $readCount;
        $returnAry['jobs_count'] = $jobsCount;
        $returnAry['unread_records'] = $unreadCount;
        $returnAry['total_records'] = $totalRecords;
        return $returnAry;
    }
    
    public function checkReferredUserSeparatedStatus($refById='' ,$companyCode='') {
        
        $return = TRUE;
        #get user details by reference id and post id
        $neoUser     = $this->neoPostRepository->getUserByNeoID($refById);
        $userDetails = !empty($neoUser[0][0]) ? $neoUser[0][0] : '';
        if(isset($neoUser[0]) && isset($neoUser[0][0])){
            #get company details by code
            $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
            $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
            #get Contact Current Status By EmailId
            $currentStatus = $this->enterpriseRepository->checkContactCurrentStatusByEmailId($companyId ,$userDetails->emailid);
            if(empty($currentStatus)){
                $ifAdminUser = $this->enterpriseRepository->checkIfTheUserIsAdmin($companyId ,$userDetails->emailid);
                if($ifAdminUser){
                    #if user is admin skip the contacts status
                    $return = true;
                } else {
                    #if user is separated
                    $return = false;
                } 
            }
        }
        return $return;
    }
    
    public function moveResume($resume='', $tenantId=0, $documentId=0)
    {   
        $renamedFileName = '';
        if (!empty($resume) && !empty($tenantId) && !empty($documentId)){
            #upload the file to s3
            $this->userFileUploader->source = $resume ;
            $this->userFileUploader->destination = public_path().Config::get('constants.UPLOAD_RESUME').$tenantId.'/' ;
            $this->userFileUploader->documentid = $documentId;
            $renamedFileName = $this->userFileUploader->moveResume($resume);
        }
        return $renamedFileName;
    }
    
    public function uploadResume($input) {
        
        $companyCode = !empty($input['company_code']) ? $input['company_code'] : '';
        $resumeFile  = !empty($input['resume']) ? $input['resume'] : '';
        $resumeName  = !empty($input['resume_name']) ? $input['resume_name'] : '';
        $data = $returnAry = array();
        #get logged in user details here
        $this->user = $this->getLoggedInEnterpriseUser();
        $userId     = $this->user->id;
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId   = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        
        if($resumeFile){
            $source = self::SOURCE_FROM_BULK_UPLOAD;
            #insert company resumes in company resumes table
            $insertResult = $this->enterpriseRepository->insertInCompanyResumes($companyId, $resumeName, $userId, $source);
            if($insertResult){
                $documentId = $insertResult->id;
                #file move to s3 folder
                $returnAry['file_name'] = $fileName = $this->moveResume($resumeFile, $companyId, $documentId);
                if($fileName){
                    #form s3 path here
                    $s3Path = Config::get('constants.S3_DOWNLOAD_PATH').$companyId.'/'.$fileName;
                    #updte s3 path in company resumes table
                    $updateResult = $this->enterpriseRepository->updateCompanyResumes($documentId, $s3Path);
                    #return response data
                    $returnAry['document_id'] = $documentId;
                    $returnAry['resume_path'] = $s3Path;
                    $returnAry['resume_name'] = $insertResult->file_original_name;
                    $data = $returnAry;
                    $responseCode   = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.upload_resume.success'))); 
                } else {
                    $responseCode   = self::ERROR_RESPONSE_CODE;
                    $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                    $responseMessage= array('msg' => array(Lang::get('MINTMESH.upload_resume.failure')));
                }
            } else {
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.upload_resume.failure')));
            }
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.upload_resume.file_not_found')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
    public function notParsedResumes($input) {
        
        $returnAry = $data = array();
        $authKey   = !empty($input['authentication_key']) ? $input['authentication_key'] : '' ;
        #AI access API tracking logs
        \Log::info("Not Parsed Resumes API Ping Time : " . date('Y-m-d H:i:s'));
        if($authKey === Config::get('constants.AI_AUTHENTICATION_KEY')){
            #get the not yet parsed resumes list
            $status = self::COMPANY_RESUME_S3_MOVED_STATUS;  
            $result = $this->enterpriseRepository->getNotParsedCompanyResumesByStatus($status);
            foreach ($result as $row) {
                $return = array();
                $return['doc_id']    = $row->id;
                $return['tenant_id'] = $row->company_id;
                $return['file_path'] = $row->file_source;
                $returnAry[] =  $return;
            }
            if($returnAry){
                    $data = $returnAry;
                    $responseCode   = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseMessage= Lang::get('MINTMESH.not_parsed_resumes.success');
            } else {
                $responseCode   = self::ERROR_RESPONSE_CODE;
                $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage= Lang::get('MINTMESH.not_parsed_resumes.failure');
            }
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= Lang::get('MINTMESH.not_parsed_resumes.auth_key_failure');
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }
    
}

?>
