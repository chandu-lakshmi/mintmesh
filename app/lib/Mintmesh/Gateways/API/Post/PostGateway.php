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

class PostGateway {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';

    protected $enterpriseRepository, $commonFormatter, $authorizer, $appEncodeDecode, $neoEnterpriseRepository;
    protected $createdNeoUser, $postValidator, $referralsRepository, $enterpriseGateway, $userGateway, $contactsRepository;

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
                                EnterpriseRepository $enterpriseRepository
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

    public function postJob($input) {
        
        $objCompany = new \stdClass();
        $bucket_id  = explode(',', $input['bucket_id']);
        $this->loggedinEnterpriseUserDetails    = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $fromId = $this->neoLoggedInEnterpriseUserDetails->id;
        
        if ($this->loggedinEnterpriseUserDetails) {
            $relationAttrs = $neoInput = $excludedList = array();
            $neoInput['service_scope']      = "find_candidate";
            $neoInput['service_from_web']   = 1;
            $neoInput['service_name']       = $input['job_title'];
            $neoInput['looking_for']        = $input['job_title'];
            $neoInput['job_function']       = $input['job_function'];
            $neoInput['service_location']   = $input['location'];
            $neoInput['industry']           = $input['industry'];
            $neoInput['employment_type']    = $input['employment_type'];
            $neoInput['experience_range']   = $input['experience_range'];
            $neoInput['service']            = $input['job_description'];
            $neoInput['position_id']        = !empty($input['position_id']) ? $input['position_id'] : "";
            $neoInput['requistion_id']      = !empty($input['requistion_id']) ? $input['requistion_id'] : "";
            $neoInput['service_period']     = $input['job_period'];
            $neoInput['service_type']       = $input['job_type'];
            $neoInput['free_service']       = !empty($input['free_job']) ? 1 : 0;
            $neoInput['service_currency']   = !empty($input['job_currency']) ? $input['job_currency'] : "";
            $neoInput['service_cost']       = !empty($input['job_cost']) ? $input['job_cost'] : "";
            $neoInput['bucket_id']          = $input['bucket_id'];
            $neoInput['company']            = $input['company_name'];
            $neoInput['job_description']    = $input['job_description'];
            $neoInput['status']             = Config::get('constants.POST.STATUSES.ACTIVE');
            $neoInput['created_by']         = $this->loggedinEnterpriseUserDetails->emailid;
            
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
                foreach ($bucket_id as $key => $value) {
                    
                    $input['bucket_id'] = $value;
                    $neoCompanyBucketContacts = $this->enterpriseGateway->enterpriseContactsList($input);
                    $contactList = $neoCompanyBucketContacts['data'];
                    $inviteCount+=$contactList['total_records'][0]->total_count;

                    foreach ($contactList['Contacts_list'] as $contact => $contacts) {
                       
                        $pushData['postId']         = $postId;
                        $pushData['bucket_id']      = $input['bucket_id'];
                        $pushData['company_code']   = $input['company_code'];
                        $pushData['user_emailid']   = $this->loggedinEnterpriseUserDetails->emailid;
                        $pushData['notification_msg'] = $notificationMsg;
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
                        Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $notifyData, 'Notification');    
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
      

        // \Log::info("<<<<<<<<<<<<<<<< In Queue >>>>>>>>>>>>>".print_r($contactNode,1));
        try {
            $postDetails = $this->neoPostRepository->createPostContactsRelation($relationAttrs, $postId, $company_code);
            $notificationLog = array(
                                    'notifications_types_id' => 27,
                                    'from_email' => $postContactRelation['user_emailid'],
                                    'to_email'   => $postDetails[0]['data']->emailid,
                                    'message' => $notificationMsg,
                                    'created_at' => date('Y-m-d H:i:s')
                                ) ;
            $this->userRepository->logNotification($notificationLog);
        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }

    public function jobsList($input) {

        $totalCount = 0;
        $this->loggedinEnterpriseUserDetails = $this->getLoggedInEnterpriseUser();
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinEnterpriseUserDetails->emailid);
        $userEmail = $this->neoLoggedInEnterpriseUserDetails->emailid;
        $page = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search_for = !empty($input['search_for']) ? $input['search_for'] : 0;
        $posts = $this->neoPostRepository->jobsList($userEmail, $input['company_code'], $input['request_type'], $page, $search_for);
        $totalCount = count($this->neoPostRepository->jobsList($userEmail, $input['company_code'], $input['request_type'], "", $search_for));
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
                
                $returnPosts['id'] = $postDetails['post_id'];
                $returnPosts['location'] = $postDetails['service_location'];
                $returnPosts['job_title'] = $postDetails['service_name'];
                $returnPosts['free_service'] = $postDetails['free_service'];
                $returnPosts['status'] = $postDetails['status'];
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

        $posts = $this->neoPostRepository->jobsDetails($input['id'], $input['company_code']);
        if (!empty(count($posts))) {
            $returnPosts = array();
            $returnPostsData = array();

            foreach ($posts as $post) {
                $postDetails    = $this->referralsGateway->formPostDetailsArray($post[0]);
                $companyDetails = $this->referralsGateway->formPostDetailsArray($post[1]);
                $returnPosts['id']  = $postDetails['post_id'];
                
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
                $returnPosts['status'] = $postDetails['status'];
                
                $returnPosts['hired_count']     = $hiredCount;
                $returnPosts['invited_count']   = $invitedCount;
                $returnPosts['referral_count']  = $referralCount;
                $returnPosts['accepted_count']  = $acceptedCount;
                $returnPosts['pending_count']   = max($pendingCount,0);
                
                $returnPosts['free_service']    = $postDetails['free_service'];
                $returnPosts['job_function']    = isset($postDetails['job_function_name']) ? $postDetails['job_function_name'] : "";
                $returnPosts['industry_name']   = $postDetails['industry_name'];
                $returnPosts['requistion_id']   = $postDetails['requistion_id'];
                $returnPosts['job_description'] = isset($postDetails['job_description']) ? $postDetails['job_description'] : "";
                $returnPosts['employment_type'] = isset($postDetails['employment_type_name']) ? $postDetails['employment_type_name'] : "";
                $returnPosts['experience_range']    = isset($postDetails['experience_range_name']) ? $postDetails['experience_range_name'] : "";
                $returnPosts['company_description'] = isset($companyDetails['description']) ? $companyDetails['description'] : "";
                $returnPosts['company_logo'] = isset($companyDetails['logo']) ? $companyDetails['logo'] : "";
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
                $returnReferralDetails['status']                = $postRelDetails['one_way_status'];   
                $returnReferralDetails['created_at']            = \Carbon\Carbon::createFromTimeStamp(strtotime($postRelDetails['created_at']))->diffForHumans();
                $returnReferralDetails['updated_at']            = !empty($postRelDetails['p1_updated_at'])?date("d M Y H:i", strtotime($postRelDetails['p1_updated_at'])):'';
                $returnReferralDetails['referred_by']           = $neoReferrerDetails['emailid'];
                $returnReferralDetails['resume_path']           = !empty($postRelDetails['resume_path'])?$postRelDetails['resume_path']:$userDetails['cv_path'];
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
    
}

?>
