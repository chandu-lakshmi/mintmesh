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
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Repositories\API\Payment\PaymentRepository;
use Mintmesh\Services\Validators\API\User\UserValidator ;
use Mintmesh\Services\Emails\API\User\UserEmailManager ;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader ;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\APPEncode\APPEncode ;

use Lang;
use Config;
use OAuth;
use URL,Queue;


class UserGateway {
    
    protected $userRepository, $neoUserRepository,$paymentRepository;    
    protected $authorizer, $appEncodeDecode;
    protected $userValidator;
    protected $userEmailManager;
    protected $userFileUploader,$declines, $refer_nots;
    protected $commonFormatter, $postNotifications, $other_status_diferrent, $referralsRepository;
    protected $loggedinUserDetails,$notificationsTypes,$extraTextsNotes,$directProfileRedirections,$infoTypes,$referFlowTypes ;
	public function __construct(UserRepository $userRepository,
                                    NeoUserRepository $neoUserRepository,
                                    Authorizer $authorizer,
                                    UserValidator $userValidator,
                                    UserEmailManager $userEmailManager,
                                    CommonFormatter $commonFormatter,
                                    UserFileUploader $userFileUploader,
                                    APPEncode $appEncodeDecode,
                                    ReferralsRepository $referralsRepository,
                                    PaymentRepository $paymentRepository) {
		$this->userRepository = $userRepository;
                $this->neoUserRepository = $neoUserRepository;
                $this->authorizer = $authorizer;
                $this->userValidator = $userValidator;
                $this->userEmailManager = $userEmailManager ;
                $this->commonFormatter = $commonFormatter ;
                $this->paymentRepository = $paymentRepository ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->referralsRepository = $referralsRepository ;
                $this->userFileUploader = $userFileUploader ;
                $this->notificationsTypes = array('3','4','5','6','9','10','11','12','13','14','15','17','18','19','20','22');
                $this->extraTextsNotes = array('10','11','12','22') ;
                $this->infoTypes = array('experience', 'education', 'certification');
                $this->directProfileRedirections = array('2','12','14');
                $this->declines = array('15','16');
                $this->postNotifications = array(10,11,12,13,14,15,16);
                $this->other_status_diferrent = array(10,12);
                $this->selfReferNotifications = array(17) ;
                $this->referFlowTypes = array(3,4,5,6,7,8,9);
                $this->refer_nots = array(3,4,5,6,7,8,9);
        }
        
