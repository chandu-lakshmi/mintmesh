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
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
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
class PostGateway {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';

    protected $enterpriseRepository, $commonFormatter, $authorizer, $appEncodeDecode, $neoEnterpriseRepository, $userFileUploader;
    protected $createdNeoUser, $postValidator, $referralsRepository, $enterpriseGateway, $userGateway, $contactsRepository, $userEmailManager;

    public function __construct(NeoPostRepository $neoPostRepository, 
                                UserRepository $userRepository, 
                                NeoUserRepository $neoUserRepository, 
                                UserGateway $userGateway, 
                                UserController $userController, 
                                ReferralsGateway $referralsGateway, 
                                EnterpriseGateway $enterpriseGateway, 
                                Authorizer $authorizer, 
                                CommonFormatter $commonFormatter, 
                                APPEncode $appEncodeDecode, 
                                postValidator $postValidator, 
                                NeoEnterpriseRepository $neoEnterpriseRepository, 
                                referralsRepository $referralsRepository,
                                ContactsRepository $contactsRepository,
                                EnterpriseRepository $enterpriseRepository,
                                UserFileUploader $userFileUploader,
                                UserEmailManager $userEmailManager
                                
    ) {
        $this->neoPostRepository = $neoPostRepository;
        $this->userController = $userController;
        $this->userRepository = $userRepository;
        $this->neoEnterpriseRepository = $neoEnterpriseRepository;
        $this->neoUserRepository = $neoUserRepository;
        $this->referralsRepository = $referralsRepository;
        $this->userGateway = $userGateway;
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

    public function postJob($input) {
        
        $objCompany  = new \stdClass();
        $bucket_id   = explode(',', $input['bucket_id']);
        $rewardsAry  = !empty($input['rewards']) ? $input['rewards'] :array();
        $this->loggedinEnterpriseUserDetails    = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $fromId     = $this->neoLoggedInEnterpriseUserDetails->id;
        $emailId    = $this->loggedinEnterpriseUserDetails->emailid;
        $fromName   = $this->loggedinEnterpriseUserDetails->firstname;
        
        if ($this->loggedinEnterpriseUserDetails) {
            $relationAttrs = $neoInput = $excludedList = array();
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
            $neoInput['no_of_vacancies']    = !empty($input['no_of_vacancies']) ? $input['no_of_vacancies'] :0;
            $neoInput['service_period']     = $input['job_period'];
            $neoInput['service_type']       = $input['job_type'];
            $neoInput['free_service']       = !empty($input['free_job']) ? 1 : 0;
            $neoInput['service_currency']   = !empty($input['job_currency']) ? $input['job_currency'] : "";
            $neoInput['service_cost']       = !empty($input['job_cost']) ? $input['job_cost'] : "";
            $neoInput['bucket_id']          = $input['bucket_id'];
            $neoInput['company']            = $input['company_name'];
            $neoInput['job_description']    = $input['job_description'];
            $neoInput['status']             = Config::get('constants.POST.STATUSES.ACTIVE');
            $neoInput['created_by']         = $emailId;
            
            $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($input['company_code']);
            
            $relationAttrs['created_at']    = date("Y-m-d H:i:s");
            $relationAttrs['company_name']  = $input['company_name'];
            $relationAttrs['company_code']  = $input['company_code'];
            $objCompany->fullname   = $relationAttrs['company_name'];
            $createdPost = $this->neoPostRepository->createPostAndUserRelation($fromId,$neoInput, $relationAttrs);
            if (isset($createdPost[0]) && isset($createdPost[0][0])) {
                $postId = $createdPost[0][0]->getID();
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
                    $inviteCount+=$contactList['total_records'][0]->total_count;

                        #creating included Relation between Post and Contacts 
                        $pushData['postId']         = $postId;
                        $pushData['bucket_id']      = $input['bucket_id'];
                        $pushData['company_code']   = $input['company_code'];
                        $pushData['user_emailid']   = $this->loggedinEnterpriseUserDetails->emailid;
                        $pushData['notification_msg'] = $notificationMsg;
                        Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                        
                    foreach ($contactList['Contacts_list'] as $contact => $contacts) {
                        
                        if($contacts->status != 'Separated'){
                            #send push notifications to all the contacts
                            $notifyData   = array();
                            $notifyData['serviceId']            = $postId;
                            $notifyData['loggedinUserDetails']  = $this->loggedinEnterpriseUserDetails;
                            $notifyData['neoLoggedInUserDetails'] = $objCompany;//obj
                            $notifyData['includedList']     = array($contacts->emailid);
                            $notifyData['excludedList']     = $excludedList;
                            $notifyData['service_type']     = '';
                            $notifyData['service_location'] = '';
                            Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $notifyData, 'Notification');

                            #send email notifications to all the contacts
                            $refId = 0;
                            $emailData  = array();
                            $refId = $this->neoPostRepository->getUserNodeIdByEmailId($contacts->emailid);
                            $replyToData                    = '+ref='.MyEncrypt::encrypt_blowfish($postId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));//'+jid='.$postId.'+ref='.$refId;
                            $emailData['company_name']      = $input['company_name'];
                            $emailData['company_code']      = $input['company_code'];
                            $emailData['company_logo']      = '';
                            $emailData['to_firstname']      = $contacts->firstname;
                            $emailData['to_lastname']       = $contacts->lastname;
                            $emailData['to_emailid']        = $contacts->emailid;
                            $emailData['from_userid']       = $fromId;
                            $emailData['from_emailid']      = $emailId;
                            $emailData['from_firstname']    = $fromName;
                            $emailData['ip_address']        = $_SERVER['REMOTE_ADDR'];
                            $emailData['reply_to']          = $replyToName.$replyToData.$replyToHost;
                            //$this->sendJobPostEmailToContacts($emailData);  
                            Queue::push('Mintmesh\Services\Queues\SendJobPostEmailToContactsQueue', $emailData, 'Notification');
                        }
                    }
                }
                $this->neoPostRepository->updatePostInviteCount($postId, $inviteCount);
            }

            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.success')));
        } else {
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.error')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, array());
    }

    public function getLoggedInEnterpriseUser() {
        return $this->userGateway->getLoggedInUser();
    }

    public function checkToCreateEnterprisePostContactsRelationQueue($company_code = '', $user_emailid = '', $bucket_id = '', $postId = '',$notificationMsg='') {
        if (!empty($bucket_id) && !empty($postId)) {
            $postContactRelation = array();
            $postContactRelation['postId'] = $postId;
            $postContactRelation['bucket_id'] = $bucket_id;
            $postContactRelation['company_code'] = $company_code;
            $postContactRelation['user_emailid'] = $user_emailid;
            $postContactRelation['notification_msg'] = $notificationMsg;
            $createResult = $this->createPostContactsRelation($postContactRelation);
        }
    }

    public function createPostContactsRelation($postContactRelation = array()) {
        $postId = $postContactRelation['postId'];
        $company_code = $postContactRelation['company_code'];
        $notificationMsg = $postContactRelation['notification_msg'];
        
        $relationAttrs = array();
        $relationAttrs['company_code'] = $postContactRelation['company_code'];
        $relationAttrs['user_emailid'] = $postContactRelation['user_emailid'];
        $relationAttrs['bucket_id'] = $postContactRelation['bucket_id'];
      
         // Log::info("<<<<<<<<<<<<<<<< In Queue >>>>>>>>>>>>>".print_r($postDetails,1));
        try {
            $postDetails = $this->neoPostRepository->createPostContactsRelation($relationAttrs, $postId, $company_code);
            if(isset($postDetails[0])){
            $notificationLog = array(
                                    'notifications_types_id' => 27,
                                    'from_email' => $postContactRelation['user_emailid'],
                                    'to_email'   => $postDetails[0]['data']->emailid,
                                    'message' => $notificationMsg,
                                    'created_at' => date('Y-m-d H:i:s')
                                ) ;
            $this->userRepository->logNotification($notificationLog);
            }
        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }
    
    public function sendJobPostEmailToContacts ($emailData) {
        
        $dataSet    = array();
        $email_sent = '';
        $fullName   = $emailData['to_firstname'] . ' ' . $emailData['to_lastname'];
        $dataSet['name']                = $fullName;
        $dataSet['email']               = $emailData['to_emailid'];
        $dataSet['fromName']            = $emailData['from_firstname'];
        $dataSet['company_name']        = $emailData['company_name'];
        $dataSet['company_logo']        = '';
        $dataSet['emailbody']           = 'just testing';
        $dataSet['send_company_name']   = $emailData['company_name'];
        $dataSet['reply_to']            = $emailData['reply_to'];//'karthik.jangeti+jid=55+ref=66@gmail.com';
        
        // set email required params
        $this->userEmailManager->templatePath   = Lang::get('MINTMESH.email_template_paths.enterprise_contacts_invitation');
        $this->userEmailManager->emailId        = $emailData['to_emailid'];//target email id
        $this->userEmailManager->dataSet        = $dataSet;
        $this->userEmailManager->subject        = 'test hello';
        $this->userEmailManager->name           = $fullName;
        $email_sent = $this->userEmailManager->sendMail();
        
        //for email logs
        $fromUserId  = $emailData['from_userid'];
        $fromEmailId = $emailData['from_emailid'];
        $companyCode = $emailData['company_code'];
        $ipAddress   = $emailData['ip_address'];
        //log email status
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
        $this->loggedinEnterpriseUserDetails = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $userEmail = $this->neoLoggedInEnterpriseUserDetails->emailid;
        $page = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search_for = !empty($input['search_for']) ? $input['search_for'] : 0;
        $post_by = !empty($input['post_by']) ? $input['post_by'] : 0;
        $checkPermissions = $this->enterpriseRepository->getUserPermissions($this->loggedinEnterpriseUserDetails->group_id,$input);
        $posts = $this->neoPostRepository->jobsList($userEmail, $input['company_code'], $input['request_type'], $page, $search_for,$checkPermissions['view_jobs'],$post_by);
        $totalCount = count($this->neoPostRepository->jobsList($userEmail, $input['company_code'], $input['request_type'], "", $search_for,$checkPermissions['view_jobs'],$post_by));
        if (!empty(count($posts))) {
            $returnPostsData = $returnPosts = array();
            
            foreach ($posts as $post) {
                $postDetails = $this->referralsGateway->formPostDetailsArray($post[0]);
                $invitedCount   = !empty($postDetails['invited_count']) ? $postDetails['invited_count'] : 0;
                $referralCount  = !empty($postDetails['total_referral_count']) ? $postDetails['total_referral_count'] : 0;
                $acceptedCount  = !empty($postDetails['referral_accepted_count']) ? $postDetails['referral_accepted_count'] : 0;
                $declinedCount  = !empty($postDetails['referral_declined_count']) ? $postDetails['referral_declined_count'] : 0;
                $hiredCount     = !empty($postDetails['referral_hired_count']) ? $postDetails['referral_hired_count'] : 0;
                $pendingCount   = $referralCount - ($acceptedCount + $declinedCount);
                
                $returnPosts['id']          = $postDetails['post_id'];
                $returnPosts['location']    = $postDetails['service_location'];
                $returnPosts['job_title']   = $postDetails['service_name'];
                $returnPosts['free_service']    = $postDetails['free_service'];
                $returnPosts['status']          = $postDetails['status'];
                $returnPosts['no_of_vacancies']  = !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:0;
                $returnPosts['experience']['id'] = $postDetails['experience_range'];
                $returnPosts['employment']['id'] = $postDetails['employment_type'];
                $returnPosts['experience']['name'] = isset($postDetails['experience_range_name']) ? $postDetails['experience_range_name'] : "";
                $returnPosts['employment']['name'] = isset($postDetails['employment_type_name']) ? $postDetails['employment_type_name'] : "";
                if ($returnPosts['free_service'] == 0) {
                    $returnPosts['service_cost'] = $postDetails['service_cost'];
                    $returnPosts['service_currency'] = $postDetails['service_currency'];
                }
                $returnPosts['created_at']      = $postDetails['created_at'];
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
        $checkPermissions = $this->enterpriseRepository->getUserPermissions($this->loggedinEnterpriseUserDetails->group_id,$input);
        if (!empty(count($posts))) {
            $returnPosts = array();
            $returnPostsData = array();
            $buckets = array();
            foreach ($posts as $post) {
                $postDetails    = $this->referralsGateway->formPostDetailsArray($post[0]);
                $companyDetails = $this->referralsGateway->formPostDetailsArray($post[1]);
                $returnPosts['id']  = $postDetails['post_id'];
               $bucket_id = explode(',', $postDetails['bucket_id']);
               foreach ($bucket_id as $bucket){
                $bucket          =    $this->neoPostRepository->bucket($bucket);
                $buckets[]       =     $bucket;
                }
                $invitedCount   = !empty($postDetails['invited_count']) ? $postDetails['invited_count'] : 0;
                $referralCount  = !empty($postDetails['total_referral_count']) ? $postDetails['total_referral_count'] : 0;
                $acceptedCount  = !empty($postDetails['referral_accepted_count']) ? $postDetails['referral_accepted_count'] : 0;
                $declinedCount  = !empty($postDetails['referral_declined_count']) ? $postDetails['referral_declined_count'] : 0;
                $hiredCount     = !empty($postDetails['referral_hired_count']) ? $postDetails['referral_hired_count'] : 0;
                $pendingCount   = $referralCount - ($acceptedCount + $declinedCount);

                $returnPosts['location']    = $postDetails['service_location'];
                $returnPosts['job_title']   = $postDetails['service_name'];
                $returnPosts['created_at']  = $postDetails['created_at'];
                $returnPosts['position_id'] = $postDetails['position_id'];
                $returnPosts['status']      = $postDetails['status'];
                if($postDetails['created_by'] == $this->loggedinEnterpriseUserDetails->emailid || $checkPermissions['close_jobs'] == '1'){
                    $returnPosts['is_close']     = '1';
                }else{
                    $returnPosts['is_close']     = '0';
                }
                $returnPosts['hired_count']     = $hiredCount;
                $returnPosts['invited_count']   = $invitedCount;
                $returnPosts['referral_count']  = $referralCount;
                $returnPosts['accepted_count']  = $acceptedCount;
                $returnPosts['pending_count']   = max($pendingCount,0);
                $returnPosts['bucket_name']     = $buckets;
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
                $returnPosts['company_description'] = isset($companyDetails['description']) ? $companyDetails['description'] : "";
                $returnPosts['company_logo'] = isset($companyDetails['logo']) ? $companyDetails['logo'] : "";
                $returnPosts['rewards']      = $this->getPostRewards($postDetails['post_id']);
                $returnPostsData[] = $returnPosts;
            }
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
                    
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($userDetails['emailid']);

                    if(empty($userDetails['firstname'])){
                          $nonMMUser    = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                          $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                    }else{
                          $referralName = $neoUserDetails['fullname'];
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
                
                $neoReferrerDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);

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
                $returnReferralDetails['created_at']            = \Carbon\Carbon::createFromTimeStamp(strtotime($postRelDetails['created_at']))->diffForHumans();
                $returnReferralDetails['updated_at']            = !empty($postRelDetails['p1_updated_at'])?date("d M Y H:i", strtotime($postRelDetails['p1_updated_at'])):'';
                $returnReferralDetails['referred_by']           = $neoReferrerDetails['emailid'];
                $returnReferralDetails['resume_path']           = !empty($postRelDetails['resume_path'])?$postRelDetails['resume_path']:$cvPath;
                $returnReferralDetails['resume_original_name']  = $postRelDetails['resume_original_name'];
                $returnReferralDetails['relation_count']        = $postRelDetails['relation_count'];
                $returnReferralDetails['referred_by_name']      = $neoReferrerDetails['fullname'];
                $returnReferralDetails['referred_by_dp_image']  = $neoReferrerDetails['dp_renamed_name'];
                $returnReferralDetails['name']                  = !empty($referralName)?$referralName:'The contact';
                // awaiting Action Details
                if($status == 'ACCEPTED'){ 
                    if(!empty($postRelDetails['awaiting_action_by'])){
                        $awaitingActionUser = $this->neoUserRepository->getNodeByEmailId($postRelDetails['awaiting_action_by']);
                        $returnReferralDetails['awaiting_action_by'] = $awaitingActionUser['fullname'];
                    }  else {
                        $returnReferralDetails['awaiting_action_by'] = '';
                    }
                    $returnReferralDetails['awaiting_action_updated_at'] = !empty($postRelDetails['awaiting_action_updated_at'])?date("d-m-Y", strtotime($postRelDetails['awaiting_action_updated_at'])):'';
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
        $result = $this->neoPostRepository->statusDetails($postId, $referredBy, $referral, $status, $postWay, $relationCount, $phoneNumberReferred);

        if (count($result)) {

            if ($status != 'DECLINED') {
                //if(!empty($result[0][0]) && !empty($result[0][0]->free_service)){//free service

                $relationId = !empty($result[0][1]) ? $result[0][1]->getID() : 0;
                $is_self_referred = ($referral == $referredBy) ? 1 : 0;

                $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus($relationId, '', $is_self_referred, $userEmail);

                //send notification to the person who referred to the post
                $sqlUser = $this->userRepository->getUserByEmail($referredBy);
                $referred_by_details = $this->userRepository->getUserById($sqlUser->id);
                $referred_by_neo_user = $this->neoUserRepository->getNodeByEmailId($referredBy);
                //add credits
                $this->userRepository->logLevel(3, $referredBy, $userEmail, $referral, Config::get('constants.POINTS.SEEK_REFERRAL'));

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
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, array("countDetails" => $returnReferralDetails));
    }
    
    public function awaitingAction($input) {

        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId   = $this->loggedinUserDetails->emailid;
        $userFirstName = $this->loggedinUserDetails->firstname;
        
        $data = $response = array();
        $postId = $input['post_id'];
        $status = $input['awaiting_action_status'];
        $referral = $input['from_user'];
        $referredBy = $input['referred_by'];
        $relationCount = $input['relation_count'];
        $nonMintmesh = !empty($input['referred_by_phone']) ? 1 : 0;
        
        $result = $this->neoPostRepository->updateAwaitingActionDetails($userEmailId, $postId, $referredBy, $referral, $status, $relationCount, $nonMintmesh);
        
        if (!empty($result)) { 
            $postDetails     = !empty($result[0][0])?$result[0][0]:'';
            $postDetails     = $this->referralsGateway->formPostDetailsArray($postDetails);
            $relationDetails = !empty($result[0][1])?$result[0][1]:'';
            $relationDetails = $this->referralsGateway->formPostDetailsArray($relationDetails);

            $response['hired_count']                =  !empty($postDetails['referral_hired_count'])?$postDetails['referral_hired_count']:0;
            $response['awaiting_action_status']     =  !empty($relationDetails['awaiting_action_status'])?$relationDetails['awaiting_action_status']:'ACCEPTED';
            $response['awaiting_action_by']         =  !empty($relationDetails['awaiting_action_by'])?$userFirstName:'';
            $response['awaiting_action_updated_at'] =  !empty($relationDetails['awaiting_action_updated_at'])?$relationDetails['awaiting_action_updated_at']:date("d-m-Y");
            $response['awaiting_action_updated_at'] =  date("d-m-Y", strtotime($response['awaiting_action_updated_at']));

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
        $postReferrals  = $dscRewards = $refRewards = $data = array();
        $aryRefRewards  = $aryDscRewards = $postDetails = array();
        $currencyType   = $totalCash = $totalPoints = 0;
        $postId         = !empty($input['post_id'])?$input['post_id']:0;
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

                        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($userDetails['emailid']);
                        if(empty($userDetails['firstname'])){
                              $nonMMUser    = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                              $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                        }else{
                              $referralName = $neoUserDetails['fullname'];
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
                    //get the referral details with emailid
                    $neoReferrerDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                    //form the referral designation here
                    $returnReferralDetails['ref_designation']       = '';
                    if ($neoReferrerDetails['completed_experience'] == '1') {
                        $title = $this->neoPostRepository->getJobTitle($neoReferrerDetails['emailid']);
                        foreach ($title as $t) {
                            $jobTitle = $this->referralsGateway->formPostDetailsArray($t[0]);
                            $returnReferralDetails['ref_designation'] = $jobTitle['name'];
                        }
                    } 

                    $returnReferralDetails['created_at']            = \Carbon\Carbon::createFromTimeStamp(strtotime($postRelDetails['created_at']))->diffForHumans();
                    $returnReferralDetails['referred_by']           = $neoReferrerDetails['emailid'];
                    $returnReferralDetails['referred_by_name']      = $neoReferrerDetails['fullname'];
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

        $inviteCount = 0;
        $notificationMsg = Lang::get('MINTMESH.notifications.messages.27');
        foreach ($bucketIds as $key => $value) {

            $input['bucket_id'] = $value;

            $neoCompanyBucketContacts = $this->enterpriseGateway->enterpriseContactsList($input);
            $contactList = $neoCompanyBucketContacts['data'];
            $inviteCount+=$contactList['total_records'][0]->total_count;

            foreach ($contactList['Contacts_list'] as $contact => $contacts) {

                $pushData['postId']         = $postId;
                $pushData['bucket_id']      = $input['bucket_id'];
                $pushData['company_code']   = $input['company_code'];
                $pushData['user_emailid']   = $loggedInUser->emailid;
                $pushData['notification_msg'] = $notificationMsg;
                //$this->checkToCreateEnterprisePostContactsRelationQueue($pushData['company_code'], $pushData['user_emailid'], $pushData['bucket_id'],$pushData['postId'],$pushData['notification_msg']) ;
                Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');

                #send push notifications to all the contacts
                $notifyData   = $excludedList = array();
                $notifyData['serviceId']            = $postId;
                $notifyData['loggedinUserDetails']  = $loggedInUser;
                $notifyData['neoLoggedInUserDetails'] = $objCompany;//obj
                $notifyData['includedList']     = array($contacts->emailid);
                $notifyData['excludedList']     = $excludedList;
                $notifyData['service_type']     = '';
                $notifyData['service_location'] = '';
                Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $notifyData, 'Notification');    
            }
        }
        $this->neoPostRepository->updatePostInviteCount($postId, $inviteCount);       
    }
    
    public function createRelationBwCampaignAndContacts($campaignId='', $input=array(), $bucketIds=array(), $loggedInUser=''){

        foreach ($bucketIds as $key => $value) {
            $input['bucket_id'] = $value;
            $neoCompanyBucketContacts = $this->enterpriseGateway->enterpriseContactsList($input);
            $contactList = $neoCompanyBucketContacts['data'];

            foreach ($contactList['Contacts_list'] as $contact => $contacts) {

                $pushData['campaign_id']        = $campaignId;
                $pushData['bucket_id']          = $input['bucket_id'];
                $pushData['contact_emailid']    = $contacts->emailid;
                $pushData['company_code']       = $input['company_code'];
                $pushData['user_emailid']       = $loggedInUser->emailid;
                $this->createCampaignContactsRelation($pushData);
                Queue::push('Mintmesh\Services\Queues\CreateCampaignContactsRelationQueue', $pushData, 'default');
            }
        }
    }
    
    public function createCampaignContactsRelation($relationInput = array()) {
        
        
        $relationAttrs  = array();
        $campaignId     = $relationInput['campaign_id'];
        $contactEmailid = $relationInput['contact_emailid'];
        $relationAttrs['bucket_id']     = $relationInput['bucket_id'];
        $relationAttrs['company_code']  = $relationInput['company_code'];
        $relationAttrs['created_by']    = $relationInput['user_emailid'];
        $relationAttrs['created_at']    = date("Y-m-d H:i:s");
        
         //\Log::info("<<<<<<<<<<<<<<<< In Queue >>>>>>>>>>>>>".print_r($neoInput,1));
        try {
            $this->neoPostRepository->createCampaignContactsRelation($relationAttrs, $campaignId, $contactEmailid);
        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }

    public function addCampaign($input) {
        //variable declaration here
        $campaignId     = '';
        $objCompany     = new \stdClass();
        $postCampaign   = $postContacts = $campaignContacts = $campaign = $createdCampaign = $campSchedule = $data = array();
        $loggedInUser   = $this->referralsGateway->getLoggedInUser();
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
//        $campaign['total_vacancies']    = !empty($input['total_vacancies'])?$input['total_vacancies']:'';// no of vacancies
        $campaign['location_type']      = !empty($input['location_type'])?$input['location_type']:'';//online | onsite 
        
        if(strtolower($campaign['location_type']) == 'onsite'){
            $campaign['address']        = !empty($input['address'])?$input['address']:'';
            $campaign['city']           = !empty($input['city'])?$input['city']:'';
            $campaign['zip_code']       = !empty($input['zip_code'])?$input['zip_code']:'';
            $campaign['state']  = !empty($input['state'])?$input['state']:'';
            $campaign['country']  = !empty($input['country'])?$input['country']:'';
        }else{
            //$campaign['campaign_url']   = $input['campaign_url'];//if online
        }    
        $campSchedule = !empty($input['schedule'])?$input['schedule']:array();
         
        $campaign['company_code'] = $companyCode;
        $campaign['status']       = Config::get('constants.POST.STATUSES.ACTIVE');
         //upload the file
         if (isset($input['ceos_pitch_file']) && !empty($input['ceos_pitch_file'])) {
                //upload the file
                $this->userFileUploader->source =  $input['ceos_pitch_file'];
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET_CAMPAIGN_IMAGES');
                $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['ceos_pitch_file']);
                $campaign['ceos_file'] = $renamedFileName;
                $campaign['ceos_name'] = $input['ceos_pitch_name'];
            }
            if (isset($input['employees_pitch_file']) && !empty($input['employees_pitch_file'])) {
                //upload the file
                $this->userFileUploader->source =  $input['employees_pitch_file'];
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET_CAMPAIGN_IMAGES');
                $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['employees_pitch_file']);
                $campaign['emp_file'] = $renamedFileName;
                $campaign['emp_name'] = $input['employees_pitch_name'];
            }
            if(isset($input['ceos_pitch_s3']) && !empty($input['ceos_pitch_s3'])){
                $campaign['ceos_file'] = $input['ceos_pitch_s3'];
            }
            if(isset($input['employees_pitch_s3']) && !empty($input['employees_pitch_s3'])){
                $campaign['emp_name'] = $input['employees_pitch_s3'];
            }
        if($requestType == 'edit'){
            $campaign['ceos_name'] = !empty($campaign['ceos_name'])?$campaign['ceos_name']:'';
            $campaign['emp_name'] = !empty($campaign['emp_name'])?$campaign['emp_name']:'';
      
            //updating Campaign details here
           $editedCampaign = $this->neoPostRepository->editCampaignAndCompanyRelation($companyCode, $campaignId, $campaign, $userEmailId);
        }  else {
            //creating Campaign And Company Relation here
            $createdCampaign = $this->neoPostRepository->createCampaignAndCompanyRelation($companyCode, $campaign, $userEmailId);
            if (isset($createdCampaign[0]) && isset($createdCampaign[0][0])) {
                $campaignId = $createdCampaign[0][0]->getID(); 
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
                        $scheduleAttrs['gmt_start_date'] = $this->appEncodeDecode->UserTimezoneGmt($gmtstart_date,$input['time_zone']);    
                        $scheduleAttrs['end_date']   = $schedule['end_on_date'];
                        $scheduleAttrs['end_time']   = $schedule['end_on_time'];
                        $gmtend_date = $schedule['end_on_date']." " .$schedule['end_on_time'];
                        $scheduleAttrs['gmt_end_date'] = $this->appEncodeDecode->UserTimezoneGmt($gmtend_date,$input['time_zone']); 
                        $scheduleAttrs['company_code'] = $companyCode;
                        if(!empty($scheduleId)){
                            //update Campaign Schedule here
                            $this->neoPostRepository->updateCampaignScheduleRelation($scheduleId, $campaignId, $scheduleAttrs, $userEmailId);
                        }  else {
                            //create Campaign Schedule here
                            $this->neoPostRepository->createCampaignScheduleRelation($campaignId, $scheduleAttrs, $userEmailId);
                        }
                    }
            }
            //checking if user selected at least one job or not
            if(!empty($campPostIds)){
                $postCampaign['company_code'] = $companyCode;
                $postCampaign['created_at']   = date("Y-m-d H:i:s");
                $postCampaign['created_by']   = $userEmailId;
                foreach ($campPostIds as $key => $postId) {
                    $postCampaignRes = '';
                    //creating Campaign And Post Relation here
                    $postCampaignRes = $this->neoPostRepository->createPostAndCampaignRelation($postId, $campaignId, $postCampaign);
                    //creating Post and contacts Relation here
                    if(!empty($postCampaignRes) && !empty($campBucketIds)){
                        $postContacts['company_id']   = $companyId;    
                        $postContacts['company_code'] = $companyCode;
                        $this->createRelationBwPostAndContacts($postId, $postContacts, $campBucketIds, $loggedInUser, $objCompany);
                    }
                }
            }
            //checking if user selected at least one bucket or not
            if(!empty($campBucketIds)){
                $campaignContacts['company_code'] = $companyCode;
                $campaignContacts['company_id']   = $companyId;
                //creating Campaign And contacts Relation here
                $this->createRelationBwCampaignAndContacts($campaignId, $campaignContacts, $campBucketIds, $loggedInUser);
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
        $this->loggedinUserDetails      = $this->referralsGateway->getLoggedInUser();
        $this->neoLoggedInUserDetails   = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $company = $this->enterpriseRepository->getUserCompanyMap( $this->loggedinUserDetails->id);
        
        $input['company_code']  = $company->code;
        $checkPermissions       = $this->enterpriseRepository->getUserPermissions($this->loggedinUserDetails->group_id, $input);
        $permission             = !empty($checkPermissions['run_campaign'])?$checkPermissions['run_campaign']:0;
        $campaigns              = $this->neoPostRepository->campaignsList($this->neoLoggedInUserDetails->emailid, $input, $page, $permission);
        
        if(!empty(count($campaigns))){
            $totalCount         = $campaigns->count();
            foreach($campaigns as $k=>$v){
                $campaign['id'] = $v[0]->getID();
                $postsRes       = $this->neoPostRepository->getCampaignPosts($campaign['id']);
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
                    $gmt_start_on_date              = $this->appEncodeDecode->UserTimezone($gmtstart_date,$input['time_zone']);
                    $campaign['gmt_start_on_date']  = !empty($gmt_start_on_date)?$gmt_start_on_date:'';
                    $gmtend_date                    = $scheduleTimes->end_date." " .$scheduleTimes->end_time;
                    $gmt_end_on_date                = $this->appEncodeDecode->UserTimezone($gmtend_date,$input['time_zone']);
                    $campaign['gmt_end_on_date']    = !empty($gmt_end_on_date)?$gmt_end_on_date:'';
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
        
        $data = $campSchedule = $scheduleRes = $postAry = $bucketAry = array();
        $loggedInUser   = $this->referralsGateway->getLoggedInUser();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        $campaignId     = !empty($input['campaign_id'])?$input['campaign_id']:'';
        $campRes        = $this->neoPostRepository->getCampaignById($campaignId);
        if($campRes){
            //form response details here
            $returnData['campaign_name']    = $campRes->campaign_name;
            $returnData['campaign_type']    = $campRes->campaign_type;
            $returnData['total_vacancies']  = $campRes->total_vacancies;
            $returnData['location_type']    = $campRes->location_type;
            if(!empty($campRes->ceos_file)){
            $filesAry[0] = $campRes->ceos_file;
            }
            if(!empty($campRes->emp_file)){
            $filesAry[1]  = $campRes->emp_file;
            }
            if(!empty($campRes->ceos_file) || !empty($campRes->emp_file)){
            $returnData['files'] = $filesAry;
            }
            //location Details here
            if(strtolower($returnData['location_type']) == 'onsite'){
                $location = array();
                $location['address']          = $campRes->address;
                $location['state']            = $campRes->state;
                $location['country']            = $campRes->country;
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
            //get Campaign Posts here
            $postsRes   = $this->neoPostRepository->getCampaignPosts($campaignId);
            $vacancies = 0;
            foreach($postsRes as $posts){
                $post = array();
                $postDetails = $this->referralsGateway->formPostDetailsArray($posts[0]);
                $post['post_id'] = $postDetails['post_id'];
                $postAry[] = $post['post_id'];
                $vacancies += !empty($postDetails['no_of_vacancies'])?$postDetails['no_of_vacancies']:'';;
            }
            $returnData['total_vacancies'] = $vacancies;
            //get Campaign Buckets here
            $bucketsRes   = $this->neoPostRepository->getCampaignBuckets($campaignId);
            foreach($bucketsRes as $buckets){
                $bucket = '';
                $bucket = (int)$buckets[0];
                $bucketAry[] = $bucket;
            }
            //form response details here
            $returnData['schedule']     = $campSchedule;
            $returnData['job_ids']      = $postAry;
            $returnData['bucket_ids']   = $bucketAry;
        
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
    
    public function ptest() {
        
        $dir = __DIR__;
        $dir_array  = explode('/', $dir, -2);
	$dir_str    = implode('/',$dir_array);
        $directory  = $dir_str.'/uploads/s3_resumes/';
        $result     = $this->neoPostRepository->ptest();
        if(!empty($result)){
            foreach($result as $value){
               $relation     = $value[0];//relation details
               $status       = 1;
               $relationId   = $relation->getID();
               #update status here
               $updateStatus = $this->neoPostRepository->updateResumeParsedStatus($relationId, $status);
               #download the file from s3 bucket
               $filepath         =  !empty($relation->resume_path)?$relation->resume_path:'';
               $this->Parser     =  new ParserManager;
               $parsedRes        =  $this->Parser->processParsing($filepath);
               #save the parsed json file path here
               $updateParsedJson =  $this->neoPostRepository->updateResumeParsedJsonPath($relationId, $parsedRes);
            }
        }
        return TRUE;
    }
    
    public function parseFile($target_file,$imageFileType){
        
			if ($imageFileType == 'pdf') {
				$pdfObj = new PdfParser();
				
				$resumeText = $pdfObj->parseFile($target_file);
				// $resumeText = $pdfObj->getText();
			} else {
				$docObj = new DocxConversion($target_file);
				$resumeText = $docObj->convertToText();
			}
              $records = APPEncode::getParserValues($resumeText);
              return $records;
    }
    
    
    public function getCompanyAllReferrals($input) {
        
        $data = $ReferralsRes  = $returnData = array();
        $loggedInUser   = $this->referralsGateway->getLoggedInUser();
        $emailId        = $loggedInUser->id;
        $page           = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search         = !empty($input['search']) ? $input['search']:'';
        $company        = $this->enterpriseRepository->getUserCompanyMap($loggedInUser->id);
        $companyCode    = !empty($company->code)?$company->code:'';
        $ReferralsRes   = $this->neoPostRepository->getCompanyAllReferrals($emailId, $companyCode, $search, $page);
        $totalReferralsRes   = $this->neoPostRepository->getCompanyAllReferrals($emailId, $companyCode, '', '');
        $totalRec       = !empty($ReferralsRes)?$ReferralsRes->count():'';
        $totalRecords       = !empty($totalReferralsRes)?$totalReferralsRes->count():'';
        if($ReferralsRes){
            foreach($ReferralsRes as $result){
                $record     = array();
                $post       = $result[0];//post details
                $user       = $result[1];//user details
                $relation   = $result[2];//relation details
                #form the referrals here
                $record['id']               = $result[2]->getID();
                $record['service_name']     = $post->service_name;
                $record['one_way_status']   = $relation->one_way_status;
                $record['resume_path']      = $relation->resume_path;
                $record['resume_name']      = $relation->resume_original_name;
                $record['created_at']       = $relation->created_at;
                $record['awt_status']       = $relation->awaiting_action_status;
                #get the user details here
                $referralName = '';
                $nonMMUser    = new \stdClass();
                if(!empty($user->emailid) && !empty($relation->referred_by)){
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($user->emailid);
                    if(empty($neoUserDetails['firstname'])){
                        $nonMMUser    = $this->contactsRepository->getImportRelationDetailsByEmail($relation->referred_by, $user->emailid);
                        $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                    }else{
                        $referralName = $neoUserDetails['fullname'];
                    } 
                }  else {
                    if(empty($user->firstname) && !empty($relation->referred_by)){
                        $nonMMUser     = $this->contactsRepository->getImportRelationDetailsByPhone($relation->referred_by, $user->phone);
                        $referralName  = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                    }
                }
                $nodeByEmail = $this->neoUserRepository->getNodeByEmailId($relation->referred_by);
                $record['fullname']    = !empty($referralName)?$referralName:'The contact';
                $record['referred_by'] = $nodeByEmail['fullname'];
                $returnData[] = $record;
            }
//            $returnData[] = array('total_records'=>$totalRec); 
             $data = array("referrals" => array_values($returnData),'count'=>$totalRec,'total_records'=>$totalRecords);
//            $data = array($returnData,'total_records'=>$totalRec);
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
    
    
}

?>
