<?php namespace Mintmesh\Gateways\API\User;

/**
 * This is the Users Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */

use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\Enterprise\EnterpriseRepository;
use Mintmesh\Repositories\API\Enterprise\NeoEnterpriseRepository;
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Repositories\API\Payment\PaymentRepository;
use Mintmesh\Services\Validators\API\User\UserValidator ;
use Mintmesh\Services\Emails\API\User\UserEmailManager ;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader ;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\APPEncode\APPEncode ;
use Mintmesh\Gateways\API\SocialContacts\ContactsGateway;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use Mintmesh\Services\Parser\ParserManager as ParserManager;

use Lang;
use Config;
use OAuth;
use URL,Queue;
use Cache;

class UserGateway {
    
    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    protected $userRepository, $neoUserRepository,$paymentRepository, $contactsRepository;    
    protected $authorizer, $appEncodeDecode;
    protected $userValidator,$contactsGateway, $referralsGateway;
    protected $userEmailManager;
    protected $userFileUploader,$declines, $refer_nots, $deleteUserTypes;
    protected $commonFormatter, $postNotifications, $other_status_diferrent, $referralsRepository, $notificationFromP2, $notificationToP2,$newServiceNotifications;
    protected $loggedinUserDetails,$notificationsTypes,$extraTextsNotes,$directProfileRedirections,$infoTypes,$referFlowTypes ;
    protected $allowedResumeExtensions, $resumeMaxSize, $enterpriseRepository, $neoPostRepository;
    public function __construct(UserRepository $userRepository,
                                    NeoUserRepository $neoUserRepository,
                                    Authorizer $authorizer,
                                    UserValidator $userValidator,
                                    UserEmailManager $userEmailManager,
                                    CommonFormatter $commonFormatter,
                                    UserFileUploader $userFileUploader,
                                    APPEncode $appEncodeDecode,
                                    ReferralsRepository $referralsRepository,
                                    PaymentRepository $paymentRepository,
                                    ContactsGateway $contactsGateway,
                                    ContactsRepository $contactsRepository,
                                    ParserManager $parserManager,
                                    EnterpriseRepository $enterpriseRepository,
                                    NeoPostRepository $neoPostRepository,
                                    NeoEnterpriseRepository $neoEnterpriseRepository) {
		$this->userRepository = $userRepository;
                $this->neoUserRepository = $neoUserRepository;
                $this->parserManager = $parserManager;
                $this->authorizer = $authorizer;
                $this->userValidator = $userValidator;
                $this->userEmailManager = $userEmailManager ;
                $this->neoEnterpriseRepository = $neoEnterpriseRepository;
                $this->enterpriseRepository = $enterpriseRepository;
                $this->commonFormatter = $commonFormatter ;
                $this->paymentRepository = $paymentRepository ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->referralsRepository = $referralsRepository ;
                $this->userFileUploader = $userFileUploader ;
                $this->contactsGateway = $contactsGateway ;
                $this->neoPostRepository = $neoPostRepository;
                $this->contactsRepository = $contactsRepository ;
                $this->notificationsTypes = array('3','4','5','6','10','11','12','13','14','15','17','18','19','20','22');
                $this->enterpriseNotificationsTypes = array(12,15,24,25,27,28,29,30,31);
                $this->extraTextsNotes = array('10','11','12','22','27','28') ;
                $this->infoTypes = array('experience', 'education', 'certification');
                $this->directProfileRedirections = array('2','12','14');
                $this->declines = array('15','16');
                $this->postNotifications = array(10,11,12,13,14,15,16,20,22,23,24,25);
                $this->other_status_diferrent = array(10,12);
                $this->selfReferNotifications = array(17) ;
                $this->referFlowTypes = array(3,4,5,6,7,8,9);
                $this->refer_nots = array(3,4,5,6,7,8,9);
                $this->deleteUserTypes = array(1,2);
                $this->you_are = array('recruiter'=>4, 'salaried_professional'=>1, 'self_employed'=>2, 'professional_service_provider'=>3, 'student'=>5, 'retired_professional'=>7, 'homemaker'=>6);
                $this->notificationFromP2 = array(10,11,20);
                $this->notificationToP2 = array(12,15);
                $this->newServiceNotifications = array(27,28);
                $this->allowedResumeExtensions = array(
                                'doc',
                                'docx',
                                'pdf',  
                                'rtf',
                                'msword'
                                );
                $this->resumeMaxSize = Config::get('constants.RESUME_MAX_SIZE');//file size max 750kb
                date_default_timezone_set('UTC');
        }
        // validation on user inputs for change password
        public function validateChangePassword($input) {            
            return $this->doValidation('change_password','MINTMESH.change_password.valid');
        }
        
        // validation on user inputs for creating a user
        public function validateCreateUserInput($input) {            
            return $this->doValidation('create','MINTMESH.user.valid');
        }

        // validation on user inputs for creating a user
        public function validateCreateUserInput_v2($input) {            
            return $this->doValidation('create_v2','MINTMESH.user.valid');
        }

        // validation logout
        public function validateUserLogOut($input) {
            return $this->doValidation('logout','MINTMESH.user.valid');     
        }
        
        //validation of connection request
        public function validateConnectionRequestInput($input) {
            return $this->doValidation('connection_request','MINTMESH.user.valid');         
        }
        
        //validation of connection accept
        public function validateAcceptConnectionInput($input) {
            return $this->doValidation('connection_accept','MINTMESH.user.valid');
        }
        
        public function validateSingleNotificationInput($input)
        {
            return $this->doValidation('get_single_notification','MINTMESH.user.valid');
        }
        
        public function validateGetUserByEmailInput($input)
        {
            return $this->doValidation('get_user_by_email','MINTMESH.user.valid');
        }
        
        // validation on user inputs for authenticating a user for special login
        public function validateUserSpecialLoginInput($input) {
            return $this->doValidation('special_login','MINTMESH.login.login_valid');
        }
        
        public function validateNotificationsInput($input)
        {
            return $this->doValidation('get_notifications','MINTMESH.login.login_valid');        
        }
        
        // validation on user inputs for updating a user
        public function validateCompleteProfileUserInput($input)
        {
            return $this->doValidation('complete_profile','MINTMESH.user.valid');           
        }
        
        // validation on user inputs for updating a user
        public function validateCompleteProfileUserInput_v2($input)
        {
            return $this->doValidation('complete_profile_v2','MINTMESH.user.valid');           
        }
        
        // validation on user inputs for authenticating a user
        public function validateUserLoginInput($input) 
        {
            return $this->doValidation('login','MINTMESH.login.login_valid');
        }        
        
        // validation on user inputs for authenticating a facebook user
        public function validateFbLoginInput($input) 
        {
            return $this->doValidation('fb_login','MINTMESH.fb_login.valid');
        }
        
        //validation on close notification
        public function validateCloseNotificationInput($input)
        {
            return $this->doValidation('close_notification','MINTMESH.user.valid');        
        }
        
        //validation get reference flow input
        public function validateGetReferenceFlowInput($input)
        {
            return $this->doValidation('get_reference_flow','MINTMESH.user.valid');     
        }
        
        // validation logout
        public function validateUsersByLocation($input) 
        {
            return $this->doValidation('get_users_by_location','MINTMESH.user.valid');            
        }
        
        //validation on forgot password input
        public function validateForgotPasswordInput($input)
        {
            return $this->doValidation('forgot_password','MINTMESH.forgot_password.valid');
        }
                
        //validation on reset password input
        public function validateCheckResetPasswordInput($input)
        {
            return $this->doValidation('check_reset_password','MINTMESH.check_reset_password.valid');
        }
        
        //validation on reset password input
        public function validateResetPasswordInput($input)
        {
            return $this->doValidation('reset_password','MINTMESH.reset_password.valid');
        }
        
        //validation on reset password input
        public function validateReferContactInput($input)
        {
            return $this->doValidation('refer_contact','MINTMESH.user.valid');
        }
        
        //validation on reset password input
        public function validateEditProfileInput($input)
        {
            return $this->doValidation('edit_profile','MINTMESH.user.valid');
        }
        
        //validation on phone existance input
        public function validatePhoneExistanceInput($input)
        {
            return $this->doValidation('validate_phone_existance','MINTMESH.user.valid');
        }
        
        //validation on specific level info input
        public function validateLevelsInfo($input)
        {
            return $this->doValidation('specific_level_info','MINTMESH.get_levels.valid');
        }
        
        //validation on refer my contact input
        public function validateReferMyContactInfo($input)
        {
            return $this->doValidation('refer_my_contact','MINTMESH.user.valid');
        }
        
        //validation on check user input
        public function validateCheckUserPasswordInfo($input)
        {
            return $this->doValidation('check_user_password','MINTMESH.user.valid');
        }
        