        // validation on user inputs for creating a user
        public function validateCreateUserInput($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('create')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
            
        }
        // validation logout
        public function validateUserLogOut($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('logout')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
            
        }
        //validation of connection request
        public function validateConnectionRequestInput($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('connection_request')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
            
        }
        //validation of connection accept
        public function validateAcceptConnectionInput($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('connection_accept')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
            
        }
        public function validateSingleNotificationInput($input)
        {
            
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('get_single_notification')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        public function validateGetUserByEmailInput($input)
        {
            
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('get_user_by_email')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        // validation on user inputs for authenticating a user for special login
        public function validateUserSpecialLoginInput($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('special_login')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.login.login_valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        public function validateNotificationsInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('get_notifications')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        
        }
        // validation on user inputs for updating a user
        public function validateCompleteProfileUserInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('complete_profile')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
           
        }
        
        // validation on user inputs for authenticating a user
        public function validateUserLoginInput($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('login')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.login.login_valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        
        // validation on user inputs for authenticating a facebook user
        public function validateFbLoginInput($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('fb_login')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.fb_login.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        //validation on close notification
        public function validateCloseNotificationInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('close_notification')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;            
        }
        //validation get reference flow input
        public function validateGetReferenceFlowInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('get_reference_flow')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;            
        }
        // validation logout
        public function validateUsersByLocation($input) {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('get_users_by_location')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
            
        }
        //validation on forgot password input
        public function validateForgotPasswordInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('forgot_password')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        
        //validation on reset password input
        public function validateResetPasswordInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('reset_password')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        //validation on reset password input
        public function validateReferContactInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('refer_contact')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        //validation on reset password input
        public function validateEditProfileInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('edit_profile')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        //validation on phone existance input
        public function validatePhoneExistanceInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('validate_phone_existance')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.user.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        //validation on specific level info input
        public function validateLevelsInfo($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('specific_level_info')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        //validation on refer my contact input
        public function validateReferMyContactInfo($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->userValidator->passes('refer_my_contact')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.invalid_input')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->userValidator->getErrors(), array()) ;
        }
        
        /**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */ 
        public function createUser($input) 
        {
            /**
            * NOTE : if you need to access more than one model do this here, All your business
            * logic and validation is handled by this gateway.
            */
            $createdUser = $this->userRepository->createUser($input) ;
            // create a node in neo
            $neoInput = array();
            $neoInput['firstname'] = $input['firstname'];
            $neoInput['lastname'] = $input['lastname'];
            $neoInput['fullname'] = $input['firstname']." ".$input['lastname'];
            $neoInput['emailid'] = $input['emailid'];
            $neoInput['phone'] = $input['phone'];
            $neoInput['phoneverified'] = !empty($input['phone_verified'])?1:0;
            $neoInput['phone_country_name'] = $input['phone_country_name'];
            $neoInput['login_source'] = $input['login_source'];
            //check for existing node in neo4j
            $neoUser =  $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
            if (empty($neoUser))
            {
                $createdNeoUser =  $this->neoUserRepository->createUser($neoInput) ;
            }
            else
            {
                $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
            }
            $deviceToken = $input['deviceToken'] ;
            $this->neoUserRepository->mapToDevice($deviceToken, $input['emailid']) ;
            
            
            if (!empty($createdUser)) {
                //add battle card for phone verification
                 $notificationLog = array(
                        'notifications_types_id' => 21,//21 is the id of notification
                        'from_user' => 0,
                        'from_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                        'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                        'other_email' => '',
                        'message' => "",
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'other_status'=>0
                    ) ;
                $t = $this->userRepository->logNotification($notificationLog);
                //send email to user
                $activationCode = $this->base_64_encode($createdUser->created_at,$createdUser->emailactivationcode);
                // send welcome email to users
                // set email required params
                 $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.user_welcome');
                 $this->userEmailManager->emailId = $input['emailid'];
                 $dataSet = array();
                 $dataSet['name'] = $input['firstname'];
                 $deep_link_type = !empty($input['os_type'])?$input['os_type']:'';
                 $deep_link = $this->getDeepLinkScheme($deep_link_type);
                 $dataSet['desktop_link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/user/activate/".$activationCode ;
                 $appLink = $deep_link.Config::get('constants.MNT_VERSION')."/user/activate/".$activationCode ;
                 //$appLinkCoded = $this->base_64_encode("", $appLink) ; 
                 $dataSet['link'] = $appLink ;
                 $dataSet['email'] = $input['emailid'] ;
                 
                // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
                 $this->userEmailManager->dataSet = $dataSet;
                 $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.welcome');
                 $this->userEmailManager->name = $input['firstname']. " ".$input['lastname'];
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
                        'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                        'related_code' => $activationCode,
                        'sent' => $emailStatus,
                        'ip_address' => $_SERVER['REMOTE_ADDR']
                    ) ;
                 $this->userRepository->logEmail($emailLog);
                 
                $message = array('msg'=>array(Lang::get('MINTMESH.user.create_success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                 
                 } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.create_failure')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                 
            }
	}
        
        public function getSkills($input)
        {
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
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.skills.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
            }
       }
        /*
         * Completing user profile
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
                $this->userFileUploader->destination = public_path().Config::get('constants.DP_PATH') ;
                $renamedFileName = $this->userFileUploader->moveFile();
            }
            
            if (!empty($input['fromLinkedin']))
            {
                $linkedinFileName = $input['linkedinImage'] ;
                $from_linkedin = 1 ;
            }
            if ($this->loggedinUserDetails = $this->getLoggedInUser())
            {
                //get loggedin user
                $neoInput = array();
                $neoInput['emailid'] = $this->loggedinUserDetails->emailid ;
                $neoInput['position'] = $input['position'];
                $neoInput['company'] = $input['company'];
                $neoInput['industry'] = $input['industry'];
                $neoInput['location'] = $input['location'];
                $neoInput['job_function'] = $input['job_function'];
                $neoInput['you_are'] = $input['you_are'];
                $neoInput['from_linkedin'] = $from_linkedin ;
                $neoInput['dp_path'] = url('/').Config::get('constants.DP_PATH') ;
                $neoInput['dp_original_name'] = $originalFileName ;
                $neoInput['dp_renamed_name'] = $renamedFileName ;
                $neoInput['linkedinImage'] = $linkedinFileName ;
                $neoInput['points_earned'] = Config::get('constants.POINTS.COMPLETE_PROFILE') ;
                $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                // log the points
                $countLevel = $this->userRepository->checkCompleteProfileExistance($this->loggedinUserDetails->emailid);
                if (empty($countLevel))
                {
                    $this->userRepository->logLevel(2, $this->loggedinUserDetails->emailid, "", "",Config::get('constants.POINTS.COMPLETE_PROFILE'));
                }
                $message = array('msg'=>array(Lang::get('MINTMESH.user.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.create_failure')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            
            
            
        }
        
        /*
         * actually verifying the user input
         * 
         * @return Response
         */
        public function verifyLogin($inputUserData = array())
        {
           
            // actually authenticating user with oauth
            $oauthResult = $this->authorizer->issueAccessToken() ;
            //check if access code is returned by oauth
            if (isset($oauthResult['access_token']))
            {
                $neoUser =  $this->neoUserRepository->getNodeByEmailId($inputUserData['username']) ;
                if (!empty($neoUser))
                {
                    if (!empty($neoUser->industry))//user has completed profile
                    {
                        if (!empty($neoUser->from_linkedin))//if  linked in
                        {
                            $neoUser->dp_path = $neoUser->linkedinImage ;
                        }
                        else if (!empty($neoUser->dp_renamed_name))
                        {
                            $neoUser->dp_path = $neoUser->dp_path."/".$neoUser->dp_renamed_name ;
                        }
                        else
                        {
                            $neoUser->dp_path = "";
                        }
                    }
                    $job_function_name = $industry_name = "";
                    if (!empty($neoUser->job_function))//get job function name
                    {
                        $job_function_name = $this->userRepository->getJobFunctionName($neoUser->job_function) ;
                    }
                    if (!empty($neoUser->industry))//get job function name
                    {
                        $industry_name = $this->userRepository->getIndustryName($neoUser->industry) ;
                    }
                    $neoUser->job_function_name = $job_function_name;
                    $neoUser->industry_name = $industry_name ;
                    if (isset($neoUser->id))
                            unset($neoUser->id);
                    $oauthResult['user'] = $neoUser ;
                    
                }
                //create a relation for device token
                $deviceToken = $inputUserData['deviceToken'] ;
                $this->neoUserRepository->mapToDevice($deviceToken, $inputUserData['username']) ;
                // returning success message
                $message = array('msg'=>array(Lang::get('MINTMESH.login.login_success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $oauthResult) ;
                
            }
            else
            {
                // returning failure message
                return $this->commonFormatter->formatResponse(406, "error", $oauthResult['error_description'], array()) ;
                
            }
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
                                if (!empty($neoUser->industry))//user has completed profile
                                {
                                    if (!empty($neoUser->from_linkedin))//if  linked in
                                    {
                                        $neoUser->dp_path = $neoUser->linkedinImage ;
                                    }
                                    else if (!empty($neoUser->dp_renamed_name))
                                    {
                                        $neoUser->dp_path = $neoUser->dp_path."/".$neoUser->dp_renamed_name ;
                                    }
                                    else
                                    {
                                        $neoUser->dp_path = "";
                                    }
                                }
                                $job_function_name = $industry_name = "";
                                if (!empty($neoUser->job_function))//get job function name
                                {
                                    $job_function_name = $this->userRepository->getJobFunctionName($neoUser->job_function) ;
                                }
                                if (!empty($neoUser->industry))//get job function name
                                {
                                    $industry_name = $this->userRepository->getIndustryName($neoUser->industry) ;
                                }
                                $neoUser->job_function_name = $job_function_name;
                                $neoUser->industry_name = $industry_name ;
                                if (isset($neoUser->id))
                                        unset($neoUser->id);
                                $oauthResult['user'] = $neoUser ;

                            }
                            //create a relation for device token
                            $deviceToken = $input['deviceToken'] ;
                            $this->neoUserRepository->mapToDevice($deviceToken, $input['emailid']) ;
                            // returning success message
                            $message = array('msg'=>array(Lang::get('MINTMESH.login.login_success')));
                            return $this->commonFormatter->formatResponse(200, "success", $message, $oauthResult) ;
                        }
                        else
                        {
                            // returning failure message
                            return $this->commonFormatter->formatResponse(406, "error", $oauthResult['error_description'], array()) ;

                        }
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                        return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.invalid')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                    
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                
            }

        }
        
        /*
         * processing the request from facebook user
         */
        public function processFbLogin($inputUserData = array())
        {
            $fb = OAuth::consumer( 'Facebook' );
            $fbResult = json_decode( $fb->request( '/me?access_token='.$inputUserData['fb_access_token'] ), true );
            //check if a user already in mysql 
            if (!empty($fbResult))
            {
                $user = $this->userRepository->getUserByEmail($fbResult['email']);
                if (!empty($user))
                {
                    $loginUserData = array();
                    $loginUserData['username'] = $fbResult['email'] ;
                    $loginUserData['password'] = Config::get('constants.FB_PASSWORD') ;
                    $loginUserData['grant_type'] = Config::get('constants.GRANT_TYPE') ;
                    $loginUserData['client_id'] = '875Fvq2wSHf5Rjyl' ;
                    $loginUserData['client_secret'] = 'Mb63nD2ZjsC94RhphxlbjRsBXB1oO1KV' ;
                    return \Response::json($this->verifyLogin($loginUserData));
                }
                else
                {
                    $inputUserData = array();
                    $inputUserData['emailid'] = $fbResult['email'] ;
                    $inputUserData['firstname'] = $fbResult['first_name'] ;
                    $inputUserData['lastname'] = $fbResult['last_name'] ;
                    $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                    $inputUserData['password'] = Config::get('constants.FB_PASSWORD') ;
                    $inputUserData['password_confirmation'] = Config::get('constants.FB_PASSWORD') ;
                    $validation = $this->validateCreateUserInput($inputUserData);
                    if($validation['status'] == 'success') {
                        // creating entry in mysql DB
                        $createResult = $this->createUser($inputUserData);
                        return \Response::json($this->verifyLogin($createResult));
                    }
                }
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
                //set timezone of mysql if different servers are being used
                //date_default_timezone_set('America/Los_Angeles');
                $expiryTime =  date('Y-m-d H:i:s', strtotime($createdTime . " +".Config::get('constants.MNT_USER_EXPIRY_HR')." hours"));
                //check if expiry time is valid
               
                if (strtotime($expiryTime) > strtotime(date('Y-m-d H:i:s')))
                {
                    $userDetails = $this->userRepository->getUserByCode($emailActCode);
                    if (!empty($userDetails)) {
                        // update status of the user to active
                        $this->userRepository->setActive($userDetails->id);
                        $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.success')));
                        $data = array('emailid'=>$userDetails->emailid);
                        return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                        
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                        return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.invalid')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                    
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.activate_user.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                
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
            if (!empty($email))
            {
                //set timezone of mysql if different servers are being used

                //date_default_timezone_set('America/Los_Angeles');
                $expiryTime =  date('Y-m-d H:i:s', strtotime($sentTime . " +".Config::get('constants.MNT_USER_EXPIRY_HR')." hours"));
                //check if expiry time is valid

                if (strtotime($expiryTime) > strtotime(date('Y-m-d H:i:s')))
                {
                    $post=array();
                    $post['email']=$email ;
                    $post['password']=$input['password'];
                    // update status of the user to active
                    $updateCount = $this->userRepository->resetPassword($post);
                    if (!empty($updateCount))
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.success')));
                        return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                        
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.failed')));
                        return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                        
                    }

                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.invalid')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                    
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.reset_password.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                
            }
            
        }
        
        /*
         * send forgot password email to users
         */
        public function sendForgotPasswordEmail($input)
        {
            if (!empty($input))
            {
                //get user details
                $userDetails = $this->userRepository->getUserByEmail($input['emailid']);
                // set email required params
                 $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.forgot_password');
                 $this->userEmailManager->emailId = $input['emailid'];
                 $dataSet = array();
                 $dataSet['name'] =$userDetails['firstname'];
                 //set reset code
                 //set timezone of mysql if different servers are being used
                 //date_default_timezone_set('America/Los_Angeles');
                 $currentTime =  date('Y-m-d H:i:s');
                 $email = md5($input['emailid']) ;
                 $code = $this->base_64_encode($currentTime, $email) ;
                 $deep_link_type = !empty($input['os_type'])?$input['os_type']:'';
                 $deep_link = $this->getDeepLinkScheme($deep_link_type);
                 $appLink = $deep_link.Config::get('constants.MNT_VERSION')."/user/reset_password/".$code ;
                 //$appLinkCoded = $this->base_64_encode("", $appLink) ; 
                 $dataSet['link'] = $appLink ;
                 $dataSet['hrs'] = Config::get('constants.MNT_USER_EXPIRY_HR');
                //$dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
                 $this->userEmailManager->dataSet = $dataSet;
                 $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.forgot_password');
                 $this->userEmailManager->name = $userDetails['firstname']." ".$userDetails['lastname'];;
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
                 if (!empty($email_sent))
                 {
                     $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.success')));
                     return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                 }
                 else
                 {
                     $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.error')));
                     return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
                 }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.forgot_password.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
            }
        }
        public function editProfile($input)
        {
            if (!empty($input['access_token']))
                unset($input['access_token']);
            if (!empty($input))
            {
                $originalFileName = $renamedFileName = $linkedinFileName = "";
                $from_linkedin =  0;
                $data=array();
                if ($this->loggedinUserDetails = $this->getLoggedInUser())
                {
                    if (!empty($input['info_type']) && $input['info_type'] == 'contact')
                    {
                        $contactInput = $input;
                        $contactInput['emailid'] = $this->loggedinUserDetails->emailid ;
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
                        $sectionInfoSuccess = $this->editSectionInfo($sectionInput, $input['info_type']);
                    }
                    else if (!empty($input['info_type']) && $input['info_type'] == 'skills')
                    {
                        $skillsInput = $input;
                        $skillsInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $skillsInfoSuccess = $this->editSkillsInfo($skillsInput);
                    }
                    if (!empty($input['info_type']) && $input['info_type'] == 'resume')
                    {
                        $resumeInput = $input;
                        $resumeInput['emailid'] = $this->loggedinUserDetails->emailid ;
                        $resumeInfoSuccess = $this->editResumeInfo($resumeInput);
                    }
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.edit_success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.edit_failure')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.edit_no_changes')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
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
                $neoInput = array();
                $neoInput['emailid'] = $input['emailid'] ;
                if (!empty($input['dpImage']))
                {
                    $originalFileName = $input['dpImage']->getClientOriginalName();
                    //upload the file
                    $this->userFileUploader->source = $input['dpImage'] ;
                    $this->userFileUploader->destination = public_path().Config::get('constants.DP_PATH') ;
                    $renamedFileName = $this->userFileUploader->moveFile();
                    $neoInput['from_linkedin'] = $from_linkedin ;
                    $neoInput['dp_path'] = url('/').Config::get('constants.DP_PATH') ;
                    $neoInput['dp_original_name'] = $originalFileName ;
                    $neoInput['dp_renamed_name'] = $renamedFileName ;
                    $neoInput['linkedinImage'] = $linkedinFileName ;
                    $returnDp = $neoInput['dp_path']."/".$neoInput['dp_renamed_name'] ;
                    unset($input['dpImage']);
                }
                foreach ($input as $key=>$val)
                {
                    $neoInput[$key] = $val ;
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
                        'other_status'=>0
                    ) ;
                    $t = $this->userRepository->logNotification($notificationLog);
                }
                $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                
            }
            return $returnDp ;
        }
        
        public function editSectionInfo($input=array(), $section="")
        {
            if (!empty($input))
            {
                $sectionName = Config::get('constants.USER_CATEGORIES.'.  strtoupper($section)) ;
                $relationName = Config::get('constants.RELATIONS_TYPES.MORE_INFO') ;
                // $expInfo = !empty($input['id'])?$this->neoUserRepository->getSectionInfo($input['id'], $sectionName):array();
                if (!empty($input['action']))
                {
                    if ($input['action']=='edit'  && !empty($input['id']))
                    {
                        //update node and relation
                        $this->neoUserRepository->updateCategoryNodeNRelation($input, array(), $sectionName, $relationName);
                    }
                    else if ($input['action']=='add')
                    {
                        //create node and relation
                        $this->neoUserRepository->createCategoryNodeNRelation($input, array(), $sectionName, $relationName);
                    }
                    else if ($input['action']=='delete' && !empty($input['id']))
                    {
                        //remove all relations for experience
                        $this->neoUserRepository->removeCategoryNodeRelation($input, $sectionName, $relationName);
                    }
                }
                
                return true ;
            }
        }
        
        public function editResumeInfo($input)
        {
            if (!empty($input))
            {
                $originalFileName = $renamedFileName = $linkedinFileName = "";
                $from_linkedin =  0;
                $neoInput = array();
                $neoInput['emailid'] = $input['emailid'] ;
                if (!empty($input['resume']))
                {
                    $originalFileName = $input['resume']->getClientOriginalName();
                    //upload the file
                    $this->userFileUploader->source = $input['resume'] ;
                    $this->userFileUploader->destination = public_path().Config::get('constants.CV_PATH') ;
                    $renamedFileName = $this->userFileUploader->moveFile();
                    $neoInput['cv_path'] = url('/').Config::get('constants.CV_PATH') ;
                    $neoInput['cv_original_name'] = $originalFileName ;
                    $neoInput['cv_renamed_name'] = $renamedFileName ;
                    unset($input['resume']);
                    $updatedNeoUser =  $this->neoUserRepository->updateUser($neoInput) ;
                    return true;
                }
            }
        }
        public function processConnectionRequest($input)
        {
            if (!empty($input['emails']))
            {
                $emails = json_decode($input['emails']);
                $loggedinUserDetails = $this->getLoggedInUser();
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
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.not_mintmesh')));
                    return $this->commonFormatter->formatResponse(404, "error", $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.invalid_input')));
                return $this->commonFormatter->formatResponse(404, "error", $message, array()) ;
            }
        }
        
        public function getUserProfile()
        {
            $loggedinUserDetails = $this->getLoggedInUser();
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
            $badgeResult = $this->userRepository->getNotificationsCount($loggedinUserDetails, 'all');
            $r = array();
            if (!empty($neoLoggedInUserDetails))
            {
                $r = $this->formUserDetailsArray($neoLoggedInUserDetails, 'attribute') ;
                if (!empty($neoLoggedInUserDetails->cv_path) && !empty($neoLoggedInUserDetails->cv_renamed_name))
                {
                    $r['cv_path'] = $neoLoggedInUserDetails->cv_path."/".$neoLoggedInUserDetails->cv_renamed_name ;
                }
                if (!empty($extraDetails))
                {
                    foreach ($extraDetails as $k=>$v)
                    {
                        $r[$k] = $v ;
                    }
                }
                $r['notifications_count']= !(empty($badgeResult))?$badgeResult:0;
                $requestsCount = $this->neoUserRepository->getMyRequestsCount($loggedinUserDetails->emailid);
                $r['requests_count']= !(empty($requestsCount))?$requestsCount:0;
                //credits count
                $creditResult = $this->userRepository->getCreditsCount($loggedinUserDetails->emailid);
                $r['total_credits'] = (!empty($creditResult))?$creditResult[0]->credits:0 ;
                if ($r['total_credits'] == null)
                {
                    $r['total_credits'] = 0;
                }
                $levels_info_r = $this->userRepository->getCurrentLevelInfo($loggedinUserDetails->emailid);
                $levels_info = array();
                if (!empty($levels_info_r))
                {
                    foreach ($levels_info_r as $row)
                    {
                        $levels_info = array("level_id"=>$row->id, "name"=>$row->name, "points"=>$row->points, "earned_points"=>$row->earned_points);
                    }
                }
                
                foreach ($levels_info as $k=>$v)
                {
                    if ($v == null)
                    {
                        $levels_info[$k]="";
                    }
                }
                $r['levels_info'] = $levels_info ;
                $total_cash = 0;
                $referral_cash_res = $this->paymentRepository->getPaymentTotalCash($loggedinUserDetails->emailid,1);
                if (!empty($referral_cash_res))
                {
                    $total_cash = $referral_cash_res[0]->total_cash ;
                }
                $r['total_cash'] = $total_cash ;
                $data = array("user"=>$r);
                $message = array('msg'=>array(Lang::get('MINTMESH.user.profile_success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.invalid_request')));
                return $this->commonFormatter->formatResponse(404, "error", $message, array()) ;
            }
        }
        
        public function formUserMoreDetailsArray($input=array())
        {
            $result = array();
            if (!empty($input))
            {
                foreach ($input as $record)
                {
                    $id = !empty($record[0])?$record[0]->getID():0;
                    $arr = !empty($record[0])?$record[0]->getProperties():array();
                    if (!empty($record[1][1]) && !empty($arr))
                    {
                        $arr['id'] = $id ;
                        $result[$record[1][1]][]=$arr ;
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
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            $return['userDetails'] = $this->formUserDetailsArray($neoLoggedInUserDetails, 'attribute') ;
            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
            $return['fromUserDetails'] = $this->formUserDetailsArray($neoUserDetails, 'attribute') ;
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
                    $return['requests'][] = $a ;
                }
            }
            $data = array("mutual_requests"=>$return) ;
            $message = array('msg'=>array(Lang::get('MINTMESH.get_requests.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
        }
        
        
        public function getMyRequests($input)
        {
            $return = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            $return['userDetails'] = $this->formUserDetailsArray($neoLoggedInUserDetails, 'attribute') ;
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
                            $toUserDetails = $this->formUserDetailsArray($relation[2], 'property') ;
                            foreach ($toUserDetails as $k=>$v)
                            {
                                $a['to_user_'.$k] = $v ;
                            }
                            $to_emailid = $toUserDetails['emailid'] ;
                        }
                        else if ($relation[1] == Config::get('constants.REFERRALS.POSTED'))
                        {
                            $postDetails = $relation[2]->getProperties() ;
                            $postId = $relation[2]->getId() ;
                            foreach ($postDetails as $k=>$v)
                            {
                                $a['post_details_'.$k] = $v ;
                            }
                            $a['post_id'] = $postId ;
                            $a['post_status'] = !empty($postDetails['status'])?strtolower($postDetails['status']):'' ;
                            $a['referrals_count'] = $this->referralsRepository->getPostReferralsCount($postId);
                        }
                        
                    }
                    if (!empty($a['request_for_emailid']))
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
                        $otherUserDetails = $this->formUserDetailsArray($neoOtherUserDetails, 'attribute') ;
                        foreach ($otherUserDetails as $k=>$v)
                        {
                            $a['other_user_'.$k] = $v ;
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
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
        }
        
        public function getUserConnections($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $userConnections = array();
            $connections = $this->neoUserRepository->getConnectedUsers($input['emailid']);
            if (count($connections))
            {
                foreach ($connections as $connection)
                {
                    $details = $this->formUserDetailsArray($connection[0],'property');;
                    if ($details['emailid'] != $loggedinUserDetails->emailid)//if not me
                    {
                        $connected = $this->neoUserRepository->checkConnection($loggedinUserDetails->emailid,$details['emailid']);
                        if (!empty($connected))
                        {
                            $details['connected'] = 1 ;
                            $details['request_sent_at'] = 0;
                        }else
                        {
                            //check staus
                            $statusRes = $this->neoUserRepository->getRequestStatus($loggedinUserDetails->emailid,$input['emailid'], $details['emailid'], Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'));
                            if (!empty($statusRes))// if pending
                            {
                                if ($statusRes['status'] == Config::get('constants.REFERENCE_STATUS.PENDING') || $statusRes['status'] == Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'))
                                {
                                    if ($statusRes['status'] != Config::get('constants.REFERENCE_STATUS.PENDING'))
                                    {
                                        //check if declined at other side
                                        $otherStatusRes = $this->neoUserRepository->getRequestStatus($input['emailid'], $details['emailid'],$loggedinUserDetails->emailid, Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'));
                                        if (!empty($otherStatusRes))
                                        {
                                            if ($otherStatusRes['status'] == Config::get('constants.REFERENCE_STATUS.DECLINED'))
                                            {
                                                $details['request_sent_at'] = 0 ;
                                                $details['connected'] = 0 ;
                                            }
                                            else
                                            {
                                                $details['request_sent_at'] = $statusRes['created_at'] ;
                                                $details['connected'] = 2 ;
                                            }
                                        }
                                        else
                                        {
                                            $details['request_sent_at'] = $statusRes['created_at'] ;
                                            $details['connected'] = 2 ;
                                        }
                                    }
                                    else
                                    {
                                        $details['request_sent_at'] = $statusRes['created_at'] ;
                                        $details['connected'] = 2 ;
                                    }
                                    
                                }
                                else
                                {
                                    $details['connected'] = 0 ;
                                    $details['request_sent_at'] = 0;
                                }
                            }else
                            {
                                $details['connected'] = 0 ;
                                $details['request_sent_at'] = 0;
                            }
                        }
                    }
                    else
                    {
                        $details['connected'] = 1 ;
                        $details['request_sent_at'] = 0;
                    }
                    $userConnections[] = $details ;
                }
            }
            $data = array("connections"=>$userConnections) ;
            $message = array('msg'=>array(Lang::get('MINTMESH.get_connections.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
        }
        /*
         * get user notification details
         */
        public function getSingleNotificationDetails($input)
        {
            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($input['emailid']) ;
            $loggedinUserDetails = $this->getLoggedInUser();
            
            if (!empty($neoUserDetails))
            {
                $r = $this->formUserDetailsArray($neoUserDetails, 'attribute') ;
                $notifications_count = 0;
                //get push data
                $notification = $this->userRepository->getNotification($input['push_id']);
                if (!empty($notification))
                {
                    $notifications_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, $input['notification_type']);
                    $notification = $notification[0];
                    $thirdName = "";
                    if (!empty($notification->other_email))
                    {
                        $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($notification->other_email) ;
                        if (in_array($notification->notifications_types_id, $this->notificationsTypes))
                        {
                            $thirdName = $otherEmailDetails->fullname ;
                        }
                        $otherUserDetails = $this->formUserDetailsArray($otherEmailDetails, 'attribute');
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
                    $r['notification'] = $neoUserDetails->fullname." ".$notification->message." ".$thirdName ;
                    $r['notify_time'] = $notification->created_at ;
                    $r['notification_type'] = $notification->not_type ;
                    $r['push_id'] = $notification->id ;
                    $r['status'] = $notification->status ;
                    $r['other_status'] = $notification->other_status ;
                    
                    
                    //get post details if post type
                    if (in_array($notification->notifications_types_id,$this->postNotifications))
                    {
                        $postDetails = $postRelationsDetails = $postDetailsR = array() ;
                        if ($notification->notifications_types_id == 10)
                        {
                            $e = $notification->other_email ;
                            $f = $notification->from_email ;
                        }
                        else if ($notification->notifications_types_id == 11)
                        {
                            $e = $notification->to_email ;
                            $f = $notification->from_email ;
                        }
                        else if ($notification->notifications_types_id == 14)
                        {
                            $e = $notification->from_email ;
                            $f = $notification->to_email ;
                        }
                        else if ($notification->notifications_types_id == 13 || $notification->notifications_types_id == 16)
                        {
                            $e = $notification->from_email ;
                            $f = $notification->other_email ;
                        }
                        else if ($notification->notifications_types_id == 12 || $notification->notifications_types_id == 15)
                        {
                            $e = $notification->other_email ;
                            $f = $notification->to_email ;
                        }
                        else
                        {
                            $e = $notification->to_email ;
                            $f = $notification->from_email ;
                        }
                        if (!empty($notification->extra_info))
                        $postDetailsR = $this->referralsRepository->getPostAndReferralDetails($notification->extra_info,$f,$e);

                        if (count($postDetailsR))
                        {
                            $postRelationsDetails = isset($postDetailsR[0][0])?$postDetailsR[0][0]->getProperties():array();
                            foreach ($postRelationsDetails as $k=>$v)
                            {
                                $r['post_'.$k] = $v ;
                            }
                            $postDetails = isset($postDetailsR[0][1])?$postDetailsR[0][1]->getProperties():array();
                            foreach ($postDetails as $k=>$v)
                            {
                                $r['post_'.$k] = $v ;
                            }
                            $r['post_id'] = !empty($notification->extra_info)?$notification->extra_info:0;
                        }
                    }
                    else if( !empty($notification->extra_info) && in_array($notification->notifications_types_id,$this->refer_nots))
                    {
                        $note['referral_relation'] =  $notification->extra_info ;
                    }
                    else if (in_array($notification->notifications_types_id,$this->selfReferNotifications))//if self reference type
                    {
                        $r['relation_id']= !empty($notification->extra_info)?$notification->extra_info:0;
                    }
                }
                $phone_verified = !empty($neoUserDetails->phoneverified)?$neoUserDetails->phoneverified:0;
                $data = array("notifications"=>array($r), "notifications_count"=>$notifications_count,"phone_verified"=>$phone_verified) ;
                //$data = array("user"=>$r);
                $message = array('msg'=>array(Lang::get('MINTMESH.user.profile_success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
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
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            if (!empty($neoUserDetails) && !empty($neoLoggedInUserDetails))
            {
                //check if both are connected
                $connected = $this->neoUserRepository->checkConnection($neoLoggedInUserDetails->emailid,$neoUserDetails->emailid);
                if (!empty($connected))// if connected
                {
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
                    $requestsCount = $this->neoUserRepository->getMutualRequestsCount($input['emailid'], $neoLoggedInUserDetails->emailid);
                }
                $r = $this->formUserDetailsArray($neoUserDetails, 'attribute') ;
                if (!empty($extraDetails))
                {
                    foreach ($extraDetails as $k=>$v)
                    {
                        $r[$k] = $v ;
                    }
                }
                if (!empty($connected))// if connected
                {
                    $r['connected']=1;
                }
                $data = array("user"=>$r,"connections_count"=>$connectionsCount,"requests_count"=>$requestsCount);
                $message = array('msg'=>array(Lang::get('MINTMESH.user.profile_success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
        }
        public function formUserDetailsArray($neoLoggedInUserDetails, $type = '')
        {
            $r = array();
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
                $job_function_name = $industry_name = "";
                if (isset($r['job_function']))//get job function name
                {
                    $job_function_name = $this->userRepository->getJobFunctionName($r['job_function']) ;
                }
                if (isset($r['industry']))//get job function name
                {
                    $industry_name = $this->userRepository->getIndustryName($r['industry']) ;
                }
                $r['job_function_name'] = $job_function_name;
                $r['industry_name'] = $industry_name ;
                if (isset($r['id']))
                    unset($r['id']);
                if (!empty($neoLoggedInUserDetails->industry))//user has completed profile
                {
                    if (!empty($neoLoggedInUserDetails->from_linkedin))//if  linked in
                    {
                        $r['dp_path'] = $neoLoggedInUserDetails->linkedinImage ;
                    }
                    else if (!empty($neoLoggedInUserDetails->dp_renamed_name))
                    {
                        $r['dp_path'] = $neoLoggedInUserDetails->dp_path."/".$neoLoggedInUserDetails->dp_renamed_name ;
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
            }
           return $r ; 
        }
        
        
        /*
         * country codes 
         */
        public function getCountryCodes()
        {
            $countryCodes = $this->userRepository->getCountryCodes();
            if (!empty($countryCodes))
            {
                $data = $countries = array();
                foreach($countryCodes as $key=>$val)
                {
                    $countries[] = array("country_name"=>trim($val->name), "country_code"=>$val->country_code) ;
                }
                $data = array("countries"=>$countries) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.country_codes.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
            }
            
        }
        /*
         * industries
         */
        public function getIndustries()
        {
            $industriesResult = $this->userRepository->getIndustries();
            if (!empty($industriesResult))
            {
                $data = $industries = array();
                foreach($industriesResult as $key=>$val)
                {
                    $industries[] = array("industry_name"=>trim($val->name), "industry_id"=>$val->id) ;
                }
                $data = array("industries"=>$industries) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.industries.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.industries.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
            }
        }
        
        /*
         * get job functions
         */
        public function getJobFunctions()
        {
            $jobFunctionsResult = $this->userRepository->getJobFunctions();
            if (!empty($jobFunctionsResult))
            {
                $data = $jobFunctions = array();
                foreach($jobFunctionsResult as $key=>$val)
                {
                    $jobFunctions[] = array("job_function_name"=>trim($val->name), "job_function_id"=>$val->id) ;
                }
                $data = array("job_functions"=>$jobFunctions) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.job_functions.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.job_functions.error')));
                return $this->commonFormatter->formatResponse(403, "error", $message, array()) ;
            }
        }
        
        public function sendNotification($fromUser, $neofromUser, $email, $notificationType = 0, $extraInserts = array(), $otherInfoParams = array(),$parse=1)
        {
            if (!empty($parse))//send if direct notification
            {
                if (!empty($email) && !empty($neofromUser))
                {
                    
                    $userDeviceResults = $this->neoUserRepository->getDeviceToken($email);
                    if (!empty($userDeviceResults))
                    foreach ($userDeviceResults as $userDeviceResult)
                    {
                        $other_email = "" ;
                        $userDetails = isset($userDeviceResult[0])?$userDeviceResult[0]->getProperties():array() ;
                        $deviceDetails = isset($userDeviceResult[1])?$userDeviceResult[1]->getProperties():array() ;
                        if (!empty($userDetails))
                        {
                            $msg = ucfirst($neofromUser->fullname)." ".Lang::get('MINTMESH.notifications.messages.'.$notificationType) ;

                            if (!empty($otherInfoParams))
                            {
                                if (!empty($otherInfoParams['other_user']))
                                {
                                $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($otherInfoParams['other_user']) ; 
                                if (in_array($notificationType, $this->notificationsTypes))
                                {
                                    $msg = $msg." ". $otherUserDetails->fullname;
                                }
                                if (in_array($notificationType,$this->extraTextsNotes))//for posts
                                {
                                    $msg = $msg." ".Lang::get('MINTMESH.notifications.extra_texts.'.$notificationType) ;
                                }
                                $other_email = $otherUserDetails->emailid ;

                                }
                            }
                            //log push notification
                            $notificationLog = array(
                                    'notifications_types_id' => $notificationType,
                                    'from_user' => $fromUser->id,
                                    'from_email' => $fromUser->emailid,
                                    'to_email' => $userDetails['emailid'],
                                    'other_email' => $other_email,
                                    'message' => Lang::get('MINTMESH.notifications.messages.'.$notificationType),
                                    'ip_address' => $_SERVER['REMOTE_ADDR']
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
                            $badgeResult = $this->userRepository->getNotificationsCount($userDeviceResult[0], 'all');
                            $badge = !empty($badgeResult)?$badgeResult:0;
                            $data = array("alert" => $msg,"emailid"=>$fromUser->emailid, "push_id"=>$t->id, "push_type"=>$notificationType, "badge"=>$badge);
                            
                            // Push to Query
                            if (!empty($deviceDetails))
                            {
                                $pushData = array();
                                $pushData['deviceToken']=$deviceDetails['deviceToken'];
                                $pushData['parse_data']=$data;
                                Queue::push('Mintmesh\Services\Queues\ParseQueue', $pushData, 'Notification');
                                
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
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            $page = !empty($input['page'])?$input['page']:0;
            $notifications = $this->userRepository->getNotifications($loggedinUserDetails, $input['notification_type'], $page);
            $notifications_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, $input['notification_type']);
            
            if (!empty($notifications))
            {
                $notes = array();
                foreach ($notifications as $notification)
                {
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($notification->from_email) ;
                    if (!empty($neoUserDetails))
                    {
                        $note = array();
                        $note = $this->formUserDetailsArray($neoUserDetails, 'attribute');
                        $thirdName = "";
                        if (!empty($notification->other_email))
                        {
                            $otherEmailDetails = $this->neoUserRepository->getNodeByEmailId($notification->other_email) ;
                            if (in_array($notification->notifications_types_id, $this->notificationsTypes))
                            {
                                $thirdName = !empty($otherEmailDetails->fullname)?$otherEmailDetails->fullname:'' ;
                            }
                            $otherUserDetails = $this->formUserDetailsArray($otherEmailDetails, 'attribute');
                            foreach ($otherUserDetails as $k=>$v)
                            {
                                $note['other_user_'.$k] = $v ;
                            }
                            
                        }
                        $extra_msg = "";
                        if (in_array($notification->notifications_types_id,$this->extraTextsNotes))//for posts
                        {
                            $extra_msg = Lang::get('MINTMESH.notifications.extra_texts.'.$notification->notifications_types_id) ;
                        }
                        if (!empty($notification->other_message))
                        $note['optional_message'] = $this->appEncodeDecode->filterStringDecode($notification->other_message) ;
                        $note['notification'] = $neoUserDetails->fullname." ".$notification->message." ".$thirdName." ".$extra_msg ;
                        $note['notify_time'] = $notification->created_at ;
                        $note['notification_type'] = $notification->not_type ;
                        $note['message'] = $notification->message ;
                        $note['push_id'] = $notification->id ;
                        $note['read_status'] = $notification->status ;
                        $note['other_status'] = $notification->other_status ;
                        //get post details if post type
                        if (in_array($notification->notifications_types_id,$this->postNotifications))
                        {
                            
                            $postDetails = $postRelationsDetails = $postDetailsR = array() ;
                            if ($notification->notifications_types_id == 10)
                            {
                                $e = $notification->other_email ;
                                $f = $notification->from_email ;
                            }
                            else if ($notification->notifications_types_id == 11)
                            {
                                $e = $notification->to_email ;
                                $f = $notification->from_email ;
                            }
                            else if ($notification->notifications_types_id == 14)
                            {
                                $e = $notification->from_email ;
                                $f = $notification->to_email ;
                            }
                            else if ($notification->notifications_types_id == 13 || $notification->notifications_types_id == 16)
                            {
                                $e = $notification->from_email ;
                                $f = $notification->other_email ;
                            }
                            else if ($notification->notifications_types_id == 12 || $notification->notifications_types_id == 15)
                            {
                                $e = $notification->other_email ;
                                $f = $notification->to_email ;
                            }
                            else
                            {
                                $e = $notification->to_email ;
                                $f = $notification->from_email ;
                            }
                            if (!empty($notification->extra_info))
                            $postDetailsR = $this->referralsRepository->getPostAndReferralDetails($notification->extra_info,$f,$e);
                            if (count($postDetailsR))
                            {
                                $postRelationsDetails = isset($postDetailsR[0][0])?$postDetailsR[0][0]->getProperties():array();
                                foreach ($postRelationsDetails as $k=>$v)
                                {
                                    $note['post_'.$k] = $v ;
                                }
                                $postDetails = isset($postDetailsR[0][1])?$postDetailsR[0][1]->getProperties():array();
                                foreach ($postDetails as $k=>$v)
                                {
                                    $note['post_'.$k] = $v ;
                                }
                                $note['referral'] = $e ;
                                $note['post_id'] = !empty($notification->extra_info)?$notification->extra_info:0;
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
                        
                        $notes[] = $note ;
                    }
                }
                $phone_verified = !empty($neoLoggedInUserDetails->phoneverified)?$neoLoggedInUserDetails->phoneverified:0;
                $data = array("notifications"=>$notes, "notifications_count"=>$notifications_count,"phone_verified"=>$phone_verified) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.notifications.no_notifications')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        
        /*
         * accept connection
         */
        public function acceptConnection($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
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
            
            if (!empty($input['refered_by']) && empty($input['self_reference']))
            {
                $extraInserts = array();
                //take base relation id
                $rel_id= !empty($input['base_rel_id'])?$input['base_rel_id']:0;
                $extraInserts['extra_info'] = $rel_id ;
                $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $to_email, 7, $extraInserts, $otherInfoParams);
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
            else
            {
                $this->neoUserRepository->changeRelationStatus($input['from_email'], $loggedinUserDetails->emailid,'', Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION'), Config::get('constants.REFERENCE_STATUS.SUCCESS'));
                $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $to_email, 2, array(), $otherInfoParams);
            }
            $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
         }
        
        /*
         * get connected users
         */
        public function getConnectedAndMMUsers($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
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
                    $uId = $user[0]->getID();
                    $u[$uId] = $this->formUserDetailsArray($user[0],'property');
                    $connected = $this->neoUserRepository->checkConnection($emailid,$user[0]->emailid);
                    if (!empty($input['emailid']))
                    {
                        //check if connected to me
                        $connectedToMe = $this->neoUserRepository->checkConnection($loggedinUserDetails->emailid,$user[0]->emailid);
                        print_r($connectedToMe);
                        if (!empty($connectedToMe))
                        {
                            $u[$uId]['connected_to_me'] = 1 ;
                        }else
                        {
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
                    
                    if (!empty($connected))
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
            $data = array("users"=>array_values($u)) ;
            $message = array('msg'=>array(Lang::get('MINTMESH.get_contacts.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            
            
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
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            //send connection request if not connected and a mintmesh
            if (empty($input['connected_to_me']) && empty($input['non_mintmesh']))
            {
                $connectInput = array('emails'=>json_encode(array($input['refer_to'])));
                $sendRequestConnectRes = $this->processConnectionRequest($connectInput);
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
            return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
        }
                
        /*
         * refer contact
         */
        public function referContact($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            //return if the person whom requesting is not a mintmesh user
            $isMintmeshUser = $this->neoUserRepository->getNodeByEmailIdMM($input['request_to']) ;
            if (empty($isMintmeshUser))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.refer_not_mintmesh')));
                return $this->commonFormatter->formatResponse(404, "error", $message, array()) ;
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
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            
        }
        public function changeReferRequestStatus($from="", $to="", $for="", $relationType="", $status="", $noteTypeId=0, $rel_id=0)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            $this->neoUserRepository->changeRelationStatus($from, $to, $for, $relationType, $status);
            $otherInfoParams = array('other_user'=>$for);
            $extraInserts = array("extra_info"=>$rel_id) ;
            //send notification to u1
            $this->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $from, $noteTypeId, $extraInserts, $otherInfoParams);
        }
        /*
         * close notification
         */
        public function closeNotification($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $notificationDetails = $this->userRepository->getPushDetails($input['push_id']);
            $this->userRepository->closeNotification($input, $notificationDetails);
            $notifications_count = $this->userRepository->getNotificationsCount($loggedinUserDetails, $input['notification_type']);
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
                            'ip_address' => $_SERVER['REMOTE_ADDR']
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
                            'ip_address' => $_SERVER['REMOTE_ADDR']
                        ) ;
                    $t = $this->userRepository->logNotification($notificationLog);
                    $relationAttrs = array("reffered_by"=>$notificationDetails->from_email) ;
                    $this->neoUserRepository->createDeclinedRelation($loggedinUserDetails->emailid,$notificationDetails->other_email,$relationAttrs);
                }
                
                
            }
            $message = array('msg'=>array(Lang::get('MINTMESH.notifications.success')));
            $data = array("notifications_count"=>$notifications_count) ;
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
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
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
        }
        
        public function getReferenceFlow($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
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
                        if ($result[0][0]->status == Config::get('constants.REFERENCE_STATUS.PENDING'))
                        {   
                            
                        }
                        else 
                        {
                            //check for p2-p3 status
                            $fromEmail = !empty($p2Details['emailid'])?$p2Details['emailid']:'';
                            $toEmail = !empty($result[0][0]->request_for_emailid)?$result[0][0]->request_for_emailid:'';
                            $forEmail = !empty($p1Details['emailid'])?$p1Details['emailid']:'';
                            $returnArray['message'] = !empty($result[0][0]->message)?$result[0][0]->message:'';
                            $introDetails = $this->neoUserRepository->getIntroduceConnection($fromEmail="", $toEmail="",$forEmail="");
                            if (count($introDetails))
                            {
                                $returnArray['other_message'] = (isset($introDetails[0][0]->message))?$introDetails[0][0]->message:"" ;
                                $returnArray['other_status'] = (isset($introDetails[0][0]->status))?$introDetails[0][0]->status:"" ;
                            }
                            else
                            {
                                $returnArray['other_message']="";
                                $returnArray['other_status'] = Config::get('constants.REFERENCE_STATUS.PENDING') ;
                            }
                        }
                        $returnArray['status'] = $result[0][0]->status ;
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
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.reference_flow.invalid_input')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                }
            }
        }
        public function getLevelsInfo()
        {
            $levelsDetailedInfo = $levelsInfo = array();
            $loggedinUserDetails = $this->getLoggedInUser();
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
            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $data = array() ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.error')));
                return $this->commonFormatter->formatResponse(406, "error", $message, $data) ;
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
            $deleted = $this->neoUserRepository->removeContact($loggedinUserDetails->emailid, $input['emailid']);
            if ($deleted)
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_disconnect_success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_disconnect_error')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
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
            $resourceOwnerId = $this->authorizer->getResourceOwnerId();
            return $this->userRepository->getUserById($resourceOwnerId);
        }
        
        public function getDeepLinkScheme($os_type)
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
                    $deep_link = Config::get('constants.MNT_DEEP_LINK_IOS');
                }
            }
            return $deep_link ;
        }
        
        public function logout($input)
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
            $this->neoUserRepository->logout($input['deviceToken'], $neoLoggedInUserDetails);
            $message = array('msg'=>array(Lang::get('MINTMESH.user.logged_out')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
        }
        
        public function checkEmailExistance($input)
        {
            $user = $this->userRepository->getUserByEmail($input['emailid']);
            if (count($user))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_found')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            
        }
        
        public function checkPhoneExistance($input)
        {
            $userCount = $this->neoUserRepository->getUserByPhone($input['phone']);
            if (!empty($userCount))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_found')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            
        }
        
        public function getSpecificLevelInfo($input)
        {
            $returnResult = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            $result = $this->userRepository->getSpecificLevelInfo($input['level_id'],$loggedinUserDetails->emailid);
            if (!empty($result))
            {
                foreach ($result as $row)
                {
                    $arr = array() ;
                    $arr['credits'] = $row->points ;
                    $arr['created_at'] = $row->created_at ;
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
                        $otherUserDetails = $this->neoUserRepository->getNodeByEmailId($row->other_email) ;
                        $details = $this->formUserDetailsArray($otherUserDetails,'attribute');
                        $arr['other_details'] = $details ;
                    }
                    $returnResult[] = $arr ;
                }
                $data = array("level_info"=>$returnResult) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $data = array() ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_levels.error')));
                return $this->commonFormatter->formatResponse(406, "error", $message, $data) ;
            }
        }
        
        
    
}
?>
