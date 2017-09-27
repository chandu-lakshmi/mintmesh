<?php

namespace Mintmesh\Gateways\API\Referrals;

/**
 * This is the Seek Referrals Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Services\Validators\API\Referrals\ReferralsValidator;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Gateways\API\SocialContacts\ContactsGateway;
use Mintmesh\Repositories\API\Payment\PaymentRepository;
use Mintmesh\Gateways\API\Payment\PaymentGateway;
use Mintmesh\Gateways\API\Post\PostGateway;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
use Mintmesh\Repositories\API\Enterprise\EnterpriseRepository;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter;
use Mintmesh\Services\APPEncode\APPEncode;
use Mintmesh\Services\Emails\API\User\UserEmailManager;
use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Mintmesh\Gateways\API\SMS\SMSGateway;
use lib\Parser\MyEncrypt;
use Lang;
use Config;
use Log,
    Queue;
use ZipArchive;

class ReferralsGateway {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    const CURL_CALL_TYPE = 1;
    const CURL_CALL_TYPE_FILE = 2;
    const IMAGE_DEFAULT_HIGHT = 120;

    protected $referralsRepository, $referralsValidator, $neoUserRepository, $userRepository;
    protected $authorizer, $appEncodeDecode, $paymentRepository, $paymentGateway, $neoPostRepository, $postGateway;
    protected $commonFormatter, $loggedinUserDetails, $neoLoggedInUserDetails, $userGateway, $contactsGateway, $contactsRepository, $smsGateway;
    protected $userEmailManager, $service_scopes, $job_types, $resumeValidations;

    public function __construct(referralsRepository $referralsRepository, referralsValidator $referralsValidator, NeoUserRepository $neoUserRepository, UserRepository $userRepository, Authorizer $authorizer, CommonFormatter $commonFormatter, UserGateway $userGateway,
    //PostGateway $postGateway,
            NeoPostRepository $neoPostRepository, EnterpriseRepository $enterpriseRepository, PaymentRepository $paymentRepository, PaymentGateway $paymentGateway, APPEncode $appEncodeDecode, UserEmailManager $userEmailManager, ContactsGateway $contactsGateway, ContactsRepository $contactsRepository, SMSGateway $smsGateway) {
        //ini_set('max_execution_time', 500);
        $this->referralsRepository = $referralsRepository;
        $this->referralsValidator = $referralsValidator;
        $this->neoUserRepository = $neoUserRepository;
        $this->userRepository = $userRepository;
        $this->userEmailManager = $userEmailManager;
        $this->authorizer = $authorizer;
        $this->commonFormatter = $commonFormatter;
        $this->userGateway = $userGateway;
        //$this->postGateway = $postGateway;
        $this->neoPostRepository = $neoPostRepository;
        $this->enterpriseRepository = $enterpriseRepository;
        $this->appEncodeDecode = $appEncodeDecode;
        $this->paymentRepository = $paymentRepository;
        $this->paymentGateway = $paymentGateway;
        $this->service_scopes = array('get_service', 'provide_service');
        $this->job_types = array('find_candidate', 'find_job');
        $this->contactsGateway = $contactsGateway;
        $this->contactsRepository = $contactsRepository;
        $this->smsGateway = $smsGateway;
        $this->resumeValidations = array('uploaded_large_file', 'invalid_file_format');
    }

    public function doValidation($validatorFilterKey, $langKey) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes($validatorFilterKey)) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get($langKey)));
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $data = array();
        } else {
            /* Return validation errors to the controller */
            $message = $this->referralsValidator->getErrors();
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $data = array();
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }

    //validation on campaign details
    public function validateGetCampaignDetails($input) {
        return $this->doValidation('get_campaign_details', 'MINTMESH.user.valid');
    }

    public function validateServiceSeekReferralInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('seek_service_referral')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function validateGetPostsInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('get_posts')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function validatereferContact($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('refer_contact')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function validateClosePostInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('close_post')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function verifyGetPostDetails($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('get_post_details')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function verifyProcessPostDetails($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('process_post')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function verifyPostStatusDetails($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('post_status_details')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function verifyreferralContacts($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('referral_contacts')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function verifyMutualPeople($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('mutual_people')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    //validation on get referrals cash
    public function validateReferralsCashInput($input) {
        //validator passes method accepts validator filter key as param
        if ($this->referralsValidator->passes('get_referrals_cash')) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get('MINTMESH.payment.valid')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }

        /* Return validation errors to the controller */
        return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array());
    }

    public function seekServiceReferral($input) {
        $excludedList = $includedList = array();
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $fromId = $this->neoLoggedInUserDetails->id;
//          if ($this->loggedinUserDetails = $this->getLoggedInUser())
        if ($this->loggedinUserDetails) {
            //$this->loggedinUserDetails
            $neoInput = array();
            //form insert array
            $neoInput['looking_for'] = !empty($input['looking_for']) ? $input['looking_for'] : 0;
            $neoInput['service'] = !empty($input['service']) ? $input['service'] : "";
            $neoInput['service_location'] = !empty($input['service_location']) ? $input['service_location'] : "";
            $neoInput['service_period'] = $input['service_period'];
            $neoInput['service_scope'] = $input['service_scope'];
            $neoInput['service_type'] = $input['service_type'];
            $neoInput['service_currency'] = !empty($input['service_currency']) ? $input['service_currency'] : "";
            $neoInput['service_cost'] = !empty($input['service_cost']) ? $input['service_cost'] : '';
            $neoInput['service_code'] = uniqid();
            $neoInput['included_set'] = isset($input['included_list']) ? 1 : 0;
            $neoInput['industry'] = !empty($input['industry']) ? $input['industry'] : '';
            $neoInput['company'] = !empty($input['company']) ? $input['company'] : '';
            $neoInput['job_function'] = !empty($input['job_function']) ? $input['job_function'] : '';
            $neoInput['experience_range'] = !empty($input['experience_range']) ? $input['experience_range'] : '';
            $neoInput['employment_type'] = !empty($input['employment_type']) ? $input['employment_type'] : '';
            $neoInput['position_id'] = !empty($input['position_id']) ? $input['position_id'] : "";
            if (!empty($input['web_link'])) {
                $neoInput['web_link'] = $input['web_link'];
            }
            if (!empty($input['free_service']) && empty($input['service_cost'])) {
                $neoInput['free_service'] = $input['free_service'];
            } else {
                $neoInput['free_service'] = 0;
            }
            $neoInput['created_by'] = $this->loggedinUserDetails->emailid;
            $neoInput['status'] = Config::get('constants.REFERRALS.STATUSES.ACTIVE');
            //relation attributes
            $relationAttrs = array();
            $relationAttrs['created_at'] = date("Y-m-d H:i:s");
            //create a relation between service/job and user 
            if (!empty($input['looking_for'])) {
                //map user and service/job
                if (in_array($input['service_scope'], $this->service_scopes)) {//if service
                    $serviceResult = $this->neoUserRepository->mapServices(array($input['looking_for']), $this->loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.LOOKING_FOR'));
                    //print_r($serviceResult);exit;
                    $neoInput['service_name'] = !empty($serviceResult[0][0]->name) ? $serviceResult[0][0]->name : '';
                } else {//if job
                    $serviceResult = $this->neoUserRepository->mapJobs(array($input['looking_for']), $this->loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.LOOKING_FOR'));
                    $neoInput['service_name'] = !empty($serviceResult[0][0]->name) ? $serviceResult[0][0]->name : '';
                }
            }
            $createdService = $this->referralsRepository->createPostAndRelation($fromId, $neoInput, $relationAttrs);
            //print_r($createdService);exit;
            if (isset($createdService[0]) && isset($createdService[0][0])) {
                $serviceId = $createdService[0][0]->getID();
            } else {
                $serviceId = 0;
            }
            #map industry if provided
            if (!empty($input['industry'])) {
                $iResult = $this->referralsRepository->mapIndustryToPost($input['industry'], $serviceId, Config::get('constants.REFERRALS.ASSIGNED_INDUSTRY'));
            }
            #map job_function if provided
            if (!empty($input['job_function'])) {
                $jfResult = $this->referralsRepository->mapJobFunctionToPost($input['job_function'], $serviceId, Config::get('constants.REFERRALS.ASSIGNED_JOB_FUNCTION'));
            }
            #map employment type if provided
            if (!empty($input['employment_type'])) {
                $emResult = $this->referralsRepository->mapEmploymentTypeToPost($input['employment_type'], $serviceId, Config::get('constants.REFERRALS.ASSIGNED_EMPLOYMENT_TYPE'));
            }
            #map experience range if provided
            if (!empty($input['experience_range'])) {
                $eResult = $this->referralsRepository->mapExperienceRangeToPost($input['experience_range'], $serviceId, Config::get('constants.REFERRALS.ASSIGNED_EXPERIENCE_RANGE'));
            }
            $allConnectedUsers = $this->neoUserRepository->getConnectedUsers($this->loggedinUserDetails->emailid);
            $connectedUsers = array();
            foreach ($allConnectedUsers as $allUsers) {
                if ($allUsers[0]->emailid != $this->loggedinUserDetails->emailid)
                    $connectedUsers[] = $allUsers[0]->emailid;
            }
            //exclude or include contacts
            if (isset($input['excluded_list']) || isset($input['included_list'])) {
                $excludedList = !empty($input['excluded_list']) ? json_decode($input['excluded_list']) : array();
                $includedList = !empty($input['included_list']) ? json_decode($input['included_list']) : array();
                $list = json_decode(!empty($input['excluded_list']) ? $input['excluded_list'] : $input['included_list']);
                if (!empty($list)) {
                    $relationAttrs = array();
                    $relationAttrs['service_scope'] = $input['service_scope'];
                    $remaningList = array_diff($connectedUsers, $list);
                    if (!empty($serviceId)) {
                        //create relation to the list sent by front end
                        if (is_array($list)) {
                            foreach ($list as $user) {
                                $excludedOrIncluded = $this->formQueueArrayForExcludeOrEnclude($serviceId, $user, $relationAttrs, !empty($input['excluded_list']) ? 'exclude' : 'include');
                                //$excludedOrIncluded = $this->referralsRepository->excludeOrIncludeContact($serviceId, $user, $relationAttrs, !empty($input['excluded_list'])?'exclude':'include') ;
                            }
                        }
                        //create relation to the remaning list 
                        if (is_array($remaningList)) {
                            foreach ($remaningList as $user) {
                                $excludedOrIncluded = $this->formQueueArrayForExcludeOrEnclude($serviceId, $user, $relationAttrs, !empty($input['excluded_list']) ? 'include' : 'exclude');
                                //$excludedOrIncluded = $this->referralsRepository->excludeOrIncludeContact($serviceId, $user, $relationAttrs, !empty($input['excluded_list'])?'include':'exclude') ;
                            }
                        }
                    }
                }
            }
            //send email to user after post done successfully
            $successSupportTemplate = Lang::get('MINTMESH.email_template_paths.post_success');
            $receipientEmail = $this->loggedinUserDetails->emailid;
            $emailData = array('name' => $this->neoLoggedInUserDetails->firstname,
                'email' => $this->neoLoggedInUserDetails->emailid);
            $emailiSent = $this->sendEmailToUser($successSupportTemplate, $receipientEmail, $emailData);

            #send push notifications to all the contacts
            $pushData = array();
            $pushData['serviceId'] = $serviceId;
            $pushData['loggedinUserDetails'] = $this->loggedinUserDetails;
            $pushData['neoLoggedInUserDetails'] = $this->neoLoggedInUserDetails;
            $pushData['includedList'] = $includedList;
            $pushData['excludedList'] = $excludedList;
            $pushData['service_type'] = $input['service_type'];
            $pushData['service_location'] = $neoInput['service_location'];
            $pushData['notification_type'] = 27;
            $pushData['service_name'] = $neoInput['service'];
            Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $pushData, 'Notification');
            //$this->sendPushNotificationsForPosts($serviceId, $this->loggedinUserDetails,$this->neoLoggedInUserDetails, $includedList, $excludedList, $input['service_type'], $neoInput['service_location']);

            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.error')));
            return $this->commonFormatter->formatResponse(406, "error", $message, array());
        }
    }

    public function formQueueArrayForExcludeOrEnclude($serviceId = 0, $user = '', $relationAttrs = array(), $relationName = '') {
        if (!empty($serviceId)) {
            $pushData = array();
            $pushData['serviceId'] = $serviceId;
            $pushData['user'] = $user;
            $pushData['relationAttrs'] = $relationAttrs;
            $pushData['relationName'] = $relationName;
            Queue::push('Mintmesh\Services\Queues\PostsQueue', $pushData);
        }
        return true;
    }

    public function sendEmailToUser($templatePath, $emailid, $data) {
        $this->userEmailManager->templatePath = $templatePath;
        $this->userEmailManager->emailId = $emailid;
        $dataSet = array();
        // $dataSet['name'] = "shweta" ;
        // $dataSet['email'] = "shwetapazarey@gmail.com";
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $dataSet[$k] = $v;
            }
        }
        /* $dataSet['name'] = $input['firstname'];
          $dataSet['link'] = $appLink ;
          $dataSet['email'] = $input['emailid'] ; */

        // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
        $this->userEmailManager->dataSet = $dataSet;
        $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.post_success');
        $this->userEmailManager->name = 'user';
        return $email_sent = $this->userEmailManager->sendMail();
    }

    public function closePost($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $postId = $input['post_id'];
        $closed = $this->referralsRepository->closePost($userEmail, $postId);
        if (!empty($closed)) {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.post_closed')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.error')));
            return $this->commonFormatter->formatResponse(406, "error", $message, array());
        }
    }

    public function deactivatePost($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $postId = $input['post_id'];
        $closed = $this->referralsRepository->deactivatePost($userEmail, $postId);
        if (!empty($closed)) {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.post_closed')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.post_not_closed')));
            return $this->commonFormatter->formatResponse(406, "error", $message, array());
        }
    }

    public function getLatestPosts() {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $posts = $this->referralsRepository->getLatestPosts($userEmail);
        if (!empty(count($posts))) {
            $returnPosts = array();
            foreach ($posts as $post) {
                $postDetails = $this->formPostDetailsArray($post[0]);
                $postDetails['no_of_referrals'] = !empty($post[1]) ? $post[1] : 0;
                $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']);
                $postDetails['UserDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                $returnPosts[] = $postDetails;
            }
            $data = array("posts" => $returnPosts);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_posts')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    //get all posts of a type
    public function getPosts($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $userCountry = $this->neoLoggedInUserDetails->phone_country_name;
        $page = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search = !empty($input['search']) ? $input['search'] : '';
        $request_type = isset($input['request_type']) ? $input['request_type'] : '';
        $posts = $this->referralsRepository->getAllPosts($userEmail, $request_type, $page, $search);
        if (!empty(count($posts))) {
            $returnPosts = array();
            foreach ($posts as $post) {
                //if($post[0]->created_by != $this->loggedinUserDetails->emailid) {
                $postDetails = $this->formPostDetailsArray($post[0]);
                //$postDetails['no_of_referrals'] = !empty($post[1])?$post[1]:0 ;
                $postDetails['no_of_referrals'] = $this->referralsRepository->getPostMyReferralsCount($userEmail, $postDetails['post_id']);
                $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']);
                $postDetails['rewards'] = $this->getPostRewards($postDetails['post_id'], $userCountry);
                $postDetails['UserDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                $returnPosts[] = $postDetails;
                //}
            }
            $data = array("posts" => $returnPosts);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_posts')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function referContact($input) {
        $referNonMintmesh = 0;
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $relationCount = 1;
        //count for succss relations
        $existingRelationCount = $this->referralsRepository->getReferralsCount(Config::get('constants.REFERRALS.GOT_REFERRED'), $input['post_id'], $userEmail, $input['refer_to']);
        if ($existingRelationCount < Config::get('constants.REFERRALS.MAX_REFERRALS')) {
            //$this->validateResume($input); 
            //continue only when the request count is in limit
            //create a relation between the post and user
            $oldRelationCount = $this->referralsRepository->getOldRelationsCount($input['post_id'], $input['referring']);
            if (!empty($oldRelationCount)) {
                $relationCount = $oldRelationCount + 1;
                //$message = array('msg'=>array(Lang::get('MINTMESH.referrals.already_referred')));
                //return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            } else {
                $relationCount = 1;
            }
            if (!empty($input['refer_non_mm_email']) && !empty($input['referring'])) {//non mintmesh and refer 
                if (!empty($input['referring_phone_no'])) {//create node for this and relate
                    //check if phone number contact exist
                    $nonMintmeshContactExist = $this->contactsRepository->getNonMintmeshContact($input['referring']);
                    $phoneContactInput = $phoneContactRelationInput = array();
                    $phoneContactInput['firstname'] = $phoneContactInput['lastname'] = $phoneContactInput['fullname'] = "";
                    $phoneContactRelationInput['firstname'] = !empty($input['referring_user_firstname']) ? $this->appEncodeDecode->filterString($input['referring_user_firstname']) : '';
                    $phoneContactRelationInput['lastname'] = !empty($input['referring_user_lastname']) ? $this->appEncodeDecode->filterString($input['referring_user_lastname']) : '';
                    $phoneContactRelationInput['fullname'] = $phoneContactRelationInput['firstname'] . " " . $phoneContactRelationInput['lastname'];
                    $phoneContactInput['phone'] = !empty($input['referring']) ? $this->appEncodeDecode->filterString($input['referring']) : '';
                    if (!empty($nonMintmeshContactExist)) {
                        //create import relation
                        $relationCreated = $this->contactsRepository->relateContacts($this->neoLoggedInUserDetails, $nonMintmeshContactExist[0], $phoneContactRelationInput, 1);
                    } else {

                        $importedContact = $this->contactsRepository->createNodeAndRelationForPhoneContacts($userEmail, $phoneContactInput, $phoneContactRelationInput);
                    }
                    //send sms invitation to p3
                    $smsInput = array();
                    $smsInput['numbers'] = json_encode(array($input['referring']));
                    $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($input['refer_to']);
                    $smsInput['other_name'] = !empty($otherUserDetails->fullname) ? $otherUserDetails->fullname : "";
                    $smsInput['sms_type'] = 3;
                    $smsSent = $this->smsGateway->sendSMSForReferring($smsInput);
                    $referNonMintmesh = 1;
                } else {
                    //check if p2 imported p3
                    $isImported = $this->contactsRepository->getContactByEmailid($input['referring']);
                    if (empty($isImported)) {
                        $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_import')));
                        return $this->commonFormatter->formatResponse(406, "error", $message, array());
                    } else {//send invitation email to p3
                        $postInvitationArray = array();
                        $postInvitationArray['emails'] = json_encode(array($input['referring']));
                        $postInvitationArray['post_id'] = $input['post_id'];
                        $invited = $this->contactsGateway->sendPostReferralInvitations($postInvitationArray);
                    }
                }
            }
            $relationAttrs = array();
            $relationAttrs['referred_by'] = strtolower($userEmail);
            $relationAttrs['referred_for'] = strtolower($input['refer_to']);
            $relationAttrs['created_at'] = gmdate("Y-m-d H:i:s");
            $relationAttrs['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
            $relationAttrs['one_way_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
            if (!empty($input['message'])) {
                $relationAttrs['message'] = $input['message'];
            }
            if (!empty($input['bestfit_message'])) {
                $relationAttrs['bestfit_message'] = $input['bestfit_message'];
            }
            $relationAttrs['relation_count'] = $relationCount;
            if (!empty($referNonMintmesh)) {
                $result = $this->referralsRepository->referContactByPhone($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
            } else {
                $result = $this->referralsRepository->referContact($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
            }

            if (!empty($result)) {
//                  if self referrence
                if ($this->loggedinUserDetails->emailid == $input['referring']) {
                    $notificationType = 23;
                } else {
                    $notificationType = 10;
                }
                //send notification to the person who created post
                $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['refer_to'], $notificationType, array('extra_info' => $input['post_id']), array('other_user' => $input['referring'], 'p3_non_mintmesh' => 1));
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array());
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.closed_post')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array());
            }
        } else {
            //return limit crossed message
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.limit_crossed')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function getPostDetails($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $postDetails = array();
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        if (!empty($userEmail)) {
            $postResult = $this->referralsRepository->getPostDetails($input['post_id']);
            if (count($postResult)) {
                $postDetails = $this->formPostDetailsArray($postResult[0][0]);
                if (!empty($postDetails['created_by'])) {
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']);
                    $postDetails['userDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                }
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $postDetails);
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array());
            }
        }
    }

    public function getPostReferences($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $postDetails = array();
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        if (!empty($userEmail)) {
            $limit = $page_no = 0;
            if (!empty($input['limit'])) {
                $limit = $input['limit'];
            }
            if (!empty($input['page_no'])) {
                $page_no = $input['page_no'];
            }
            $result = $this->referralsRepository->getPostReferences($input['post_id'], $limit, $page_no);
            if (count($result)) {
                $returnArray = array();
                $postResult = $this->referralsRepository->getPostDetails($input['post_id']);
                if (count($postResult)) {//continue when post exist and is active
                    $returnArray['postDetails'] = $postDetails = $this->formPostDetailsArray($postResult[0][0]);
                    $returnArray['referrals_count'] = $this->referralsRepository->getPostReferralsCount($input['post_id']);
                    if (!empty($postDetails['created_by'])) {
                        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']);
                        $returnArray['userDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                    }
                } else { //else return 
                    $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array());
                }

                foreach ($result as $k => $v) {
                    $nonMintmeshUserDetails = array();
                    $isNonMintmesh = 0;
                    if (isset($v[0]) && isset($v[1])) {
                        $postDetails = array();
                        $p1Status = '';
                        $reference_details = $this->userGateway->formUserDetailsArray($v[0], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                        foreach ($reference_details as $k1 => $v1) {
                            $postDetails['to_user_' . $k1] = $v1;
                        }
                        //get label of user
                        if (!empty($v[2][0]) && $v[2][0] == 'NonMintmesh') {
                            $postDetails['to_user_refered_by'] = 'phone';
                            $postDetails['to_user_is_mintmesh'] = 0;
                            $postDetails['referred_by_phone'] = 1;
                            $isNonMintmesh = 1;
                            if (!empty($v[0]->phone))
                                $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByPhone($v[1]->referred_by, $v[0]->phone);
                        }else if (!empty($v[2][1]) && $v[2][1] == 'Mintmesh') {
                            $postDetails['to_user_is_mintmesh'] = 1;
                        } else {
                            $postDetails['to_user_referred_by'] = 'emailid';
                            $postDetails['to_user_is_mintmesh'] = 0;
                            $isNonMintmesh = 1;
                            if (!empty($v[0]->emailid))
                                $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByEmail($v[1]->referred_by, $v[0]->emailid);
                        }

                        $viaUserDetails = $this->neoUserRepository->getNodeByEmailId($v[1]->referred_by);
                        $referred_by_details = $this->userGateway->formUserDetailsArray($viaUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                        foreach ($referred_by_details as $k2 => $v2) {
                            $postDetails['from_user_' . $k2] = $v2;
                        }
                        //form user name details for non mintmesh contacts
                        if (!empty($nonMintmeshUserDetails) || !empty($isNonMintmesh)) {
                            if (!empty($nonMintmeshUserDetails->fullname)) {
                                $nonMintmeshUserDetails->fullname = trim($nonMintmeshUserDetails->fullname);
                            }
                            $fName = !empty($postDetails['to_user_emailid']) ? $postDetails['to_user_emailid'] : str_replace("-", "", $postDetails['to_user_phone']);
                            $checkFName = $fName . " " . $fName;
                            $postDetails['to_user_fullname'] = (empty($nonMintmeshUserDetails->fullname) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : ($nonMintmeshUserDetails->fullname == $checkFName || $nonMintmeshUserDetails->fullname == $fName) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : $nonMintmeshUserDetails->fullname);
                            $postDetails['to_user_firstname'] = (empty($nonMintmeshUserDetails->firstname) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : ($nonMintmeshUserDetails->firstname == $fName) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : $nonMintmeshUserDetails->firstname);
                            $postDetails['to_user_lastname'] = (empty($nonMintmeshUserDetails->lastname) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : ($nonMintmeshUserDetails->lastname == $fName) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : $nonMintmeshUserDetails->lastname);
//                                $postDetails['to_user_firstname'] = !empty($nonMintmeshUserDetails->firstname)?$nonMintmeshUserDetails->firstname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
//                                $postDetails['to_user_lastname'] = !empty($nonMintmeshUserDetails->lastname)?$nonMintmeshUserDetails->lastname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
//                                $postDetails['to_user_fullname'] = !empty($nonMintmeshUserDetails->fullname)?$nonMintmeshUserDetails->fullname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                        }
                        //check if self referred
                        if (!empty($postDetails['to_user_emailid']) && $postDetails['to_user_emailid'] == $postDetails['from_user_emailid']) {
                            $postDetails['to_is_self_referred'] = 1;
                        } else {
                            $postDetails['to_is_self_referred'] = 0;
                        }
                        $relationDetails = $v[1]->getProperties();
                        if (!empty($relationDetails['one_way_status'])) {
                            if ($relationDetails['one_way_status'] == Config::get('constants.REFERRALS.STATUSES.ACCEPTED')) {
                                $p1Status = strtolower(Config::get('constants.REFERRALS.STATUSES.ACCEPTED'));
                                $postDetails['relation_status'] = !empty($relationDetails['completed_status']) ? strtolower($relationDetails['completed_status']) : strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                            } else {
                                $postDetails['relation_status'] = $p1Status = strtolower($relationDetails['one_way_status']);
                            }
                        } else {
                            $p1Status = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                            $postDetails['relation_status'] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                        }
                        $postDetails['relation_count'] = $relationDetails['relation_count'];
                        //categorize references
                        if (!empty($p1Status) && $p1Status == strtolower(Config::get('constants.REFERRALS.STATUSES.ACCEPTED'))) {
                            $returnArray['references']['accepted'][] = $postDetails;
                        } else if (!empty($p1Status) && $p1Status == strtolower(Config::get('constants.REFERRALS.STATUSES.DECLINED'))) {
                            $returnArray['references']['declined'][] = $postDetails;
                        } else {
                            $returnArray['references']['pending'][] = $postDetails;
                        }
                        //$returnArray['references'][] = $postDetails ;
                    }
                }
                $data = $returnArray;
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data);
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_referrals_found')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array());
            }
        }
    }

    public function formPostDetailsArray($postResult = array(), $flag = TRUE) {
        if (!empty($postResult)) {
            $postId = $postResult->getId();
            $return = $postResult->getProperties();
            $return['post_id'] = $postId;
            #added flag for enterprise jobs list api no need industry and job function
            if ($flag) {
                #get industry name
                $return['industry_name'] = $this->referralsRepository->getIndustryNameForPost($postId);
                if (empty($return['industry_name'])) {
                    $return['industry_name'] = $postResult->industry;
                }
                #get job function name
                $return['job_function_name'] = $this->referralsRepository->getJobFunctionNameForPost($postId);
                if (empty($return['job_function_name'])) {
                    $return['job_function_name'] = $postResult->job_function;
                }
            }
            #get experience range name
            $return['experience_range_name'] = $this->referralsRepository->getExperienceRangeNameForPost($postId);
            if (empty($return['experience_range_name'])) {
                $return['experience_range_name'] = $postResult->experience_range;
            }
            #get employment type name
            $return['employment_type_name'] = $this->referralsRepository->getEmploymentTypeNameForPost($postId);
            if (empty($return['employment_type_name'])) {
                $return['employment_type_name'] = $postResult->employment_type;
            }
            return $return;
        } else {
            return array();
        }
    }

    public function getMyReferrals($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $users = $this->referralsRepository->getMyReferrals($input['post_id'], $userEmail);
        $postCreatedBy = '';
        //get post details
        $postDetails = $this->referralsRepository->getPostDetails($input['post_id'], $userEmail);
        if (count($users)) {
            $userDetails = array();
            $self_referred = 0;
            foreach ($users as $k => $v) {
                $isNonMintmesh = 0;
                $nonMintmeshUserDetails = array();
                $u = $this->userGateway->formUserDetailsArray($v[0], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                $u['relation_id'] = $v[1]->getId();
                $relation_details = $v[1]->getProperties();
                if (!empty($relation_details['one_way_status'])) {
                    if ($relation_details['one_way_status'] == Config::get('constants.REFERRALS.STATUSES.ACCEPTED')) {
                        $u['relation_status'] = !empty($relation_details['completed_status']) ? strtolower($relation_details['completed_status']) : strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                    } else {
                        $u['relation_status'] = strtolower($relation_details['one_way_status']);
                    }
                } else {
                    $u['relation_status'] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                }
                $u['relation_count'] = $relation_details['relation_count'];
                $input['post_created_by'] = $u['post_created_by'] = $relation_details['referred_for'];
                //add label if non mintmesh
                if (!empty($v[2][0]) && $v[2][0] == 'NonMintmesh') {
                    $u["is_mintmesh"] = 0;
                    $u["user_referred_by"] = 'phone';
                    $u['referred_by_phone'] = 1;
                    $isNonMintmesh = 1;
                    if (!empty($v[0]->phone))
                        $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByPhone($this->loggedinUserDetails->emailid, $v[0]->phone);
                }else if (!empty($v[2][1]) && $v[2][1] == 'Mintmesh') {
                    $u["is_mintmesh"] = 1;
                } else {
                    $u["is_mintmesh"] = 0;
                    $u["user_referred_by"] = 'emailid';
                    $isNonMintmesh = 1;
                    if (!empty($v[0]->emailid))
                        $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByEmail($this->loggedinUserDetails->emailid, $v[0]->emailid);
                }
                //form user name details for non mintmesh contacts
                if (!empty($nonMintmeshUserDetails) || !empty($isNonMintmesh)) {
                    if (!empty($nonMintmeshUserDetails->fullname)) {
                        $nonMintmeshUserDetails->fullname = trim($nonMintmeshUserDetails->fullname);
                    }
                    $u['firstname'] = !empty($nonMintmeshUserDetails->firstname) ? $nonMintmeshUserDetails->firstname : Lang::get('MINTMESH.user.non_mintmesh_user_name');
                    $u['lastname'] = !empty($nonMintmeshUserDetails->lastname) ? $nonMintmeshUserDetails->lastname : Lang::get('MINTMESH.user.non_mintmesh_user_name');
                    $u['fullname'] = !empty($nonMintmeshUserDetails->fullname) ? $nonMintmeshUserDetails->fullname : Lang::get('MINTMESH.user.non_mintmesh_user_name');
                }
                //check if self referred
                if (!empty($u['emailid']) && $u['emailid'] == $userEmail) {
                    $u['is_self_referred'] = 1;
                    $self_referred = 1;
                } else {
                    $u['is_self_referred'] = 0;
                }
                $userDetails[] = $u;
            }
            $data = array("users" => $userDetails);
            $data['postDetails'] = array();
            if (count($postDetails) && isset($postDetails[0][0])) {
                $input['post_scope'] = !empty($postDetails[0][0]->service_scope) ? $postDetails[0][0]->service_scope : '';
                $data['postDetails'] = $postDetails = $this->formPostDetailsArray($postDetails[0][0]);
            }
            $suggestions = $this->getPostSuggestions($input);
            $data['suggestions'] = !empty($suggestions['data']['users']) ? $suggestions['data']['users'] : array();
            $data['referrals_count'] = $this->referralsRepository->getPostMyReferralsCount($userEmail, $input['post_id']);
            $data['is_self_referred'] = $self_referred;
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $data = array("users" => array());
            if (count($postDetails) && isset($postDetails[0][0])) {
                $input['post_created_by'] = !empty($postDetails[0][0]->created_by) ? $postDetails[0][0]->created_by : '';
                $input['post_scope'] = !empty($postDetails[0][0]->service_scope) ? $postDetails[0][0]->service_scope : '';
                $suggestions = $this->getPostSuggestions($input);
                $data['suggestions'] = !empty($suggestions['data']['users']) ? $suggestions['data']['users'] : array();
                $data['postDetails'] = $postDetails = $this->formPostDetailsArray($postDetails[0][0]);
            }
            $data['referrals_count'] = $this->referralsRepository->getPostMyReferralsCount($userEmail, $input['post_id']);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_referrals')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        }
    }

    public function getPostSuggestions($input) {
        //get suggestions for the post
        $suggestionInput['other_email'] = $input['post_created_by'];
        $suggestionInput['limit'] = 5;
        $suggestionInput['suggestion'] = 1;
        $suggestionInput['post_id'] = $input['post_id'];
        $suggestionInput['post_scope'] = !empty($input['post_scope']) ? $input['post_scope'] : 'get_service';
        $suggestions = $this->getMyReferralContacts($suggestionInput);
        return $suggestions;
    }

    public function getLoggedInUser() {
        $resourceOwnerId = $this->authorizer->getResourceOwnerId();
        return $this->userRepository->getUserById($resourceOwnerId);
    }

    public function editPost($input) {
        if (!empty($input['post_id'])) {
            $id = $input['post_id'];
            unset($input['post_id']);
            //remove free servce
            if (!empty($input['free_service'])) {
                unset($input['free_service']);
            }
            if (!empty($input['access_token']))
                unset($input['access_token']);
            if (!empty($input)) {
                $result = $this->referralsRepository->editPost($input, $id);
                if (!empty($result)) {
                    $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array());
                } else {
                    $message = array('msg' => array(Lang::get('MINTMESH.referrals.closed_post')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array());
                }
            }
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function processPost($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $parse = 1;
        $payment_transaction_id = $phoneNumberReferred = 0;
        $data = array();
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        if ($input['post_way'] == 'one') {
            $referral = $input['from_user'];
        } else if ($input['post_way'] == 'round') {
            $referral = $userEmail;
        }
        if (!empty($input['referred_by_phone'])) {
            $phoneNumberReferred = 1;
        }
        $result = $this->referralsRepository->processPost($input['post_id'], $input['referred_by'], $referral, $input['status'], $input['post_way'], $input['relation_count'], $phoneNumberReferred);
        if (count($result)) {
            if ($input['post_way'] == 'one') {//p1 accepting
                $relationId = !empty($result[0][1]) ? $result[0][1]->getID() : 0;
                $postId = $input['post_id'];

                if ($input['status'] != 'declined') {

                    //enter into payment transactions if not free service
                    if (!empty($result[0][0]) && empty($result[0][0]->free_service)) {
                        //update existing transactions if any
                        $tranRes = $this->paymentRepository->cancelOtherTransactions($postId, $relationId);
                        //get comission percentage
                        $payment_per_res = $this->paymentRepository->getComissionPercentage(1);
                        $transactionInput = array();
                        $transactionInput['from_user'] = $this->appEncodeDecode->filterString(strtolower($userEmail));
                        $transactionInput['to_user'] = $this->appEncodeDecode->filterString(strtolower($input['referred_by']));
                        $transactionInput['for_user'] = $this->appEncodeDecode->filterString(strtolower($referral));
                        $transactionInput['amount'] = !empty($result[0][0]->service_cost) ? $result[0][0]->service_cost : 0;
                        if (!empty($result[0][0]->service_currency)) {
                            $transactionInput['payment_type'] = (strtolower($result[0][0]->service_currency) == 'dlr') ? 1 : 2;
                        } else {
                            $transactionInput['payment_type'] = 1;
                        }
                        $transactionInput['payment_reason'] = 1;
                        $transactionInput['mm_transaction_id'] = $t_id = $this->paymentGateway->generateTansactionId($input['referred_by']);
                        $transactionInput['comission_percentage'] = !empty($payment_per_res->comission_percentage) ? $payment_per_res->comission_percentage : 0;
                        $transactionInput['payed_for_id'] = $input['post_id'];
                        $transactionInput['relation_id'] = !empty($result[0][1]) ? $result[0][1]->getID() : 0;
                        $transactionInput['status'] = Config::get('constants.PAYMENTS.STATUSES.PENDING');
                        $payment_transaction = $this->paymentRepository->insertTransaction($transactionInput);
                        $data['transaction_id'] = $t_id;
                        $data['comission_percentage'] = $percentage = !empty($payment_per_res->comission_percentage) ? $payment_per_res->comission_percentage : 0;
                        $data['amount'] = $amount = !empty($result[0][0]->service_cost) ? $result[0][0]->service_cost : 0;
                        if ($transactionInput['payment_type'] == 1) {
                            $data['client_token'] = $this->paymentGateway->getBTClientToken();
                            $data['total_amount'] = $this->paymentGateway->calculateTotalAmount($percentage, $amount);
                            //insert into payment gateways inputs table
                            $paymentInput = array();
                            $paymentInput['token'] = !empty($data['client_token']) ? $data['client_token'] : '';
                            $paymentInput['bill'] = !empty($data['bill_details']) ? json_encode($data['bill_details']) : '';
                            $paymentInput['mm_transaction_id'] = !empty($transactionInput['mm_transaction_id']) ? $transactionInput['mm_transaction_id'] : '';
                            $paymentInputRes = $this->paymentRepository->insertGatewayInput($paymentInput);
                        } else {
                            $data['bill_details'] = 'citrus';
                            // $data['bill_details'] = $this->paymentGateway->generateCitrusBill($data['amount'], $data['comission_percentage']);
                        }

                        /*

                          // update post payment status
                          $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus(!empty($result[0][1])?$result[0][1]->getID():0,Config::get('constants.PAYMENTS.STATUSES.PENDING'));
                         */
                    } else if (!empty($result[0][0]) && !empty($result[0][0]->free_service)) {//free service
                        $is_self_referred = 0;
                        if ($input['referred_by'] == $referral) {
                            $is_self_referred = 1;
                        }
                        $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus(!empty($result[0][1]) ? $result[0][1]->getID() : 0, '', $is_self_referred);
                        //send notifications
                        //send notification to the person who referred to the post
                        $sqlUser = $this->userRepository->getUserByEmail($input['referred_by']);
                        $mysqlId = $sqlUser->id;
                        $referred_by_details = $this->userRepository->getUserById($mysqlId);
                        $referred_by_neo_user = $this->neoUserRepository->getNodeByEmailId($input['referred_by']);
                        //add credits
                        $this->userRepository->logLevel(3, $input['referred_by'], $userEmail, $referral, Config::get('constants.POINTS.SEEK_REFERRAL'));
                        if ($input['from_user'] == $input['referred_by']) {
                            //send notification to via person
                            $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 24, array('extra_info' => $input['post_id']), array('other_user' => $referral), 1);
                        } else {
                            $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $referral, 11, array('extra_info' => $input['post_id']), array('other_user' => $userEmail), 1);
                            //send notification to via person
                            $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 12, array('extra_info' => $input['post_id']), array('other_user' => $referral), 1);
                            //send battle card to u1 containing u3 details
                            $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $this->loggedinUserDetails->emailid, 20, array('extra_info' => $input['post_id']), array('other_user' => $referral), 1);
                        }
                        //send resume attachment to p1 if post type is find_candidate
                        if (!empty($result[0][1]->resume_path) && $result[0][0]->service_scope == 'find_candidate') {
                            $emailNameDetails = array();
                            $emailNameDetails['to_name'] = $this->neoLoggedInUserDetails->fullname;
                            $forname = !empty($result[0][2]->fullname) ? trim($result[0][2]->fullname) : "";
                            if (empty($forname)) {//if non mintmesh
                                if (!empty($result[0][2]->emailid)) {//i.e non mintmesh with only emailid
                                    $relationDetailsResult = $this->contactsRepository->getImportRelationDetailsByEmail($input['referred_by'], $result[0][2]->emailid);
                                    $forname = !empty($relationDetailsResult->fullname) ? trim($relationDetailsResult->fullname) : "";
                                } else if (!empty($result[0][2]->phone)) {
                                    $relationDetailsResult = $this->contactsRepository->getImportRelationDetailsByPhone($input['referred_by'], $result[0][2]->phone);
                                    $forname = !empty($relationDetailsResult->fullname) ? trim($relationDetailsResult->fullname) : "";
                                }
                            }
                            $emailNameDetails['for_name'] = ucwords($forname);
                            $this->userGateway->sendAttachmentResumeToP1($input['referred_by'], $this->loggedinUserDetails->emailid, $result[0][1]->resume_path, $emailNameDetails);
                        }
                    }
                } else {
                    if ($input['from_user'] == $input['referred_by']) {//indicates self referral
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 25, array('extra_info' => $input['post_id']), array('other_user' => $referral), $parse);
                    } else {
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 15, array('extra_info' => $input['post_id']), array('other_user' => $referral), $parse);
                    }
                }
            } else if ($input['post_way'] == 'round') {
                if ($input['status'] != 'declined') {
                    $this->userRepository->logLevel(4, $userEmail, $input['from_user'], $input['referred_by'], Config::get('constants.POINTS.ACCEPT_REFERRAL'));
                    //connect u3 and u1
                    $relationAttrs = array();
                    $relationAttrs['created_at'] = date("Y-m-d H:i:s");
                    $relationAttrs['refered_by_email'] = $input['referred_by'];
                    $responce = $this->neoUserRepository->acceptConnection($userEmail, $input['from_user'], $relationAttrs);
                    //send notification to the person who posted the service
                    $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['from_user'], 13, array('extra_info' => $input['post_id']), array('other_user' => $input['referred_by']), $parse);
                    //send notification to the via person 
                    $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 14, array('extra_info' => $input['post_id']), array('other_user' => $input['from_user']), $parse);
                } else {
                    $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['from_user'], 16, array('extra_info' => $input['post_id']), array('other_user' => $input['referred_by']), $parse);
                    //send notification to the via person 
                    $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 22, array('extra_info' => $input['post_id']), array('other_user' => $input['from_user']), $parse);
                }
            }
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function getPostStatusDetails($input) {
        $isReferredUser = false;
        $isNonMintmesh = 0;
        $timeZone = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $userId = !empty($this->loggedinUserDetails->id) ? $this->loggedinUserDetails->id : '';
        #log user activity here
        $this->userRepository->addUserActivityLogs($userId, $appType = 1, $moduleType = 17);

        $result = $this->referralsRepository->getPostStatusDetails($input);
        if (count($result)) {
            $returnArray = array();
            if (!empty($result[0][0]) && !empty($result[0][1])) {
                $relation_updated_at = !empty($result[0][0]->updated_at) ? $result[0][0]->updated_at : $result[0][0]->created_at;
                if ($relation_updated_at) {
                    $relation_updated_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($relation_updated_at, $timeZone)));
                }
                $returnArray['relation_updated_at'] = $relation_updated_at;
                $returnArray['p3_cv_path'] = !empty($result[0][0]->resume_path) ? $result[0][0]->resume_path : "";
                $returnArray['p3_cv_original_name'] = !empty($result[0][0]->resume_original_name) ? $result[0][0]->resume_original_name : "Resume";
                $returnArray['uploaded_by_p2'] = !empty($result[0][0]->uploaded_by_p2) ? $result[0][0]->uploaded_by_p2 : 0;
                $returnArray['service_scope'] = !empty($result[0][2]->service_scope) ? $result[0][2]->service_scope : "";
                $returnArray['awaiting_action_status'] = !empty($result[0][0]->awaiting_action_status) ? $result[0][0]->awaiting_action_status : "";
                if (!empty($result[0][0]->referred_for)) {
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($result[0][0]->referred_for);
                    $UserDetails = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                    if (!empty($UserDetails)) {
                        foreach ($UserDetails as $k => $v) {
                            $returnArray[$k] = $v;
                        }
                    }
                }
                if (!empty($result[0][0]->referred_by)) {
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($result[0][0]->referred_by);
                    $fromUserDetails = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                    if (!empty($fromUserDetails)) {
                        foreach ($fromUserDetails as $k => $v) {
                            $returnArray["from_" . $k] = $v;
                        }
                    }
                }
                if (!empty($result[0][1])) {
                    $nonMintmeshUserDetails = array();
                    $toUserDetails = $this->userGateway->formUserDetailsArray($result[0][1], 'property');
                    if (!empty($toUserDetails)) {
                        foreach ($toUserDetails as $k => $v) {
                            $returnArray["to_" . $k] = $v;
                        }
                    }
                    //add label if non mintmesh
                    if (!empty($result[0][3][0]) && $result[0][3][0] == 'NonMintmesh') {
                        $returnArray["to_is_mintmesh"] = 0;
                        $returnArray["to_referred_by"] = 'phone';
                        $returnArray['referred_by_phone'] = 1;
                        $isNonMintmesh = 1;
                        if (!empty($result[0][1]->phone))
                            $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByPhone($result[0][0]->referred_by, $result[0][1]->phone);
                    }else if (!empty($result[0][3][1]) && $result[0][3][1] == 'Mintmesh') {
                        $returnArray["to_is_mintmesh"] = 1;
                        $returnArray["to_referred_by"] = '';
                    } else {
                        $returnArray["to_is_mintmesh"] = 0;
                        $returnArray["to_referred_by"] = 'emailid';
                        $isNonMintmesh = 1;
                        if (!empty($result[0][1]->emailid)) {
                            $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByEmail($result[0][0]->referred_by, $result[0][1]->emailid);
                            if (empty($nonMintmeshUserDetails)) {
                                #In case of referral made from emailid
                                $nonMintmeshUserDetails = $this->neoUserRepository->getNodeByEmailId($result[0][1]->emailid);
                            }
                        }
                    }
                    //check if self referred
                    if (!empty($returnArray['to_emailid']) && $returnArray['to_emailid'] == $returnArray['from_emailid']) {
                        $returnArray['is_self_referred'] = 1;
                    } else {
                        $returnArray['is_self_referred'] = 0;
                    }
                    //form user name details for non mintmesh contacts
                    if (!empty($nonMintmeshUserDetails) || !empty($isNonMintmesh)) {
                        if (!empty($nonMintmeshUserDetails->fullname)) {
                            $nonMintmeshUserDetails->fullname = trim($nonMintmeshUserDetails->fullname);
                        }
                        $fName = '';
                        if (!empty($result[0][1]->phone)) {
                            $fName = str_replace("-", "", $result[0][1]->phone);
                            //$checkFName = $fName." ".$fName;
                            //$returnArray['to_fullname'] = (empty($nonMintmeshUserDetails->fullname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($nonMintmeshUserDetails->fullname == $checkFName || $nonMintmeshUserDetails->fullname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$nonMintmeshUserDetails->fullname);
                            //$returnArray['to_firstname'] = (empty($nonMintmeshUserDetails->firstname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($nonMintmeshUserDetails->firstname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$nonMintmeshUserDetails->firstname);
                            //$returnArray['to_lastname'] = (empty($nonMintmeshUserDetails->lastname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($nonMintmeshUserDetails->lastname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$nonMintmeshUserDetails->lastname);
                        } else {
                            $fName = $result[0][1]->emailid;
                            //$checkFName = $fName." ".$fName;
                            //$returnArray['to_firstname'] = !empty($nonMintmeshUserDetails->firstname)?$nonMintmeshUserDetails->firstname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                            //$returnArray['to_lastname'] = !empty($nonMintmeshUserDetails->lastname)?$nonMintmeshUserDetails->lastname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                            //$returnArray['to_fullname'] = !empty($nonMintmeshUserDetails->fullname)?$nonMintmeshUserDetails->fullname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                        }
                        $checkFName = $fName . " " . $fName;
                        $returnArray['to_fullname'] = (empty($nonMintmeshUserDetails->fullname) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : ($nonMintmeshUserDetails->fullname == $checkFName || $nonMintmeshUserDetails->fullname == $fName) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : $nonMintmeshUserDetails->fullname);
                        $returnArray['to_firstname'] = (empty($nonMintmeshUserDetails->firstname) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : ($nonMintmeshUserDetails->firstname == $fName) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : $nonMintmeshUserDetails->firstname);
                        $returnArray['to_lastname'] = (empty($nonMintmeshUserDetails->lastname) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : ($nonMintmeshUserDetails->lastname == $fName) ? Lang::get('MINTMESH.user.non_mintmesh_user_name') : $nonMintmeshUserDetails->lastname);
                    }
                    //check if phone is verified if p3 is loggedin
                    $this->loggedinUserDetails = $this->getLoggedInUser();
                    $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
                    if (isset($returnArray['to_emailid']) && $returnArray['to_emailid'] == $this->loggedinUserDetails->emailid) {
                        $phone_verified = !empty($this->neoLoggedInUserDetails->phoneverified) ? $this->neoLoggedInUserDetails->phoneverified : 0;
                        $returnArray['phone_verified'] = $phone_verified;
                        $isReferredUser = true;
                    }
                }

                $returnArray["relation_id"] = !empty($result[0][0]) ? $result[0][0]->getID() : 0;
                //get transaction details
                $getTranDetsInp = array();
                $getTranDetsInp['from_user'] = $input['from_user'];
                $getTranDetsInp['to_user'] = $input['referred_by'];
                $getTranDetsInp['for_user'] = $input['referral'];
                $getTranDetsInp['service_id'] = $input['post_id'];
                $getTranDetsInp['relation_id'] = $returnArray["relation_id"];
                $getTranDetsInp['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                $returnArray["payment_status"] = !empty($result[0][0]->payment_status) ? $result[0][0]->payment_status : "";
                if ($returnArray["payment_status"] == Config::get('constants.REFERRALS.STATUSES.PENDING')) {
                    $transactionDetails = $this->paymentRepository->getTransactionDetails($getTranDetsInp);
                    if (!empty($transactionDetails)) {
                        $returnArray['mm_transaction_id'] = !empty($transactionDetails->mm_transaction_id) ? $transactionDetails->mm_transaction_id : 0;
                        if ($transactionDetails->payment_type == 1) {
                            $returnArray['bt_client_token'] = $this->paymentGateway->getBTClientToken();
                            //insert into payment gateways inputs table
                            $paymentInput = array();
                            $paymentInput['token'] = !empty($returnArray['bt_client_token']) ? $returnArray['bt_client_token'] : '';
                            $paymentInput['bill'] = '';
                            $paymentInput['mm_transaction_id'] = !empty($transactionDetails->mm_transaction_id) ? $transactionDetails->mm_transaction_id : '';
                            $paymentInputRes = $this->paymentRepository->insertGatewayInput($paymentInput);
                        } else {
                            $returnArray['bill_details'] = 'citrus';
                        }
                        if (!empty($transactionDetails->amount) && !empty($transactionDetails->comission_percentage)) {
                            $perAmount = ($transactionDetails->comission_percentage / 100) * $transactionDetails->amount;
                            $totalAmount = $transactionDetails->amount + $perAmount;
                            $returnArray['amount'] = $totalAmount;
                        }
                    }
                }

                if (!empty($result[0][0]->one_way_status)) {
                    $returnArray["one_way_status"] = strtolower($result[0][0]->one_way_status);
                    $p1_updated_at = !empty($result[0][0]->p1_updated_at) ? $result[0][0]->p1_updated_at : "";
                    if ($p1_updated_at) {
                        $p1_updated_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($p1_updated_at, $timeZone)));
                    }
                    $returnArray["p1_updated_at"] = $p1_updated_at;
                } else {
                    $returnArray["one_way_status"] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                }
                if (!empty($result[0][0]->completed_status)) {
                    $returnArray["complete_status"] = strtolower($result[0][0]->completed_status);
                    $p3_updated_at = !empty($result[0][0]->p3_updated_at) ? $result[0][0]->p3_updated_at : "";
                    if ($p3_updated_at) {
                        $p3_updated_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($p3_updated_at, $timeZone)));
                    }
                    $returnArray["p3_updated_at"] = $p3_updated_at;
                } else {
                    $returnArray["complete_status"] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                }
                $p2_updated_at = !empty($result[0][0]->created_at) ? $result[0][0]->created_at : "";
                if ($p2_updated_at) {
                    $p2_updated_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($p2_updated_at, $timeZone)));
                }
                $service_created_at = !empty($result[0][2]->created_at) ? $result[0][2]->created_at : "";
                if ($service_created_at) {
                    $service_created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($service_created_at, $timeZone)));
                }
                $returnArray["optional_message"] = !empty($result[0][0]->message) ? $result[0][0]->message : "";
                $returnArray["bestfit_message"] = !empty($result[0][0]->bestfit_message) ? $result[0][0]->bestfit_message : "";
                $returnArray["p2_updated_at"] = $p2_updated_at;
                $returnArray["service_name"] = !empty($result[0][2]->service_name) ? $result[0][2]->service_name : "";
                $returnArray["service_description"] = !empty($result[0][2]->service) ? $result[0][2]->service : "";
                $returnArray["service_created_at"] = $service_created_at;
                $returnArray['points_awarded'] = Config::get('constants.POINTS.SEEK_REFERRAL');
                //post details from web
                $returnArray["service_from_web"] = !empty($result[0][2]->service_from_web) ? $result[0][2]->service_from_web : 0;
                #get company details here
                if (isset($input['post_id'])) {
                    $companyDetails = $this->neoPostRepository->getPostCompany($input['post_id']);
                }
                $companyLogo = !empty($companyDetails->logo) ? $companyDetails->logo : '';
                $companyName = !empty($companyDetails->name) ? $companyDetails->name : '';
                $returnArray["company_logo"] = $companyLogo;
                $returnArray["company_name"] = $companyName;

                if ($isReferredUser) {//p3 is veiwing it
                    if (isset($returnArray['bestfit_message'])) {
                        unset($returnArray['bestfit_message']);
                    }
                }
                $data = array("status_details" => $returnArray);
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data);
            }
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_post')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function getMyReferralContacts($input) {
        $selfReferred = 0;
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $input['email'] = $userEmail = $this->neoLoggedInUserDetails->emailid;
        $postReferralsResult = $this->referralsRepository->getPostReferrals($input['post_id'], $userEmail);
        $referrals = $users = $nonMintmeshReferrals = array();
        if (count($postReferralsResult)) {
            foreach ($postReferralsResult as $k => $v) {
                $referrals[] = $v[0]->emailid;
                if ($v[0]->emailid == $this->loggedinUserDetails->emailid) {//i.e if seld referred
                    $selfReferred = 1;
                }
            }
        }
        $result = $this->referralsRepository->getMyReferralContacts($input);
        if (count($result)) {
            $setLimit = 0;
            $maxLimit = !empty($input['suggestion']) ? 10 : true; //if request for suggestion, set max limit otherwise no limit
            foreach ($result as $k1 => $v1) {
                if (!in_array($v1[0]->emailid, $referrals) && ++$setLimit <= $maxLimit) {
                    if ($v1[0]->emailid != $input['other_email']) {
                        $users[] = $this->userGateway->formUserDetailsArray($v1[0], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                    }
                }
            }
            //get non mintmesh users who already got referred
            $nonMintmeshUsersResult = $this->referralsRepository->getMyNonMintmeshReferrals($input['post_id'], $userEmail);
            if (count($nonMintmeshUsersResult)) {
                foreach ($nonMintmeshUsersResult as $k => $v) {

                    if (empty($v[1][1]))//this skips the mintmesh users, i.e label with user:Mintmesh
                        $nonMintmeshReferrals[] = $this->userGateway->formUserDetailsArray($v[0], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                }
            }
            $data = array("users" => $users, "self_referred" => $selfReferred, "non_mintmesh_referrals" => $nonMintmeshReferrals);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        }
        else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_result')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function searchPeople($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        if (!empty($input['access_token']))
            unset($input['access_token']);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        /* if (!empty($input))
          { */
        $result = $this->referralsRepository->searchPeople($userEmail, $input);
        if (count($result)) {
            $users = array();
            foreach ($result as $k => $v) {
                if ($v[0]->emailid != $userEmail) {//ignore if logged in user
                    $a = $this->userGateway->formUserDetailsArray($v[0], 'property');
                    //check staus
                    $statusRes = $this->neoUserRepository->getRequestStatus($userEmail, '', $v[0]->emailid, Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'));
                    if (!empty($statusRes)) {// if pending
                        if ($statusRes['status'] == Config::get('constants.REFERENCE_STATUS.PENDING') || $statusRes['status'] == Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE')) {
                            if ($statusRes['status'] != Config::get('constants.REFERENCE_STATUS.PENDING')) {
                                //check if declined at other side
                                $otherStatusRes = $this->neoUserRepository->getRequestStatus('', $v[0]->emailid, $userEmail, Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'));
                                if (!empty($otherStatusRes)) {
                                    if ($otherStatusRes['status'] == Config::get('constants.REFERENCE_STATUS.DECLINED')) {
                                        $a['request_sent_at'] = 0;
                                        $a['connected'] = 0;
                                    } else {
                                        $a['request_sent_at'] = $statusRes['created_at'];
                                        $a['connected'] = 2;
                                    }
                                } else {
                                    $a['request_sent_at'] = $statusRes['created_at'];
                                    $a['connected'] = 2;
                                }
                            } else {
                                $a['request_sent_at'] = $statusRes['created_at'];
                                $a['connected'] = 2;
                            }
                        } else {
                            $a['connected'] = 0;
                            $a['request_sent_at'] = 0;
                        }
                    } else {
                        $a['connected'] = 0;
                        $a['request_sent_at'] = 0;
                    }
                    //get known list
                    $knownPeopleInput = $knownPeopleListResult = $knownPeopleList = array();
                    $knownPeopleInput['other_email'] = !empty($a['emailid']) ? $a['emailid'] : '';
                    if (!empty($knownPeopleInput['other_email'])) {
                        $knownPeopleListResult = $this->getMutualPeople($knownPeopleInput);
                        if (!empty($knownPeopleListResult['data']['users']))
                            foreach ($knownPeopleListResult['data']['users'] as $r => $v) {
                                $knownPeopleList[] = $v['fullname'];
                            }
                        $a['known_people'] = $knownPeopleList;
                    }

                    $users[] = $a;
                }
            }
            $data = array("users" => $users);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_result')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
        /* }
          else
          {
          $message = array('msg'=>array(Lang::get('MINTMESH.referrals.invalid_input')));
          return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
          } */
    }

    public function getMutualPeople($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $result = $this->referralsRepository->getMutualPeople($userEmail, $input['other_email']);
        if (count($result)) {
            $users = array();
            foreach ($result as $k => $v) {
                $users[] = $this->userGateway->formUserDetailsArray($v[0], 'property');
            }
            $data = array("users" => $users);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_result')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function getAllReferrals($input) {
        $return = array();
        $loggedinUserDetails = $this->getLoggedInUser();
        $posts = array();
        if ($loggedinUserDetails) {
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid);
            $return['userDetails'] = $this->userGateway->formUserDetailsArray($neoLoggedInUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
            $userEmail = !empty($loggedinUserDetails->emailid) ? $loggedinUserDetails->emailid : '';
            $page = !empty($input['page']) ? $input['page'] : 0;
            $relationDetails = $this->referralsRepository->getAllReferrals($userEmail, $page);
            $return['referrals'] = array();
            if (count($relationDetails)) {

                foreach ($relationDetails as $relation) {
                    $enterReferrals = true;
                    $p2Status = Config::get('constants.REFERENCE_STATUS.PENDING');
                    $to_emailid = "";
                    $is_non_mintmesh = 0;
                    $a = $relation[0]->getProperties();
                    if (!empty($relation[1]) && !empty($a)) {
                        $a['relation_type'] = strtolower($relation[1]);
                    }
                    if (!empty($relation[2]) && !empty($a) && !empty($relation[1])) {
                        if ($relation[1] == Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION')) {
                            $toUserDetails = $this->userGateway->formUserDetailsArray($relation[2], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                            //details of third user
                            foreach ($toUserDetails as $k => $v) {
                                $a['other_user_' . $k] = $v;
                            }
                            $to_emailid = $toUserDetails['emailid'];
                            $a['other_status'] = !empty($relation[0]->status) ? $relation[0]->status : Config::get('constants.REFERENCE_STATUS.PENDING');
                            //get details of the person who requested for reference (p1)
                            if (!empty($a['request_for_emailid'])) {
                                //get relation id of the request reference relation

                                $requestR = $this->referralsRepository->getRequestReferenceRelationId($a['request_for_emailid'], $loggedinUserDetails->emailid, $toUserDetails['emailid'], $a['request_count']);
                                if (count($requestR)) {
                                    $a['referral_relation'] = !empty($requestR[0][0]) ? $requestR[0][0] : 0;
                                }
                                $otherUserResult = $this->neoUserRepository->getNodeByEmailId($a['request_for_emailid']);
                                $otherUserDetails = $this->userGateway->formUserDetailsArray($otherUserResult, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                                foreach ($otherUserDetails as $k => $v) {
                                    $a['to_user_' . $k] = $v;
                                }
                            }
                        } else if ($relation[1] == Config::get('constants.REFERRALS.GOT_REFERRED')) {
                            //get details of the person who got referred(p3)
                            $toUserDetails = $this->userGateway->formUserDetailsArray($relation[3], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                            //if (!empty($toUserDetails['emailid']) && $toUserDetails['emailid'] == 'ugh@gmail.com'){
                            //echo "Fds";exit;}
                            foreach ($toUserDetails as $k => $v) {
                                $a['to_user_' . $k] = $v;
                            }
                            $postDetails = $this->formPostDetailsArray($relation[2]); //$relation[2]->getProperties() ;
                            $postId = $relation[2]->getId();
                            if (!in_array($postId, $posts)) {
                                $posts[] = $postId;
                            } else {
                                $enterReferrals = false;
                            }

                            foreach ($postDetails as $k => $v) {
                                $a['post_details_' . $k] = $v;
                            }
                            //get details of the person who created the post(p1)
                            if (!empty($a['post_details_created_by'])) {
                                $otherUserResult = $this->neoUserRepository->getNodeByEmailId($a['post_details_created_by']);
                                $otherUserDetails = $this->userGateway->formUserDetailsArray($otherUserResult, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                                foreach ($otherUserDetails as $k => $v) {
                                    $a['other_user_' . $k] = $v;
                                }
                            }
                            //add if referred by phone number
                            if ($relation[4][0] == 'NonMintmesh') {
                                $a['referred_by_phone'] = 1;
                                $is_non_mintmesh = 1;
                                if (!empty($relation[3]->phone))
                                    $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByPhone($loggedinUserDetails->emailid, $relation[3]->phone);
                            }else if (!empty($relation[4][1]) && $relation[4][1] == 'Mintmesh') {
                                $a['referred_by_phone'] = 0;
                            } else {
                                $a['referred_by_phone'] = 0;
                                $is_non_mintmesh = 1;
                                if (!empty($relation[3]->emailid))
                                    $nonMintmeshUserDetails = $this->contactsRepository->getImportRelationDetailsByEmail($loggedinUserDetails->emailid, $relation[3]->emailid);
                            }
                            //form user name details for non mintmesh contacts
                            if (!empty($nonMintmeshUserDetails) || !empty($is_non_mintmesh)) {
                                if (!empty($nonMintmeshUserDetails->fullname)) {
                                    $nonMintmeshUserDetails->fullname = trim($nonMintmeshUserDetails->fullname);
                                }
                                $a['to_user_firstname'] = !empty($nonMintmeshUserDetails->firstname) ? $nonMintmeshUserDetails->firstname : Lang::get('MINTMESH.user.non_mintmesh_user_name');
                                $a['to_user_lastname'] = !empty($nonMintmeshUserDetails->lastname) ? $nonMintmeshUserDetails->lastname : Lang::get('MINTMESH.user.non_mintmesh_user_name');
                                $a['to_user_fullname'] = !empty($nonMintmeshUserDetails->fullname) ? $nonMintmeshUserDetails->fullname : Lang::get('MINTMESH.user.non_mintmesh_user_name');
                            }
                            $a['post_id'] = $postId;
                            $a['post_status'] = !empty($postDetails['status']) ? strtolower($postDetails['status']) : '';
                            $a['referrals_count'] = $this->referralsRepository->getPostMyReferralsCount($userEmail, $postId);
                            $a['other_status'] = !empty($relation[0]->one_way_status) ? $relation[0]->one_way_status : Config::get('constants.REFERENCE_STATUS.PENDING');
                        }
                    }
                    if ($enterReferrals)
                        $return['referrals'][] = $a;
                }
            }
            #sort the referrals
            $sort_by = 'created_at';
            $sort_order = SORT_DESC;
            $a = $this->appEncodeDecode->array_sort($return['referrals'], $sort_by, $sort_order);
            $return['referrals'] = array_values($a);
            //print_r($a);exit;
            $data = array("my_referrals" => $return);
            $message = array('msg' => array(Lang::get('MINTMESH.get_requests.success')));
            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data);
        }
        else {
            $responseMessage = Lang::get('MINTMESH.user.user_not_found');
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseStatus = self::ERROR_RESPONSE_MESSAGE;
            $responseData = array();
        }
        $message = array('msg' => array($responseMessage));
        return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData);
    }

    public function getReferralsCash($input) {
        $timeZone = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $page = 0;

        if (!empty($input['page'])) {
            $page = $input['page'];
        }
        $result = $this->paymentRepository->getPaymentTransactions($this->loggedinUserDetails->emailid, $input['payment_reason'], $page);
        $total_cash = 0;
        if (count($result)) {
            $returnArray = array();
            $total_cash_res = $this->paymentRepository->getPaymentTotalCash($this->loggedinUserDetails->emailid, $input['payment_reason']);
            if (!empty($total_cash_res)) {
                $total_cash = $total_cash_res[0]->total_cash;
            }
            foreach ($result as $res) {
                $r['post_id'] = $res->service_id;
                $postDetailsR = $this->referralsRepository->getPostAndReferralDetails($res->service_id, $this->loggedinUserDetails->emailid, $res->for_user);
                if (count($postDetailsR)) {
                    $r['relation_count'] = isset($postDetailsR[0][0]) ? $postDetailsR[0][0]->relation_count : array();
                }
                $fromUser = $this->neoUserRepository->getNodeByEmailId($res->from_user);

                $last_modified_at = !empty($res->last_modified_at) ? $res->last_modified_at : '';
                if ($last_modified_at) {
                    $last_modified_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($last_modified_at, $timeZone)));
                }

                $r['from_email'] = $res->from_user;
                $r['for_email'] = $res->for_user;
                $r['my_email'] = $res->to_user;
                $r['created_at'] = $last_modified_at;
                $r['amount'] = $res->amount;
                if (!empty($this->neoLoggedInUserDetails->phone_country_name) && strtolower($this->neoLoggedInUserDetails->phone_country_name) == 'india') {
                    $r['currency'] = Config::get('constants.PAYMENTS.CURRENCY.INR');
                    if ($res->payment_type == 1) {//i.e brain tree
                        //convert cash to indian rupee
                        $r['amount'] = $this->paymentGateway->convertUSDToINR($res->amount);
                    }
                } else {
                    $r['currency'] = Config::get('constants.PAYMENTS.CURRENCY.USD');
                    if ($res->payment_type == 2) {//i.e citrus
                        //convert cash to indian rupee
                        $r['amount'] = $this->paymentGateway->convertINRToUSD($res->amount);
                    }
                }
                $fromUserDetails = $this->userGateway->formUserDetailsArray($fromUser);
                if (!empty($fromUserDetails)) {
                    foreach ($fromUserDetails as $k => $v) {
                        $r['from_user_' . $k] = $v;
                    }
                }
                $forUser = $this->neoUserRepository->getNodeByEmailId($res->for_user);
                if (!count($forUser)) {//i.e if details not found in email users list
                    $forUser = $this->neoUserRepository->getNonMintmeshUserDetails($res->for_user); //find details in non mintmesh users list
                    $forUserDetails = $this->userGateway->formUserDetailsArray($forUser, 'property');
                } else {
                    $forUserDetails = $this->userGateway->formUserDetailsArray($forUser);
                }
                if (!empty($forUserDetails)) {
                    foreach ($forUserDetails as $k => $v) {
                        $r['for_user_' . $k] = $v;
                    }
                }
                $returnArray[] = $r;
            }

            $data = array("referrals" => $returnArray, "total_cash" => $total_cash);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_result')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    public function testIndex() {
        $this->referralsRepository->testIndex();
    }

    public function getServiceDetailsByCode($serviceCode = '') {
        $returnArray = array();
        if (!empty($serviceCode)) {

            $serviceDetails = $this->referralsRepository->getServiceDetailsByCode($serviceCode);
            if ($serviceDetails->count()) {
                foreach ($serviceDetails as $key => $val) {
                    $returnArray['user_name'] = !empty($val[0]->fullname) ? $val[0]->fullname : '';
                    $returnArray['service_points'] = Config::get('constants.POINTS.SEEK_REFERRAL');
                    $serviceInfo = $val[1]->getproperties();
                    foreach ($serviceInfo as $k => $v) {
                        $returnArray[$k] = $v;
                    }
                }
            }
        }
        return $returnArray;
    }

    //get all posts of a type
    public function getPostsV3($input) {
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $userCountry = $this->neoLoggedInUserDetails->phone_country_name;
        $page = !empty($input['page_no']) ? $input['page_no'] : 0;
        $search = !empty($input['search']) ? $input['search'] : '';
        $request_type = isset($input['request_type']) ? $input['request_type'] : '';
        $posts = $this->referralsRepository->getAllPostsV3($userEmail, $request_type, $page, $search);
        $postIds = $excludedPostsList = array();
        if (!empty(count($posts))) {
            $returnPosts = array();
            foreach ($posts as $post) {
                //if($post[0]->created_by != $this->loggedinUserDetails->emailid) {
                $postDetails = $this->formPostDetailsArray($post[0]);
                //$postDetails['no_of_referrals'] = !empty($post[1])?$post[1]:0 ;
                $postDetails['no_of_referrals'] = $this->referralsRepository->getPostMyReferralsCount($userEmail, $postDetails['post_id']);
                $postDetails['company_logo'] = !empty($post[2]->logo) ? $post[2]->logo : '';
                $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']);
                $postDetails['rewards'] = $this->getPostRewards($postDetails['post_id'], $userCountry);
                $postDetails['UserDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                $returnPosts[$postDetails['post_id']] = $postDetails;
                $postIds[] = $postDetails['post_id'];
                //}
            }
            /* if (!empty($postIds)){
              // print_r($postIds);exit;
              //get all my excluded list
              $excludedPostsList = $this->getExcludedPostsList($this->loggedinUserDetails->emailid, $postIds);
              }
              //subtract and unset the posts which are excluded from the return list
              if (!empty($excludedPostsList)){
              $returnPosts = $this->subtractExlucedPosts($returnPosts, $excludedPostsList);
              //get referrals count for each post
              $returnPosts = $this->getReferralsListCounts($this->loggedinUserDetails->emailid, $returnPosts);
              } */
            //print_r($returnPosts).exit;
            //get referrals count for each post
            //$returnPosts = $this->getReferralsListCounts($this->loggedinUserDetails->emailid, $returnPosts);
            $data = array("posts" => array_values($returnPosts));

            //pagination
            $show_per_page = 10;
            $offset = 0;
            if (!empty($page)) {
                $page = $page - 1;
                $offset = $page * $show_per_page;
            }
            usort($data['posts'], array($this, "createdAtDesc"));
            $data = array_splice($data['posts'], $offset, $show_per_page);
            $data = array("posts" => array_values($data));

            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_posts')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
            //echo round(microtime(true) * 1000);exit;
        }
    }

    public function createdAtDesc($a, $b) {
        return strtotime($b["created_at"]) - strtotime($a["created_at"]);
    }

    public function getExcludedPostsList($userEmail = '', $postIds = array()) {
        $returnPostIds = array();
        if (!empty($userEmail)) {
            $postIds = $this->referralsRepository->getExcludedPostsList($userEmail, $postIds);
            foreach ($postIds as $val) {
                $returnPostIds[] = $val['post_id'];
            }
            return $returnPostIds;
        }
    }

    public function subtractExlucedPosts($totalPosts = array(), $excludedPosts = array()) {
        foreach ($excludedPosts as $postId) {
            if (isset($totalPosts[$postId])) {
                unset($totalPosts[$postId]); //remove the excluded list
            }
        }
        return $totalPosts;
    }

    public function getReferralsListCounts($userEmail = '', $totalPosts = array()) {
        $returnPostIds = array();
        if (!empty($userEmail)) {
            $postIds = array_keys($totalPosts);
            $postReferralsCounts = $this->referralsRepository->getReferralsListCounts($userEmail, $postIds);
            foreach ($postReferralsCounts as $val) {
                if (!empty($totalPosts[$val[0]])) {
                    $temp = $totalPosts[$val[0]];
                    $temp['no_of_referrals'] = $val[1];
                    $totalPosts[$val[0]] = $temp;
                    //echo $val[0] ;
                    //print_r($totalPosts[$val[0]]);exit;
                }
            }
        }
        return $totalPosts;
    }

    /*
     * send push notifications to the contacts when ever a new request is posted
     */

    public function sendPushNotificationsForPosts($serviceId = 0, $fromUser = array(), $neofromUser = array(), $includedList = array(), $excludedList = array(), $serviceType = 'global', $serviceLocation = '', $notification_type = 0, $serviceName = '') {
        if (!empty($fromUser) && !empty($serviceId)) {
            $fromUser = (object) $fromUser;
            $neofromUser = (object) $neofromUser;
            #check if included list is set or not so that the notification will be sent to only included list
            if (!empty($includedList)) {
                foreach ($includedList as $receiverEmailId) {
                    $this->userGateway->sendNotification($fromUser, $neofromUser, $receiverEmailId, $notification_type, array('extra_info' => $serviceId, 'other_message' => $serviceName), array(), 1, 0);
                }
            } else {
                $contactsListResult = $this->referralsRepository->getMyConnectionForNewPostPush($fromUser->emailid, $serviceType, $serviceLocation, $excludedList);
                if (!empty($contactsListResult)) {
                    foreach ($contactsListResult as $contact) {
                        if ($contact[0] != $fromUser->emailid) {//if not me
                            $this->userGateway->sendNotification($fromUser, $neofromUser, $contact[0], $notification_type, array('extra_info' => $serviceId, 'other_message' => $serviceName), array(), 1, 0);
                        }
                    }
                }
            }
        }
    }

    /*
     * refer contact v2 version
     */

    public function referContactV2($input) {

        #if enterprise user flag
        $isEnt = !empty($input['is_ent']) ? $input['is_ent'] : 0;
        $referNonMintmesh = $nonMintmeshPhoneRefer = 0;
        $uploadedByP2 = $documentId = 0;
        $p3CvOriginalName = "";
        $referResumePath = "";
        $this->loggedinUserDetails = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail = $this->neoLoggedInUserDetails->emailid;
        $userId = $this->loggedinUserDetails->id;
        $relationCount = 1;
        //count for all relations
        $existingRelationCount = $this->referralsRepository->getReferralsCount(Config::get('constants.REFERRALS.GOT_REFERRED'), $input['post_id'], $userEmail, $input['refer_to']);
        if ($existingRelationCount < Config::get('constants.REFERRALS.MAX_REFERRALS')) {
            # process resume for hire a candidate service scope
            if (!empty($input['is_hire_candidate'])) {
                #process resume fields
                if (!empty($isEnt)) {
                    $postId = !empty($input['post_id']) ? $input['post_id'] : '';
                    $companyDetils = $this->neoPostRepository->getPostCompany($postId);
                    $companyDetils->companyCode;
                    #get company details by code
                    $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyDetils->companyCode);
                    $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
                    #for enterprise app
                    $resumeResult = $this->processResumeForEnterpriseRefer($input, $companyId, $userId);
                    $documentId   = !empty($resumeResult['document_id']) ? $resumeResult['document_id'] : 0;
                } else {
                    #for consumer app
                    $resumeResult = $this->processResumeForRefer($input);
                }
                if (!$resumeResult['status']) {
                    return $this->commonFormatter->formatResponse(406, "error", $resumeResult['message'], array());
                } else {
                    $referResumePath = $resumeResult['resume_path'];
                    $uploadedByP2 = $resumeResult['uploaded'];
                    $p3CvOriginalName = $resumeResult['resume_original_name'];
                }
            }
            if (!empty($input['refer_non_mm_email']) && !empty($input['referring'])) {
                $nonMintmeshPhoneRefer = 1;
            }
            //continue only when the request count is in limit
            //create a relation between the post and user
            $oldRelationCount = $this->referralsRepository->getOldRelationsCount($input['post_id'], $input['referring'], $nonMintmeshPhoneRefer);
            if (!empty($oldRelationCount)) {
                $relationCount = $oldRelationCount + 1;
                //$message = array('msg'=>array(Lang::get('MINTMESH.referrals.already_referred')));
                //return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }

            if (!empty($input['refer_non_mm_email']) && !empty($input['referring'])) {//non mintmesh and refer 
                if (!empty($input['referring_phone_no'])) {//create node for this and relate
                    $input['referring'] = $this->appEncodeDecode->formatphoneNumbers(strtolower($input['referring']));

                    $nonMintmeshContactExist = $this->contactsRepository->getNonMintmeshContact($input['referring']);
                    //$nonMintmeshContactExist = $this->appEncodeDecode->formatphoneNumbers($input['referring']);
                    //print_r($nonMintmeshContactExist).exit;
                    $phoneContactInput = $phoneContactRelationInput = array();
                    $phoneContactInput['firstname'] = $phoneContactInput['lastname'] = $phoneContactInput['fullname'] = "";
                    $phoneContactRelationInput['firstname'] = !empty($input['referring_user_firstname']) ? $this->appEncodeDecode->filterString($input['referring_user_firstname']) : '';
                    $phoneContactRelationInput['lastname'] = !empty($input['referring_user_lastname']) ? $this->appEncodeDecode->filterString($input['referring_user_lastname']) : '';
                    $phoneContactRelationInput['fullname'] = $phoneContactRelationInput['firstname'] . " " . $phoneContactRelationInput['lastname'];
                    $phoneContactInput['phone'] = !empty($input['referring']) ? $this->appEncodeDecode->formatphoneNumbers($input['referring']) : '';
                    if (!empty($nonMintmeshContactExist)) {
                        //create import relation
                        $relationCreated = $this->contactsRepository->relateContacts($this->neoLoggedInUserDetails, $nonMintmeshContactExist[0], $phoneContactRelationInput, 1);
                    } else {
                        $importedContact = $this->contactsRepository->createNodeAndRelationForPhoneContacts($userEmail, $phoneContactInput, $phoneContactRelationInput);
                    }
                    //send sms invitation to p3
                    #not send to enterprise user 
                    if (empty($isEnt)) {
                        $smsInput = array();
                        $smsInput['numbers'] = json_encode(array($phoneContactInput['phone']));
                        $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($input['refer_to']);
                        $smsInput['other_name'] = !empty($otherUserDetails->fullname) ? $otherUserDetails->fullname : "";
                        $smsInput['sms_type'] = 3;
                        $smsSent = $this->smsGateway->sendSMSForReferring($smsInput);
                    }
                    $referNonMintmesh = 1;
                } else {
                    //check if p2 imported p3
                    $isImported = $this->contactsRepository->getContactByEmailid($input['referring']);
                    if (empty($isImported)) {
                        $message = array('msg' => array(Lang::get('MINTMESH.referrals.no_import')));
                        return $this->commonFormatter->formatResponse(406, "error", $message, array());
                    } else {//send invitation email to p3
                        #not send to enterprise user 
                        if (empty($isEnt)) {
                            $postInvitationArray = array();
                            $postInvitationArray['emails'] = json_encode(array($input['referring']));
                            $postInvitationArray['post_id'] = $input['post_id'];
                            $invited = $this->contactsGateway->sendPostReferralInvitations($postInvitationArray);
                        }
                    }
                }
            }
            $relationAttrs = array();
            $relationAttrs['document_id'] = $documentId;
            $relationAttrs['referred_by'] = strtolower($userEmail);
            $relationAttrs['referred_for'] = strtolower($input['refer_to']);
            $relationAttrs['created_at'] = gmdate("Y-m-d H:i:s");
            $relationAttrs['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
            $relationAttrs['one_way_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
            if (!empty($input['message'])) {
                $relationAttrs['message'] = $input['message'];
            }
            if (!empty($input['bestfit_message'])) {
                $relationAttrs['bestfit_message'] = $input['bestfit_message'];
            }
            $relationAttrs['relation_count'] = $relationCount;
            $relationAttrs['resume_path'] = $referResumePath;
            $relationAttrs['uploaded_by_p2'] = $uploadedByP2;
            $relationAttrs['resume_original_name'] = $p3CvOriginalName;
            if (!empty($referNonMintmesh)) {
                $result = $this->referralsRepository->referContactByPhone($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
            } else {
                $result = $this->referralsRepository->referContact($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
            }
            if (!empty($result)) {
                
                #update Got Referred Id in CompanyResumes table
                if (isset($result[0]) && !empty($result[0][1])) {
                    $gotReferredId = $result[0][1];
                    $this->enterpriseRepository->updateCompanyResumesWithGotReferredId($documentId, $gotReferredId);
                }
//                  if self referrence
                if ($this->loggedinUserDetails->emailid == $input['referring']) {
                    $notificationType = 23;
                } else {
                    $notificationType = 10;
                }
                //send notification to the person who created post
                $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['refer_to'], $notificationType, array('extra_info' => $input['post_id']), array('other_user' => $input['referring'], 'p3_non_mintmesh' => 1));
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array());
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.closed_post')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array());
            }
        } else {
            //return limit crossed message
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.limit_crossed')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }
    
    public function enterpriseReferContact($input) {

        #if enterprise user flag
        $isEnt = !empty($input['is_ent']) ? $input['is_ent'] : 0;
        $referNonMintmesh   = $nonMintmeshPhoneRefer = 0;
        $uploadedByP2       = $documentId = $isAlreadyReferred = 0;
        $p3CvOriginalName   = $referResumePath = $s3_path = "";
        $relationCount      = 1;
        $postId = !empty($input['post_id']) ? $input['post_id'] : '';
        
        $this->loggedinUserDetails      = $this->getLoggedInUser();
        $this->neoLoggedInUserDetails   = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $userEmail      = $this->neoLoggedInUserDetails->emailid;
        $neoUserId      = $this->neoLoggedInUserDetails->id;
        $neoUserName    = $this->neoLoggedInUserDetails->firstname;
        $userId         = $this->loggedinUserDetails->id;
        $isSelfReferral = ($this->loggedinUserDetails->emailid == $input['referring']) ? 1 : 0;
        
        
        $firstname = !empty($input['referring_user_firstname']) ? $input['referring_user_firstname'] : '';
        $lastname  = !empty($input['referring_user_lastname']) ? $input['referring_user_lastname'] : '';
            
        #count for all relations
        $existingRelationCount = $this->referralsRepository->getReferralsCount(Config::get('constants.REFERRALS.GOT_REFERRED'), $input['post_id'], $userEmail, $input['refer_to']);
        if ($existingRelationCount < Config::get('constants.REFERRALS.MAX_REFERRALS')) {
            #check user already got referred or not
            $isAlreadyReferred = $this->referralsRepository->isUserAlreadyReferred($postId, $input['referring'], $userEmail);
            if (empty($isAlreadyReferred)) {
                # process resume for hire a candidate service scope
                if (!empty($input['is_hire_candidate'])) {
                    #process resume fields
                    $companyDetils = $this->neoPostRepository->getPostCompany($postId);
                    $companyDetils->companyCode;
                    #get company details by code
                    $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyDetils->companyCode);
                    $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
                    #for enterprise app
                    $resumeResult = $this->processResumeForEnterpriseRefer($input, $companyId, $userId);
                    $documentId   = !empty($resumeResult['document_id']) ? $resumeResult['document_id'] : 0;
                    $s3_path      = !empty($resumeResult['s3_path']) ? $resumeResult['s3_path'] : '';

                    if (!$resumeResult['status']) {
                        return $this->commonFormatter->formatResponse(406, "error", $resumeResult['message'], array());
                    } else {
                        $referResumePath    = $resumeResult['resume_path'];
                        $uploadedByP2       = $resumeResult['uploaded'];
                        $p3CvOriginalName   = $resumeResult['resume_original_name'];
                    }
                }
                if (!empty($input['refer_non_mm_email']) && !empty($input['referring'])) {
                    $nonMintmeshPhoneRefer = 1;//continue only when the request count is in limit
                }
                #create a relation between the post and user
                $oldRelationCount = $this->referralsRepository->getOldRelationsCount($input['post_id'], $input['referring'], $nonMintmeshPhoneRefer);
                if (!empty($oldRelationCount)) {
                    $relationCount = $oldRelationCount + 1;
                }
                #non mintmesh and refer 
                if (!empty($input['refer_non_mm_email']) && !empty($input['referring'])) {
                    #p3 user details
                    $neoContactInput = $neoContactRelInput = array();
                    $neoContactInput['firstname'] = $neoContactInput['lastname'] = $neoContactInput['fullname'] = "";
                    #relation details
                    $neoContactRelInput['firstname'] = $firstname;
                    $neoContactRelInput['lastname']  = $lastname;
                    $neoContactRelInput['fullname']  = $firstname . " " . $lastname;
                    #create node for this and relate
                    if (!empty($input['referring_phone_no'])) {

                        $input['referring']         = $this->appEncodeDecode->formatphoneNumbers(strtolower($input['referring']));
                        $nonMintmeshContactExist    = $this->contactsRepository->getNonMintmeshContact($input['referring']);
                        $phoneContactInput['phone'] = !empty($input['referring']) ? $this->appEncodeDecode->formatphoneNumbers($input['referring']) : '';
                        #create import relation
                        if (!empty($nonMintmeshContactExist)) {
                            $relationCreated = $this->contactsRepository->relateContacts($this->neoLoggedInUserDetails, $nonMintmeshContactExist[0], $neoContactRelInput, 1);
                        } else {
                            $importedContact = $this->contactsRepository->createNodeAndRelationForPhoneContacts($userEmail, $phoneContactInput, $neoContactRelInput);
                        }
                        $referNonMintmesh = 1;
                    } else {
                        #check User Node already there or not
                        $referringNodeId = $this->referralsRepository->checkUserNodeWithEmailid($input['referring']);
                        if(!empty($referringNodeId)){
                            #check if p2 imported p3
                            $this->contactsRepository->createUserRelationWithEmailid($userEmail, $referringNodeId, $neoContactRelInput);
                        } else {//send invitation email to p3
                            $neoContactInput['emailid'] = $input['referring'];
                            #create User Node here
                            $referringNodeId = $this->contactsRepository->createUserNodeAndRelationWithEmailid($userEmail, $neoContactInput, $neoContactRelInput);
                        }
                    }
                }
                #Neo4j GOT_REFERRED relation attributes
                $relationAttrs = array();
                $relationAttrs['document_id']       = $documentId;
                $relationAttrs['referred_by']       = strtolower($userEmail);
                $relationAttrs['referred_for']      = strtolower($input['refer_to']);
                $relationAttrs['created_at']        = gmdate("Y-m-d H:i:s");
                $relationAttrs['status']            = Config::get('constants.REFERRALS.STATUSES.PENDING');
                $relationAttrs['one_way_status']    = Config::get('constants.REFERRALS.STATUSES.PENDING');
                $relationAttrs['relation_count']    = $relationCount;
                $relationAttrs['resume_path']       = !empty($s3_path) ? $s3_path : $referResumePath;
                $relationAttrs['uploaded_by_p2']    = $uploadedByP2;
                $relationAttrs['resume_original_name']  = $p3CvOriginalName;
                if (!empty($input['message'])) {
                    $relationAttrs['message'] = $input['message'];
                }
                if (!empty($input['bestfit_message'])) {
                    $relationAttrs['bestfit_message'] = $input['bestfit_message'];
                }
                #creating GOT_REFERRED relation here
                if (!empty($referNonMintmesh)) {
                    $result = $this->referralsRepository->referContactByPhone($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
                } else {
                    #here creating GOT_REFERRED relation with emailid
                    $result = $this->referralsRepository->referContact($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
                }
                #check result
                if (isset($result[0]) && !empty($result[0][1])) {
                    $gotReferredId = $result[0][1];
                    #update Got Referred Id in CompanyResumes table
                    if (!empty($documentId)) {
                        $this->enterpriseRepository->updateCompanyResumesWithGotReferredId($documentId, $gotReferredId);
                    }
                    #if self referrence
                    $notificationType = ($isSelfReferral) ? 23 : 10;
                    #send notification to the person who created post
                    $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['refer_to'], $notificationType, array('extra_info' => $input['post_id']), array('other_user' => $input['referring'], 'p3_non_mintmesh' => 1));
                    
                    #send email to p3
                    $posts  = $this->neoPostRepository->getPosts($postId);
                    if(empty($referResumePath) || $posts->post_type == 'campaign'){
                        
                        $referring = $input['referring'];
                        $companyLogoWidth = $companyLogoHeight = '';
                        $companyDetils = $this->neoPostRepository->getPostCompany($postId);
                        #company logo Aspect Ratio details for email template
                        if(!empty($companyDetils->logo)){
                            $companyLogoAspectRatio = $this->getImageAspectRatio($companyDetils->logo);
                            $companyLogoWidth       = !empty($companyLogoAspectRatio['width']) ? $companyLogoAspectRatio['width'] : '';
                            $companyLogoHeight      = !empty($companyLogoAspectRatio['height']) ? $companyLogoAspectRatio['height'] : '';
                        }
                        #for reply emailid 
                        $replyToName = Config::get('constants.MINTMESH_SUPPORT.REFERRER_NAME');
                        $replyToHost = Config::get('constants.MINTMESH_SUPPORT.REFERRER_HOST');       
                        #send email notifications to all the contacts
                        $refId = $refCode = 0;
                        $emailData  = array();
                        $refId = $this->neoPostRepository->getUserNodeIdByEmailId($userEmail);
                        $refCode                            = MyEncrypt::encrypt_blowfish($postId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));
                        $replyToData                        = '+ref='.$refCode;
                        $refRelCode                         = MyEncrypt::encrypt_blowfish($postId.'_'.$gotReferredId,Config::get('constants.MINTMESH_ENCCODE'));
                        $emailData['company_name']          = $companyDetils->name;
                        $emailData['company_code']          = $companyDetils->companyCode;
                        $emailData['post_id']               = $postId;
                        $emailData['company_logo']          = $companyDetils->logo;
                        $emailData['company_logo_width']    = $companyLogoWidth;
                        $emailData['company_logo_height']   = $companyLogoHeight;
                        $emailData['to_firstname']          = $firstname;
                        $emailData['to_lastname']           = $lastname;
                        $emailData['to_emailid']            = $referring;
                        $emailData['from_userid']           = $neoUserId;
                        $emailData['from_emailid']          = $userEmail;
                        $emailData['from_firstname']        = $neoUserName;
                        $emailData['email_template']        = ($isSelfReferral) ? 0 : 1 ;
                        $emailData['attachment_status']     = !empty($referResumePath) ? 1 : 0;
                        $emailData['ip_address']            = $_SERVER['REMOTE_ADDR'];
                        $emailData['ref_code']              = $refCode;
                        $emailData['ref_rel_code']          = $refRelCode;
                        $emailData['reply_to']              = $replyToName.$replyToData.$replyToHost;

                        $this->sendEmailRequestToCandidateForResume($emailData);
                    }       
                    $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array());
                } else {
                    $message = array('msg' => array(Lang::get('MINTMESH.referrals.closed_post')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array());
                }
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.referrals.already_referred')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array());
            }
        } else {
            #return limit crossed message
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.limit_crossed')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array());
        }
    }

    /*
     * validate resume in refer contact
     */

    public function processResumeForRefer($input = array()) {
        $returnBoolean = true;
        $uploaded = 1;
        $resumePath = $resumePathRes = $message = $resumeOriginalName = "";
        $message = Lang::get('MINTMESH.user.no_resume');
        if (!empty($input['refer_non_mm_email']) && empty($input['resume'])) {#for non mintmesh with no resume
            $returnBoolean = false;
        } else if (empty($input['refer_non_mm_email']) && empty($input['resume'])) {#for mintmesh with no resume
            #check if the referring person is having resume attached
            $resumePathResult = $this->neoUserRepository->getMintmeshUserResume($input['referring']);
            if (empty($resumePathResult)) {# if no resume uploaded in profile then ask for resume
                $returnBoolean = false;
            } else {
                $uploaded = 0;
                $resumePath = $resumePathResult['cvRenamedName'];
                $resumeOriginalName = $resumePathResult['cvOriginalName'];
            }
        } else if (!empty($input['refer_non_mm_email']) && !empty($input['resume'])) {#for non mintmesh with resume
            $resumePathRes = $this->userGateway->uploadResumeForRefer($input['resume'], 0);
        } else if (empty($input['refer_non_mm_email']) && !empty($input['resume'])) {#for mintmesh with resume
            $resumePathRes = $this->userGateway->uploadResumeForRefer($input['resume'], 1);
        }
        #check for validation of resume
        if ($returnBoolean && !in_array($resumePathRes, $this->resumeValidations)) {# check if resume validations are fine
            $resumePath = $resumePathRes;
            if ($uploaded != 0) {//uploaded
                $resumeOriginalName = $input['resume']->getClientOriginalName();
            }
        } else if (in_array($resumePathRes, $this->resumeValidations)) {
            $returnBoolean = false;
            $message = Lang::get('MINTMESH.user.' . $resumePathRes);
        }
        $msg = array('msg' => array($message));
        return array('status' => $returnBoolean, 'uploaded' => $uploaded, 'resume_path' => $resumePath, 'message' => $msg, 'resume_original_name' => $resumeOriginalName);
    }

    /*
     * validate resume in refer contact
     */

    public function processResumeForEnterpriseRefer($input = array(), $companyId = 0, $userId = 0) {
        
        $documentId     = $uploaded = 0;
        $returnBoolean  = true;
        $resumePath     = $resumePathRes = $message = $resumeOriginalName = $s3_path = "";
        
        if (!empty($input['refer_non_mm_email']) && !empty($input['resume'])) {
            #for non mintmesh user with resume
            $uploaded = 1;
            $responseAry    = $this->userGateway->uploadResumeForEnterpriseRefer($input['resume'], $companyId, $userId);
            $documentId     = !empty($responseAry['document_id']) ? $responseAry['document_id'] : 0;
            $s3_path        = !empty($responseAry['s3_path']) ? $responseAry['s3_path'] : '';
            $resumePathRes  = $responseAry['response'];
            
        } else if (empty($input['refer_non_mm_email']) && !empty($input['resume'])) {
            #for mintmesh user with resume    
            $uploaded = 1;
            $responseAry    = $this->userGateway->uploadResumeForEnterpriseRefer($input['resume'], $companyId, $userId);
            $documentId     = !empty($responseAry['document_id']) ? $responseAry['document_id'] : 0;
            $s3_path        = !empty($responseAry['s3_path']) ? $responseAry['s3_path'] : '';
            $resumePathRes  = $responseAry['response'];
            
        } else if (empty($input['refer_non_mm_email']) && empty($input['resume'])) {
            #for mintmesh user with no resume
            $resumePathResult   = $this->neoUserRepository->getMintmeshUserResume($input['referring']);
            $resumePathRes      = !empty($resumePathResult['cvRenamedName']) ? $resumePathResult['cvRenamedName'] : '';
            $resumeOriginalName = !empty($resumePathResult['cvOriginalName']) ? $resumePathResult['cvOriginalName'] : '';
            
        }
        #check for validation of resume
        if (!in_array($resumePathRes, $this->resumeValidations)) {
            # check if resume validations are fine
            $resumePath = $resumePathRes;
            if ($uploaded == 1) {//uploaded
                $resumeOriginalName = $input['resume']->getClientOriginalName();
            }
        } else if (in_array($resumePathRes, $this->resumeValidations)) {
            $returnBoolean = false;
            $message = Lang::get('MINTMESH.user.' . $resumePathRes);
        }
        $msg = array('msg' => array($message));
        return array('status' => $returnBoolean, 'uploaded' => $uploaded, 'resume_path' => $resumePath, 'message' => $msg, 'resume_original_name' => $resumeOriginalName, 'document_id' => $documentId, 's3_path'=>$s3_path);
    }

    public function getPostRewards($postId = '', $userCountry = '', $isEnterprise = 0) {
        $aryRewards = $rewards = array();
        $tot_points = $tot_cash = 0;
        $postRewards = $this->neoPostRepository->getPostRewards($postId);
        foreach ($postRewards as $value) {
            $rewards = array();
            $relObj = $value[1]; //POST_REWARDS relation
            $valueObj = $value[2]; //REWARDS node
            $relRewardsMode = !empty($relObj->rewards_mode) ? $relObj->rewards_mode : '';
            $currency_type = !empty($valueObj->currency_type) ? $valueObj->currency_type : 0;
            $rewards['rewards_type'] = !empty($valueObj->rewards_type) ? $valueObj->rewards_type : '';
            $rewards['rewards_value'] = !empty($valueObj->rewards_value) ? $valueObj->rewards_value : '';

            if ($rewards['rewards_type'] == 'paid') {

                if (strtolower($userCountry) == "india" && $currency_type == 1) {
                    $rewards['rewards_value'] = $this->paymentGateway->convertUSDToINR($rewards['rewards_value']);
                } else if (strtolower($userCountry) != "india" && $currency_type == 2) {
                    $rewards['rewards_value'] = $this->paymentGateway->convertINRToUSD($rewards['rewards_value']);
                } else {
                    $rewards['rewards_value'];
                }
                $tot_cash += $rewards['rewards_value'];
            } else if ($rewards['rewards_type'] == 'points') {
                $tot_points += $rewards['rewards_value'];
            } else {
                $tot_points += ($isEnterprise) ? 0 : 50; //default 50 points
            }
            $aryRewards[$relRewardsMode] = $rewards;
        }
        $aryRewards['total_points'] = $tot_points;
        $aryRewards['total_cash'] = $tot_cash;
        $aryRewards['currency_type'] = (strtolower($userCountry) == "india") ? 2 : 1;

        return $aryRewards;
    }

    public function viewCampaignApp($campaignId = '', $timeZone = '') {
        
        $page = 0; 
        $search =  $status = '';
        $campSchedule = $returnData = $scheduleRes = $postAry = $postsRes = array();
        #get Campaign Schedule here
        $scheduleRes = $this->neoPostRepository->getCampaignSchedule($campaignId);
        foreach ($scheduleRes as $k => $value) {
            $value = $value[0];
            $schedule['schedule_id'] = $value->getID();
            $schedule['start_on_date'] = date('Y-m-d', strtotime($value->start_date));
            $schedule['start_on_time'] = $value->start_time;
            $schedule['end_on_date'] = date('Y-m-d', strtotime($value->end_date));
            $schedule['end_on_time'] = $value->end_time;
            $campSchedule[] = $schedule;
        }
        $status = Config::get('constants.REFERRALS.STATUSES.ACTIVE');
        #get Campaign Posts here
        $postsRes = $this->neoPostRepository->getCampaignPosts($campaignId,$page, $search, $status);
        $vacancies = 0;
        $jobs_count = $postsRes->count();
        foreach ($postsRes as $posts) {
            $post = array();
            $postDetails = $this->formPostDetailsArray($posts[0]);
            $post['post_id'] = $postDetails['post_id'];
            $post['service_name'] = $postDetails['service_name'];
            $post['experience_range'] = $postDetails['experience_range_name'];
            $post['service_location'] = $postDetails['service_location'];
            $created_at = !empty($postDetails['created_at']) ? $postDetails['created_at'] : '';
            if ($created_at) {
                $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
            }
            $post['created_at'] = $created_at;
            $postAry[] = $post;
            $vacancies +=!empty($postDetails['no_of_vacancies']) ? $postDetails['no_of_vacancies'] : '';
        }
        $returnData['schedule'] = $campSchedule;
        $returnData['jobs'] = $postAry;
        $returnData['jobs_count'] = $jobs_count;
        $returnData['total_vacancies'] = $vacancies;
        return $returnData;
    }

    public function getCampaignDetails($input) {
        #variable declaration
        $campaignsAry = $viewCampRes = $data = $campAry = array();
        $campaignId = $input['campaign_id'];
        $timeZone = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        $encodeString = Config::get('constants.MINTMESH_ENCCODE');
        $enterpriseUrl = Config::get('constants.MM_ENTERPRISE_URL');
        #get Logged In User Details  
        $this->user = $this->getLoggedInUser();
        $userId = !empty($this->user->id) ? $this->user->id : '';
        #log user activity here
        $this->userRepository->addUserActivityLogs($userId, $appType = 1, $moduleType = 4);
        $this->neoUser = $this->neoUserRepository->getNodeByEmailId($this->user->emailid);
        $userEmail = $this->neoUser->emailid;
        $neoUserId = $this->neoUser->id;
        #get campaign result here
        $campaignsAry = $this->referralsRepository->getCampaigns($userEmail, $campaignId);

        if (!empty($campaignsAry) && isset($campaignsAry[0])) {
            $campaign = !empty($campaignsAry[0][0]) ? $campaignsAry[0][0] : '';
            $campRel = !empty($campaignsAry[0][1]) ? $campaignsAry[0][1] : '';
            #get company details here
            $companyCode = $campaign->company_code;
            $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
            $companyLogo = !empty($companyDetails[0]) ? $companyDetails[0]->logo : 0;
            $companyName = !empty($companyDetails[0]) ? $companyDetails[0]->name : '';
            #form return campaign Details here
            $campAry['campaign_id'] = $campaignId;
            $campAry['campaign_name'] = $campaign->campaign_name;
            $campAry['campaign_type'] = $campaign->campaign_type;
            $campAry['location_type'] = $campaign->location_type;
            #attachment details form here
            $campAry['ceos_file'] = $campaign->ceos_file;
            $campAry['emp_file'] = $campaign->emp_file;
            $campAry['cmp_logo'] = $companyLogo;
            $campAry['cmp_name'] = $companyName;
            #get campaign schedule and posts details
            $viewCampRes = $this->viewCampaignApp($campaignId, $timeZone);
            $created_at = !empty($campaign->created_at) ? $campaign->created_at : '';
            if ($created_at) {
                $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
            }
            $campAry['created_at'] = $created_at;
            $campAry['posts_count'] = $viewCampRes['jobs_count'];
            #form location Details here
            if (strtolower($campAry['location_type']) == 'onsite') {
                $location = array();
                $location['address'] = $campaign->address;
                $location['state'] = $campaign->state;
                $location['country'] = $campaign->country;
                $location['city'] = $campaign->city;
                $location['zip_code'] = $campaign->zip_code;
                $campAry['location'] = str_replace(',,', ',', $location);
            }
            $campAry['schedule'] = $viewCampRes['schedule'];
            $campAry['posts'] = $viewCampRes['jobs'];
            #social campaign share link
            $refId = $campaignId . '_' . $neoUserId;
            $refCode = MyEncrypt::encrypt_blowfish($refId, $encodeString);
            $url = $enterpriseUrl . "/email/all-campaigns/share?ref=" . $refCode . "";
            $campAry['social_campaign_share'] = !empty($campRel->bittly_url) ? $campRel->bittly_url : $url;
            #return data here    
            $data = array('campaign_details' => $campAry);
            $message = array('msg' => array(Lang::get('MINTMESH.campaigns.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.campaigns.no_campaigns')));
        }

        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data);
    }

    public function urlShortner($url) {
        $bitly = Config::get('constants.BITLY_URL') . Config::get('constants.BITLY_ACCESS_TOKEN') . '&longUrl=' . $url;
        $fp = '';
        $type = self::CURL_CALL_TYPE;
        $response = $this->curlCall($bitly, $fp, $type);
        $b = (array) json_decode($response, TRUE);
        if (!empty($b['data']['link_save']['link'])) {
            $return = $b['data']['link_save']['link'];
        } else {
            $return = $url;
        }
        return $return;
    }

    public function getAllMyReferrals($input) {
        #variable declaration
        $referralsAry = $companyDetails = $refAry = $returnAry = $data = array();
        $companyCode = $input['company_code'];
        $page = !empty($input['page_no']) ? $input['page_no'] : 0;
        $timeZone = !empty($input['time_zone']) ? $input['time_zone'] : 0;
        $companyLogo = '';
        #get Logged In User Details  
        $this->user = $this->getLoggedInUser();
        $this->neoUser = $this->neoUserRepository->getNodeByEmailId($this->user->emailid);
        $userEmail = $this->neoUser->emailid;
        $neoUserId = $this->neoUser->id;
        $userCountry = $this->neoUser->phone_country_name;
        #get campaign result here
        $referralsAry = $this->referralsRepository->getAllMyReferrals($userEmail, $companyCode, $page);

        if (!empty($referralsAry)) {
            #get company details here
            if (isset($referralsAry[0]) && isset($referralsAry[0][2])) {
                $companyDetails = $referralsAry[0][2];
                $companyLogo = !empty($companyDetails->logo) ? $companyDetails->logo : '';
            }
            $companyAry['company_logo'] = $companyLogo;
            #get referral post details
            foreach ($referralsAry as $value) {
                $refAry = array();
                $value = isset($value[0]) ? $value[0] : '';
                $postId = !empty($value->getID()) ? $value->getID() : '';
                $created_at = !empty($value->created_at) ? $value->created_at : '';
                if ($created_at) {
                    $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
                }
                $refAry['post_id'] = $postId;
                $refAry['post_name'] = !empty($value->service_name) ? $value->service_name : '';
                $refAry['location'] = !empty($value->service_location) ? $value->service_location : '';
                $refAry['created_at'] = $created_at;
                #get the post reward details here
                $postRewards = $this->getPostRewards($postId, $userCountry, $isEnterprise = 1);
                $refAry['rewards'] = $postRewards;

                $returnAry[] = $refAry;
            }
            #return data here    
            $data = array('company_details' => $companyAry, 'referral_details' => $returnAry);
            $message = array('msg' => array(Lang::get('MINTMESH.referrals.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.referral_details.no_referrals')));
        }
        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data);
    }

    public function getZipResumesDownload($input) {

        set_time_limit(0);
        $referralsAry = $resumeFiles = $resumePathfile = $returnAry = $data = array();
        $companyCode = !empty($input['company_id']) ? $input['company_id'] : '';
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        $resumeFiles = $input['resumes'];
        $resumeFiles = explode(",", $resumeFiles);
        $i = 0;
        $target_name = time();
        $target_name .= '.zip';

        //Zip PHP Class
        $zip = new ZipArchive();
        $tmp_file = tempnam('.', '');
        $zip->open($tmp_file, ZipArchive::CREATE);
        foreach ($resumeFiles as $resumeValue) {

            $docId = $resumeValue;
            $resumePathfile = $this->getResumeFilePath($docId, $companyId);

            foreach ($resumePathfile as $resumeFileInfo) {
                $returnAry[$i]['file_source'] = $resumeFileInfo->file_source;
                $returnAry[$i]['file_original_name'] = $resumeFileInfo->file_original_name;
                $download_file = file_get_contents($resumeFileInfo->file_source);
                //var_dump($download_file);
                $zip->addFromString($resumeFileInfo->file_original_name, $download_file);
            }
            $i++;
        }
        # close zip
        $zip->close();
         header('Content-Description: File Transfer');
        header('Content-type: application/zip');
         header('Content-disposition: attachment; filename=' . $target_name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmp_file));
        ob_clean();
        flush();
        readfile($tmp_file);
        unlink($tmp_file);
    }

    public function getResumeFilePath($docId, $companyId) {
        return $this->referralsRepository->getResumeFilePath($docId, $companyId);
    }

    public function getResumeDownload($input) {

        $resumePathfile = array();
        $companyCode = !empty($input['company_id']) ? $input['company_id'] : '';
        #get company details by code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
        $doc_id = $input['doc_id'];
        $resumePathfile = $this->getResumeFilePath($doc_id, $companyId);

        set_time_limit(0);
        $url  = $resumePathfile[0]->file_source;
        $file = 'uploads/'. basename($url);
        $fp   = fopen($file, 'w');
        $type = self::CURL_CALL_TYPE_FILE;
        $data =  $this->curlCall($url,$fp,$type);
        fclose($fp);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $resumePathfile[0]->file_original_name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        unlink($file);

    }
    
    public function curlCall($url='',$fp='',$type=''){
        $data1 = '';
        $fp1 = !empty($fp) ? $fp : 0;
        if($url){
            $ch = curl_init($url);
            if($type == self::CURL_CALL_TYPE_FILE){
                curl_setopt($ch, CURLOPT_FILE, $fp1);
            }else if($type == self::CURL_CALL_TYPE){
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            }
            $data1 = curl_exec($ch);
            curl_close($ch);
        return $data1;
        }
    }
    
    public function getPostRewardsDetils($postId = '') {
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
    
    
    public function sendEmailRequestToCandidateForResume ($emailData = array()) {
      
        if(!empty($emailData)){  
            $dataSet     = array();
            $email_sent  = $linkType = '';
            $postId      = $emailData['post_id'];
            $refCode     = $emailData['ref_code'];
            $refRelCode  = $emailData['ref_rel_code'];
            $fullName    = $emailData['to_firstname'] . ' ' . $emailData['to_lastname'];
            $entUrl      = Config::get('constants.MM_ENTERPRISE_URL');
            $posts       = $this->neoPostRepository->getPosts($postId);
            $postDetails = $this->formPostDetailsArray($posts);
            $freeService = $postDetails['free_service']; 
            #form email variables here
            $dataSet['name']                = $fullName;
            $dataSet['email_template']      = isset($emailData['email_template']) ? $emailData['email_template'] : 0;;
            $dataSet['reply_emailid']       = $emailData['reply_to'];
            $dataSet['email']               = $emailData['to_emailid'];
            $dataSet['fromName']            = $emailData['from_firstname'];
            $dataSet['post_type']           = 'internal';//$posts->post_type;
            $dataSet['company_name']        = $emailData['company_name'];//Enterpi Software Solutions Pvt.Ltd.
            $dataSet['company_logo']        = $emailData['company_logo'];
            $dataSet['logo_width']          = $emailData['company_logo_width'];
            $dataSet['logo_height']         = $emailData['company_logo_height'];
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
                $postRewards = $this->getPostRewardsDetils($postId);
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
            $dataSet['free_service']        = $freeService;
            $dataSet['discovery']           = $discovery;
            $dataSet['referral']            = $referral;
            $dataSet['job_details_link']    = $entUrl . "/email/job-details/share?ref=" . $refCode."";
            $bitlyUrl                       = $this->urlShortner($dataSet['job_details_link']);
            $dataSet['bittly_link']         = $bitlyUrl;
            
            if($posts->post_type == 'campaign'){
                $campaignId = $this->neoPostRepository->getPostCampaignId($postId);
                $refId      = $this->neoPostRepository->getUserNodeIdByEmailId($emailData['from_emailid']);
                $refCmpCode = MyEncrypt::encrypt_blowfish($campaignId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));
                #check resume attachment status
                if(!empty($emailData['attachment_status'])) {
                    $dataSet['apply_link']  = $entUrl . "/email/campaign/candidate-assessment/share?ref=" . $refCode."&refrel=" . $refRelCode."&camp_ref=".$refCmpCode."&flag=0&jc=1";
                } else{
                    $dataSet['apply_link']  = $entUrl . "/email/campaign/candidate-details/share?ref=" . $refCode."&refrel=" . $refRelCode."&camp_ref=".$refCmpCode."&flag=0&jc=1";
                }
                $dataSet['view_jobs_link']      = $entUrl . "/email/all-campaigns/share?ref=" . $refCmpCode;
            } else {
                $dataSet['apply_link']          = $entUrl . "/email/candidate-details/share?ref=" . $refCode."&refrel=" . $refRelCode."&flag=0&jc=0";
                $dataSet['view_jobs_link']      = $entUrl . "/email/all-jobs/share?ref=" . $refCode."&jc=0";
            }
            #redirect email links
            $dataSet['refer_link']          = $entUrl . "/email/referral-details/share?ref=" . $refCode."&flag=0&jc=0";
            $dataSet['drop_cv_link']        = $entUrl . "/email/referral-details/share?ref=" . $refCode."&flag=1&jc=0";
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
                'emails_types_id'   => 8,
                'from_user'         => $fromUserId,
                'from_email'        => $fromEmailId,
                'to_email'          => $this->appEncodeDecode->filterString(strtolower($emailData['to_emailid'])),
                'related_code'      => $companyCode,
                'sent'              => $emailStatus,
                'ip_address'        => $ipAddress
            );
            $this->userRepository->logEmail($emailLog);
        }
        return true;
    }
    
    public function getImageAspectRatio($imagePath = '') {
        
        $return = $result = array();
        if($imagePath){
            $file_headers   = @get_headers($imagePath);
            if(isset($file_headers[0]) && $file_headers[0] == 'HTTP/1.1 200 OK') {
                $result = getimagesize($imagePath);
                if(isset($result[0]) && isset($result[1])){
                    $height     = self::IMAGE_DEFAULT_HIGHT; 
                    $newWidth   = ($result[0]/$result[1])*$height;
                    $return['width']  = round($newWidth);
                    $return['height'] = $height;
                }  
            }
        }
        return $return;
    }
           
}

?>