        //validation on get services input
        public function validategetServicesInfo($input)
        {
            return $this->doValidation('get_services','MINTMESH.services.valid');
        }
        
        
        /**
	 * update password.
	 *
	 * @return Response
	 */ 
        public function changePassword($input) 
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            if ($this->loggedinUserDetails) {
                if(Hash::check($input['password_old'],$this->loggedinUserDetails->password)) {
                    if($input['password_new'] == $input['password_new_confirmation']) {
                        $post=array();
                        $post['email']=$this->loggedinUserDetails->emailid ;
                        $post['password']=$input['password_new'];
                        // change password of user
                        $changePwd = $this->userRepository->changePassword($post);
                        if (!empty($changePwd)) {
                            
                            
                            $currentTime =  date('Y-m-d H:i:s');
                            $code = $this->base_64_encode($currentTime, $this->loggedinUserDetails->emailid) ;
                            //send mail
                            if($this->loggedinUserDetails['is_enterprise'] == 1){
                             $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.enterprise_reset_password_success');
                            }else{
                            $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.reset_password_success');
                            }
                            $this->userEmailManager->emailId = $this->neoLoggedInUserDetails->emailid;
                            $dataSet = array();
                            $dataSet['name'] =$this->neoLoggedInUserDetails->firstname;
                            $this->userEmailManager->dataSet = $dataSet;
                            $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.reset_password_success');
                            $this->userEmailManager->name = $this->neoLoggedInUserDetails->fullname;
                            $email_sent = $this->userEmailManager->sendMail();
                            //log email status
                            $emailStatus = 0;
                            if (!empty($email_sent))
                            {
                                $emailStatus = 1;
                            }
                            $emailLog = array(
                                   'emails_types_id' => 2,
                                   'from_user' => 0,
                                   'from_email' => '',
                                   'to_email' => !empty($this->loggedinUserDetails)?$this->loggedinUserDetails->emailid:'',
                                   'related_code' => $code,
                                   'sent' => $emailStatus,
                                   'ip_address' => $_SERVER['REMOTE_ADDR']
                               ) ;
                            $this->userRepository->logEmail($emailLog);
                    
                            
                            
                            $message = array('msg'=>array(Lang::get('MINTMESH.change_password.success')));
                            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                        } else {
                            $message = array('msg'=>array(Lang::get('MINTMESH.change_password.failed')));
                            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                        }
                    } else {
                        $message = array('msg'=>array(Lang::get('MINTMESH.change_password.confirmPasswordMismatch')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.change_password.oldPasswordMismatch')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.change_password.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
	}
        
        /**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */ 
        public function createUser($input) 
        {
            $userCount = 0;
            if (!empty($input['phone'])) {
                $userCount = $this->neoUserRepository->getUserByPhone($input['phone']);
            }
            if (empty($userCount)) {
                $responseMessage =  $responseCode = $responseStatus = "";
                $responseData = array();
                $createdUser = $this->userRepository->createUser($input) ;
                // create a node in neo
                $neoInput = array();
                $neoInput['firstname']          = $input['firstname'];
                $neoInput['lastname']           = $input['lastname'];
                $neoInput['fullname']           = $input['firstname']." ".$input['lastname'];
                $neoInput['emailid']            = $input['emailid'];
                $neoInput['phone']              = $input['phone'];
                $neoInput['phoneverified']      = !empty($input['phone_verified'])?1:0;
                $neoInput['phone_country_name'] = $input['phone_country_name'];
                if (!empty($input['location'])){
                    $neoInput['location'] = $input['location'];
                }
                $neoInput['login_source']       = $input['login_source'];
                $osType   =  $deep_link_type    = !empty($input['os_type'])?$input['os_type']:'';
                $neoInput['os_type']  = $osType;
                //check for existing node in neo4j
                $neoUser =  $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
                if (empty($neoUser)) {
                    $createdNeoUser =  $this->neoUserRepository->createUser($neoInput) ;
                } else {
                    //change user label
                    $changeLabelNeoUser =  $this->neoUserRepository->changeUserLabel($input['emailid']) ;
                    if (!empty($changeLabelNeoUser)){
                    $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;}
                }
                $deviceToken = $input['deviceToken'] ;
                $this->neoUserRepository->mapToDevice($deviceToken, $input['emailid'], $osType) ;


                if (!empty($createdUser)) {
                    //add battle card for phone verification
                    $notificationLogPhone = array(
                            'notifications_types_id' => 21,//21 is the id of notification
                            'from_user' => 0,
                            'from_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                            'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                            'other_email' => '',
                            'message' => "",
                            'ip_address' => $_SERVER['REMOTE_ADDR'],
                            'other_status'=>0,
                            'created_at' => date('Y-m-d H:i:s')
                        ) ;
                    $t = $this->userRepository->logNotification($notificationLogPhone);
                    //add battle card for email verification
                    $notificationLogEmail = array(
                           'notifications_types_id' => 26,//21 is the id of notification
                           'from_user' => 0,
                           'from_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                           'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                           'other_email' => '',
                           'message' => "",
                           'ip_address' => $_SERVER['REMOTE_ADDR'],
                           'other_status'=>0,
                           'created_at' => date('Y-m-d H:i:s')
                        ) ;
                    $t = $this->userRepository->logNotification($notificationLogEmail);
                    //send email to user
                    $activationCode = $this->base_64_encode($createdUser->created_at,$createdUser->emailactivationcode);
                    // send welcome email to users
                    // set email required params
//                    $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.user_welcome');
//                    $this->userEmailManager->emailId = $input['emailid'];
//                    $dataSet = array();
//                    $dataSet['name'] = $input['firstname'];
//                    $deep_link = $this->getDeepLinkScheme($deep_link_type);
//                    $dataSet['desktop_link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/user/activate/".$activationCode ;
//                    $appLink = $deep_link.Config::get('constants.MNT_VERSION')."/user/activate/".$activationCode ;
                    //$appLinkCoded = $this->base_64_encode("", $appLink) ; 
//                    $dataSet['link'] = $appLink ;
//                    $dataSet['email'] = $input['emailid'] ;

                   // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
//                    $this->userEmailManager->dataSet = $dataSet;
//                    $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.welcome');
//                    $this->userEmailManager->name = $input['firstname']. " ".$input['lastname'];
//                    $email_sent = $this->userEmailManager->sendMail();
                    //log email status
//                    $emailStatus = 0;
//                    if (!empty($email_sent)) {
//                        $emailStatus = 1;
//                    }
//                    $emailLog = array(
//                           'emails_types_id' => 1,
//                           'from_user' => 0,
//                           'from_email' => '',
//                           'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
//                           'related_code' => $activationCode,
//                           'sent' => $emailStatus,
//                           'ip_address' => $_SERVER['REMOTE_ADDR']
//                       ) ;
//                    $this->userRepository->logEmail($emailLog);
                    
                    //send second email for thanks for download
//                    $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.user_introduction');
//                    $this->userEmailManager->emailId = $input['emailid'];
//                    $dataSet = array();
//                    $dataSet['name'] = $input['firstname'];
//                    $this->userEmailManager->dataSet = $dataSet;
//                    $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.introduction');
//                    $this->userEmailManager->name = $input['firstname']. " ".$input['lastname'];
//                    $email_sent = $this->userEmailManager->sendMail();
//                    //log email status
//                    $emailStatus = 0;
//                    if (!empty($email_sent)) {
//                        $emailStatus = 1;
//                    }
//                    $emailLog = array(
//                           'emails_types_id' => 1,
//                           'from_user' => 0,
//                           'from_email' => '',
//                           'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
////                           'related_code' => $activationCode,
//                           'sent' => $emailStatus,
//                           'ip_address' => $_SERVER['REMOTE_ADDR']
//                       ) ;
//                    $this->userRepository->logEmail($emailLog);
                    //log points if location is filled..i.e if it v2 version api
                    if (!empty($input['location'])){
                        $this->userRepository->logLevel(6, $this->appEncodeDecode->filterString(strtolower($input['emailid'])), "", "",Config::get('constants.POINTS.SIGNUP'));
                    }
                    //check with phone number in non mintmesh users
                    $nonMintmeshUserResult = $this->checkForNonMintmeshPhoneNumber($input['phone'], $input['emailid']);
                    $input['grant_type'] = "password";
                    $input['username'] = $input['emailid'];
                    $response = $this->loginCall($input);
                    $userDetails = (array) json_decode($response, TRUE);
                    if($userDetails['status'] == 'success') {
                        $responseMessage = Lang::get('MINTMESH.user.create_success');
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                        $responseData = $userDetails['data'];
                    } else {
                        $responseMessage = Lang::get('MINTMESH.user.create_success_login_fail');
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                        $responseData = array();
                    }
               
                } else {
                    $responseMessage = Lang::get('MINTMESH.user.create_failure');
                    $responseCode    = self::ERROR_RESPONSE_CODE;
                    $responseStatus  = self::ERROR_RESPONSE_MESSAGE;
                    $responseData    = array();
                }
                $message = array('msg'=>array($responseMessage));
                return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.user_exist')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
	}
        
        public function resendActivationLink()
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            if($loggedinUserDetails->status && $loggedinUserDetails->emailverified) {
                $responseMessage = Lang::get('MINTMESH.resendActivationLink.already_activated');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $responseData = array();
            } else {
                //add battle card for phone verification
                $notificationLogPhone = array(
                       'notifications_types_id' => 21,//21 is the id of notification
                       'from_user' => 0,
                       'from_email' => $this->appEncodeDecode->filterString(strtolower($loggedinUserDetails->emailid)),
                       'to_email' => $this->appEncodeDecode->filterString(strtolower($loggedinUserDetails->emailid)),
                       'other_email' => '',
                       'message' => "",
                       'ip_address' => $_SERVER['REMOTE_ADDR'],
                       'other_status'=>0,
                       'created_at' => date('Y-m-d H:i:s')
                    ) ;
                $t = $this->userRepository->logNotification($notificationLogPhone);
                //add battle card for email verification
                $notificationLogEmail = array(
                       'notifications_types_id' => 26,//21 is the id of notification
                       'from_user' => 0,
                       'from_email' => $this->appEncodeDecode->filterString(strtolower($loggedinUserDetails->emailid)),
                       'to_email' => $this->appEncodeDecode->filterString(strtolower($loggedinUserDetails->emailid)),
                       'other_email' => '',
                       'message' => "",
                       'ip_address' => $_SERVER['REMOTE_ADDR'],
                       'other_status'=>0,
                       'created_at' => date('Y-m-d H:i:s')
                    ) ;
                $t = $this->userRepository->logNotification($notificationLogEmail);
                //send email to user
                $activationCode = $this->base_64_encode(date('Y-m-d H:i:s'),$loggedinUserDetails->emailactivationcode);
                // send welcome email to users
                // set email required params
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.user_welcome');
                $this->userEmailManager->emailId = $loggedinUserDetails->emailid;
                $dataSet = array();
                $dataSet['name'] = $neoLoggedInUserDetails->firstname;
                $deep_link_type = '';
                $deep_link = $this->getDeepLinkScheme($deep_link_type);
                $dataSet['desktop_link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/user/activate/".$activationCode ;
                $appLink = $deep_link.Config::get('constants.MNT_VERSION')."/user/activate/".$activationCode ;
                //$appLinkCoded = $this->base_64_encode("", $appLink) ; 
                $dataSet['link'] = $appLink ;
                $dataSet['email'] = $loggedinUserDetails->emailid ;

                // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.welcome');
                $this->userEmailManager->name = $neoLoggedInUserDetails->fullname;
                $email_sent = $this->userEmailManager->sendMail();
                //log email status
                $emailStatus = 0;
                if (!empty($email_sent))
                {
                    $emailStatus = 1;
                }
                $emailLog = array(
                       'emails_types_id' => 1,
                       'from_user' => 0,
                       'from_email' => '',
                       'to_email' => $this->appEncodeDecode->filterString(strtolower($loggedinUserDetails->emailid)),
                       'related_code' => $activationCode,
                       'sent' => $emailStatus,
                       'ip_address' => $_SERVER['REMOTE_ADDR']
                   ) ;
                $this->userRepository->logEmail($emailLog);

                $responseMessage = Lang::get('MINTMESH.resendActivationLink.success');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $responseData = array();
            }
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;

        }

        public function getSkills($input)
        { 
            if(empty($input)) {
                return $this->getAllSkills();
            } else {
                return $this->getFilterSkills($input);
            }
        }
        
        public function getSkills_v2($input)
        { 
            if(empty($input) || empty($input['search_for'])) {
                $data = array() ;
                $message = array('msg'=>array(Lang::get('MINTMESH.skills.no_data_found')));              
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
            } else {
                return $this->getFilterSkills($input);
            }
        }
        
        /*
         * Completing user profile version1
         */
        public function completeUserProfile($input)
        {
            $originalFileName = $renamedFileName = $linkedinFileName = "";
            $from_linkedin =  0;
            if (!empty($input['dpImage']))
            {
                $originalFileName = $input['dpImage']->getClientOriginalName();
                //upload the file
                $this->userFileUploader->source = $input['dpImage'] ;
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET') ;
                $renamedFileName = $this->userFileUploader->uploadToS3();
            }
            
            if (!empty($input['fromLinkedin']))
            {
                $linkedinFileName = $input['linkedinImage'] ;
                $from_linkedin = 1 ;
            }
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails)
            {
                //get loggedin user
                $neoInput = array();
                $neoInput['emailid'] = $this->loggedinUserDetails->emailid ;
                $neoInput['position'] = $input['position'];
                $neoInput['company'] = $input['company'];
                $neoInput['industry'] = $input['industry'];
                $neoInput['location'] = $input['location'];
                $neoInput['job_function'] = $input['job_function'];
                $neoInput['you_are'] = $this->you_are[$input['you_are']];
                $neoInput['from_linkedin'] = $from_linkedin ;
                $neoInput['dp_path'] = url('/').Config::get('constants.DP_PATH') ;
                $neoInput['dp_original_name'] = $originalFileName ;
                $neoInput['dp_renamed_name'] = $renamedFileName ;
                $neoInput['linkedinImage'] = $linkedinFileName ;
                $neoInput['points_earned'] = Config::get('constants.POINTS.COMPLETE_PROFILE') ;
                $neoInput['completed_contact'] = 1 ;
                $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                if (count($updatedNeoUser))
                {
                    // log the points
                    $countLevel = $this->userRepository->checkCompleteProfileExistance($this->loggedinUserDetails->emailid);
                    if (empty($countLevel))
                    {
                        $this->userRepository->logLevel(2, $this->loggedinUserDetails->emailid, "", "",Config::get('constants.POINTS.COMPLETE_PROFILE'));
                    }
                    if (!empty($input['job_function']))
                    {
                        //remove the job function associated
                        $this->neoUserRepository->unMapJobFunction($this->loggedinUserDetails->emailid);
                        //relate to job functions
                        $this->neoUserRepository->mapJobFunction($input['job_function'], $this->loggedinUserDetails->emailid);
                    }
                    if (!empty($input['industry']))
                    {
                        //remove the job function associated
                        $this->neoUserRepository->unMapIndustry($this->loggedinUserDetails->emailid);
                        //relate to job functions
                        $this->neoUserRepository->mapIndustry($input['industry'], $this->loggedinUserDetails->emailid);
                    }
                    // connect to the people who has invited this person
                    $pushData = array();
                    $pushData['user_email']=$this->loggedinUserDetails->emailid;
                    $pushData['relationAttrs']=array('auto_connected'=>1);
                    Queue::push('Mintmesh\Services\Queues\ConnectToInviteesQueue', $pushData, 'IMPORT');
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.success')));
                    $data = array(); 
                    $data['dp_path'] = $renamedFileName ;
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.create_failure')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        
        /*
         * Completing user profile version2
         */
        public function completeUserProfile_v2($input)
        {
            \Log::info("-----in complete profile ------");
            $str = "";
            foreach ($input as $k=>$v){
                $str.= $k."=>".$v ;
            }
            \Log::info("-----in complete profile input------".$str);
            $originalFileName = $renamedFileName = $linkedinFileName = "";
            $from_linkedin =  0;
            if (!empty($input['dpImage']) && is_file ($input['dpImage']) && is_array(getimagesize($input['dpImage'])))//check if image
            {
                $originalFileName = $input['dpImage']->getClientOriginalName();
                //upload the file
                $this->userFileUploader->source = $input['dpImage'] ;
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET') ;
                $renamedFileName = $this->userFileUploader->uploadToS3();
            }
            
            if (!empty($input['fromLinkedin']))
            {
                $linkedinFileName = $input['linkedinImage'] ;
                $from_linkedin = 1 ;
            }
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails)
            {
                //get loggedin user
                $neoInput = array();
                $neoInput['emailid'] = $this->loggedinUserDetails->emailid ;
                $neoInput['position'] = !empty($input['position'])?$input['position']:'';
                $neoInput['company'] = !empty($input['company'])?$input['company']:'';
                $neoInput['industry'] = !empty($input['industry'])?$input['industry']:'';
                $neoInput['location'] = !empty($input['location'])?$input['location']:'';
                $neoInput['job_function'] = !empty($input['job_function'])?$input['job_function']:'';
                $neoInput['profession'] = !empty($input['profession'])?$input['profession']:'';
                $neoInput['specialization'] = !empty($input['specialization'])?$input['specialization']:'';
                $neoInput['college'] = !empty($input['college'])?$input['college']:'';
                $neoInput['course'] = !empty($input['course'])?$input['course']:'';
                $neoInput['user_description'] = !empty($input['user_description'])?$input['user_description']:'';
                $neoInput['website'] = !empty($input['website'])?$input['website']:'';
                $neoInput['to_be_referred'] = $input['to_be_referred'];
                $neoInput['you_are'] = $input['you_are'];
                $neoInput['from_linkedin'] = $from_linkedin ;
                $neoInput['dp_path'] = url('/').Config::get('constants.DP_PATH') ;
                $neoInput['dp_original_name'] = $originalFileName ;
                $neoInput['dp_renamed_name'] = $renamedFileName ;
                $neoInput['linkedinImage'] = $linkedinFileName ;
                $neoInput['points_earned'] = Config::get('constants.POINTS.COMPLETE_PROFILE') ;
                $neoInput['completed_contact'] = 1 ;
                $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                if (count($updatedNeoUser))
                {
                    // log the points
                    $countLevel = $this->userRepository->checkCompleteProfileExistance($this->loggedinUserDetails->emailid);
                    if (empty($countLevel))
                    {
                        $this->userRepository->logLevel(2, $this->loggedinUserDetails->emailid, "", "",Config::get('constants.POINTS.COMPLETE_PROFILE'));
                    }
                    if (!empty($input['job_function']))
                    {
                        //remove the job function associated
                        $this->neoUserRepository->unMapJobFunction($this->loggedinUserDetails->emailid);
                        //relate to job functions
                        $this->neoUserRepository->mapJobFunction($input['job_function'], $this->loggedinUserDetails->emailid);
                    }
                    if (!empty($input['industry']))
                    {
                        //remove the job function associated
                        $this->neoUserRepository->unMapIndustry($this->loggedinUserDetails->emailid);
                        //relate to job functions
                        $this->neoUserRepository->mapIndustry($input['industry'], $this->loggedinUserDetails->emailid);
                    }
                    if (!empty($input['services']))
                    {
                        $services = json_decode($input['services']);
                        //relate to services
                        $this->neoUserRepository->mapServices($services, $this->loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.PROVIDES'));
                    }
                    // connect to the people who has invited this person
                    $pushData = array();
                    $pushData['user_email']=$this->loggedinUserDetails->emailid;
                    $pushData['relationAttrs']=array('auto_connected'=>1);
                    Queue::push('Mintmesh\Services\Queues\ConnectToInviteesQueue', $pushData, 'IMPORT');
                    //add education details if college and student details are added
                    if ($input['you_are'] == 5){
                        $educationInput = array();
                        $educationInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $educationInput['info_type']='education';
                        $educationInput['action']='add';
                        $educationInput['school_college']=!empty($input['college'])?$input['college']:'';
                        $educationInput['degree']=!empty($input['course'])?$input['course']:'';
                        $educationInput['start_year']='';
                        $educationInput['end_year']='';
                        $educationInput['description']='';
                        $edcationAdded = $this->editProfile($educationInput);
                    }
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.success')));
                    $data = array(); 
                    $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
                    $userDetails = $this->formUserDetailsArray($neoLoggedInUserDetails);
                    $loggedinUserDetails = $this->userRepository->getUserByEmail($this->loggedinUserDetails->emailid);
                    $userCountDetails = $this->getUserBadgeCounts($loggedinUserDetails,$userDetails['profile_completion_percentage']);
                    foreach ($userCountDetails as $k=>$v){
                        $userDetails[$k]=$v ;
                    }
                    $data['user']=$userDetails;
                    
                    \Log::info("-----in complete profile success response------".print_r($data, true));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else {
                    \Log::info("-----in complete profile failure ------");
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.create_failure')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                \Log::info("-----in complete profile user not found------");
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        
        /*
         * actually verifying the user input
         * 
         * @return Response
         */
        public function verifyLogin($inputUserData = array())
        {
            $oauthResult = "";
            // actually authenticating user with oauth
            try {
                $oauthResult = $this->authorizer->issueAccessToken();
            } catch (\Exception $e) {
                $oauthResult['error_description'] = $e->getMessage();
            }
            //check if access code is returned by oauth
            if (isset($oauthResult['access_token']))
            {
                $neoUser =  $this->neoUserRepository->getNodeByEmailId($inputUserData['username']) ;
                $loggedinUserDetails = $this->userRepository->getUserByEmail($inputUserData['username']);
                
                if($loggedinUserDetails->status == 1){
                    
                    $userId         = !empty($loggedinUserDetails->id)?$loggedinUserDetails->id:'';
                    #log user activity here
                    $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=12);
                    if($loggedinUserDetails->is_enterprise=='1'){
                        $this->userRepository->updateIsEnterpriseStatus($loggedinUserDetails->emailid);
                    }

                    $remaning_days = $this->userRepository->getRemaningDays($inputUserData['username']);
                    if (!empty($neoUser))
                    {
                        $userDetails = $this->formUserDetailsArray($neoUser) ;
    //                    $loggedinUserDetails = $this->userRepository->getUserByEmail($inputUserData['username']);
                        $userCountDetails = $this->getUserBadgeCounts($loggedinUserDetails,$userDetails['profile_completion_percentage']);
                        foreach ($userCountDetails as $k=>$v){
                            $userDetails[$k]=$v ;
                        }
                        $oauthResult['user'] = $userDetails ;
                        $oauthResult['user']['remaning_days'] = $remaning_days;//$this->userRepository->getRemaningDays($userDetails['emailid']);
                    }
                    //create a relation for device token
                    $deviceToken = $inputUserData['deviceToken'] ;
                    $osType      = !empty($inputUserData['os_type'])?$inputUserData['os_type']:'';
                    $this->neoUserRepository->mapToDevice($deviceToken, $inputUserData['username'], $osType) ;
                    if($remaning_days->days != 0 || ($remaning_days->status && $remaning_days->emailverified)) {
                        // returning success message
                        $message = array('msg'=>array(Lang::get('MINTMESH.login.login_success')));
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                        $data = $oauthResult;
                    } else {
                        // returning failure message                      
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                        $message = array('msg'=>array(Lang::get('MINTMESH.login.email_inactive')));
                        $data = $oauthResult;
                    }
                } else {
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                    $message = Lang::get('MINTMESH.login.inactive_user');
                    $data = array();
                }
                
            }
            else
            {
                // returning failure message                      
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $message = $oauthResult['error_description'];
                $data = array();
            }
            $message = array('msg'=>array($message));            
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data) ;
        }
        
        /*
         * actually verifying the user input
         * 
         * @return Response
         */
        
        public function specialLogin($input)
        {
            $code = !(empty($input['code']))?$input['code']:'';
            $decodedString = $this->base_64_decode($code) ;
            $createdTime = $decodedString['string1'] ;
            $emailActCode = $decodedString['string2'];
            if (!empty($emailActCode))
            {
                //set timezone of mysql if different servers are being used
                //date_default_timezone_set('America/Los_Angeles');
                $expiryTime =  date('Y-m-d H:i:s', strtotime($createdTime . " +".Config::get('constants.MNT_USER_EXPIRY_HR')." hours"));
                //check if expiry time is valid
               
                if (strtotime($expiryTime) > strtotime(date('Y-m-d H:i:s')))
                {
                    $userDetails = $this->userRepository->getUserByCode($emailActCode);
                    if (!empty($userDetails)) {
                        //login the user
                        $oauthResult = $this->authorizer->issueAccessToken() ;
                        if (isset($oauthResult['access_token']))
                        {
                            //remove the activation code
                            $this->userRepository->removeActiveCode($userDetails->id);
                            $neoUser =  $this->neoUserRepository->getNodeByEmailId($userDetails->emailid) ;
                            if (!empty($neoUser))
                            {
                                $userDetailsArray = $this->formUserDetailsArray($neoUser) ;
                                $loggedinUserDetails = $this->userRepository->getUserByEmail($userDetails->emailid);
                                $userCountDetails = $this->getUserBadgeCounts($loggedinUserDetails,$userDetailsArray['profile_completion_percentage']);
                                foreach ($userCountDetails as $k=>$v){
                                    $userDetailsArray[$k]=$v ;
                                }
                                $oauthResult['user'] = $userDetailsArray ;

                            }
                            //create a relation for device token
                            $deviceToken = $input['deviceToken'] ;
                            $osType      = !empty($input['os_type'])?$input['os_type']:'';
                            $this->neoUserRepository->mapToDevice($deviceToken, $input['emailid'], $osType) ;
                            // returning success message
                            $message = array('msg'=>array(Lang::get('MINTMESH.login.login_success')));
                            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $oauthResult) ;
                        }
                        else
                        {
                            // returning failure message
                            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $oauthResult['error_description'], array()) ;

                        }
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.invalid')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                
            }

        }

        
        /*
         * activating a user
         */
        public function activateUser($code)
        {
            $decodedString = $this->base_64_decode($code) ;
            $createdTime = $decodedString['string1'] ;
            $emailActCode = $decodedString['string2'];
            if (!empty($emailActCode))
            {
                //get user details
                $userDetails = $this->userRepository->getUserByCode($emailActCode);
                if(strtotime($userDetails->created_at) == strtotime($createdTime)) {
                    //set timezone of mysql if different servers are being used
                    //date_default_timezone_set('America/Los_Angeles');
                    $expiryTime =  date('Y-m-d H:i:s', strtotime($createdTime . " +".Config::get('constants.MNT_USER_EXPIRY_HR')." hours"));
                } else {
                    $expiryTime =  date('Y-m-d H:i:s', strtotime($createdTime . " +".Config::get('constants.MNT_USER_EXPIRY_HR_FOR_RESEND_ACTIVATION')." hours"));
                }
                //check if expiry time is valid
                if (strtotime($expiryTime) > strtotime(date('Y-m-d H:i:s')))
                {
                    $userDetails = $this->userRepository->getUserByCode($emailActCode);
                    if (!empty($userDetails) && empty($userDetails->status)) {
//                        if (empty($userDetails->status))
//                        {
                            // update status of the user to active
                            $this->userRepository->setActive($userDetails->id,$userDetails->emailid);
                            $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.success')));
                            $data = array('emailid'=>$userDetails->emailid);
                            //remove activation code
                           // $this->userRepository->removeActiveCode($userDetails->id);
                            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
//                        }
//                        else
//                        {
//                            $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.already_activated')));
//                            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
//                        }
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.already_activated')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.invalid')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                
            }
            
        }
        /*
         * check reset a user password
         */
        public function checkResetPassword($input)
        {
            $decodedString = $this->base_64_decode($input['code']) ;
            $sentTime = $decodedString['string1'] ;
            $email = $decodedString['string2'];
            //to get resetactivationcode 
            $passwordData = $this->userRepository->getresetcodeNpassword($email);
            if (!empty($email) && !empty($passwordData) && $passwordData->resetactivationcode == $input['code']) {
                $message = array('msg'=>array(Lang::get('MINTMESH.check_reset_password.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.check_reset_password.failed')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                
            }
        }
        
        /*
         * reset a user password
         */
        public function resetPassword($input)
        {
            $decodedString = $this->base_64_decode($input['code']) ;
            $sentTime = $decodedString['string1'] ;
            $email = $decodedString['string2'];
            //to get resetactivationcode 
            $passwordData = $this->userRepository->getresetcodeNpassword($email);
            if (!empty($email) && !empty($passwordData) && $passwordData->resetactivationcode == $input['code']) {
                    //set timezone of mysql if different servers are being used
                    //date_default_timezone_set('America/Los_Angeles');
                    $expiryTime =  date('Y-m-d H:i:s', strtotime($sentTime . " +".Config::get('constants.MNT_USER_EXPIRY_HR')." hours"));
                    //check if expiry time is valid
                    if (strtotime($expiryTime) > strtotime(date('Y-m-d H:i:s'))) {
                        $post=array();
                        $post['email']=$email ;
                        $post['password']=$input['password'];
                        // update status of the user to active
                        $updateCount = $this->userRepository->resetPassword($post);
                        if (!empty($updateCount)) {
                            //get user details
                            $userDetails = $this->userRepository->getUserByEmail($passwordData->emailid);
                            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($passwordData->emailid) ;      
                            $currentTime =  date('Y-m-d H:i:s');
                            $code = $this->base_64_encode($currentTime, $email) ;
                            //send mail
                            $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.reset_password_success');
                            $this->userEmailManager->emailId = $passwordData->emailid;
                            $dataSet = array();
                            $dataSet['name'] =$neoUserDetails['firstname'];
                            $this->userEmailManager->dataSet = $dataSet;
                            $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.reset_password_success');
                            $this->userEmailManager->name = $neoUserDetails['fullname'];
                            $email_sent = $this->userEmailManager->sendMail();
                            //log email status
                            $emailStatus = 0;
                            if (!empty($email_sent))
                            {
                                $emailStatus = 1;
                            }
                            $emailLog = array(
                                   'emails_types_id' => 2,
                                   'from_user' => 0,
                                   'from_email' => '',
                                   'to_email' => !empty($userDetails)?$userDetails['emailid']:'',
                                   'related_code' => $code,
                                   'sent' => $emailStatus,
                                   'ip_address' => $_SERVER['REMOTE_ADDR']
                               ) ;
                            $this->userRepository->logEmail($emailLog);
                    
                            $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.success')));
                            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                        } else {
                            $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.failed')));
                            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                        }
                    } else {
                        $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.invalid')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
            } else {
                if(empty($passwordData->resetactivationcode)) {
                    $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.codeexpired')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.error')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            }
        }
        
        /*
         * send forgot password email to users
         */
        public function sendForgotPasswordEmail($input)
        {
            if (!empty($input))
            {
                $isEnt = !empty($input['is_ent'])?$input['is_ent']:0;
                //get user details
                $userDetails = $this->userRepository->getUserByEmail($input['emailid']);
                $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;      
                if (!empty($userDetails))
                {
                    // set email required params
                    $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.forgot_password');
                    $this->userEmailManager->emailId = $input['emailid'];
                    $dataSet = array();
                    $dataSet['name'] =$neoUserDetails['firstname'];
                    //set reset code
                    //set timezone of mysql if different servers are being used
                    //date_default_timezone_set('America/Los_Angeles');
                    $currentTime =  date('Y-m-d H:i:s');
                    $email = md5($input['emailid']) ;
                    $code = $this->base_64_encode($currentTime, $email) ;
                    $deep_link_type = !empty($input['os_type'])?$input['os_type']:'';
                    $deep_link = $this->getDeepLinkScheme($deep_link_type, $isEnt);
                    $appLink = $deep_link.Config::get('constants.MNT_VERSION')."/user/reset_password/".$code ;
                    $appLinkCoded = $this->base_64_encode("", $appLink) ; //comment it for normal flow of deep linki.e without http
                    //$dataSet['link'] = $appLink ;//remove comment it for normal flow of deep linki.e without http
                    $dataSet['hrs'] = Config::get('constants.MNT_USER_EXPIRY_HR');
                    $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;//comment it for normal flow of deep linki.e without http
                    $this->userEmailManager->dataSet = $dataSet;
                    $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.forgot_password');
                    $this->userEmailManager->name = $neoUserDetails['fullname'];
                    $email_sent = $this->userEmailManager->sendMail();
                    //log email status
                    $emailStatus = 0;
                    if (!empty($email_sent))
                    {
                        $emailStatus = 1;
                    }
                    $emailLog = array(
                           'emails_types_id' => 2,
                           'from_user' => 0,
                           'from_email' => '',
                           'to_email' => !empty($userDetails)?$userDetails->emailid:'',
                           'related_code' => $code,
                           'sent' => $emailStatus,
                           'ip_address' => $_SERVER['REMOTE_ADDR']
                       ) ;
                    $this->userRepository->logEmail($emailLog);
                    //update code in users table
                    $inputdata = array('user_id' => $userDetails->id,
                                       'resetactivationcode' => $code);
                    $this->userRepository->updateUserresetpwdcode($inputdata);
                    if (!empty($email_sent))
                    {

                        $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.error')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.activate_user')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        public function editProfile($input)
        {
            $userInput = array();
            if (!empty($input['access_token']))
                unset($input['access_token']);
            if (!empty($input))
            {
                $originalFileName = $renamedFileName = $linkedinFileName = "";
                $from_linkedin =  0;
                $data=array();
                $this->loggedinUserDetails = $this->getLoggedInUser();
                $userId         = !empty($this->loggedinUserDetails->id)?$this->loggedinUserDetails->id:'';
                #log user activity here
                $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=8);
                if ($this->loggedinUserDetails)
                {
                    if (!empty($input['info_type']) && $input['info_type'] == 'contact')
                    {
                        $contactInput = $input;
                        $contactInput['emailid'] = $userEmailId = !empty($this->loggedinUserDetails->emailid)?$this->loggedinUserDetails->emailid:'' ;
                        if (!empty($input['phone']))
                        {
                            //check if a verified phone number is existing
                            $userCount = $this->neoUserRepository->getUserByPhone($input['phone'], $userEmailId);
                            if (!empty($userCount))
                            {
                                $message = array('msg'=>array(Lang::get('MINTMESH.sms.user_exist')));
                                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                            }
                        }
                        
                        $contactInfoSuccess = $this->editContactInfo($contactInput);
                        if (!empty($contactInfoSuccess))
                        {
                            $data['dp_path'] = $contactInfoSuccess ;
                        }
                    }
                    else if (!empty($input['info_type']) && in_array($input['info_type'], $this->infoTypes))
                    {
                        $sectionInput = $input;
                        $sectionInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $nodeID = !empty($input['id'])?$input['id']:0;
                        $sectionInfoSuccess = $this->editSectionInfo($sectionInput, $input['info_type']);
                        if ($sectionInfoSuccess)
                        {
                            $nodeID = !empty($sectionInfoSuccess['node_id'])?$sectionInfoSuccess['node_id']:$nodeID;//added a section info
                            //update profile completion percentage
                            $userInput['completed_'.strtolower($input['info_type'])] = 1 ;
                            if (!empty($sectionInfoSuccess)){
                                $moreDetails = $this->neoUserRepository->getMoreDetails($this->loggedinUserDetails->emailid, 'Experience');
                                if (!empty($moreDetails))
                                {
                                    $returnData = $this->formUserMoreDetailsArray($moreDetails);
                                    if (!empty($returnData['Experience'])){
                                        foreach ($returnData['Experience'] as $k=>$v){//only return the current experience details
                                            if (!empty($v['id']) && $nodeID == $v['id']){
                                                $data = $v ;
                                            }
                                        }
                                        $data['total_experience'] = !empty($returnData['total_experience'])?$returnData['total_experience']:0;
                                    }
                                }
                                $data['id'] = $nodeID;
                            }
                        }
                        else
                        {
                            //update profile completion percentage
                            $userInput['completed_'.strtolower($input['info_type'])] = 0 ;
                        }
                    }
                    else if (!empty($input['info_type']) && $input['info_type'] == 'skills')
                    {
                        //update profile completion percentage
                        if (!empty($input['skills']))
                        {
                            $userInput['completed_'.strtolower($input['info_type'])] = 1 ;
                        }
                        else
                        {
                            $userInput['completed_'.strtolower($input['info_type'])] = 0 ;
                        }
                        
                        $skillsInput = $input;
                        $skillsInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $skillsInfoSuccess = $this->editSkillsInfo($skillsInput);
                    }
                    if (!empty($input['info_type']) && $input['info_type'] == 'resume')
                    {
                        $resumeInput = $input;
                        $resumeInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $resumeInfoSuccess = $this->editResumeInfo($resumeInput);
                        if($resumeInfoSuccess=='uploaded_large_file'||$resumeInfoSuccess=='invalid_file_format'){
                            $message = array('msg'=>array(Lang::get('MINTMESH.user.'.$resumeInfoSuccess)));
                            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                        }
                        else if(!empty($resumeInfoSuccess['originalFileName']))
                        {
                            $data['cv_original_name'] = $resumeInfoSuccess['originalFileName'];
                            $data['cv_path']          = $resumeInfoSuccess['renamedFileName'];
                        }
                     
                    }
                    //update user node to update proflie completion percentage
                    if (!empty($userInput))
                    {
                        $userInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $this->neoUserRepository->updateUser($userInput);
                    }
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.edit_success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.edit_no_changes')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        public function editSkillsInfo($input=array())
        {
            if (!empty($input['skills']))
            {
                $skills = json_decode($input['skills']) ;
                if (!empty($skills) && is_array($skills))
                {
                    //delete all skills assigned to the user
                    $this->neoUserRepository->unMapSkills($input['emailid']);
                    //add new skills
                    $result = $this->neoUserRepository->mapSkills($skills,$input['emailid']);
                    return true ;
                }
                else
                {
                    return false ;
                }
            }
            else
            {
                $this->neoUserRepository->unMapSkills($input['emailid']);
                return true ;
            }
        }
        public function editContactInfo($input)
        {
            if (!empty($input))
            {
                $returnDp = "";
                $originalFileName = $renamedFileName = $linkedinFileName = "";
                $neoUser =  $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
                $from_linkedin =  0;
                //if same number then do not edit
                if (!empty($input['phone']) && $input['phone'] == $neoUser->phone)
                {
                    unset($input['phone']);
                }
                $neoInput = array();
                $neoInput['emailid'] = $input['emailid'] ;
                if (!empty($input['dpImage']))
                {
                    $originalFileName = $input['dpImage']->getClientOriginalName();
                    //upload the file
                    $this->userFileUploader->source = $input['dpImage'] ;
                    $this->userFileUploader->destination = Config::get('constants.S3BUCKET') ;
                    $renamedFileName = $this->userFileUploader->uploadToS3();
                    $neoInput['from_linkedin'] = $from_linkedin ;
                    $neoInput['dp_path'] = url('/').Config::get('constants.DP_PATH') ;
                    $neoInput['dp_original_name'] = $originalFileName ;
                    $neoInput['dp_renamed_name'] = $renamedFileName ;
                    $neoInput['linkedinImage'] = $linkedinFileName ;
                    $returnDp = $renamedFileName;
                    unset($input['dpImage']);
                }
                foreach ($input as $key=>$val)
                {
                    $neoInput[$key] = $val ;
                }
                if (!empty($input['you_are'])){
                    $neoInput['you_are'] = !empty($this->you_are[$input['you_are']])?$this->you_are[$input['you_are']]:$input['you_are'];
                    // log the points for complete profile
                    $countLevel = $this->userRepository->checkCompleteProfileExistance($input['emailid']);
                    if (empty($countLevel))
                    {
                        $this->userRepository->logLevel(2, $input['emailid'], "", "",Config::get('constants.POINTS.COMPLETE_PROFILE'));
                    }
                    $neoInput['completed_contact'] = 1 ;
                    $job_industry_set = array('7','3','6','5');
                    $company_set = array('5','6');
                    if(in_array($neoInput['you_are'], $job_industry_set)) {
                        //remove the job function associated
                        $this->neoUserRepository->unMapJobFunction($this->loggedinUserDetails->emailid);
                        //remove the job function associated
                        $this->neoUserRepository->unMapIndustry($this->loggedinUserDetails->emailid);
                    }
                    if(in_array($neoInput['you_are'], $company_set)) {
                        $neoInput['company'] = "";
                    }
                }
                if (!empty($input['firstname']) && !empty($input['lastname']))
                {
                    $neoInput["fullname"] =  $input['firstname']." ".$input['lastname'];
                }
                else if (!empty($input['firstname']) && empty($input['lastname']))
                {
                   $neoInput["fullname"] =  $input['firstname']." ".$neoUser->lastname; 
                }
                else if (empty($input['firstname']) && !empty($input['lastname']))
                {
                    $neoInput["fullname"] =  $neoUser->firstname." ".$input['lastname']; 
                }
                if (!empty($neoInput['phone']))//make phone verified to null
                {
                    $neoInput["phoneverified"] =  0; 
                    //add verify otp battlecard
                    $notificationLog = array(
                        'notifications_types_id' => 21,//21 is the id of notification
                        'from_user' => 0,
                        'from_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                        'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                        'other_email' => '',
                        'message' => "",
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'other_status'=>0,
                        'created_at' => date('Y-m-d H:i:s')
                    ) ;
                    $t = $this->userRepository->logNotification($notificationLog);
                }
                $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                if (!empty($input['job_function']))
                {
                    //remove the job function associated
                    $this->neoUserRepository->unMapJobFunction($this->loggedinUserDetails->emailid);
                    //relate to job functions
                    $this->neoUserRepository->mapJobFunction($input['job_function'], $this->loggedinUserDetails->emailid);
                }
                if (!empty($input['industry']))
                {
                    //remove the job function associated
                    $this->neoUserRepository->unMapIndustry($this->loggedinUserDetails->emailid);
                    //relate to job functions
                    $this->neoUserRepository->mapIndustry($input['industry'], $this->loggedinUserDetails->emailid);
                }
                if (!empty($input['services']))
                {
                    $services = json_decode($input['services']);
                    //remove the services associated
                    $this->neoUserRepository->unMapServices($this->loggedinUserDetails->emailid);
                    //relate to services
                    $this->neoUserRepository->mapServices($services, $this->loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.PROVIDES'));
                }
                
                
            }
            return $returnDp ;
        }
        
        public function editSectionInfo($input=array(), $section="")
        {
            if (!empty($input))
            {
                $return = true ;
                $sectionName = Config::get('constants.USER_CATEGORIES.'.  strtoupper($section)) ;
                $relationName = Config::get('constants.RELATIONS_TYPES.MORE_INFO') ;
                // $expInfo = !empty($input['id'])?$this->neoUserRepository->getSectionInfo($input['id'], $sectionName):array();
                if (!empty($input['action']))
                {
                    if ($input['action']=='edit'  && !empty($input['id']))
                    {
                        //update node and relation
                        $this->neoUserRepository->updateCategoryNodeNRelation($input, array(), $sectionName, $relationName);
//                        edit relation between user and job node
                        if($sectionName == 'Experience' && !empty($input['job_title'])){
                            $this->neoUserRepository->unMapJobs($this->loggedinUserDetails->emailid,$input['id']);
                            $this->neoUserRepository->mapJobs(array($input['job_title']), $this->loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.WORKS_AS'),array('experience_id'=>$input['id']));
                        }
                        }
                    else if ($input['action']=='add')
                    {
                        //create node and relation
                        $nodeId = $this->neoUserRepository->createCategoryNodeNRelation($input, array(), $sectionName, $relationName);
//                        create relation between user and job node
                        if($sectionName == 'Experience' && !empty($input['job_title'])){
                            $jobResult = $this->neoUserRepository->mapJobs(array($input['job_title']), $this->loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.WORKS_AS'),array('experience_id'=>$nodeId));
                        }
                        $return = array('node_id'=>$nodeId);
                    }
                    else if ($input['action']=='delete' && !empty($input['id']))
                    {
                        //remove all relations for experience
                        $this->neoUserRepository->removeCategoryNodeRelation($input, $sectionName, $relationName);
//                      delete relation between user and job node 
                        if($sectionName == 'Experience'){
                         $this->neoUserRepository->unMapJobs($this->loggedinUserDetails->emailid,$input['id']);
                         }
                        //get number of categories relation remainng for the user
                        $remainingCount = $this->neoUserRepository->getCategoryNodeRelationCount($input, $sectionName, $relationName);
                        if (empty($remainingCount))
                        {
                            $return = false ;
                        }
                    }
                }
                
                return $return ;
            }
        }
        
        public function editResumeInfo($input)
        {           
            $response = array('FALSE');
            if (!empty($input))
            {
                $loggedinUserDetails = $this->getLoggedInUser();
                (!empty($loggedinUserDetails))?$userId=$loggedinUserDetails->id:$userId=0;
                $originalFileName = $renamedFileName = "";
                $originalFileExtension = $originalFileSize = "";
                $neoInput = array();
                $neoInput['emailid'] = $input['emailid'] ;
                //delete resume
                if(empty($input['resume'])){
                        $neoInput['cv_original_name'] = '';
                        $neoInput['cv_renamed_name'] = '' ;    
                        $neoInput['cv_path'] = '' ;
                        $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                }      
                
                if (!empty($input['resume']))
                {
                    $originalFileName      =  $input['resume']->getClientOriginalName();
                    $originalFileExtension =  $input['resume']->getClientOriginalExtension();
                    $originalFileSize      =  $input['resume']->getClientSize();
                     //cheking file format              
                   if(in_array($originalFileExtension, $this->allowedResumeExtensions)){
                       //cheking file size
                       if($originalFileSize <= $this->resumeMaxSize ){
                            //upload the file
                            $this->userFileUploader->source = $input['resume'] ;
                            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_RESUME').$userId ;
                            $renamedFileName = $this->userFileUploader->uploadToS3();
                            $neoInput['cv_original_name'] = $originalFileName ;
                            $neoInput['cv_renamed_name'] = $renamedFileName ;
                            $neoInput['cv_path'] = $renamedFileName ;
                            unset($input['resume']);
                            $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                            $response['originalFileName'] = $originalFileName;
                            $response['renamedFileName']  = $renamedFileName;
                       }
                       else
                       {
                          $response = "uploaded_large_file";   
                       }        
                   }
                   else
                   {
                      $response = "invalid_file_format";
                   }
                }
            }
            return $response;
        }
        public function processConnectionRequest($input)
        {
            $emails = json_decode($input['emails']);
            if (!empty($emails) && is_array($emails))
            {
                
                $loggedinUserDetails = $this->getLoggedInUser();
                if ($loggedinUserDetails)
                {
                    $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                    $success_list = $failed_list = array();
                    foreach ($emails as $email)
                    {
                        $neoUser =  $this->neoUserRepository->getNodeByEmailIdMM($email) ;
                        if (!empty($neoUser) && !empty($neoLoggedInUserDetails))
                        {
                            $toUserId = !empty($neoUser[0]['id']->getId())?$neoUser[0]['id']->getId():0;
                            $relationAttrs = array('status'=>Config::get('constants.REFERENCE_STATUS.PENDING')) ;
                            $result = $this->neoUserRepository->setConnectionRequest($neoLoggedInUserDetails->id, $toUserId, $relationAttrs);
                            $success_list[] = $email ;
                            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $email, 1);//1 is request connect type
                        }
                        else
                        {
                            $failed_list[] = $email ;
                        }
                    }
                    if (!empty($success_list))
                    {
                        $data = array("success_list"=>$success_list,"failure_list"=>$failed_list);
                        $message = array('msg'=>array(Lang::get('MINTMESH.connections.request_success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.user.not_mintmesh')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.invalid_input')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        
        public function getUserProfile()
        {
//            if (Cache::has('userprofile')) { 
//                $loggedinUserDetails = Cache::get('userprofile_'.Crypt::encrypt($this->loggedinUserDetails->emailid));                
//                \Log::info("<<<<<<<<< In if >>>>>>>>>");
//            } else {
//                $loggedinUserDetails = $this->getLoggedInUser();
//                \Log::info("<<<<<<<<< In else >>>>>>>>>");
//                Cache::add('userprofile_'.Crypt::encrypt($this->loggedinUserDetails->emailid), $loggedinUserDetails, 1000);                
//            }  
//            
            $responseMessage = $responseCode = $responseStatus = "";
            $responseData = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            $userId         = !empty($loggedinUserDetails->id)?$loggedinUserDetails->id:'';
            #log user activity here
            $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=7);
            if ($loggedinUserDetails)
            {                
                $requestsCount = 0;
                $extraDetails = array();
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                $moreDetails = $this->neoUserRepository->getMoreDetails($loggedinUserDetails->emailid);
                if (!empty($moreDetails))
                {
                    $extraDetails = $this->formUserMoreDetailsArray($moreDetails);
                }
                $skills = $this->neoUserRepository->getUserSkills($loggedinUserDetails->emailid);
                if (!empty($skills))
                {
                    $skillsArray = array();
                    foreach ($skills as $skill)
                    {
                        $skillsArray[] = $skill[0]->getProperties();
                    }
                    $extraDetails['skills'] = $skillsArray ;
                }
		$r = array();
                if (!empty($neoLoggedInUserDetails))
                {
                    $r = $this->formUserDetailsArray($neoLoggedInUserDetails);
                    if (!empty($neoLoggedInUserDetails->cv_path) && !empty($neoLoggedInUserDetails->cv_renamed_name))
                    {
                        $r['cv_path'] = $neoLoggedInUserDetails->cv_renamed_name ;
                    }
                    if (!empty($extraDetails))
                    {
                        foreach ($extraDetails as $k=>$v)
                        {
                            $r[$k] = $v ;
                        }
                    }
                    $countDetails = $this->getUserBadgeCounts($loggedinUserDetails,$r['profile_completion_percentage']);
                    if (!empty($countDetails))
                    {
                        foreach ($countDetails as $key=>$val)
                        {
                            $r[$key]=$val ;
                        }
                    }
                    $r['position'] = empty($r['position'])?$r['you_are_name']:$r['position'];
                    //$r['company'] = empty($r['company'])?'Company not yet added':$r['company'];
                    $r['remaning_days'] = $this->userRepository->getRemaningDays($r['emailid']);
                    $data = array("user"=>$r);
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseMessage = Lang::get('MINTMESH.user.profile_success');
                    $responseData = $data ;
                }
                else
                {
                    $responseMessage = Lang::get('MINTMESH.user.user_not_found');
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                    $responseData = array();
                }
            }
            else
            {
                $responseMessage = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $responseData = array();
            }
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
            
        }
        
        public function getUserBadgeCounts($loggedinUserDetails,$profilePercentage)
        {
            $returnArray =  array();
            $battle_cards_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'request_connect');
            $battle_cards_count = $battle_cards_count + Config::get('constants.ADD_BATTLE_CARDS_COUNT');//adding battle cards count
            $returnArray['battle_cards_count']= ($profilePercentage < 100)?$battle_cards_count+1:$battle_cards_count;
            $badgeResult = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'all');
            //$returnArray['notifications_count']= !(empty($badgeResult))?$badgeResult:0;
            $returnArray['notifications_count']= $this->getBellNotificationCount($loggedinUserDetails->emailid);;
            $requestsCount = $this->neoUserRepository->getMyRequestsCount($loggedinUserDetails->emailid);
            $returnArray['requests_count']= !(empty($requestsCount))?$requestsCount:0;
            //credits count
            $creditResult = $this->userRepository->getCreditsCount($loggedinUserDetails->emailid);
            $returnArray['total_credits'] = (!empty($creditResult))?$creditResult[0]->credits:0 ;
            if ($returnArray['total_credits'] == null)
            {
                $returnArray['total_credits'] = 0;
            }
            $levels_info_r = $this->userRepository->getCurrentLevelInfo($loggedinUserDetails->emailid);
            $levels_info = $levels_info = array("level_id"=>'0', "name"=>'', "points"=>'0', "earned_points"=>'0');
            if (!empty($levels_info_r))
            {
                foreach ($levels_info_r as $row)
                {
                    $rowID = !empty($row->id)?$row->id:0;
                    //$rowID = !empty($row->id)?$row->id:0;
                    $levels_info = array("level_id"=>$rowID, "name"=>$row->name, "points"=>$row->points, "earned_points"=>$row->earned_points);
                }
            }

            foreach ($levels_info as $k=>$v)
            {
                if ($v == null)
                {
                    $levels_info[$k]="";
                }
            }
            $returnArray['levels_info'] = $levels_info ;
            $total_cash = 0;
            $referral_cash_res = $this->paymentRepository->getPaymentTotalCash($loggedinUserDetails->emailid,1);
            if (!empty($referral_cash_res))
            {
                $total_cash = !empty($referral_cash_res[0]->total_cash)?$referral_cash_res[0]->total_cash:0 ;
            }
            $returnArray['total_cash'] = $total_cash ;
            //get recruiters count
            $recruitersCount = $this->neoUserRepository->getRecruitersListCount($loggedinUserDetails->emailid);
            $returnArray['recriutersCount'] = $recruitersCount ;
            //get influencers count
            $influencersCount = $this->neoUserRepository->getInfluencersListCount($loggedinUserDetails->emailid);
            $returnArray['influencersCount'] = $influencersCount ;
            //get Companies count
            $companiesCount= $this->neoEnterpriseRepository->connectedCompaniesList($loggedinUserDetails->emailid);
            $returnArray['connectedCompaniesCount'] = count($companiesCount);
            //get company Details
            $companyDetailsAry= $this->neoEnterpriseRepository->connectedCompanyDetails($loggedinUserDetails->emailid);
            $returnArray['connected_company_code'] = $companyCode = !empty($companyDetailsAry->companyCode)?$companyDetailsAry->companyCode:0;
            $returnArray['connected_company_name'] = !empty($companyDetailsAry->name)?$companyDetailsAry->name:0;
            $returnArray['connected_company_logo'] = !empty($companyDetailsAry->logo)?$companyDetailsAry->logo:0;
            //get my all referrals count
            $referralsAry = $this->referralsRepository->getAllMyReferrals($loggedinUserDetails->emailid, $companyCode);
            $returnArray['referrals_count'] = !empty($referralsAry)?$referralsAry->count():0;
            
            return $returnArray ;
        }
        public function formUserMoreDetailsArray($input=array())
        {
            $categories = array(Config::get('constants.USER_CATEGORIES.EXPERIENCE'),Config::get('constants.USER_CATEGORIES.EDUCATION'),Config::get('constants.USER_CATEGORIES.CERTIFICATION'));
            
            $result = array();
            if (!empty($input))
            {
                foreach ($input as $record)
                {
                    $id = !empty($record[0])?$record[0]->getID():0;
                    $arr = !empty($record[0])?$record[0]->getProperties():array();
                    if (!empty($record[1][2]) && !empty($record[1][1]) && !empty($arr))
                    {
                        $arr['id'] = $id ;
                        if (in_array($record[1][1], $categories))
                        {
                            $result[$record[1][1]][]=$arr ;
                        }
                        else{
                            $result[$record[1][2]][]=$arr ;
                        }
                        
                    }
                }
                foreach ($result as $key=>$val)
                {
                    $sort_by = "" ;
                    $sort_order = SORT_DESC ;
                    if ($key == Config::get('constants.USER_CATEGORIES.EXPERIENCE'))
                    {
                        //seperate the current job elements
                        $current_jobs = $old_jobs = array();
                        $total_months = 0;
                        foreach ($val as $k=>$v)
                        {
                            
                            //calculate experience months and years
                            if (!empty($v['start_date']))
                            {
                                 $start_date = $v['start_date']."/01" ;
                                if (!empty($v['end_date']))
                                {
                                    $end_date = $v['end_date']."/01" ;
                                }
                                else
                                {
                                    $end_date = date('Y/m')."/01" ;
                                }
                                $months = $this->appEncodeDecode->dateDiff($start_date, $end_date);
                                $v['experience_count'] = $this->appEncodeDecode->calculateYear($months) ;
                                $total_months = $total_months+$months ;
                            }
                            //get user job details..like title nad mysql id
                            $jobDetailsResult = $this->neoUserRepository->getJobTitleDetails($v['id']);
                            $v['job_title']=!empty($v['job_title'])?$v['job_title']:"";
                            $v['job_title'] = !empty($jobDetailsResult['job_title'])?$jobDetailsResult['job_title']:$v['job_title'];
                            $v['job_title_id'] = !empty($jobDetailsResult['job_title_id'])?$jobDetailsResult['job_title_id']:0;
                            if (!empty($v['current_job']))
                            {
                                $current_jobs[] = $v ;
                            }
                            else
                            {
                                $old_jobs[] = $v ;
                            }
                        }
                        
                        $sort_by = 'start_date' ;
                        $sort_order = SORT_DESC ;
                        $a = $this->appEncodeDecode->array_sort($current_jobs, $sort_by, $sort_order) ;
                        $b = array_values($a);
                        $c = $this->appEncodeDecode->array_sort($old_jobs, $sort_by, $sort_order) ;
                        $d = array_values($c);
                        $result['total_experience'] = $this->appEncodeDecode->calculateYear($total_months) ;
                        $result[$key] = array_merge($b, $d);
                    }
                    else if ($key == Config::get('constants.USER_CATEGORIES.EDUCATION'))
                    {
                        $sort_by = 'start_year' ;
                        $sort_order = SORT_DESC ;
                        $a = $this->appEncodeDecode->array_sort($val, $sort_by, $sort_order) ;
                        $b = array_values($a);
                        $result[$key] = $b;
                        //sort by end year now
                        $sort_by = 'end_year' ;
                        $a = $this->appEncodeDecode->array_sort($val, $sort_by, $sort_order) ;
                        $b = array_values($a);
                        $result[$key] = $b;
                        
                    }
                    else if ($key == Config::get('constants.USER_CATEGORIES.CERTIFICATION'))
                    {
                        $sort_by = 'title' ;
                        $sort_order = SORT_ASC ;
                        $a = $this->appEncodeDecode->array_sort($val, $sort_by, $sort_order) ;
                        $result[$key] = array_values($a);
                    }
                       
                }
                return $result ;
            }
        }
        
        public function getMutualRequests($input)
        {
            $return = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (count($neoLoggedInUserDetails))
                {
                    $userDetails = $this->formUserDetailsArray($neoLoggedInUserDetails, 'attribute') ;
                }
                else
                {
                    $userDetails = array();
                }
                $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
                if (count($neoUserDetails))
                {
                    $toUserDetails = $this->formUserDetailsArray($neoUserDetails, 'attribute') ;
                }
                else
                {
                    $toUserDetails = array() ;
                }
                
                $relationDetails = $this->neoUserRepository->getMutualRequests($input['emailid'], $loggedinUserDetails->emailid); 
                $return['requests'] = array();
                if (count($relationDetails))
                {
                    foreach ($relationDetails as $relation)
                    {
                        $a = $relation[0]->getProperties();
                        if (!empty($relation[1]) && !empty($a))
                        {
                            $a['relation_type'] = strtolower($relation[1]) ;
                        }

                        if (!empty($a['request_for_emailid']))
                        {
                            //get other relation message
                            $otherRelationDetails = $this->neoUserRepository->getIntroduceConnection($loggedinUserDetails->emailid, $a['request_for_emailid'],$input['emailid']);
                            if (count($otherRelationDetails))
                            {
                                $a['other_message'] = (isset($otherRelationDetails[0][0]->message))?$otherRelationDetails[0][0]->message:"" ;
                                $a['other_status'] = (isset($otherRelationDetails[0][0]->status))?$otherRelationDetails[0][0]->status:"" ;
                            }
                            //get third user details
                            $neoOtherUserDetails = $this->neoUserRepository->getNodeByEmailId($a['request_for_emailid']) ;
                            $otherUserDetails = $this->formUserDetailsArray($neoOtherUserDetails, 'attribute') ;
                            foreach ($otherUserDetails as $k=>$v)
                            {
                                $a['other_user_'.$k] = $v ;
                            }

                        }
                        if (!empty($toUserDetails))
                        {
                            foreach ($toUserDetails as $k=>$v)
                            {
                                $a["to_user_".$k] = $v ;
                            }
                        }
                        if (!empty($userDetails))
                        {
                            foreach ($userDetails as $k=>$v)
                            {
                                $a["from_user_".$k] = $v ;
                            }
                        }
                        $a['referral_relation'] = !empty($relation[0]->getID())?$relation[0]->getID():0;
                        $return['requests'][] = $a ;
                    }
                }
                $data = array("mutual_requests"=>$return) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_requests.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        
        public function getMyRequests($input)
        {
            $return = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                $return['userDetails'] = $this->formUserDetailsArray($neoLoggedInUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC')) ;
                $page = !empty($input['page'])?$input['page']:0;
                $relationDetails = $this->neoUserRepository->getMyRequests($loggedinUserDetails->emailid, $page); 
                $return['requests'] = array();
                if (count($relationDetails))
                {
                    foreach ($relationDetails as $relation)
                    {
                        $to_emailid = "" ;
                        $a = $relation[0]->getProperties();
                        if (!empty($relation[1]) && !empty($a))
                        {
                            $a['relation_type'] = strtolower($relation[1]) ;
                        }

                        if (!empty($relation[2]) && !empty($a) && !empty($relation[1]))
                        {
                            if ($relation[1] == Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'))
                            {
                                $toUserDetails = $this->formUserDetailsArray($relation[2], 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC')) ;
                                foreach ($toUserDetails as $k=>$v)
                                {
                                    $a['to_user_'.$k] = $v ;
                                }
                                $to_emailid = $toUserDetails['emailid'] ;
                                //relation id of first request
                                $a['referral_relation'] = $relation[0]->getID();
                            }
                            else if ($relation[1] == Config::get('constants.REFERRALS.POSTED'))
                            {
                                $postDetails = $relation[2]->getProperties() ;
                                $postId = $relation[2]->getId() ;
                                #get industry name
                                $postDetails['industry_name'] = $this->referralsRepository->getIndustryNameForPost($postId);
                                #get job function name
                                $postDetails['job_function_name'] = $this->referralsRepository->getJobFunctionNameForPost($postId);
                                #get experience range name
                                $postDetails['experience_range_name'] = $this->referralsRepository->getExperienceRangeNameForPost($postId);
                                #get employment type name
                                $postDetails['employment_type_name'] = $this->referralsRepository->getEmploymentTypeNameForPost($postId);
                                foreach ($postDetails as $k=>$v)
                                {
                                    $a['post_details_'.$k] = $v ;
                                }
                                $a['post_id'] = $postId ;
                                $a['post_status'] = !empty($postDetails['status'])?strtolower($postDetails['status']):'' ;
                                $a['referrals_count'] = $this->referralsRepository->getPostReferralsCount($postId);
                                /*//get name of service/job user is looking for
                                if (!empty($postDetails['looking_for'])){
                                    //get name of service/job
                                    if (!empty($postDetails['service_scope'])){
                                        if (in_array($postDetails['service_scope'],$this->service_scopes)){//from service
                                            $a['service_name'] = $this->neoUserRepository->getServiceName($postDetails['looking_for']);
                                        }
                                        else{//from job
                                            $a['service_name'] = $this->neoUserRepository->getJobName($postDetails['looking_for']);
                                        }
                                    }
                                }*/
                            }

                        }
                        $p2Status = !empty($relation[0]->status)?$relation[0]->status:Config::get('constants.REFERENCE_STATUS.PENDING');
                        $p2StatusIn=array(Config::get('constants.REFERENCE_STATUS.SUCCESS'),Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'));
                        if (!empty($a['request_for_emailid']) && in_array($p2Status, $p2StatusIn))
                        {
                            $relationCount = !empty($relation[0]->request_count)?$relation[0]->request_count:0;
                            //get other relation message
                            if (!empty($to_emailid))//get relation between second and third user
                                $otherRelationDetails = $this->neoUserRepository->getIntroduceConnection($to_emailid, $a['request_for_emailid'],$loggedinUserDetails->emailid, $relationCount);
                            if (count($otherRelationDetails))
                            {
                                $a['other_message'] = (isset($otherRelationDetails[0][0]->message))?$otherRelationDetails[0][0]->message:"" ;
                                $a['other_status'] = (isset($otherRelationDetails[0][0]->status))?$otherRelationDetails[0][0]->status:"" ;
                                $a['introduced_at'] = (isset($otherRelationDetails[0][0]->created_at))?$otherRelationDetails[0][0]->created_at:"" ;
                                if ($a['other_status'] == Config::get('constants.REFERENCE_STATUS.SUCCESS'))//if intro completed then get p3 status
                                {
                                    //get the time p3 accepted
                                    $completedResult = $this->neoUserRepository->getReferralAcceptConnection($a['request_for_emailid'], $loggedinUserDetails->emailid, $to_emailid);
                                    if (count($completedResult))
                                    {
                                        $a['completed_at'] = (isset($completedResult[0][0]->created_at))?$completedResult[0][0]->created_at:"" ;
                                    }
                                }

                            }
                            //get third user details
                            $neoOtherUserDetails = $this->neoUserRepository->getNodeByEmailId($a['request_for_emailid']) ;
                            $otherUserDetails = $this->formUserDetailsArray($neoOtherUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC')) ;
                            foreach ($otherUserDetails as $k=>$v)
                            {
                                $a['other_user_'.$k] = $v ;
                            }

                        }
                        else
                        {
                            $a['other_status'] = $p2Status;
                            if (!empty($a['request_for_emailid']))
                            {
                                //get third user details
                                $neoOtherUserDetails = $this->neoUserRepository->getNodeByEmailId($a['request_for_emailid']) ;
                                $otherUserDetails = $this->formUserDetailsArray($neoOtherUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC')) ;
                                foreach ($otherUserDetails as $k=>$v)
                                {
                                    $a['other_user_'.$k] = $v ;
                                }
                            }
                            
                        }
                        /*if (!empty($a['status']) && $a['status'] == Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'))//if intro completed then get p3 status
                        {
                            $a['status'] = Config::get('constants.REFERENCE_STATUS.PENDING') ;
                        }*/
                        $return['requests'][] = $a ;
                    }
                }
                $data = array("my_requests"=>$return) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_requests.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function getUserConnections($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $isConnected = $connections = array();
            if ($loggedinUserDetails)
            {
                $userConnections = $connections = array();
                //check if these two users are connected
                if ($input['emailid'] !=  $loggedinUserDetails->emailid){
                $isConnected = $this->neoUserRepository->checkConnection($loggedinUserDetails->emailid, $input['emailid']);
                }
                if ((!empty($isConnected) && !empty($isConnected['connected'])) || $input['emailid'] ==  $loggedinUserDetails->emailid){
                    if (!empty($input['location'])){//get users by location
                        $connections = $this->neoUserRepository->getConnectionsByLocation($input['emailid'], $input['location']);
                    }else{
                        $connections = $this->neoUserRepository->getConnectedUsers($input['emailid']);
                    }
                }
                if (count($connections))
                {
                    foreach ($connections as $connection)
                    {
                        if($connection[0]->emailid != $loggedinUserDetails->emailid) {
                        $details = $this->formUserDetailsArray($connection[0],'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));;
                        if ($details['emailid'] != $loggedinUserDetails->emailid)//if not me
                        {
                            $connectionsR = $this->checkConnections($loggedinUserDetails->emailid, $input['emailid'], $details['emailid']);
                            if (!empty($connectionsR))
                            {
                                foreach ($connectionsR as $k=>$v)
                                {
                                    $details[$k]=$v ;
                                }
                                
                            }
                        }
                        else
                        {
                            $details['connected'] = 1 ;
                            $details['request_sent_at'] = 0;
                        }
                        //check if i imported the contact or not
                        if ($details['connected'] == 0)//check only when the contact is not connected
                        {
                            $importResult = $this->neoUserRepository->checkImport($loggedinUserDetails->emailid, $details['emailid']);
                            if ($importResult->count())
                            {
                                 $details['imported'] = 1 ;
                            }
                            else
                            {
                                $details['imported']=0;
                            }
                        }
                        $userConnections[] = $details ;
                        }
                    }
                }
                $data = array("connections"=>$userConnections) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_connections.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function checkConnections($loggedInUser='', $inputUser='',$currentUser='')
        {
            $returnArray = array();
            $connected = $this->neoUserRepository->checkConnection($loggedInUser,$currentUser);
            if (!empty($connected) && !empty($connected['connected']))
            {
                if (!empty($connected['connected'])){//connected
                    $returnArray['connected'] = 1 ;
                }else{//deleted
                    $returnArray['connected'] = 0 ;
                }
                $returnArray['connected'] = 1 ;
                $returnArray['request_sent_at'] = 0;
            }else
            {
                $pendingConnection = $this->neoUserRepository->checkPendingConnection($loggedInUser,$currentUser);
                if (!empty($pendingConnection))// if pending
                {
                    $returnArray['request_sent_at'] = $pendingConnection ;
                    $returnArray['connected'] = 2 ;
                }
                else //check for reference status
                {
                    //check staus
                    $statusRes = $this->neoUserRepository->getRequestStatus($loggedInUser,$inputUser, $currentUser, Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'));
                    if (!empty($statusRes))// if pending
                    {
                        if ($statusRes['status'] == Config::get('constants.REFERENCE_STATUS.PENDING') || $statusRes['status'] == Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'))
                        {
                            if ($statusRes['status'] != Config::get('constants.REFERENCE_STATUS.PENDING'))
                            {
                                //check if declined at other side
                                $otherStatusRes = $this->neoUserRepository->getRequestStatus($inputUser, $currentUser,$loggedInUser, Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'));
                                if (!empty($otherStatusRes))
                                {
                                    if ($otherStatusRes['status'] == Config::get('constants.REFERENCE_STATUS.DECLINED'))
                                    {
                                        $returnArray['request_sent_at'] = 0 ;
                                        $returnArray['connected'] = 0 ;
                                    }
                                    else
                                    {
                                        $returnArray['request_sent_at'] = $statusRes['created_at'] ;
                                        $returnArray['connected'] = 2 ;
                                    }
                                }
                                else
                                {
                                    $returnArray['request_sent_at'] = $statusRes['created_at'] ;
                                    $returnArray['connected'] = 2 ;
                                }
                            }
                            else
                            {
                                $returnArray['request_sent_at'] = $statusRes['created_at'] ;
                                $returnArray['connected'] = 2 ;
                            }

                        }
                        else
                        {
                            $returnArray['connected'] = 0 ;
                            $returnArray['request_sent_at'] = 0;
                        }
                    }else
                    {
                        $returnArray['connected'] = 0 ;
                        $returnArray['request_sent_at'] = 0;
                    }
                }

            }
            return $returnArray ;
        }
        /*
         * get user notification details
         */
        public function getSingleNotificationDetails($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                    $r = array() ;
                    $notifications_count = 0;
                    $battle_cards_count = 0;
                    //get push data
                    $notification = $this->userRepository->getNotification($input['push_id']);
                    if (!empty($notification))
                    {
                        $notification = $notification[0];
                        $noReferralsPost = false ;
                        $is_deleted = $normalFlow = 0;
                        $connectedToMe = $this->neoUserRepository->checkConnection($loggedinUserDetails->emailid,$notification->from_email);
                        if (!empty($connectedToMe) && !empty($connectedToMe['deleted']))
                        {
                            $is_deleted = 1;
                        }
                        $notifications_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'all');
                        $battle_cards_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'request_connect');
                        
                        if ($notification->notifications_types_id == 20){//if payment done notification the change the from and other details
                            if (empty($notification->for_mintmesh) && empty($notification->other_email)){//i.e refred non mintmesh user
                                $otherNoteUser = $neoUserDetails = $this->neoUserRepository->getNonMintmeshUserDetails($notification->other_phone) ;
                                $normalFlow = 0;
                            }else{
                                $otherNoteUser = $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($notification->other_email) ;
                                 $normalFlow = 1 ;
                            }
                                
                        }
                        else{
                            $fromNoteUser = $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($notification->from_email) ;
                            $normalFlow = 1 ;
                        }
                        if (empty($notification->for_mintmesh) && empty($normalFlow)){
                            $r = $this->formUserDetailsArray($neoUserDetails, 'property');
                            //print_r($note);exit;
                         }else{
                             $r = $this->formUserDetailsArray($neoUserDetails, 'attribute');
                         }
                        $thirdName = "";
                        if (!empty($notification->other_email) || !empty($notification->other_phone))//consider mintmesh and non mintmesh users
                        {
                            if ($notification->notifications_types_id == 20){//if payment done notification the change the from and other details
                                $fromNoteUser = $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($notification->from_email) ;
                                $normalFlow = 1 ;
                            }
                            else{
                                if (empty($notification->for_mintmesh) && empty($notification->other_email)){//i.e refred non mintmesh user
                                    $otherNoteUser = $otherEmailDetails = $this->neoUserRepository->getNonMintmeshUserDetails($notification->other_phone) ;
                                    $normalFlow = 0;
                                }else{
                                    $otherNoteUser = $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($notification->other_email) ;
                                     $normalFlow = 1 ;
                                }
                            }
                            if (in_array($notification->notifications_types_id, $this->notificationsTypes))
                            {
                                $thirdName = !empty($otherNoteUser->fullname)?$otherNoteUser->fullname:'' ;
                            }
                            if (empty($notification->for_mintmesh) && empty($normalFlow)){
                                //echo "here";exit;
                                $otherUserDetails = $this->formUserDetailsArray($otherEmailDetails, 'property');
                            }else{
                                $otherUserDetails = $this->formUserDetailsArray($otherEmailDetails, 'attribute');
                            }
                            foreach ($otherUserDetails as $k=>$v)
                            {
                                $r['other_user_'.$k] = $v ;
                            }

                        }
                        $extra_msg = "";
                        if (in_array($notification->notifications_types_id,$this->extraTextsNotes))//for posts
                        {
                            $extra_msg = Lang::get('MINTMESH.notifications.extra_texts.'.$notification->notifications_types_id) ;
                        }
                        if (!empty($notification->other_message))
                                $r['optional_message'] = $this->appEncodeDecode->filterStringDecode($notification->other_message) ;
                        $r['notification'] = $fromNoteUser->fullname." ".$notification->message." ".$thirdName ;
                        $r['notify_time'] = $notification->created_at ;
                        $r['notification_type'] = $notification->not_type ;
                        $r['push_id'] = $notification->id ;
                        $r['status'] = $notification->status ;
                        $r['other_status'] = $notification->other_status ;
                        if (empty($notification->for_mintmesh) && !empty($notification->other_phone))//i.e referrals by phone contact
                        {
                            $r['referred_by_phone'] = 1;
                        }

                        //get post details if post type
                        if (in_array($notification->notifications_types_id,$this->postNotifications))
                        {
                            $postDetails = $postRelationsDetails = $postDetailsR = array() ;
                            if ($notification->notifications_types_id == 10 || $notification->notifications_types_id == 23)
                            {
                                $e = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                $f = $notification->from_email ;
                            }
                            else if ($notification->notifications_types_id == 20)
                            {
                                $e = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                $f = $notification->from_email ;
                            }
                            else if ($notification->notifications_types_id == 14 || $notification->notifications_types_id == 22)
                            {
                                $e = $notification->from_email ;
                                $f = $notification->to_email ;
                            }
                            else if ($notification->notifications_types_id == 16 || $notification->notifications_types_id == 13)
                            {
                                $e = $notification->from_email ;
                                $f = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                            }
                            else if ($notification->notifications_types_id == 12 || $notification->notifications_types_id == 15 || $notification->notifications_types_id == 24 || $notification->notifications_types_id == 25)
                            {
                                $e = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                $f = $notification->to_email ;
                            }
                            else
                            {
                                $e = $notification->to_email ;
                                $f = $notification->from_email ;
                            }
                            if (!empty($notification->extra_info) && empty($notification->other_phone)){//i.e for email referrals
                                $postDetailsR = $this->referralsRepository->getPostAndReferralDetails($notification->extra_info,$f,$e);
                            }else if(!empty($notification->extra_info) && !empty($notification->other_phone)){//i.e for non mintmesh referrals
                                $postDetailsR = $this->referralsRepository->getPostAndReferralDetailsNonMintmesh($notification->extra_info,$f,$e);
                            }

                            if (count($postDetailsR))
                            {
                                $postRelationsDetails = isset($postDetailsR[0][0])?$postDetailsR[0][0]->getProperties():array();
                                foreach ($postRelationsDetails as $k=>$v)
                                {
                                    $r['post_'.$k] = $v ;
                                }
                                $r['relation_count'] = !empty($postRelationsDetails['relation_count'])?$postRelationsDetails['relation_count']:1;
                                $postDetails = isset($postDetailsR[0][1])?$postDetailsR[0][1]->getProperties():array();
                                foreach ($postDetails as $k=>$v)
                                {
                                    $r['post_'.$k] = $v ;
                                }
                                $r['post_id'] = !empty($notification->extra_info)?$notification->extra_info:0;
                                if ($e == $f){//self referred
                                    $r['is_self_referred'] = 1 ;
                                }
                            }
                        }
                        else if( !empty($notification->extra_info) && in_array($notification->notifications_types_id,$this->refer_nots))
                        {
                            $r['referral_relation'] =  $notification->extra_info ;
                        }
                        else if (in_array($notification->notifications_types_id,$this->selfReferNotifications))//if self reference type
                        {
                            $r['relation_id']= !empty($notification->extra_info)?$notification->extra_info:0;
                        }
                        
                    }
                    $phone_verified = !empty($neoUserDetails->phoneverified)?$neoUserDetails->phoneverified:0;
                    $data = array("notifications"=>array($r), "notifications_count"=>$notifications_count,"battle_cards_count"=>$battle_cards_count,"phone_verified"=>$phone_verified) ;
                    //$data = array("user"=>$r);
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.profile_success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        /*
         * get user all details
         */
        public function getUserDetailsByEmail($input)
        {
            $connectionsCount = $requestsCount = 0 ;
            $extraDetails = array();
            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails && count($neoUserDetails))
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (!empty($neoUserDetails) && !empty($neoLoggedInUserDetails))
                {
                    $r = $this->formUserDetailsArray($neoUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.FULL')) ;
                    $moreDetails = $this->neoUserRepository->getMoreDetails($input['emailid']);
                    if (!empty($moreDetails))
                    {
                        $extraDetails = $this->formUserMoreDetailsArray($moreDetails);
                    }
                    $skills = $this->neoUserRepository->getUserSkills($input['emailid']);
                    if (!empty($skills))
                    {
                        $skillsArray = array();
                        foreach ($skills as $skill)
                        {
                            $skillsArray[] = $skill[0]->getProperties();
                        }
                        $extraDetails['skills'] = $skillsArray ;
                    }
                    $connectionsCount = $this->neoUserRepository->getConnectedUsersCount($input['emailid']);
//                   remove loggedin user from the count
                    $connectionsCount = $connectionsCount - 1;
                    $requestsCount = $this->neoUserRepository->getMutualRequestsCount($input['emailid'], $neoLoggedInUserDetails->emailid);
                    if (!empty($extraDetails))
                    {
                        foreach ($extraDetails as $k=>$v)
                        {
                            $r[$k] = $v ;
                        }
                    }
                    $r['user_description'] = (isset($r['user_description'])?$r['user_description']:"");
                    $data = array("user"=>$r,"connections_count"=>$connectionsCount,"requests_count"=>$requestsCount);
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.profile_success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        public function formUserDetailsArray($neoLoggedInUserDetails, $type = '',$userAbstractionLevel='full')
        {
            $r = array();
            $r['fullname'] = "";
            if (strpos(\Request::url(), 'v3') !== false){
                $r = $this->formUserDetailsArrayV3($neoLoggedInUserDetails, $type,$userAbstractionLevel) ;
            }else{
                if (!empty($neoLoggedInUserDetails))
                {
                    $r = array();
                    if ($type == 'property')
                    {
                        $r = $neoLoggedInUserDetails->getProperties();
                    }
                    else
                    {
                        $r = $neoLoggedInUserDetails->getAttributes();
                    }
                    if (!empty($r['fullname'])){
                        $r['fullname'] = empty(trim($r['fullname']))?"":$r['fullname'];
                    }
                    if (!empty($neoLoggedInUserDetails->dp_renamed_name))//user has completed profile
                    {
                        if (!empty($neoLoggedInUserDetails->from_linkedin))//if  linked in
                        {
                            $r['dp_path'] = $neoLoggedInUserDetails->linkedinImage ;
                        }
                        else if (!empty($neoLoggedInUserDetails->dp_renamed_name))
                        {
                            $r['dp_path'] = $neoLoggedInUserDetails->dp_renamed_name ;
                        }
                        else
                        {
                            $r['dp_path'] = "";
                        }
                    }
                    else
                    {
                        $r['dp_path']="";
                    }
                    if (!empty($neoLoggedInUserDetails->cv_path) && !empty($neoLoggedInUserDetails->cv_renamed_name))
                    {
                        $r['cv_path'] = $neoLoggedInUserDetails->cv_renamed_name ;
                    }
                    else
                    {
                        $r['cv_path']="";
                    }
                    
                    if (isset($r['id']))
                        unset($r['id']);

                    unset($r['services']);
                    $job_function_name = $industry_name = "";
                    if (isset($r['job_function']))//get job function name
                    {
                        $job_function_result = $this->neoUserRepository->getUserJobFunction($neoLoggedInUserDetails->emailid) ;
                        $job_function_name = !empty($job_function_result[0])?$job_function_result[0][0]->name:"";

                    }
                    if (isset($r['industry']))//get job function name
                    {
                        $industry_name_result = $this->neoUserRepository->getUserIndustry($neoLoggedInUserDetails->emailid) ;
                        $industry_name = !empty($industry_name_result[0])?$industry_name_result[0][0]->name:"";
                    }
                    $you_are_name = "";
                    if (isset($r['you_are']))//get job function name
                    {
                        $you_are_name = $this->userRepository->getYouAreName($r['you_are'],"name");
                    }
                    if(isset($r['you_are']) && !is_numeric($r['you_are'])){
                        $r['you_are'] = $this->userRepository->getYouAreName($r['you_are'],"id");
                    }
                    $profession_name = "";
                    if (isset($r['profession']))//get profession name
                    {
                        $profession_name = $this->userRepository->getProfessionName($r['profession']);
                    }
                    $r['job_function_name'] = $job_function_name;
                    $r['industry_name'] = $industry_name ;
                    $r['you_are_name'] = $you_are_name ;
                    $r['profession_name'] = $profession_name ;
                    //change response for services
                    $services = $this->neoUserRepository->getUserServices($neoLoggedInUserDetails->emailid);
                    if (!empty($services))
                    {
                        $servicesArray = array();
                        foreach ($services as $service)
                        {
                            $servicesArray[] = $service[0]->getProperties();
                            //$servicesArray[] = array('service_name'=>$servD['name'],'service_id'=>$servD['mysql_id']);
                        }
                        $r['services'] = $servicesArray ;
                    }
                    //get profile completion percentage
                    $r['profile_completion_percentage'] = $this->calculateProfilePercentageCompletion($neoLoggedInUserDetails);
                }
            }
           return $r ; 
        }
        public function formUserDetailsArrayV3($neoLoggedInUserDetails, $type = '',$userAbstractionLevel='full')
        {
            $r = array();
            if (!empty($neoLoggedInUserDetails))
            {
                $r = $properties = array();
                if ($type == 'property')
                {
                    $properties = $neoLoggedInUserDetails->getProperties();
                }
                else
                {
                    $properties = $neoLoggedInUserDetails->getAttributes();
                }
                if ($userAbstractionLevel == Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC')){
                    foreach (Lang::get('MINTMESH.user_profiles_abstractions.basic') as $key){
                        $r[$key] = !empty($properties[$key])?$properties[$key]:'';
                    }
                }else {
                    foreach (Lang::get('MINTMESH.user_profiles_abstractions.medium') as $key){
                        $r[$key] = !empty($properties[$key])?$properties[$key]:'';
                    }
                }
                //image remains same for all abstractions
                if (!empty($neoLoggedInUserDetails->dp_renamed_name))//user has completed profile
                {
                    if (!empty($neoLoggedInUserDetails->from_linkedin))//if  linked in
                    {
                        $r['dp_path'] = $neoLoggedInUserDetails->linkedinImage ;
                    }
                    else if (!empty($neoLoggedInUserDetails->dp_renamed_name))
                    {
                        $r['dp_path'] = $neoLoggedInUserDetails->dp_renamed_name ;
                    }
                    else
                    {
                        $r['dp_path'] = "";
                    }
                }
                else
                {
                    $r['dp_path']="";
                }
                
                if (!empty($neoLoggedInUserDetails->cv_path) && !empty($neoLoggedInUserDetails->cv_renamed_name))
                {
                    $r['cv_path'] = $neoLoggedInUserDetails->cv_renamed_name ;
                }
                else
                {
                    $r['cv_path']="";
                }
                    
                if ($userAbstractionLevel == Config::get('constants.USER_ABSTRACTION_LEVELS.MEDIUM') || $userAbstractionLevel == Config::get('constants.USER_ABSTRACTION_LEVELS.FULL')){
                    
                    $job_function_name = $industry_name = "";
                    if (isset($r['job_function']))//get job function name
                    {
                        $job_function_result = $this->neoUserRepository->getUserJobFunction($neoLoggedInUserDetails->emailid) ;
                        $job_function_name = !empty($job_function_result[0])?$job_function_result[0][0]->name:"";
                    }
                    if (isset($r['industry']))//get job function name
                    {
                        $industry_name_result = $this->neoUserRepository->getUserIndustry($neoLoggedInUserDetails->emailid) ;
                        $industry_name = !empty($industry_name_result[0])?$industry_name_result[0][0]->name:"";
                    }
                    $you_are_name = "";
                    if (isset($r['you_are']))//get job function name
                    {
                        $you_are_name = $this->userRepository->getYouAreName($r['you_are'],"name");
                    }
                    if(isset($r['you_are']) && !is_numeric($r['you_are'])){
                        $r['you_are'] = $this->userRepository->getYouAreName($r['you_are'],"id");
                    }
                    $profession_name = "";
                    if (isset($r['profession']))//get profession name
                    {
                        $profession_name = $this->userRepository->getProfessionName($r['profession']);
                    }
                    $r['job_function_name'] = $job_function_name;
                    $r['industry_name'] = $industry_name ;
                    $r['you_are_name'] = $you_are_name ;
                    $r['profession_name'] = $profession_name ;
                }
                if ($userAbstractionLevel == Config::get('constants.USER_ABSTRACTION_LEVELS.FULL')){
                    //change response for services
                    $services = $this->neoUserRepository->getUserServices($neoLoggedInUserDetails->emailid);
                    if (!empty($services))
                    {
                        $servicesArray = array();
                        foreach ($services as $service)
                        {
                            $servicesArray[] = $service[0]->getProperties();
                            //$servicesArray[] = array('service_name'=>$servD['name'],'service_id'=>$servD['mysql_id']);
                        }
                        $r['services'] = $servicesArray ;
                    }
                    //get profile completion percentage
                $r['profile_completion_percentage'] = $this->calculateProfilePercentageCompletion($neoLoggedInUserDetails);
                }
            }
           return $r ; 
        }
        
        public function calculateProfilePercentageCompletion($neoLoggedInUserDetails)
        {
            $percentage = 0;
            $profileSections = Config::get('constants.PROFILE_COMPLETION_SECTIONS');
            foreach ($profileSections as $val)
            {
                $varName = 'completed_'.$val ;
                if (!empty($neoLoggedInUserDetails->$varName))
                {
                    //echo $val;
                    $percentage = $percentage+Config::get('constants.PROFILE_COMPLETION_VALUES.'.  strtoupper($val));
                }
            }
            return $percentage ;
        }
        
        /*
         * country codes 
         */
        public function getCountryCodes()
        {
            if (Cache::has('countryCodes')) {                 
                $data         = Cache::get('countryCodes');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg  = self::SUCCESS_RESPONSE_MESSAGE;
                $message      = array('msg'=>array(Lang::get('MINTMESH.country_codes.success')));
                
            } else {                
                $countryCodes = $this->userRepository->getCountryCodes();
                if (!empty($countryCodes))
                {
                    $data = $countries = array();
                    foreach($countryCodes as $key=>$val)
                    {
                        $countries[] = array("country_name"=>trim($val->name), "country_code"=>$val->country_code) ;
                    }

                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.success')));
                    $data = array("countries"=>$countries);
                    // caching country array
                    Cache::forever('countryCodes', $data);
                }
                else
                {
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                    $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.error')));
                    $data = array();                
                }
            }
            
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data, $checkBadWords=false) ;
            
            /*
            if (Cache::has('countryCodes')) { 
                $countryCodes = Cache::get('countryCodes');                
                \Log::info("<<<<<<<<< In if >>>>>>>>>");
            } else {
                $countryCodes = $this->userRepository->getCountryCodes();
                \Log::info("<<<<<<<<< In else >>>>>>>>>");
                Cache::add('countryCodes', $countryCodes, 1000);                
            } 
            */
            
            /*if (Cache::has('countryCodes')) { 
                $countryCodes = Cache::get('countryCodes');                
            } else {
                $countryCodes = $this->userRepository->getCountryCodes();
                Cache::forever('countryCodes', $countryCodes);                
            }
            if (!empty($countryCodes))
            {
                $data = $countries = array();
                foreach($countryCodes as $key=>$val)
                {
                    $countries[] = array("country_name"=>trim($val->name), "country_code"=>$val->country_code) ;
                }
                $data = array("countries"=>$countries) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }*/
            
        }
        /*
         * industries
         */
        public function getIndustries()
        {
            if (Cache::has('industries')) { 
                $industriesResult = Cache::get('industries');                                
            } else {
                $industriesResult = $this->userRepository->getIndustries();                
                Cache::add('industries', $industriesResult, 1000);                
            }            
            if (!empty($industriesResult))
            {
                $data = $industries = array();
                foreach($industriesResult as $key=>$val)
                {
                    $industries[] = array("industry_name"=>trim($val->name), "industry_id"=>$val->id) ;
                }
                $data = array("industries"=>$industries) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.industries.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data, $checkBadWords=false) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.industries.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        
        /*
         * get job functions
         */
        public function getJobFunctions()
        {
        	if (Cache::has('jobfunctions')) { 
                $jobFunctionsResult = Cache::get('jobfunctions');                                
            } else {
                $jobFunctionsResult = $this->userRepository->getJobFunctions();                
                Cache::add('jobfunctions', $jobFunctionsResult, 1000);                
            }  
	        // $jobFunctionsResult = $this->userRepository->getJobFunctions();
	        if (!empty($jobFunctionsResult))
	        {
	            $data = $jobFunctions = array();
	            foreach($jobFunctionsResult as $key=>$val)
	            {
	                $jobFunctions[] = array("job_function_name"=>trim($val->name), "job_function_id"=>$val->id) ;
	            }
	            $data = array("job_functions"=>$jobFunctions) ;
	            $message = array('msg'=>array(Lang::get('MINTMESH.job_functions.success')));
	            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data, $checkBadWords=false) ;
	        }
	        else
	        {
	            $message = array('msg'=>array(Lang::get('MINTMESH.job_functions.error')));
	            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
	        }
        }
        
        public function sendNotification($fromUser, $neofromUser, $email, $notificationType = 0, $extraInserts = array(), $otherInfoParams = array(),$parse=1, $nonMintmesh=0)
        {
            $userDeviceResults = $data = array();
            if (!empty($parse))//send if direct notification
            {
                if (!empty($email) && !empty($neofromUser))
                {
                    if (strpos($email, "@") !== false) {//if emailid
                        $userDeviceResults = $this->neoUserRepository->getDeviceToken($email);
                    }else{//else is a number with non mintmesh
                        $userDeviceResults[0][0] = $this->neoUserRepository->getNonMintmeshUserDetails($email);
                       // print_r($userDeviceResults[0]->getProperties());exit;
                    }
                    $is_mintmesh = 1 ;
                    
                    if (!empty($userDeviceResults))
                    foreach ($userDeviceResults as $userDeviceResult)
                    {
                        $other_email = $other_phone = $otherUserDetails = "" ;
                        $userDetails = isset($userDeviceResult[0])?$userDeviceResult[0]->getProperties():array() ;
                        $deviceDetails = isset($userDeviceResult[1])?$userDeviceResult[1]->getProperties():array() ;
                        if (!empty($userDetails))
                        {
                            $msg = ucfirst($neofromUser->fullname)." ".Lang::get('MINTMESH.notifications.messages.'.$notificationType) ;
                            if (!empty($otherInfoParams))
                            {
                                if (!empty($otherInfoParams['other_user']))//for not mintmesh
                                {
                                    $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($otherInfoParams['other_user']) ;
                                    if (!count($otherUserDetails)){//if non mintmesh user
                                        $otherUserDetails = $this->neoUserRepository->getNonMintmeshUserDetails($otherInfoParams['other_user']) ; 
                                        $is_mintmesh = 0 ;
                                        $other_phone = $otherUserDetails->phone ;
                                    }
                                
                                if (count($otherUserDetails)){
                                    $other_email = !empty($otherUserDetails->emailid)?$otherUserDetails->emailid:"" ;//for non mintmesh users
                                }
                                }
                            }
                            //log push notification
                            $notificationLog = array(
                                    'notifications_types_id' => $notificationType,
                                    'from_user' => $fromUser->id,
                                    'from_email' => $fromUser->emailid,
                                    'to_email' => !empty($userDetails['emailid'])?$userDetails['emailid']:"",
                                    'to_phone' => empty($userDetails['emailid'])?$userDetails['phone']:"",
                                    'other_email' => $other_email,
                                    'other_phone'=>$other_phone,
                                    'for_mintmesh' => $is_mintmesh,
                                    'message' => Lang::get('MINTMESH.notifications.messages.'.$notificationType),
                                    'ip_address' => !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'',
                                    'created_at' => gmdate('Y-m-d H:i:s')
                                ) ;
                            //add other status 1 to redirect to profile
                            if (in_array($notificationType, $this->directProfileRedirections))
                            {
                                $notificationLog['other_status'] = '1' ;
                            }
                            else if (in_array($notificationType, $this->other_status_diferrent))
                            {
                                $notificationLog['other_status'] = '4' ;//for referrals
                            }
                            else if (in_array($notificationType, $this->declines))
                            {
                                $notificationLog['other_status'] = '2' ;//for referrals
                            }
                            if (!empty($extraInserts))
                            {
                                foreach ($extraInserts as $k=>$e)
                                {
                                    $notificationLog[$k]=$e ;
                                }

                            }
                            $t = $this->userRepository->logNotification($notificationLog);
                            //add non mintmesh user names if empty
                            if (count($otherUserDetails)){
                                $notificationRow = (object) $notificationLog;
                                if (in_array($notificationType, $this->notificationsTypes))
                                {
                                    //check if user has name
                                    if (!empty($otherUserDetails->fullname) && !empty(trim($otherUserDetails->fullname))){
                                       $thirdFullName = $otherUserDetails->fullname ;
                                    }else{
                                        $thirdUserResult = $this->getNonMintmeshUserName($notificationRow);//$otherNoteUser
                                        if (!empty($thirdUserResult->fullname)){
                                            $thirdUserResult->fullname = trim($thirdUserResult->fullname);
                                        }
                                        $fName = str_replace("-","",$notificationLog['other_phone']);
                                        $checkFName = $fName." ".$fName;
                                        //$thirdFullName = (!empty($thirdUserResult->fullname) && $thirdUserResult->fullname != $notificationLog['other_phone']." ".$notificationLog['other_phone'])?$thirdUserResult->fullname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                                        $thirdFullName = (empty($thirdUserResult->fullname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->fullname == $checkFName || $thirdUserResult->fullname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->fullname);
                                        
                                    }
                                    $msg = $msg." ".$thirdFullName ;
                                }
                                if (in_array($notificationType,$this->extraTextsNotes) && !in_array($notificationType,$this->newServiceNotifications))//for posts
                                {
                                    $msg = $msg." ".Lang::get('MINTMESH.notifications.extra_texts.'.$notificationType) ;
                                }
                            }
                            //for new service push notification
                            if (in_array($notificationType,$this->newServiceNotifications)){
                                # get service name for the request
                                $serviceId = !empty($extraInserts['extra_info'])?$extraInserts['extra_info']:0;
                                $serviceName = $this->referralsRepository->getPostName($serviceId);
                                $msg=$msg." ".trim($serviceName) ;
                                if (in_array($notificationType,$this->extraTextsNotes))//for posts
                                {
                                    $msg = $msg.Lang::get('MINTMESH.notifications.extra_texts.'.$notificationType) ;
                                }
                            }
                            $badge=$battle_cards_count=0;
                            $profilePercentage = $this->calculateProfilePercentageCompletion($userDeviceResult[0]);
                            $battle_cards_count = $this->userRepository->getNotificationsCount($userDeviceResult[0], 'request_connect');
                            $battle_cards_count = $battle_cards_count + Config::get('constants.ADD_BATTLE_CARDS_COUNT');//adding battle cards count
                            $battle_cards_count= ($profilePercentage < 100)?$battle_cards_count+1:$battle_cards_count;
                            if($battle_cards_count > 0)
                            {
                              $badge = $battle_cards_count;
                            }
                            else 
                            {
                                $notification_count = $this->userRepository->getNotificationsCount($userDeviceResult[0], 'all');
                                if($notification_count > 0 ){
                                    $badge = 1;
                                }
                            }
                            #enterprise push notification
                            $serviceId = !empty($extraInserts['extra_info'])?$extraInserts['extra_info']:0;
                            if (in_array($notificationType, $this->enterpriseNotificationsTypes) && !empty($serviceId))
                            {   
                                
                                $message = $from_user = $referral = $referred_by = $relation_count = $referred_by_phone = $notification = '';
                                $noteMsg = Lang::get('MINTMESH.notifications.messages.'.$notificationType);
                                $noteTypes  = $this->userRepository->getNotificationTypeById($notificationType);
                                $noteType   = !empty($noteTypes[0]->name)?$noteTypes[0]->name:'';
                                
                                if($notificationType == 27){
                                    $companyDetails = $this->neoPostRepository->getPostCompany($serviceId); 
                                    $extra_msg      = Lang::get('MINTMESH.notifications.extra_texts.'.$notificationType) ; 
                                    $postDetails    = $this->neoUserRepository->getPost($serviceId);
                                    $serviceName    = !empty($postDetails->service_name)?$postDetails->service_name:'';
                                    $companyName    = !empty($postDetails->company)?$postDetails->company:'';
                                    $message        = $companyName." ".$noteMsg." ".$serviceName.$extra_msg;
                                    
                                }else if($notificationType == 28){

                                    $campDetails  = $this->neoUserRepository->getCampaign($serviceId);
                                    $extra_msg      = Lang::get('MINTMESH.notifications.extra_texts.'.$notificationType) ; 
                                    $companyCode  = !empty($campDetails->company_code)?$campDetails->company_code:'';
                                    $campaignType = !empty($campDetails->campaign_type)?$campDetails->campaign_type:'';
                                    #get company details here
                                    if($companyCode){
                                        $companyData    = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
                                        $companyData    = isset($companyData[0])?$companyData[0]:0;
                                        $companyName    = !empty($companyData->name)?$companyData->name:'';//company name  
                                    }
                                    $serviceName    = !empty($note->other_message)?trim($note->other_message):'';
                                    $message        = $companyName." ".$noteMsg." ".$campaignType.$extra_msg;
                                    
                                } else {
                                    
                                    #get company details here
                                    $postDetails    = $this->neoUserRepository->getPost($serviceId);
                                    $serviceName    = !empty($postDetails->service_name)?$postDetails->service_name:'';
                                    $companyDetails = $this->neoPostRepository->getPostCompany($serviceId);    
                                    $companyName    = !empty($companyDetails->name)?$companyDetails->name:'';
                                    $emailId        = !empty($userDetails['emailid'])?$userDetails['emailid']:"";
                                    $extra_msg      = Lang::get('MINTMESH.notifications.extra_texts.'.$notificationType) ; 
                                    #referral accept notification
                                    $from_user       = $fromUser->emailid;
                                    $referral        = ($is_mintmesh)?$other_email:$other_phone;
                                    $referred_by     = $emailId;
                                    $relation_count  = 1;
                                    $message         = $companyName." ".$noteMsg.$extra_msg." ".$serviceName;
                                    $referred_by_phone  = ($is_mintmesh)?0:1;
                                    
                                }
                                $data = array(
                                    "message"           => $message,
                                    "from_user"         => $from_user,  
                                    "id"                => $serviceId, 
                                    "referral"          => $referral, 
                                    "referred_by"       => $referred_by,
                                    "relation_count"    => $relation_count,
                                    "referred_by_phone" => $referred_by_phone,
                                    "note_type"         => $noteType 
                                    );
                                    
                            }
                              \Log::info("<<<<<<<<<<<<<<<< In data userGateway  >>>>>>>>>>>>>".print_r($data,1));
                            //$data = array("alert" => $msg,"emailid"=>$fromUser->emailid, "push_id"=>$t->id, "push_type"=>$notificationType, "badge"=>$badge);
                            
                            // Push to Query
                            if (!empty($deviceDetails))
                            {
                                $pushData = array();
                                $pushData['emailid']        = $email;
                                $pushData['deviceToken']    = $deviceDetails['deviceToken'];
                                $pushData['EndpointArn']    = !empty($deviceDetails['endpointArn'])?$deviceDetails['endpointArn']:'';
                                $pushData['os_type']        = !empty($deviceDetails['os_type'])?$deviceDetails['os_type']:'';
                                $pushData['data']           = $data;
                                Queue::push('Mintmesh\Services\Queues\PushNotificationQueue', $pushData, 'Notification');
                                
                            }
                        }
                    }
                }
            }
        }
        
        /* 
         * get all notifications related to a user
         */
        public function getAllNotifications($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                $loggeduserDetails = $this->formUserDetailsArray($neoLoggedInUserDetails,'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.FULL'));
                $userBadgeCounts = $this->getUserBadgeCounts($loggedinUserDetails,$loggeduserDetails['profile_completion_percentage']);
               if (!empty($userBadgeCounts))
                {
                    foreach ($userBadgeCounts as $k=>$v)
                    {
                        $loggeduserDetails[$k]=$v ;
                    }
                }
                $loggeduserDetails['remaning_days'] = array("days"=>0,"status"=>1,"emailverified"=>1);
                if (count($neoLoggedInUserDetails))
                {
                    //get remaining days only when emailverified is 0
                    if (empty($loggedinUserDetails->emailverified)){
                        $loggeduserDetails['remaning_days'] = $this->userRepository->getRemaningDays($loggeduserDetails['emailid']);
                    }
                    $page = !empty($input['page'])?$input['page']:0;
                    $notifications = $this->userRepository->getNotifications($loggedinUserDetails, $input['notification_type'], $page);

                    if (!empty($notifications))
                    {
                        $notes = array();
                        foreach ($notifications as $notification)
                        {
                            //check if user is not detaileted for only some notification types
                            $is_deleted = $normalFlow = 0;
                            /*if (in_array($notification->notifications_types_id,$this->deleteUserTypes))//commented to check for every notification
                            {*/
                                $connectedToMe = $this->neoUserRepository->checkConnection($loggedinUserDetails->emailid,$notification->from_email);
                                if (!empty($connectedToMe) && !empty($connectedToMe['deleted']))
                                {
                                    $is_deleted = 1;
                                }
                            //}
                            if ($notification->notifications_types_id == 20){//if payment done notification the change the from and other details
                                if (empty($notification->for_mintmesh) && empty($notification->other_email)){//i.e refred non mintmesh user
                                    $otherNoteUser = $neoUserDetails = $this->neoUserRepository->getNonMintmeshUserDetails($notification->other_phone) ;
                                    $normalFlow = 0;
                                }else{
                                    $otherNoteUser = $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($notification->other_email) ;
                                     $normalFlow = 1 ;
                                }
                                
                            }
                            else{
                                $fromNoteUser = $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($notification->from_email) ;
                                $normalFlow = 1 ;
                            }
                            
                            if (!empty($neoUserDetails))
                            {
                                $noReferralsPost = false ;
                                $note = array();
                                $note['post_service_name']="";
                                 if (empty($notification->for_mintmesh) && empty($normalFlow)){
                                    $note = $this->formUserDetailsArray($neoUserDetails, 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                                    //print_r($note);exit;
                                 }else{
                                     $note = $this->formUserDetailsArray($neoUserDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                                 }
                                $thirdName = $thirdFirstName = "";
                                $thirdLastName = "";
                                if (!empty($notification->other_email) || !empty($notification->other_phone))//consider mintmesh and non mintmesh users
                                {
                                    if ($notification->notifications_types_id == 20){//if payment done notification the change the from and other details
                                       $fromNoteUser = $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($notification->from_email) ;
                                       $normalFlow = 1 ;
                                    }
                                    else{
                                        if (empty($notification->for_mintmesh) && empty($notification->other_email)){//i.e refred non mintmesh user
                                            $otherNoteUser = $otherEmailDetails = $this->neoUserRepository->getNonMintmeshUserDetails($notification->other_phone) ;
                                            $normalFlow = 0;
                                        }else{
                                            $otherNoteUser = $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($notification->other_email) ;
                                             $normalFlow = 1 ;
                                        }
                                    }
                                    if (in_array($notification->notifications_types_id, $this->notificationsTypes))
                                    {
                                        $thirdName = !empty($otherNoteUser->fullname)?$otherNoteUser->fullname:'' ;
                                        $fName = !empty($notification->other_phone)?str_replace("-","",$notification->other_phone):$notification->other_email;
                                        $checkFName = $fName." ".$fName;
                                        if (empty(trim($thirdName)) || $thirdName == $checkFName)//if name is empty try to get the name from the import relation
                                        {
                                            $thirdUserResult = $this->getNonMintmeshUserName($notification);//$otherNoteUser
                                            if (!empty($thirdUserResult->fullname)){
                                                $thirdUserResult->fullname = trim($thirdUserResult->fullname);
                                            }
                                            $thirdName = (empty($thirdUserResult->fullname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->fullname == $checkFName || $thirdUserResult->fullname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->fullname);
                                            $thirdFirstName = (empty($thirdUserResult->firstname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->firstname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->firstname);
                                            $thirdLastName = !empty($thirdUserResult->lastname)?$thirdUserResult->lastname:"";
                                        }
                                    }
                                    if (empty($notification->for_mintmesh) && empty($normalFlow)){
                                        $otherUserDetails = $this->formUserDetailsArray($otherEmailDetails, 'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                                    }else{
                                        $otherUserDetails = $this->formUserDetailsArray($otherEmailDetails, 'attribute', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                                    }
                                    foreach ($otherUserDetails as $k=>$v)
                                    {
                                        $note['other_user_'.$k] = $v ;
                                    }
                                    if (empty($note['other_user_fullname']) || (!empty($note['other_user_fullname']) && empty(trim($note['other_user_fullname'])))){
                                        $note['other_user_fullname'] = $thirdName ;
                                        $note['other_user_firstname'] = $thirdFirstName ;
                                        $note['other_user_lastname'] = $thirdLastName ;
                                    }
                                    # for shared details battle card, the from user can be a non mintmesh user
                                    if ($notification->notifications_types_id == 20){
                                        #check if it is a non mintmesh contact
                                        if (empty($neoUserDetails->login_source)){
                                            $note['is_non_mintmesh'] = 1;
                                        }
                                        #check if the fullname are empty
                                        if(empty($neoUserDetails->fullname) || (!empty($neoUserDetails->fullname) && empty(trim($neoUserDetails->fullname)))){
                                            $note['fullname'] = $thirdName ;
                                            $note['firstname'] = $thirdFirstName ;
                                            $note['lastname'] = $thirdLastName ;
                                        }
                                    }

                                }
                                $extra_msg = "";
                                if (in_array($notification->notifications_types_id,$this->extraTextsNotes))//for posts
                                {
                                     //for new service push notification
                                    if (in_array($notification->notifications_types_id,$this->newServiceNotifications)){
                                        # get service name for the request
                                        $serviceId = !empty($notification->extra_info)?$notification->extra_info:0;
                                        $serviceName = $this->referralsRepository->getPostName($serviceId);
                                        $note['post_service_name']=trim($serviceName) ;
                                        $extra_msg=$extra_msg.trim($serviceName) ;
                                    }
                                    $extra_msg = $extra_msg.Lang::get('MINTMESH.notifications.extra_texts.'.$notification->notifications_types_id) ;
                                }
                                if (!empty($notification->other_message))
                                $note['optional_message'] = $this->appEncodeDecode->filterStringDecode($notification->other_message) ;
                                $thirdName = !empty($thirdName)?" ".$thirdName:"";
                                $note['notification'] = $fromNoteUser->fullname." ".$notification->message.$thirdName." ".$extra_msg ;
                                $note['notify_time'] = $notification->created_at ;
                                $note['notification_type'] = $notification->not_type ;
                                $note['message'] = $notification->message ;
                                $note['push_id'] = $notification->id ;
                                $note['read_status'] = $notification->status ;
                                $note['other_status'] = $notification->other_status ;
                                if (empty($notification->for_mintmesh) && !empty($notification->other_phone))//i.e referrals by phone contact
                                {
                                    $note['referred_by_phone'] = 1;
                                }
                                $note['firstname'] = (empty($note['firstname'])?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$note['firstname']);
                                $note['fullname'] = (empty($note['fullname'])?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$note['fullname']);
                                $note['other_user_firstname'] = (empty($note['other_user_firstname'])?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$note['other_user_firstname']);
                                $note['other_user_fullname'] = (empty($note['other_user_fullname'])?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$note['other_user_fullname']);
                                //get post details if post type
                                if (in_array($notification->notifications_types_id,$this->postNotifications))
                                {

                                    $postDetails = $postRelationsDetails = $postDetailsR = array() ;
                                    if ($notification->notifications_types_id == 10 || $notification->notifications_types_id == 23)
                                    {
                                        $e = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                        $f = $notification->from_email ;
                                    }
                                    else if ($notification->notifications_types_id == 20)
                                    {
                                        $e = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                        $f = $notification->from_email ;
                                    }
                                    else if ($notification->notifications_types_id == 14 || $notification->notifications_types_id == 22)
                                    {
                                        $e = $notification->from_email ;
                                        $f = $notification->to_email ;
                                    }
                                    else if ($notification->notifications_types_id == 16 || $notification->notifications_types_id == 13)
                                    {
                                        $e = $notification->from_email ;
                                        $f = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                    }
                                    else if ($notification->notifications_types_id == 12 || $notification->notifications_types_id == 15 || $notification->notifications_types_id == 24 || $notification->notifications_types_id == 25)
                                    {
                                        $e = !empty($notification->other_email)?$notification->other_email:$notification->other_phone ;
                                        $f = $notification->to_email ;
                                    }
                                    else
                                    {
                                        $e = $notification->to_email ;
                                        $f = $notification->from_email ;
                                    }
                                    if (!empty($notification->extra_info) && empty($notification->other_phone)){//i.e for email referrals
                                        $postDetailsR = $this->referralsRepository->getPostAndReferralDetails($notification->extra_info,$f,$e);
                                    }else if(!empty($notification->extra_info) && !empty($notification->other_phone)){//i.e for non mintmesh referrals
                                        $postDetailsR = $this->referralsRepository->getPostAndReferralDetailsNonMintmesh($notification->extra_info,$f,$e);
                                    }
                                    if (count($postDetailsR))
                                    {
                                        $postRelationsDetails = isset($postDetailsR[0][0])?$postDetailsR[0][0]->getProperties():array();
                                        foreach ($postRelationsDetails as $k=>$v)
                                        {
                                            $note['post_'.$k] = $v ;
                                        }
                                        $note['relation_count'] = !empty($postRelationsDetails['relation_count'])?$postRelationsDetails['relation_count']:1;
                                        $postDetails = isset($postDetailsR[0][1])?$postDetailsR[0][1]->getProperties():array();
                                        foreach ($postDetails as $k=>$v)
                                        {
                                            $note['post_'.$k] = $v ;
                                        }
                                        $note['referral'] = $e ;
                                        $postId = $note['post_id'] = !empty($notification->extra_info)?$notification->extra_info:0;
                                        #get industry name
                                        $note['post_industry_name'] = $this->referralsRepository->getIndustryNameForPost($postId);
                                        #get job function name
                                        $note['post_job_function_name'] = $this->referralsRepository->getJobFunctionNameForPost($postId);
                                        #get experience range name
                                        $note['post_experience_range_name'] = $this->referralsRepository->getExperienceRangeNameForPost($postId);
                                        #get employment type name
                                        $note['post_employment_type_name'] = $this->referralsRepository->getEmploymentTypeNameForPost($postId);
                                        if ($e == $f){//self referred
                                            $note['is_self_referred'] = 1 ;
                                        }
                                    }


                                }
                                else if( !empty($notification->extra_info) && in_array($notification->notifications_types_id,$this->refer_nots))
                                {
                                    $note['referral_relation'] =  $notification->extra_info ;
                                }
                                else if (in_array($notification->notifications_types_id,$this->selfReferNotifications))//if self reference type
                                {

                                    $note['relation_id']= !empty($notification->extra_info)?$notification->extra_info:0;

                                }
                                // open requests battle cards
                                if (($notification->notifications_types_id == 10 || $notification->notifications_types_id == 23) && $input['notification_type'] == 'request_connect')//10 is for referenced notification type
                                {
                                    //check if any pending referrals are their
                                    $postId = !empty($notification->extra_info)?$notification->extra_info:0 ;
                                    $activeResult = $this->referralsRepository->checkActivePost($postId);
                                    if (count($activeResult))
                                    {
                                        
                                        //get all referrals
                                        $referrals = $this->referralsRepository->getPostReferences($postId, 0, 0);
                                        if (count($referrals))
                                        {
                                            $note['notification_type'] = 'open_request_battle_card';
                                            $referralsList = $this->classifyReferrals($referrals);
                                            if (!empty($referralsList))
                                            foreach ($referralsList as $r_k=>$r_v)
                                            {
                                                $note[$r_k]=$r_v;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        $noReferralsPost = true ;
                                    }
                                }
                                $note['is_deleted'] = $is_deleted ;
                                $note['request_closed_at'] = $notification->updated_at ;
                                if (!$noReferralsPost)//for post battle cards for which no referrals found
                                $notes[] = $note ;
                            }
                        }
                        $phone_verified = !empty($neoLoggedInUserDetails->phoneverified)?$neoLoggedInUserDetails->phoneverified:0;
                        $data = array("notifications"=>$notes, "phone_verified"=>$phone_verified,'user_details'=>$loggeduserDetails) ;
                        $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                    }
                    else
                    {
                        $phone_verified = !empty($neoLoggedInUserDetails->phoneverified)?$neoLoggedInUserDetails->phoneverified:0;
                        $data = array("notifications"=>array(), "notifications_count"=>0,"phone_verified"=>$phone_verified,'user_details'=>$loggeduserDetails) ;
                        $message = array('msg'=>array(Lang::get('MINTMESH.notifications.no_notifications')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function classifyReferrals($referrals = array())
        {
            $referredBy = '';
            $returnArray = array();
            $returnArray['accepted'] = array();
            $returnArray['pending'] = array();
            if (!empty($referrals))
            {
                foreach ($referrals as $referral)
                {
                    if ($referral[1]->one_way_status != Config::get('constants.REFERRALS.STATUSES.DECLINED'))//skip the declined one
                    {
                        $userDetails = $this->formUserDetailsArray($referral[0],'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                        $relationDetails = $referral[1]->getProperties();
                        if (!empty($referral[1]->referred_by))
                        {
                            $fromUseremail = $referral[1]->referred_by ;
                            $fromUserResult = $this->neoUserRepository->getNodeByEmailId($fromUseremail) ;
                            $fromUserDetails = $this->formUserDetailsArray($fromUserResult,'', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                            $referredBy = $referral[1]->referred_by ;
                        }
                        $referDetails = array();
                        foreach ($userDetails as $k_r=>$v_r)
                        {
                            $referDetails['to_user_'.$k_r] = $v_r ;
                        }
                        foreach ($relationDetails as $k_r=>$v_r)
                        {
                            $referDetails[$k_r] = $v_r ;
                        }
                        if (!empty($fromUserDetails))
                        foreach ($fromUserDetails as $k_r=>$v_r)
                        {
                            $referDetails['from_user_'.$k_r] = $v_r ;
                        }
                        //check if self referred
                        if (!empty($referDetails['from_user_emailid']) && !empty($referDetails['to_user_emailid'])){
                            if ($referDetails['from_user_emailid'] == $referDetails['to_user_emailid']){
                                $referDetails['is_self_referred'] = 1 ;
                            }
                        }
                        //check if referred by phone
                        if (!empty($referral[2][0]) && $referral[2][0] == 'NonMintmesh')//i.e non mintmesh phone contact
                        {
                            $referDetails['referred_by_phone'] = 1 ;
                            //get non mintmesh user name details
                            $referralPhone = !empty($referDetails['to_user_phone'])?$referDetails['to_user_phone']:'';
                            $thirdUserResult = $this->getNonMintmeshReferralDetails($referredBy, $referralPhone, 'phone');
                            if (!empty($thirdUserResult->fullname)){
                                $thirdUserResult->fullname = trim($thirdUserResult->fullname);
                            }
                            $fName = str_replace("-","",$referral[0]->phone);
                            $checkFName = $fName." ".$fName;
                            $referDetails['to_user_fullname'] = (empty($thirdUserResult->fullname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->fullname == $checkFName || $thirdUserResult->fullname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->fullname);
                            $referDetails['to_user_firstname'] = (empty($thirdUserResult->firstname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->firstname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->firstname);
                            $referDetails['to_user_lastname'] = (empty($thirdUserResult->lastname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->lastname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->lastname);
//                            $referDetails['to_user_fullname'] = (!empty($thirdUserResult->fullname) && $thirdUserResult->fullname != $referral[0]->phone." ".$referral[0]->phone)?$thirdUserResult->fullname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
//                            $referDetails['to_user_firstname'] = (!empty($thirdUserResult->firstname) && $thirdUserResult->firstname != $referral[0]->phone)?$thirdUserResult->firstname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
//                            $referDetails['to_user_lastname'] = (!empty($thirdUserResult->lastname) && $thirdUserResult->lastname != $referral[0]->phone)?$thirdUserResult->lastname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                            
                        }
                        else if (empty($referral[2][1])){//non mintmesh email
                        //echo "in this";exit;
                            //get non mintmesh user name details
                            $referralEmail = !empty($referDetails['to_user_emailid'])?$referDetails['to_user_emailid']:'';
                            $thirdUserResult = $this->getNonMintmeshReferralDetails($referredBy, $referralEmail, 'email');
                            if (!empty($thirdUserResult->fullname)){
                                $thirdUserResult->fullname = trim($thirdUserResult->fullname);
                            }
                            $fName = $referral[0]->emailid;
                            $checkFName = $fName." ".$fName;
                            $referDetails['to_user_fullname'] = (empty($thirdUserResult->fullname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->fullname == $checkFName || $thirdUserResult->fullname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->fullname);
                            $referDetails['to_user_firstname'] = (empty($thirdUserResult->firstname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->firstname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->firstname);
                            $referDetails['to_user_lastname'] = (empty($thirdUserResult->lastname)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):($thirdUserResult->lastname == $fName)?Lang::get('MINTMESH.user.non_mintmesh_user_name'):$thirdUserResult->lastname);
                            //$referDetails['to_user_fullname'] = !empty($thirdUserResult->fullname)?$thirdUserResult->fullname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                            //$referDetails['to_user_firstname'] = !empty($thirdUserResult->firstname)?$thirdUserResult->firstname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                            //$referDetails['to_user_lastname'] = !empty($thirdUserResult->lastname)?$thirdUserResult->lastname:Lang::get('MINTMESH.user.non_mintmesh_user_name');
                        }
                        if ($referral[1]->one_way_status == Config::get('constants.REFERRALS.STATUSES.ACCEPTED'))
                        {
                            
                            $returnArray['accepted'][] = $referDetails ;
                        }
                        else 
                        {
                            $returnArray['pending'][] = $referDetails ;
                        }
                    }
                }
                return $returnArray ;
            }
        }
        /*
         * accept connection
         */
        public function acceptConnection($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (count($neoLoggedInUserDetails))
                {
                    $from_email = $loggedinUserDetails->emailid ;
                        $to_email = $input['from_email'] ;
                        $relationAttrs = $otherInfoParams = array();
                        if (!empty($input['refered_by']))
                        {
                            //$referedDetails = $this->neoUserRepository->getNodeByEmailId($input['refered_by']) ;
                            //if (!empty($referedDetails))
                            //{
                                //$relationAttrs['refered_by_id'] = $referedDetails->id ;
                            $relationAttrs['refered_by_email'] = $input['refered_by'] ;
                            $otherInfoParams = array("other_user"=>$input['refered_by']) ;
                            //}
                        }
                        //set to false for self refer flow
                        $acceptConnection = false ;
                        if (!empty($input['self_reference']) && !empty($input['relation_id']))
                        {
                            //check if the reference status is in partial accepted state
                            $relationStatusRes = $this->neoUserRepository->checkSelfReferenceStatus($input['refered_by'],$input['relation_id']);
                            if (!empty($relationStatusRes) && count($relationStatusRes))
                            {
                                if ($relationStatusRes[0][0]->status == Config::get('constants.REFERENCE_STATUS.PENDING'))
                                {
                                    //means first user is accepting
                                    //change the status to partial accepted
                                    $relStatusChange = $this->neoUserRepository->changeSelfReferContactStatus($input['relation_id'],Config::get('constants.REFERENCE_STATUS.PARTIAL_ACCEPTED'));
                                }
                                else // first member accepted so can connect them
                                {
                                    $relStatusChange = $this->neoUserRepository->changeSelfReferContactStatus($input['relation_id'],Config::get('constants.REFERENCE_STATUS.SUCCESS'));
                                    $acceptConnection = true ;
                                }
                            }
                        }
                        else //normal acceptance
                        {
                            $acceptConnection = true ;
                        }
                        if ($acceptConnection)
                        {
                            $responce = $this->neoUserRepository->acceptConnection($from_email, $to_email, $relationAttrs);
                        }
                        //send notification
                        //$fromUser = $this->userRepository->getUserByEmail($from_email);
                        //$neofromUser = $this->neoUserRepository->getNodeByEmailId($from_email) ;

                        if (!empty($input['refered_by']) && empty($input['self_reference']))//for request_reference flow
                        {
                            $extraInserts = array();
                            //take base relation id
                            $rel_id= !empty($input['base_rel_id'])?$input['base_rel_id']:0;
                            $extraInserts['extra_info'] = $rel_id ;
                            //send notification to p1
                            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $to_email, 7, $extraInserts, $otherInfoParams);
                            //change relation status between p1 and p2
                            $this->neoUserRepository->changeRelationStatus($input['from_email'], $input['refered_by'], $loggedinUserDetails->emailid,  Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'), Config::get('constants.REFERENCE_STATUS.SUCCESS'), Config::get('constants.POINTS.REFER_REQUEST'));
                            // log the points for p2
                            $this->userRepository->logLevel(1, $input['refered_by'], $input['from_email'], $loggedinUserDetails->emailid,Config::get('constants.POINTS.REFER_REQUEST'));
                            //points for p3
                            $this->userRepository->logLevel(5, $loggedinUserDetails->emailid, $input['refered_by'], $input['from_email'],Config::get('constants.POINTS.ACCEPT_CONNECTION_REFERRAL'));
                            $this->neoUserRepository->changeRelationStatus($input['refered_by'], $loggedinUserDetails->emailid, $input['from_email'], Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'), Config::get('constants.REFERENCE_STATUS.SUCCESS'));
                            //send notification to refered by person
                            $otherInfoParams = array("other_user"=>$input['from_email']) ;
                            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $input['refered_by'], 6, $extraInserts, $otherInfoParams);
                        }
                        else if(!empty($input['refered_by']) && !empty($input['self_reference']) && !empty($input['relation_id']))
                        {
                            //send notification to members
                            $otherInfoParams = array("other_user"=>$input['from_email']) ;
                            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $input['refered_by'], 18, array(), $otherInfoParams);
                            //send notification to members
                            $otherInfoParams = array("other_user"=>$input['refered_by']) ;
                            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $input['from_email'], 19, array(), $otherInfoParams);
                            if ($acceptConnection)
                            {
                                $this->userRepository->logLevel(1, $input['refered_by'], $input['from_email'], $loggedinUserDetails->emailid,Config::get('constants.POINTS.REFER_REQUEST'));
                            }
                        }
                        else //for one to one connection
                        {
                            $this->neoUserRepository->changeRelationStatus($input['from_email'], $loggedinUserDetails->emailid,'', Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION'), Config::get('constants.REFERENCE_STATUS.SUCCESS'));
                            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $to_email, 2, array(), $otherInfoParams);
                        }
                        $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;

                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
            
        }
        
        /*
         * get connected users
         */
        public function getConnectedAndMMUsers($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                //$neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (!empty($input['emailid']))
                {
                    $emailid = $input['emailid'] ;
                }
                else
                {
                    $emailid = $loggedinUserDetails->emailid ;
                }
                $users = $this->neoUserRepository->getConnectedAndMMUsers($loggedinUserDetails->emailid);
                $u = array();
                if (count($users))
                {
                   foreach ($users as $user)
                    {
                       if (!empty($user[0]->emailid) && $user[0]->emailid != $emailid){//do not display my own contact
                            $uId = $user[0]->getID();
                            $u[$uId] = $this->formUserDetailsArray($user[0],'property', Config::get('constants.USER_ABSTRACTION_LEVELS.BASIC'));
                            $connected = $this->neoUserRepository->checkConnection($emailid,$user[0]->emailid);
                            /*if (!empty($input['emailid']))//not required for now
                            {
                                //check if connected to me
                                $connectedToMe = $this->neoUserRepository->checkConnection($loggedinUserDetails->emailid,$user[0]->emailid);
                                if (!empty($connectedToMe) && !empty($connectedToMe['connected']))//checking for deletion is not required for normal refer and one to one connection
                                {
                                    $u[$uId]['connected_to_me'] = 1 ;
                                }{
                                    //check if in pending state
                                    $pending_with_me = $this->neoUserRepository->checkPendingConnection($loggedinUserDetails->emailid,$user[0]->emailid);
                                    if (!empty($pending_with_me))// if pending
                                    {
                                        $u[$uId]['connected_to_me'] = 2 ;
                                    }else
                                    {
                                        $u[$uId]['connected_to_me'] = 0 ;
                                    }


                                }

                            }
                            */
                            if (!empty($connected) && !empty($connected['connected']))//checking for deletion is not required for normal refer and one to one connection
                            {
                                $u[$uId]['connected'] = 1 ;
                                $u[$uId]['request_sent_at'] = 0;
                            }else
                            {
                                //check if in pending state
                                $pending = $this->neoUserRepository->checkPendingConnection($emailid,$user[0]->emailid);
                                //$u[$uId] = $this->formBasicProfileArray($u[$uId]);
                                if (!empty($pending))// if pending
                                {
                                    $u[$uId]['request_sent_at'] = $pending ;
                                    $u[$uId]['connected'] = 2 ;
                                }else
                                {
                                    $u[$uId]['connected'] = 0 ;
                                    $u[$uId]['request_sent_at'] = 0;
                                }
                            }
                        }
                   }
                }
                $u=$this->appEncodeDecode->callFirstNameSort($u);
                $data = array("users"=>array_values($u)) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_contacts.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;

            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
            
        }
        
        public function formBasicProfileArray($input)
        {
            $return = array();
            if (!empty($input))
            {
                $return['emailid'] = !empty($input['emailid'])?$input['emailid']:"" ;
                $return['fullname'] = !empty($input['fullname'])?$input['fullname']:"" ;
                $return['firstname'] = !empty($input['firstname'])?$input['firstname']:"" ;
                $return['lastname'] = !empty($input['lastname'])?$input['lastname']:"" ;
                $return['position'] = !empty($input['position'])?$input['position']:"" ;
                $return['location'] = !empty($input['location'])?$input['location']:"" ;
                $return['company'] = !empty($input['company'])?$input['company']:"" ;
                $return['dp_path'] = !empty($input['dp_path'])?$input['dp_path']:"" ;
            }
            return $return ;
        }
        /*
         * refer my connection
         */
        public function referMyConnection($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (count($neoLoggedInUserDetails))
                {
                    //send connection request if not connected and a mintmesh
                    if (empty($input['connected_to_me']) && empty($input['non_mintmesh']))
                    {
                        $connectInput = array('emails'=>json_encode(array($input['refer_to'])));
                        $sendRequestConnectRes = $this->processConnectionRequest($connectInput);
                    }
                    //if not connected and not in mintmesh
                    else if (empty($input['connected_to_me']) && !empty($input['non_mintmesh']))
                    {
                        //send invite by email
                        if (!empty($input['invite_via_email']))
                        {
                            $inviteEmail = array();
                            $inviteEmail['emails'] = json_encode(array($input['refer_to'])) ;
                            $inviteEmail['for_email'] = $input['referring'];
                            $inviteResult = $this->contactsGateway->sendReferralInvitations($inviteEmail) ;
                        }
                    }
                    $relationAttrs = array();
                    if (!empty($input['refer_to']))
                    {
                        $relationAttrs['request_for_emailid'] = $input['refer_to'] ;
                        $relationAttrs['status'] = Config::get('constants.REFERENCE_STATUS.PENDING') ;
                    }
                    $referredResponse = $this->neoUserRepository->referMyConnection($loggedinUserDetails->emailid,$input['referring'],$relationAttrs) ;
                    //send notifications to users (u1)
                    $otherInfoParams = array();
                    $otherInfoParams['other_user'] = $input['referring'] ;
                    $extra_insert['extra_info'] = !empty($referredResponse[0][0])?$referredResponse[0][0]->getID():0 ;
                    $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $input['refer_to'], 17, $extra_insert, $otherInfoParams, 1) ;

                    //send notifications to users (u3)
                    $otherInfoParams2 = array();
                    $otherInfoParams2['other_user'] = $input['refer_to'] ;

                    $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $input['referring'], 17, $extra_insert, $otherInfoParams2, 1);
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
                
        /*
         * refer contact
         */
        public function referContact($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (count($neoLoggedInUserDetails))
                {
                    //return if the person whom requesting is not a mintmesh user
                    $isMintmeshUser = $this->neoUserRepository->getNodeByEmailIdMM($input['request_to']) ;
                    if (empty($isMintmeshUser))
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.user.refer_not_mintmesh')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                    $referForDetails = $this->neoUserRepository->getNodeByEmailId($input['request_for']) ;
                    $askToDetails = $this->neoUserRepository->getNodeByEmailId($input['request_to']) ;
                    if (!empty($askToDetails) && !empty($referForDetails))
                    {
                        $notificationTypeId = 3 ;
                        $requestType = $input['request_type'];
                        if ($requestType == 'introduce')
                        {
                           $notificationTypeId = 4 ;
                           $relType = Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION') ;
                        }
                        else
                        {
                            $relType = Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') ;
                        }
                        // create relation
                        $relationAttrs = array();
                        $relationAttrs['message'] = !(empty($input['message']))?$input['message']:"";
                        $relationAttrs['request_for_id'] = $referForDetails->id  ;
                        $relationAttrs['request_for_emailid'] = $referForDetails->emailid  ;
                        $relationAttrs['status'] = Config::get('constants.REFERENCE_STATUS.PENDING') ;
                        $response = $this->neoUserRepository->requestReference($loggedinUserDetails->emailid, $input['request_to'], $relationAttrs, $relType);
                        if ($requestType == 'refer')
                        {
                            $rel_id= !empty($response[0][0])?$response[0][0]:0;
                        }
                        else
                        {
                            $rel_id= !empty($input['base_rel_id'])?$input['base_rel_id']:0;
                        }
                        $extraInserts = array('other_message'=>!(empty($input['message']))?$this->appEncodeDecode->filterString($input['message']):"","extra_info"=>$rel_id);
                        $otherInfoParams = array('other_user'=>$input['request_for']);
                        $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $input['request_to'], $notificationTypeId, $extraInserts, $otherInfoParams);
                        //notify user1 about this
                        if ($requestType == 'introduce')
                        {
                            $setReferRequest = $this->changeReferRequestStatus($input['request_for'], $loggedinUserDetails->emailid, $input['request_to'], Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'), Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'), 5, $rel_id);
                        }
                        $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
            
        }
        public function changeReferRequestStatus($from="", $to="", $for="", $relationType="", $status="", $noteTypeId=0, $rel_id=0)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                 $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                 if (count($neoLoggedInUserDetails))
                 {
                     $this->neoUserRepository->changeRelationStatus($from, $to, $for, $relationType, $status);
                        $otherInfoParams = array('other_user'=>$for);
                        $extraInserts = array("extra_info"=>$rel_id) ;
                        //send notification to u1
                        $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $from, $noteTypeId, $extraInserts, $otherInfoParams);
                 }
                
            }
           
        }
        /*
         * close notification
         */
        public function closeNotification($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $battle_cards_count = 0;
            if ($loggedinUserDetails)
            {
                $notificationDetails = $this->userRepository->getPushDetails($input['push_id']);
                $this->userRepository->closeNotification($input, $notificationDetails);
                //$notifications_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'all');
                $notifications_count = $this->getBellNotificationCount($loggedinUserDetails->emailid);//for enterprise
                $battle_cards_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'request_connect');
                if ($input['request_type'] == 'decline')
                {
                    //change status to declined
                    if (Config::get('constants.MAPPED_RELATION_TYPES.'.$notificationDetails->notifications_types_id))
                    {
                        $this->neoUserRepository->changeRelationStatus($notificationDetails->from_email, $notificationDetails->to_email, $notificationDetails->other_email, Config::get('constants.MAPPED_RELATION_TYPES.'.$notificationDetails->notifications_types_id), Config::get('constants.REFERENCE_STATUS.DECLINED'));
                    }

                    if ($notificationDetails->notifications_types_id == 4 && !empty($notificationDetails->other_email))//if it is introduce request to u3
                    {
                        $rel_id= !empty($input['base_rel_id'])?$input['base_rel_id']:0;
                        //add silent notification for u1 and u2 
                        //add it for u1
                         $notificationLog = array(
                                'notifications_types_id' => 8,
                                'from_user' => $loggedinUserDetails->id,
                                'from_email' => $loggedinUserDetails->emailid,
                                'to_email' => $notificationDetails->other_email,
                                'other_email' => "",
                                'message' => Lang::get('MINTMESH.notifications.messages.8'),
                                'extra_info'=>$rel_id,
                                'ip_address' => $_SERVER['REMOTE_ADDR'],
                                'created_at' => date('Y-m-d H:i:s')
                            ) ;
                        $t = $this->userRepository->logNotification($notificationLog);
                        //add it for u2
                         $notificationLog = array(
                                'notifications_types_id' => 9,
                                'from_user' => $loggedinUserDetails->id,
                                'from_email' => $loggedinUserDetails->emailid,
                                'to_email' => $notificationDetails->from_email,
                                'other_email' => $notificationDetails->other_email,
                                'message' => Lang::get('MINTMESH.notifications.messages.9'),
                                'extra_info'=>$rel_id,
                                'ip_address' => $_SERVER['REMOTE_ADDR'],
                                'created_at' => date('Y-m-d H:i:s')
                            ) ;
                        $t = $this->userRepository->logNotification($notificationLog);
                        $relationAttrs = array("reffered_by"=>$notificationDetails->from_email) ;
                        $this->neoUserRepository->createDeclinedRelation($loggedinUserDetails->emailid,$notificationDetails->other_email,$relationAttrs);
                    }


                }
                $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
                $data = array("notifications_count"=>$notifications_count, "battle_cards_count"=>$battle_cards_count) ;
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        
        public function getConnectionsByLocation($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $userConnections = array();
            $connections = $this->neoUserRepository->getConnectionsByLocation($loggedinUserDetails->emailid, $input['location']);
            if (count($connections))
            {
                foreach ($connections as $connection)
                {
                    $details = $this->formUserDetailsArray($connection[0],'property');;
                    $userConnections[] = $details ;
                }
            }
            $data = array("connections"=>$userConnections) ;
            $message = array('msg'=>array(Lang::get('MINTMESH.get_connections.success')));
            return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
        }
        
        public function getReferenceFlow($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                if(!empty($input['base_rel_id']))
                {
                    //get complete flow
                    $result = $this->neoUserRepository->getReferenceFlow($input['base_rel_id']);
                    if (count($result))
                    {
                        if (!empty($result[0][0]))
                        {
                            $returnArray=array();
                            $p1Details = !empty($result[0][1])?$this->formUserDetailsArray($result[0][1],'property'):array();
                            foreach ($p1Details as $k=>$v)
                            {
                                $returnArray[$k]=$v ;
                            }
                            $p2Details = !empty($result[0][2])?$this->formUserDetailsArray($result[0][2],'property'):array();
                            foreach ($p2Details as $k=>$v)
                            {
                                $returnArray["to_user_".$k]=$v ;
                            }
                            $p3Email = !empty($result[0][0]->request_for_emailid)?$result[0][0]->request_for_emailid:'';

                            $p3NeoDetails = $this->neoUserRepository->getNodeByEmailId($p3Email) ;
                            $p3Details = count($p3NeoDetails)?$this->formUserDetailsArray($p3NeoDetails):array();
                            foreach ($p3Details as $k=>$v)
                            {
                                $returnArray["other_user_".$k]=$v ;
                            }
                            $returnArray['message'] = !empty($result[0][0]->message)?$result[0][0]->message:'';
                            $returnArray['requested_at'] = !empty($result[0][0]->created_at)?$result[0][0]->created_at:'';
                            if ($result[0][0]->status == Config::get('constants.REFERENCE_STATUS.PENDING'))
                            {   

                            }
                            else 
                            {
                                $p2Status = !empty($result[0][0]->status)?$result[0][0]->status:Config::get('constants.REFERENCE_STATUS.PENDING');
                                $p2StatusIn=array(Config::get('constants.REFERENCE_STATUS.SUCCESS'),Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'));
                                if (in_array($p2Status, $p2StatusIn))
                                {
                                    //check for p2-p3 status
                                    $fromEmail = !empty($p2Details['emailid'])?$p2Details['emailid']:'';
                                    $toEmail = !empty($result[0][0]->request_for_emailid)?$result[0][0]->request_for_emailid:'';
                                    $forEmail = !empty($p1Details['emailid'])?$p1Details['emailid']:'';
                                    $returnArray['message'] = !empty($result[0][0]->message)?$result[0][0]->message:'';
                                    $relationCount = !empty($result[0][0]->request_count)?$result[0][0]->request_count:0;
                                    $introDetails = $this->neoUserRepository->getIntroduceConnection($fromEmail, $toEmail,$forEmail, $relationCount);
                                    if (count($introDetails))
                                    {
                                        $returnArray['other_message'] = (isset($introDetails[0][0]->message))?$introDetails[0][0]->message:"" ;
                                        $returnArray['other_status'] = (isset($introDetails[0][0]->status))?$introDetails[0][0]->status:"" ;
                                        $returnArray['introduced_at'] = (isset($introDetails[0][0]->created_at))?$introDetails[0][0]->created_at:"" ;
                                    }
                                    else
                                    {
                                        $returnArray['other_message']="";
                                        $returnArray['other_status'] = Config::get('constants.REFERENCE_STATUS.PENDING') ;
                                    }

                                    if ($returnArray['other_status'] == Config::get('constants.REFERENCE_STATUS.SUCCESS'))//if intro completed then get p3 status
                                    {
                                        //get the time p3 accepted
                                        $completedResult = $this->neoUserRepository->getReferralAcceptConnection($toEmail, $forEmail, $fromEmail);
                                        if (count($completedResult))
                                        {
                                            $returnArray['completed_at'] = (isset($completedResult[0][0]->created_at))?$completedResult[0][0]->created_at:"" ;
                                        }
                                    }
                                }
                            }
                            $returnArray['status'] = $result[0][0]->status ;
                            $returnArray['referral_relation'] = $input['base_rel_id'] ;
                            $returnArray['current_user'] = "p2" ;
                            if (!empty($p1Details) && !empty($p1Details['emailid']))
                            {
                                if ($p1Details['emailid'] == $loggedinUserDetails->emailid)
                                {
                                    //if p1 then remove p2-p3 msg
                                    $returnArray['other_message'] = "";
                                    $returnArray['current_user'] = "p1" ;
                                }
                            }

                            if (!empty($p3Details) && !empty($p3Details['emailid']))
                            {
                                if ($p3Details['emailid'] == $loggedinUserDetails->emailid)
                                {
                                    //if p3 then remove p1-p2 msg
                                    $returnArray['message'] = "";
                                    $returnArray['current_user'] = "p3" ;
                                }
                            }
                             //get referral points
                            $returnArray['points_awarded'] = Config::get('constants.POINTS.REFER_REQUEST') ;
                        }
                        $data =  array('details'=>$returnArray);
                        $message = array('msg'=>array(Lang::get('MINTMESH.reference_flow.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.reference_flow.invalid_input')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.reference_flow.invalid_input')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        public function getLevelsInfo()
        {
            $levelsDetailedInfo = $levelsInfo = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $levels = $this->userRepository->getLevels();
                if (!empty($levels))
                {
                    //print_r($levels);exit;
                $levelsDetailedInfoResult = $this->userRepository->getLevelsLogsInfo($loggedinUserDetails->emailid);
                if (!empty($levelsDetailedInfoResult))
                {
                    $levelsDetailedInfo = $this->formatLevelsInfo($levelsDetailedInfoResult);

                }
                $total_points = 0 ;
                foreach ($levels as $key => $value) {
                    if (array_key_exists($key, $levelsDetailedInfo))
                    {
                        $total_points = $total_points + $levelsDetailedInfo[$key]->max_points ;
                        $levels[$key]['earned_points'] = $total_points;//$levelsDetailedInfo[$key]->max_points ;
                    }
                    else
                    {
                        $levels[$key]['earned_points'] = 0 ;
                    }
                }
                $data = array("total_points"=>$total_points, "levels"=>$levels) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else
                {
                    $data = array() ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.error')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, $data) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
            
        }
        
        public  function formatLevelsInfo($levels)
        {
            $levelsInfo = array();
            if (!empty($levels))
            {
                foreach ($levels as $level)
                {
                    $levelsInfo[$level->levels_id] = $level ;
                }
            }
            return $levelsInfo ;
        }
        
        public function disConnectUsers($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $deleted = $this->neoUserRepository->removeContact($loggedinUserDetails->emailid, $input['emailid']);
                if ($deleted)
                {
                    //create a delete contact relation
                    $deleted = $this->neoUserRepository->createDeleteContactRelation($loggedinUserDetails->emailid, $input['emailid']);
                    
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_disconnect_success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_disconnect_error')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        public function base_64_encode($string1 = "",$string2="")
        {
            return base64_encode ($string1.",".$string2 );
        }
        
        public function base_64_decode($string)
        {
            $decoded = base64_decode ($string);
            $decodedString = array();
            $explodedString=explode(",",$decoded);
            if (!empty($explodedString[1]))//check if it is valid
            {
                $decodedString['string1'] = $explodedString[0];
                $decodedString['string2'] = $explodedString[1];
            }
            else
            {
                $decodedString['string1'] = date("1998-05-06 10:05:05");//set some random expiry date
                $decodedString['string2'] = "";
            }
            
            return $decodedString ;
        }
        public function getLoggedInUser()
        {
            try {
                $resourceOwnerId = $this->authorizer->getResourceOwnerId();
                return $this->userRepository->getUserById($resourceOwnerId);
            } catch (\Exception $e) {
                return false;
            }
            
        }
        
        public function getDeepLinkScheme($os_type,$isEnt=0)
        {
            $deep_link = "";
            if (!empty($os_type))
            {
                if ($os_type == Config::get('constants.ANDROID'))
                {
                    $deep_link = Config::get('constants.MNT_DEEP_LINK_ANDROID');
                }
                else if ($os_type == Config::get('constants.IOS'))
                {
                    if($isEnt){#only from enterprise app
                        $deep_link = Config::get('constants.MNT_ENT_DEEP_LINK_IOS');
                    }else{
                        $deep_link = Config::get('constants.MNT_DEEP_LINK_IOS');
                    }
                }
            }
            return $deep_link ;
        }
        
        public function logout($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $userId         = !empty($loggedinUserDetails->id)?$loggedinUserDetails->id:'';
                #log user activity here
                $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=13);
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                if (count($neoLoggedInUserDetails))
                {
                    $this->neoUserRepository->logout($input['deviceToken'], $neoLoggedInUserDetails);
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.logged_out')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function checkEmailExistance($input)
        {
            $user = $this->userRepository->getUserByEmailWithoutStatus($input['emailid']);
            if (count($user))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function checkPhoneExistance($input)
        {
            //$userCount = $this->userRepository->getUserByPhone($input['phone']);
            $userCount = $this->neoUserRepository->getUserByPhone($input['phone']);
            if (!empty($userCount))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function getSpecificLevelInfo($input)
        {
            $returnResult = array();
            $timeZone     = !empty($input['time_zone']) ? $input['time_zone'] : 0; 
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $result = $this->userRepository->getSpecificLevelInfo($input['level_id'],$loggedinUserDetails->emailid);
                if (!empty($result))
                {
                    foreach ($result as $row)
                    {
                        $created_at = !empty($row->created_at) ? $row->created_at : '';
                        if($created_at){
                            $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
                        }
                        $arr = array() ;
                        $arr['credits'] = $row->points ;
                        $arr['created_at'] = $created_at;
                        $arr['points_type'] = $row->point_type ;
                        if (!empty($row->user_email))
                        {
                            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($row->user_email) ;
                            $details = $this->formUserDetailsArray($neoLoggedInUserDetails,'attribute');
                            $arr['user_details'] = $details ;
                        }
                        if (!empty($row->from_email))
                        {
                            $fromUserDetails = $this->neoUserRepository->getNodeByEmailId($row->from_email) ;
                            $details = $this->formUserDetailsArray($fromUserDetails,'attribute');
                            $arr['from_details'] = $details ;
                        }
                        if (!empty($row->other_email))
                        {
                            if(filter_var($row->other_email, FILTER_VALIDATE_EMAIL)) {
                                $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($row->other_email) ;
                                $type = 'attribute';
                            } else {
                                $otherUserDetailsResult = $this->contactsRepository->getNonMintmeshContact($row->other_email) ;
                                $otherUserDetails = $otherUserDetailsResult[0][0];
                                $type = 'property';
                            }
//                            $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($row->other_email) ;
                            $details = $this->formUserDetailsArray($otherUserDetails,$type);
                            //////////////////////////////
                          $details['fullname'] = (isset($details['fullname']) && !empty(trim($details['fullname'])))?$details['fullname']:Lang::get('MINTMESH.user.non_mintmesh_user_name') ;             
                           //////////////////////////////////////
                            $arr['other_details'] = $details ;
                        }
                        $returnResult[] = $arr ;
                    }
                    $data = array("level_info"=>$returnResult) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else
                {
                    $data = array() ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.error')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, $data) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        
        public function getInfluencersList()
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $influencersList = array();
                $influencers = $this->neoUserRepository->getInfluencersList($loggedinUserDetails->emailid);
                if (!empty($influencers) && count($influencers))
                {
                    foreach ($influencers as $influencer)
                    {
                        $details = $this->formUserDetailsArray($influencer[0], 'property') ;
                        $details['no_of_connections'] = !empty($influencer[1])?$influencer[1]:0;
                         //get known list
                        $knownPeopleInput = $knownPeopleListResult = $knownPeopleList = array();
                        $knownPeopleInput['emailid'] = $loggedinUserDetails->emailid ;
                        $knownPeopleInput['other_email'] = !empty($details['emailid'])?$details['emailid']:'' ;
                        if (!empty($knownPeopleInput['other_email']))
                        {
                            $knownPeopleListResult = $this->getMutualPeopleInInfluncers($knownPeopleInput) ;
                            if (!empty($knownPeopleListResult['data']['users']))
                            foreach ($knownPeopleListResult['data']['users'] as $r=>$v)
                            {
                                $knownPeopleList[] = $v['fullname'] ;
                            }
                            $details['known_people'] = $knownPeopleList ;
                        }
                        $connectionsR = $this->checkConnections($loggedinUserDetails->emailid, '', $details['emailid']);
                        if (!empty($connectionsR))
                        {
                            foreach ($connectionsR as $k=>$v)
                            {
                                $details[$k]=$v ;
                            }

                        }
                        $influencersList[] = $details ;
                    }
                    //get count of influencers
                    $influencersCount = count($influencersList);
                    $data = array("influencers"=>$influencersList, "influencersCount"=>$influencersCount) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.influencers.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.influencers.not_found')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        public function getMutualPeopleInInfluncers($input)
        {
            $userEmail = $input['emailid'] ;
            $result = $this->referralsRepository->getMutualPeople($userEmail,$input['other_email']);
            if (count($result))
            {
                $users = array();
                foreach ($result as $k=>$v)
                {
                    $users[]=$this->formUserDetailsArray($v[0],'property') ;
                }
                $data=array("users"=>$users) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_result')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        public function getRecruitersList($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
               $recruitersList = array();
               $page = !empty($input['page'])?$input['page']:0;
                $recruiters = $this->neoUserRepository->getRecruitersList($loggedinUserDetails->emailid, $page);
                if (!empty($recruiters) && count($recruiters))
                {
                    foreach ($recruiters as $recruiter)
                    {
                        $details = $this->formUserDetailsArray($recruiter[0], 'property') ;
                        $connectionsR = $this->checkConnections($loggedinUserDetails->emailid, '', $details['emailid']);
                        if (!empty($connectionsR))
                        {
                            foreach ($connectionsR as $k=>$v)
                            {
                                $details[$k]=$v ;
                            }

                        }
                        $recruitersList[] = $details;
                    }
                    //get count of recruiters
                    $recruitersCount = $this->neoUserRepository->getRecruitersListCount($loggedinUserDetails->emailid);
                    $data = array("recruiters"=>$recruitersList,"recriutersCount"=>$recruitersCount) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.recruiters.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ; 
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.recruiters.not_found')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
//            $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
//            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
        }
        
        public function doValidation($validatorFilterKey, $langKey) {
             //validator passes method accepts validator filter key as param
            if($this->userValidator->passes($validatorFilterKey)) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get($langKey)));
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                $data = array();                
            } else {
                /* Return validation errors to the controller */
                $message = $this->userValidator->getErrors();
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data) ;
        }
        
        public function getBadWords()
        {
            $badwords = array();
            $badWordsList = $this->userRepository->getBadWords();
            if (!empty($badWordsList))
            {
                foreach ($badWordsList as $badWord)
                {
                    $badwords[]=$badWord->word ;
                }
            }
            $data = $badwords;
            Cache::forever('badWords', $badwords);
                
//            if (Cache::has('badWords')) { 
//                $data = Cache::get('badWords');
//            } 
//            else
//            {
//                $badwords = array();
//                $badWordsList = $this->userRepository->getBadWords();
//                if (!empty($badWordsList))
//                {
//                    foreach ($badWordsList as $badWord)
//                    {
//                        $badwords[]=$badWord->word ;
//                    }
//                }
//                $data = $badwords;
//                Cache::forever('badWords', $badwords);
//            }
                
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg  = self::SUCCESS_RESPONSE_MESSAGE;
            $message      = array('msg'=>array(Lang::get('MINTMESH.bad_words.success')));
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data) ;
            
        }
        
        public function checkUserPassword($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails) {
                //check password
                if(Hash::check($input['password'],$loggedinUserDetails->password)) {
                    $responseMessage = Lang::get('MINTMESH.user.correct_password');
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseData = array();
                }
                else
                {
                    $responseMessage = Lang::get('MINTMESH.user.wrong_password');
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                    $responseData = array();
                }
            }
            else {
                $responseMessage = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $responseData = array();
            }
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
        }
        
        public function getAllSkills() {            
            if (Cache::has('allSkills')) {                 
                $data = Cache::get('allSkills');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg'=>array(Lang::get('MINTMESH.skills.success')));
            } else {                
                $skillsR = $this->userRepository->getSkills();
                if (!empty($skillsR))
                {
                    $data = $skills = array();
                    foreach($skillsR as $key=>$val)
                    {
                        $skills[] = array("skill_name"=>trim($val->name), "skill_id"=>$val->id,"skill_color"=>$val->color) ;
                    }
                    $data = array("skills"=>$skills) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.skills.success')));                    
                    // adding to memcache
                    Cache::add('allSkills', $data, false, 0);
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.skills.error')));
                    $data    = array();
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;                    
                }
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data, $checkBadWords=false);
        }
        
        public function getFilterSkills($input) {
            $skillsR = $this->userRepository->getSkills($input);
            if (!empty($skillsR))
            {
                $data = $skills = array();
                foreach($skillsR as $key=>$val)
                {
                    $skills[] = array("skill_name"=>trim($val->name), "skill_id"=>$val->id,"skill_color"=>$val->color) ;
                }
                $data = array("skills"=>$skills) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.skills.success')));              
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.skills.error')));
                $data    = array();
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;                    
            }            
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data, $checkBadWords=false);
        }
        
        /*
         * get services list for ask flow in profile
         */
        public function getServices($input){
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails) {
               $searchString = !empty($input['search'])?strtolower($input['search']):'';
                $serviceType = !empty($input['service_type'])?strtolower($input['service_type']):'';
                $userCountry = !empty($input['user_country'])?strtolower($input['user_country']):'';
                if (!empty($serviceType) && $serviceType == 'job'){
                    $servicesListResult = $this->userRepository->getJobs($searchString);
                }
                else{
                    $servicesListResult = $this->userRepository->getServices($searchString, $userCountry);
                }
                
                if (!empty($servicesListResult)){
                    $services = array();
                    foreach ($servicesListResult as $service){
                        $services[] = array("service_name"=>trim($service->name), "service_id"=>$service->id) ;
                    }
                    $data = array("services"=>$services) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.services.success')));              
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                }
                else{
                    $data = array() ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.services.not_found')));              
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                } 
            }
            else {
                $message = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data, $checkBadWords=false);
        }
        
        /*
         * get professions list for you are field
         */
        public function getYouAreValues(){
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails) {
                if (Cache::has('allYouAreValues')) {                 
                    $data = Cache::get('allYouAreValues');
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = array('msg'=>array(Lang::get('MINTMESH.professions.success')));
                } else {                
                    $professionsR = $this->userRepository->getYouAreValues();
                    if (!empty($professionsR))
                    {
                       // print_r($professionsR);exit;
                        $data = $professions = array();
                        foreach($professionsR as $key=>$val)
                        {
                            $professions[] = array("you_are_name"=>trim($val->name), "you_are_value"=>$val->id) ;
                        }
                        $data = array("you_are_options"=>$professions) ;
                        $message = array('msg'=>array(Lang::get('MINTMESH.you_are.success')));                    
                        // adding to memcache
                        Cache::forever('allYouAreValues', $data);
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                        $message = array('msg'=>array(Lang::get('MINTMESH.you_are.success')));
                    }
                }
            }
            else{
                $message = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
        }
        
        /*
         * get professions list for you are field
         */
        public function getYouAreValues_v2(){
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails) {
                if (Cache::has('allYouAreValues_v2')) {                 
                    $data = Cache::get('allYouAreValues_v2');
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = array('msg'=>array(Lang::get('MINTMESH.professions.success')));
                } else {                
                    $professionsR = $this->userRepository->getYouAreValues();
                    if (!empty($professionsR))
                    {
                       // print_r($professionsR);exit;
                        $data = $professions = array();
                        foreach($professionsR as $key=>$val)
                        {
                            $professions[] = array("you_are_name"=>trim($val->name), "you_are_value"=>$val->value) ;
                        }
                        $data = array("you_are_options"=>$professions) ;
                        $message = array('msg'=>array(Lang::get('MINTMESH.you_are.success')));                    
                        // adding to memcache
                        Cache::forever('allYouAreValues_v2', $data);
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                        $message = array('msg'=>array(Lang::get('MINTMESH.you_are.success')));
                    }
                }
            }
            else{
                $message = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
        }
    
	/*
         * get professions list for you are field
         */
        public function getProfessions(){
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails) {
                if (Cache::has('allProfessions')) {                 
                    $data = Cache::get('allProfessions');
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = array('msg'=>array(Lang::get('MINTMESH.professions.success')));
                } else {                
                    $professionsR = $this->userRepository->getProfessions();
                    if (!empty($professionsR))
                    {
                       // print_r($professionsR);exit;
                        $data = $professions = array();
                        foreach($professionsR as $key=>$val)
                        {
                            $professions[] = array("profession_name"=>trim($val->name), "profession_value"=>$val->id) ;
                        }
                        $data = array("professions"=>$professions) ;
                        $message = array('msg'=>array(Lang::get('MINTMESH.professions.success')));                    
                        // adding to memcache
                        Cache::forever('allProfessions', $data);
                        $responseCode = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                        $message = array('msg'=>array(Lang::get('MINTMESH.professions.success')));
                    }
                }
            }else{
                $message = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
        }
        
        public function getNonMintmeshUserName($notification){//$neoUserDetails
            $relationDetailsResult = array();
	    $email1 = '';
            if (in_array($notification->notifications_types_id, $this->notificationFromP2)){
                $email1 = $notification->from_email ;
            }else if (in_array($notification->notifications_types_id, $this->notificationToP2)){
                $email1 = $notification->to_email ;
            }
            if (!empty($notification->other_email))//i.e non mintmesh with only emailid
            {
                $relationDetailsResult = $this->contactsRepository->getImportRelationDetailsByEmail($email1, $notification->other_email);
            }else if (!empty($notification->other_phone)){
                $relationDetailsResult = $this->contactsRepository->getImportRelationDetailsByPhone($email1, $notification->other_phone);
            }
            return $relationDetailsResult ;
            
        }

        public function getNonMintmeshReferralDetails($referredBy='', $referral='', $referredUsing=''){
            $relationDetailsResult = array();
            if (!empty($referredBy) && !empty($referral) && !empty($referredUsing)){
                if ($referredUsing == 'phone'){
                    $relationDetailsResult = $this->contactsRepository->getImportRelationDetailsByPhone($referredBy, $referral);
                }else{
                    $relationDetailsResult = $this->contactsRepository->getImportRelationDetailsByEmail($referredBy, $referral);
                }
                return $relationDetailsResult ;
                
            }else{
                return $relationDetailsResult;
            }
        }

	 public function loginCall($input=array()){
            $response = array();
            if (!empty($input)){
                $url = url('/')."/v1/user/login";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$input);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response  = curl_exec($ch);
                curl_close($ch);
            }
            return $response ;
        }
        
        public function checkForNonMintmeshPhoneNumber($phone='',$emailid=''){
            if (!empty($phone) && !empty($emailid)){
                try{
                $pushData = array();
                $pushData['user_phone']=$this->appEncodeDecode->formatphoneNumbers($phone);
                $pushData['user_email']=$this->appEncodeDecode->filterString(strtolower($emailid));
                Queue::push('Mintmesh\Services\Queues\NonMintmeshPhoneCheckQueue', $pushData);
                }
                catch(\RuntimeException $e)
                {

                }
            }
            return true;
            
        }
	public function getExperiences() {
            $data = array();
            if (Cache::has('experiences')) {                 
                $data = Cache::get('experiences');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $message = Lang::get('MINTMESH.experience.success');
            } else {
                $experienceDetails = $this->userRepository->getExperiences(); 
                if (!empty($experienceDetails)) {
                    $experiences = array();
                    foreach ($experienceDetails as $experience){
                        $experiences[] = array("experience_name"=>trim($experience->name), "experience_id"=>$experience->id) ;
                    }
                    $data = array("experiences"=>$experiences);
                    // adding to memcache for employment types
                    Cache::forever('experiences', $data);
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = Lang::get('MINTMESH.experience.success');
                }else{
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                    $message = Lang::get('MINTMESH.experience.failure');
                }
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
        }
        
        public function getEmploymentTypes() {
            $data = array();
            if (Cache::has('employmenttypes')) {                 
                $data = Cache::get('employmenttypes');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $message = Lang::get('MINTMESH.employment_types.success');
            } else {
                $getEmploymentTypes = $this->userRepository->getEmploymentTypes();
                if (!empty($getEmploymentTypes)) {
                    $employmentTypes = array();
                    foreach ($getEmploymentTypes as $getEmploymentType){
                        $employmentTypes[] = array("employment_type_name"=>trim($getEmploymentType->name), "employment_type_id"=>$getEmploymentType->id) ;
                    }
                    $data = array("employmentTypes"=>$employmentTypes);
                    // adding to memcache for employment types
                    Cache::forever('employmenttypes', $data);
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = Lang::get('MINTMESH.employment_types.success');
                } else {
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                    $message = Lang::get('MINTMESH.employment_types.failure');
                }
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
        }
        
        public function uploadResumeForRefer($resume='', $forMintmesh=0){
            if (!empty($resume))
            {
                $response="";
                $originalFileName      =  $resume->getClientOriginalName();
                $originalFileExtension =  $resume->getClientOriginalExtension();
                $originalFileSize      =  $resume->getClientSize();
                $bucketSource = Config::get('constants.S3BUCKET_NON_MM_REFER_RESUME') ;
                 //cheking file format              
               if(in_array($originalFileExtension, $this->allowedResumeExtensions)){
                   //cheking file size
                   if($originalFileSize <= $this->resumeMaxSize ){
                       if (!empty($forMintmesh)){#is mintmesh
                           $bucketSource = Config::get('constants.S3BUCKET_MM_REFER_RESUME') ;
                       }
                        //upload the file
                        $this->userFileUploader->source = $resume ;
                        $this->userFileUploader->destination = $bucketSource ;
                        $renamedFileName = $this->userFileUploader->uploadToS3();
                        $response  = $renamedFileName;
                   }
                   else
                   {
                      $response = "uploaded_large_file";   
                   }        
               }
               else
               {
                  $response = "invalid_file_format";
               }
            }
            return $response ;
        }
        
        public function sendAttachmentResumeToP1($fromEmail='', $toEmail='', $resume_path='', $userNameDetails=array()){
            if (!empty($resume_path)){
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.resume_attachment');
                $this->userEmailManager->emailId = $toEmail;
                $dataSet = array();
                $dataSet['attachment_path'] = $resume_path;
                $dataSet['name'] = !empty($userNameDetails['to_name'])?$userNameDetails['to_name']:"";
                $dataSet['for_name'] = !empty($userNameDetails['for_name'])?$userNameDetails['for_name']:"The Contact";
               // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.resume_attachment').$dataSet['for_name'].".";
                $this->userEmailManager->name = "";
                $email_sent = $this->userEmailManager->sendMail();
                $emailLog = array(
                               'emails_types_id' => 4,
                               'from_user' => 0,
                               'from_email' => $fromEmail,
                               'to_email' => $toEmail,
                               'related_code' => "",
                               'sent' => $email_sent,
                               'ip_address' => $_SERVER['REMOTE_ADDR']
                           ) ;
                $this->userRepository->logEmail($emailLog);
            }
        }
        
        public function getParser() {
           return $this->parserManager->processParsing();
       }
       
       /*
         * countries
         */
        public function getCountries($input)
        {
            $name = !empty($input['search'])?$input['search']:'';
            $data = $countries = array();     
            $countryCodes = $this->userRepository->getCountryCodes($name);
            if (!empty($countryCodes))
            {
                foreach($countryCodes as $key=>$val)
                {
                    $countries[] = array("country_name"=>trim($val->name), "country_code"=>$val->country_code) ;
                }
                $data = array("countries"=>$countries);
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg  = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.success')));
            } else {
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg  = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.error')));               
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data, $checkBadWords=false) ;
        }
        
        public function getSkillsFromJobTitle($title='', $description='')
        {   $matchesAry   = array();
            $getSkillsAry = $this->userRepository->getSkills();           
            $jobData      = $title." ".$description;
            foreach ($getSkillsAry as $skill){
                $matches  ='';
                $keywords = preg_quote($skill->name, '/');
                preg_match("/\b$keywords\b/i", $jobData, $matches, PREG_OFFSET_CAPTURE);
                if(isset($matches[0]) && isset($matches[0][0])){
                     $matchesAry[] = $matches[0][0];
                }
            }
            //$matchesAry = array_unique($matchesAry, SORT_REGULAR);
            $return =  implode(', ', $matchesAry);
            return $return;
        }
        
        /* 
         * get notifications related to a user
         */
        public function getBellNotifications($input)
        {
            $notifications  = $returnNote = array();
            $page           = !empty($input['page'])?$input['page']:0;
            $timeZone       = !empty($input['time_zone']) ? $input['time_zone'] : 0;
            $noteType       = !empty($input['notification_type'])?$input['notification_type']:'';
            $defaultName    = Lang::get('MINTMESH.user.non_mintmesh_user_name');
            $user           = $this->getLoggedInUser();
            $emailId        = $user->emailid;
            $userId         = !empty($user->id)?$user->id:'';
            #log user activity here
            $this->userRepository->addUserActivityLogs($userId, $appType=1, $moduleType=5);
            $count = FALSE;
            $userDetails    = $this->neoUserRepository->getNodeByEmailId($user->emailid);
            $unreadCount    = $this->getBellNotificationCount($emailId);
            $notifications  = $this->userRepository->getBellNotifications($emailId, $count, $page);
            if (!empty($notifications)){
                   
                foreach ($notifications as $note)
                {
                    $dpImage = '';
                    $fromUser    = $noteAry = $otherUser = array();
                    $fromEmail   = !empty($note->from_email)?$note->from_email:'';
                    $otherPhone  = !empty($note->other_phone)?$note->other_phone:'';
                    $otherEmail  = !empty($note->other_email)?$note->other_email:'';
                    $forMintmesh = !empty($note->for_mintmesh)?$note->for_mintmesh:0;
                    $nTypeId     = !empty($note->notifications_types_id)?$note->notifications_types_id:0;
                    
                    if($fromEmail){
                        $fromUser = $this->neoUserRepository->getNodeByEmailId($fromEmail);
                    }
                    #i.e refred non mintmesh user
                    if (!empty($forMintmesh) && !empty($otherPhone)){
                        $otherUser = $otherEmailDetails = $this->neoUserRepository->getNonMintmeshUserDetails($otherPhone) ;
                        
                    }else{
                        $otherUser = $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($otherEmail) ;

                    }
                    #i.e non mintmesh with only emailid
                    if (!empty($otherEmail))
                    {
                        $otherUserRel = $this->contactsRepository->getImportRelationDetailsByEmail($emailId, $otherEmail);
                    }else if (!empty($otherPhone)){
                        $otherUserRel = $this->contactsRepository->getImportRelationDetailsByPhone($emailId, $otherPhone);
                    }
                   
                    $thirdName  = $thirdFirstName = $thirdLastName = $extra_msg = '';
                    $thirdName  = !empty($otherUserRel->fullname)?$otherUserRel->fullname:'' ;
                    $fName      = !empty($otherPhone)?str_replace("-","",$otherPhone):$otherEmail;
                    $checkFName = $fName." ".$fName;
                    if (empty(trim($thirdName)) || $thirdName == $checkFName)//if name is empty try to get the name from the import relation
                    {
                        $thirdName      = !empty($otherUserRel->fullname)?$otherUserRel->fullname:$defaultName;
                        $thirdFirstName = !empty($otherUserRel->firstname)?$otherUserRel->firstname:$defaultName;
                        $thirdLastName  = !empty($otherUserRel->lastname)?$otherUserRel->lastname:"";
                    }
                    $serviceName = $serviceId = $companyName = '';
                    $extra_msg    = Lang::get('MINTMESH.notifications.extra_texts.'.$nTypeId) ;
                    # get service name for the request
                    $serviceId = !empty($note->extra_info)?$note->extra_info:'';
                    if($note->note_type == 'new_service' && !empty($serviceId)){
                        #new job notification
                        $postDetails  = $this->neoUserRepository->getPost($serviceId);
                        $extra_msg    = Lang::get('MINTMESH.notifications.extra_texts.'.$nTypeId) ;
                        $created_at   = !empty($postDetails->created_at)?$postDetails->created_at:'';
                        if($created_at){
                            $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
                        }
                        $noteAry['job_id']          = $serviceId;
                        $noteAry['service_name']    = !empty($postDetails->service_name)?$postDetails->service_name:'';
                        $noteAry['company_name']    = !empty($postDetails->company)?$postDetails->company:'';
                        $noteAry['created_by']      = !empty($postDetails->created_by)?$postDetails->created_by:'';
                        $noteAry['created_at']      = $created_at;
                        $serviceName                = !empty($note->other_message)?trim($note->other_message):'';
                        $noteAry['notification']    = $noteAry['company_name']." ".$note->message." ".$noteAry['service_name'].$extra_msg;
                        #get company details here
                        $companyData = $this->neoEnterpriseRepository->connectedCompanyDetails($noteAry['created_by']);
                        $noteAry['dp_image']        = !empty($companyData->logo)?$companyData->logo:'';

                    } else if($note->note_type == 'new_campaign' && !empty($serviceId)){
                        #new campaign notification
                        $postDetails  = $this->neoUserRepository->getCampaign($serviceId);
                        $extra_msg    = Lang::get('MINTMESH.notifications.extra_texts.'.$nTypeId) ;
                        $created_at   = !empty($postDetails->created_at)?$postDetails->created_at:'';
                        if($created_at){
                           $created_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($created_at, $timeZone)));
                        }
                        
                        $noteAry['campaign_id']     = $serviceId;
                        $noteAry['campaign_name']   = !empty($postDetails->campaign_name)?$postDetails->campaign_name:'';
                        $noteAry['campaign_type']   = !empty($postDetails->campaign_type)?$postDetails->campaign_type:'';
                        $noteAry['created_by']      = !empty($postDetails->created_by)?$postDetails->created_by:'';
                        $noteAry['created_at']      = $created_at;
                        $companyCode                = !empty($postDetails->company_code)?$postDetails->company_code:'';
                        #get company details here
                        if($companyCode){
                            $companyData    = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
                            $companyData    = isset($companyData[0])?$companyData[0]:0;
                            $companyName    = !empty($companyData->name)?$companyData->name:'';//company name  
                            $dpImage        = !empty($companyData->logo)?$companyData->logo:'';//company logo 
                        }
                        $noteAry['company_name']    = $companyName;
                        $serviceName                = !empty($note->other_message)?trim($note->other_message):'';
                        $noteAry['notification']    = $noteAry['company_name']." ".$note->message." ".$noteAry['campaign_type'].$extra_msg;
                        $noteAry['dp_image']        = $dpImage;
                   
                    }else if(($note->note_type == 'post_one_way_notify' || $note->note_type == 'post_declined'|| $note->note_type == 'post_one_way_self_notify'|| $note->note_type == 'post_declined_self' || $note->note_type == 'referral_interviewed'|| $note->note_type == 'referral_offered'|| $note->note_type == 'referral_hired') && !empty($serviceId)){
                        #get company details here
                        $companyDetails = $this->neoPostRepository->getPostCompany($serviceId);
                        $postDetails    = $this->neoUserRepository->getPost($serviceId);
                        
                            $companyLogo    = !empty($companyDetails->logo)?$companyDetails->logo:'';
                            $companyName    = !empty($companyDetails->name)?$companyDetails->name:'';
                        $extra_msg    = Lang::get('MINTMESH.notifications.extra_texts.'.$nTypeId) ;
                        $noteAry['service_name']    = !empty($postDetails->service_name)?$postDetails->service_name:'';
                        $noteAry['company_name']    = $companyName;
                        #referral accept notification
                        $noteAry['from_user']       = $note->from_email;
                        $noteAry['referral']        = ($forMintmesh)?$otherEmail:$otherPhone;
                        $noteAry['referred_by']     = $emailId;
                        $noteAry['relation_count']  = 1;
                        $noteAry['post_id']         = $serviceId;
                        $noteAry['notification']    = $companyName." ".$note->message." ".$extra_msg." ".$noteAry['service_name'];
                        $noteAry['dp_image']        = $companyLogo;
                        $noteAry['referred_by_phone']  = ($forMintmesh)?0:1;
                    } else if($note->note_type == 'accept_connect'){
                        #accept connect notification
                        $noteAry['other_email']   = $otherEmail;
                        $noteAry['notification']  = $fromUser->fullname." ".$note->message." ".$thirdName." ".$extra_msg;
                        $noteAry['dp_image']      = '';    
                    }
                    $noteCreated_at = !empty($note->created_at) ? $note->created_at : '';
                    if($noteCreated_at){
                           $noteCreated_at = date("Y-m-d H:i:s", strtotime($this->appEncodeDecode->UserTimezone($noteCreated_at, $timeZone)));
                    }
                    $noteAry['other_name']   = $thirdName;
                    $noteAry['push_id']      = $note->id;
                    $noteAry['note_type']    = $note->note_type;                    
                    $noteAry['notify_time']  = $noteCreated_at;
                    $noteAry['read_status']  = $note->status;

                    $returnNote[] = $noteAry;
                }
                        
                $data = array("notifications"=>$returnNote, "unread_count"=>$unreadCount) ;
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.notifications.success')));
            } else {
                $data = array();
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.notifications.no_notifications')));
            }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
        }
        
        public function getBellNotificationCount($emailId)
        {   
           $count = 1; 
           $notificationsCount  = $this->userRepository->getBellNotifications($emailId, $count);
           return count($notificationsCount);   
        }

}
?>
