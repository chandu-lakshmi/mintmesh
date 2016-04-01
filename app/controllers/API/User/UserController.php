<?php
namespace API\User;
use Mintmesh\Gateways\API\User\UserGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response;
use Config ;
use View;


class UserController extends \BaseController {

        
	public function __construct(UserGateway $userGateway)
	{
		$this->userGateway = $userGateway;
        }
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return $this->userGateway->getUserlist();
	}
        
        /**
	 * Change Password entry
         * 
         * POST/user
         * @param string $access_token The Access token of a user
	 * @param string $password_old The old password of a user
         * @param string $password_new The new password of a user
         * @param string $password_new_confirmation The new password of a user
         * @param string $deviceToken The device token of the user device
         * 
	 * @return Response
	 */
	public function changePassword()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateChangePassword($inputUserData);
            if($validation['status'] == 'success') 
            {
                // update password in mysql DB
                return \Response::json($this->userGateway->changePassword($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Create new user entry
         * 
         * POST/user
         * 
	 * @param string $firstname The firstname of a user
         * @param string $lastname The last_name of a user
         * @param string $emailid The email id of a user
         * @param string $phone The phone number of a user
         * @param string $phone_country_name user's phone location
         * @param string $password The password of a user profile
         * @param string $password_confirmation The password of a user profile
         * @param string $deviceToken The device token of the user device
         * @param string $phone_verified
         * @param string $location
         * @param string $login_source
         * @param string $os_type
         * 
	 * @return Response
	 */
	public function create()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCreateUserInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                return \Response::json($this->userGateway->createUser($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Create new user entry
         * 
         * POST/user
         * 
	 * @param string $firstname The firstname of a user
         * @param string $lastname The last_name of a user
         * @param string $emailid The email id of a user
         * @param string $phone The phone number of a user
         * @param string $phone_country_name user's phone location
         * @param string $location user's location
         * @param string $password The password of a user profile
         * @param string $password_confirmation The password of a user profile
         * @param string $deviceToken The device token of the user device
         * @param string $phone_verified
         * @param string $location
         * @param string $login_source
         * @param string $os_type
         * 
      	 * @return Response
	 */
	public function create_v2()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCreateUserInput_v2($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                return \Response::json($this->userGateway->createUser($inputUserData));
            } else {
                // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Resend Activation link for user who is not activated
         * 
         * POST/user
         * 
	 * @param string $access_token The Access token of a user
         * 
	 * @return Response
	 */
	public function resendActivationLink()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // creating entry in mysql DB
            return \Response::json($this->userGateway->resendActivationLink($inputUserData));
            
	}
        
        /**
	 * update an existing resource version1
         * 
         * POST/user
         * 
         * @param string $access_token The Access token of a user
	 * @param string $position The position of a user
         * @param string $company The company of a user
         * @param string $industry The industry of a user
         * @param string $location The location of a user 
         * @param string $job_function The job function of a user
         * @param string $fromLinkedin if user is from linkedin 
         * @param string $linkedinImage if user is from linkedin then profile image url
         * @param file $dpImage The profile image of a user profile
         * @param string $you_are The you are field
         * 
	 * @return Response
	 */
	public function completeUserProfile()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCompleteProfileUserInput($inputUserData);
            if($validation['status'] == 'success') 
            {
               //update entry in neo4j DB
               return \Response::json($this->userGateway->completeUserProfile($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * update an existing resource version2
         * 
         * POST/user
         * 
         * @param string $access_token The Access token of a user
	 * @param string $position The position of a user
         * @param string $company The company of a user
         * @param string $industry The industry of a user
         * @param string $location The location of a user 
         * @param string $job_function The job function of a user
         * @param string $fromLinkedin if user is from linkedin 
         * @param string $linkedinImage if user is from linkedin then profile image url
         * @param file $dpImage The profile image of a user profile
         * @param string $you_are The you are field
         * @param string $to_be_referred like to be referred field 0|1
         * @param string $services json encoded list of services, list of ids (text in case of new one)
         * @param string $user_description describe yourself field
         * @param string $website link of website
         * @param string $profession profession field
         * @param string $specialization specialization field
         * @param string $college college field
         * @param string $course course field
         * 
	 * @return Response
	 */
	public function completeUserProfile_v2()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCompleteProfileUserInput_v2($inputUserData);
            if($validation['status'] == 'success') 
            {
               //update entry in neo4j DB
               return \Response::json($this->userGateway->completeUserProfile_v2($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Authenticate a user login
         * 
         * POST/login
         * 
	 * @param string $username The email id of a user
         * @param string $password The password of a user profile
         * @param string $deviceToken The device token of the user device
         * 
	 * @return Response
	 */
	public function login()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateUserLoginInput($inputUserData);
            if($validation['status'] == 'success') {
                return \Response::json($this->userGateway->verifyLogin($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Authenticate a user special login
         * 
         * POST/special_login
         * 
	 * @param string $code The activation code of a user
         * @param string $emailid The email id of a user
         * @param string $deviceToken The device token of the user device
         * 
	 * @return Response
	 */
	public function special_login()
	{
            // Receiving user input data
            $inputUserData = \Input::all();// Validating user input data
            $validation = $this->userGateway->validateUserSpecialLoginInput($inputUserData);
            if($validation['status'] == 'success') {
                return \Response::json($this->userGateway->specialLogin($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}

         /**
	 * Authenticate a user login from facebook
         * 
         * POST/fb_login
         * 
         * @param string $fb_access_token The access token received from facebook
         * 
	 * @return Response
	 */

        public function loginWithFacebook() {

            $inputUserData = \Input::all();
            //$fb = OAuth::consumer( 'Facebook' );
            /*
            $validation = $this->userGateway->validateFbLoginInput($inputUserData);
            if($validation['status'] == 'success') {
                return \Response::json($this->userGateway->processFbLogin($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            exit;*/
            $code = "";
            if (!empty($inputUserData['code']))
            {
                $code = $inputUserData['code'] ;
            }
            // check if code is valid
            // if code is provided get user data and sign in
            $fb = OAuth::consumer( 'Facebook' );
            if ( !empty( $code ) ) {
                
                // This was a callback request from facebook, get the token
                $token = $fb->requestAccessToken( $code );
                print_r($token);
                // Send a request with it
                $fbResult = json_decode( $fb->request( '/me' ), true );
                $friends = json_decode( $fb->request( '/me/taggable_friends?limit=400' ), true );
                // $inv_friends = json_decode( $fb->request( '/me/invitable_friends?limit=400' ), true );
                //$fbResult1 = json_decode( $fb->request( '/AaKsAPC0dv7G2i6FBP7gtbseShmnaD-FlYnAzb9KtYpGpDaIOEjNxtUiqt2mpaW8-Az_9gYCRH_-pcHIQ6YlCAgnjUdntqMiEtHLlzZfhqjNbQ' ), true );
                //create user array to store in local storage
                $inputUserData = array();
                $inputUserData['emailid'] = $fbResult['email'] ;
                $inputUserData['firstname'] = $fbResult['first_name'] ;
                $inputUserData['lastname'] = $fbResult['last_name'] ;
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                $inputUserData['password'] = Config::get('constants.FB_PASSWORD') ;
                $inputUserData['password_confirmation'] = Config::get('constants.FB_PASSWORD') ;
                
                //create input array for loggin into the system
                $loginUserData = array();
                $loginUserData['username'] = $fbResult['email'] ;
                $loginUserData['password'] = Config::get('constants.FB_PASSWORD') ;
                $loginUserData['grant_type'] = Config::get('constants.GRANT_TYPE') ;
                $loginUserData['client_id'] = '875Fvq2wSHf5Rjyl' ;
                $loginUserData['client_secret'] = 'Mb63nD2ZjsC94RhphxlbjRsBXB1oO1KV' ;
                
                // Validating user input data
                $validation = $this->userGateway->validateCreateUserInput($inputUserData);
                if($validation['status'] == 'success') {
                    // creating entry in mysql DB
                    $createResult = $this->userGateway->createUser($inputUserData);
                }
                
                //verifying the user data against local storage
                return \Response::json($this->userGateway->verifyLogin($loginUserData));

            }
            // if not then ask for permission first
            else {
                // get fb authorization
                $url = $fb->getAuthorizationUri();
                // return to facebook login url
                 return Redirect::to( (string)$url );
            }

        }
        
        /**
	 * Activate a user account
         * 
         * GET/activate
         * 
         * 
	 * @return Response
	 */
        public function activateAccount($code)
        {
            $input = \Input::all();
            $os_type = !empty($input['os_type'])?$input['os_type']:'';
           if (!empty($code))
           {
               $response = $this->userGateway->activateUser($code);
               if (empty($os_type))
               {
                  if(empty($response['data'])) {
                       return View::make('landings/activationResponce',array('msg' => $response['message']['msg'][0]));
                   } else {
                       return View::make('landings/activation');
                   }
               }
               else
               {
                   return \Response::json($response);
               }
           }
        }
        
        /**
	 * Send reset password link to users email id
         * 
         * POST/forgot_password
         * 
         * @param $emailid
         * @param $os_type
         * 
	 * @return Response
	 */
        public function forgotPassword()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateForgotPasswordInput($inputUserData);
            if($validation['status'] == 'success') {
                return \Response::json($this->userGateway->sendForgotPasswordEmail($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /*
         * redirect to app
         * GET/redirect_to_app
         * @param url
         * $return Redirect
         */
        public function redirectToApp($url)
        {
            if (!empty($url))
            {
                $urlDecoded = $this->userGateway->base_64_decode($url);
                $url = $urlDecoded['string2'];
                echo "<script>";
                echo "window.location = '".$url."'";
                echo "</script>";
            }
        }
        /**
	 * check reset user's password
         * 
         * POST/check_reset_password
         * 
         * @param string $code The reset password code
         * 
	 * @return Response
	 */
        public function checkResetPassword()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCheckResetPasswordInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->userGateway->checkResetPassword($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * reset user's password
         * 
         * POST/reset_password
         * 
         * @param string $code The reset password code
         * @param string $password The new password of a user account
         * @param string $password_confirmation password confirmation field
         * 
	 * @return Response
	 */
        public function resetPassword()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateResetPasswordInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->userGateway->resetPassword($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * list the countrycodes of countries
         *  
         * GET
         * 
	 * @return Response
	 */
        public function countryCodes()
        {
            $response = $this->userGateway->getCountryCodes();
            return \Response::json($response);
        }
        
        /**
	 * list the industries
         * 
         * GET/get_industries
         * 
	 * @return Response
	 */
        public function getIndustries()
        {
            $response = $this->userGateway->getIndustries();
            return \Response::json($response);
        }
        
        /**
	 * list the job functions
         * 
         * GET/get_job_functions
         * 
	 * @return Response
	 */
        public function getJobFunctions()
        {
            $response = $this->userGateway->getJobFunctions();
            return \Response::json($response);
        }
        
        /**
	 * list the skills
         * 
         * POST/get_skills
         * 
         * @param string $search_for search string
         * 
	 * @return Response
	 */
        public function getSkills()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->userGateway->getSkills($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * list the skills
         * 
         * POST/get_skills
         * 
         * @param string $search_for search string
         * 
	 * @return Response
	 */
        public function getSkills_v2()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->userGateway->getSkills_v2($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * reset user's password
         * 
         * POST/edit_profile
         * 
         * @param string $access_token The Access token of a user
         * @param string $info_type contact|resume|experience|education|certification|skills
         * @param string $action add|edit|delete only for experience|education|certification
         * @param string $firstname First name of user
         * @param string $lastname The lastname of a user
         * @param string $position User's position
         * @param string $location User's location
         * @param string $phone_country_name User's phone location
         * @param string $phone User's phone
         * @param string $workphone User's work phone
         * @param string $skypeid User's skype id
         * @param file $dpImage The profile image of a user profile
         * @param string $fbUserId Facebook user id of the user
         * @param string $googleUserId Google + user id of the user
         * @param string $lnUserId LinkedIn user id of the user
         * @param string $twitterName Twitter Name of the user
         * @param string $title Certification
         * @param string $description certification
         * @param string $id certification in case edit
         * @param string $company_name experience
         * @param string $job_title experience
         * @param string $current_job experience 0|1
         * @param string $start_date experience
         * @param string $end_date experience
         * @param string $location experience
         * @param string $id experience in case edit
         * @param string $school_college education
         * @param string $start_year education
         * @param string $end_year education
         * @param string $degree education
         * @param string $description education
         * @param string $id education in case edit
         * @param file $resume user resume
         * @param json $skills json encoded list of skills
	 * @return Response
	 */
        public function editProfile()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateEditProfileInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->userGateway->editProfile($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * connect user request
         * 
         * POST/request_connect
         * 
         * @param string $access_token The Access token of a user
         * @param string $emails The email ids of the users to which connect request is to be sent
	 * @return Response
	 */
        public function connectUserRequest()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateConnectionRequestInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->userGateway->processConnectionRequest($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
        /**
	 * get user profile
         * 
         * POST/get_profile
         * 
         * @param string $access_token The Access token of a user
	 * @return Response
	 */
        public function getUserProfile()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->userGateway->getUserProfile($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * get user notification details
         * 
         * POST/get_single_notification_details
         * 
         * @param string $access_token The Access token of a user
         * @param string $emailid
         * @param string $push_id
         * @param string $notification_type
	 * @return Response
	 */
        public function getSingleNotificationDetails()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateSingleNotificationInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getSingleNotificationDetails($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get user all details
         * 
         * POST/get_profile_by_emailid
         * 
         * @param string $access_token The Access token of a user
         * @param string $emailid
	 * @return Response
	 */
        public function getUserDetailsByEmail()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateGetUserByEmailInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getUserDetailsByEmail($inputUserData);
                return \Response::json($response);
            } else {
                // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get user notifications
         * 
         * POST/get_notifications
         * 
         * @param string $access_token The Access token of a user
         * @param string $notification_type
         * @param string $page
	 * @return Response
	 */
        public function getAllNotifications()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateNotificationsInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getAllNotifications($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * post accept connection
         * 
         * POST/accept_connection
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_email
         * @param string $refered_by
         * @param string $self_reference used in self reference flow
         * @param string $relation_id used in self reference flow
         * @param string $base_rel_id
         * 
	 * @return Response
	 */
        public function acceptConnection()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateAcceptConnectionInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->acceptConnection($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        /**
	 * close notification
         * 
         * POST/close_connection
         * 
         * @param string $access_token The Access token of a user
         * @param string $push_id
         * @param string $notification_type
         * @param string $request_type
         * @param string $base_rel_id applied for normal refer flow. comes with notifications in referral_relation field
         * @return Response
	 */
        public function closeNotification()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCloseNotificationInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->closeNotification($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get reference flow
         * 
         * POST/get_reference_flow
         * 
         * @param string $access_token The Access token of a user
         * @param string $base_rel_id referral_relation field from notification
	 * @return Response
	 */
        public function getReferenceFlow()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateGetReferenceFlowInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getReferenceFlow($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        
        /**
	 * get connected users
         * 
         * POST/get_connected_users
         * 
         * @param string $access_token The Access token of a user
         * @param string $emailid used in refer my contact
	 * @return Response
	 */
        public function getConnectedAndMMUsers()
        {
            $inputUserData = \Input::all();
            $response = $this->userGateway->getConnectedAndMMUsers($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * logout of the device
         * 
         * POST/logout
         * 
         * @param string $access_token The Access token of a user
         * @param string $deviceToken
	 * @return Response
	 */
        public function logout()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateUserLogOut($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->logout($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        /**
	 * request reference
         * 
         * POST/request_reference
         * 
         * @param string $access_token The Access token of a user 
         * @param string $request_to
         * @param string $request_for
         * @param string $message
         * @param string $request_type   refer|introduce
         * @param string $base_rel_id   referral_relation parameter from notifications.comes for introduce type
	 * @return Response
	 */
        public function referContact()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateReferContactInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->referContact($inputUserData);
                return \Response::json($response);
            } else {
                // returning validation failure
                return \Response::json($validation);
            }
//            $response = $this->userGateway->getConnectedAndMMUsers();
//            return \Response::json($response);
        }
        
        
        /**
	 * check email existance
         * 
         * POST/checkEmailExistance
         * 
         * @param string $emailid
	 * @return Response
	 */
        public function checkEmailExistance()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateGetUserByEmailInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->checkEmailExistance($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * check phone existance
         * 
         * POST/checkPhoneExistance
         * 
         * @param string $phone
	 * @return Response
	 */
        public function checkPhoneExistance()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validatePhoneExistanceInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->checkPhoneExistance($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get mutual requests
         * 
         * POST/get_mutual_requests
         * 
         * @param string $access_token The Access token of a user
         * @param string $emailid
	 * @return Response
	 */
        public function getMutualRequests()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateGetUserByEmailInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getMutualRequests($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get user connections
         * 
         * POST/get_user_connections
         * 
         * @param string $access_token The Access token of a user
         * @param string $emailid
	 * @return Response
	 */
        public function getUserConnections()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateGetUserByEmailInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getUserConnections($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get my requests
         * 
         * POST/get_my_requests
         * 
         * @param string $access_token The Access token of a user
         * @param string $page
         * 
	 * @return Response
	 */
        public function getMyRequests()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->userGateway->getMyRequests($inputUserData);
            return \Response::json($response);
        }
        
        
        /**
	 * get users by location
         * 
         * POST/get_users_by_location
         * 
         * @param string $access_token The Access token of a user
         * @param string $location
	 * @return Response
	 */
        public function getUsersByLocation()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateUsersByLocation($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getConnectionsByLocation($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get levels info
         * 
         * POST/get_levels_info
         * 
         * @param string $access_token The Access token of a user
	 * @return Response
	 */
        public function getLevelsInfo()
        {
            $response = $this->userGateway->getLevelsInfo();
            return \Response::json($response);
        }
        
        /**
	 * refer my connection
         * 
         * POST/refer_my_connection
         * 
         * @param string $access_token The Access token of a user
         * @param string $connected_to_me 0/1/2
         * @param string $non_mintmesh 0/1
         * @param string $invite_via_email 0/1
         * @param string $refer_to whom to be referred
         * @param string $referring referral
	 * @return Response
	 */
        public function referMyConnection()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateReferMyContactInfo($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->referMyConnection($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        /**
	 * get specific level info
         * 
         * POST/get_specific_level_info
         * 
         * @param string $access_token The Access token of a user
         * @param string $level_id
	 * @return Response
	 */
        public function getSpecificLevelInfo()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateLevelsInfo($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getSpecificLevelInfo($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * disconnect a user
         * 
         * POST/disconnect_user
         * 
         * @param string $access_token The Access token of a user
         * @param string $emailid
	 * @return Response
	 */
        public function disConnectUsers()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateGetUserByEmailInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->disConnectUsers($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get influencers list
         * 
         * POST/get_influencers_list
         * 
         * @param string $access_token
	 * @return Response
	 */
        public function getInfluencersList()
        {
            
            $response = $this->userGateway->getInfluencersList();
            return \Response::json($response);
            
        }
        
        
        /**
	 * get recruiters list
         * 
         * POST/get_recruiters_list
         * 
         * @param string $access_token
         * @param string $page
	 * @return Response
	 */
        public function getRecruitersList()
        {
            $inputUserData = \Input::all();
            $response = $this->userGateway->getRecruitersList($inputUserData);
            return \Response::json($response);
            
        }
        
        public function cacheBadWords()
        {
            $response = $this->userGateway->getBadWords();
            return \Response::json($response);
        }
        
        /**
	 * check user password
         * 
         * POST/check_user_password
         * 
         * @param string $access_token
         * @param string $password
	 * @return Response
	 */
        public function checkUserPassword()
        {
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCheckUserPasswordInfo($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->checkUserPassword($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
            
        }
        
        /**
	 * get services for ask flow
         * 
         * POST/get_services
         * 
         * @param string $access_token
         * @param string $service_type type of service list service|job
         * @param string $user_country
         * @param string $search
	 * @return Response
	 */
        public function getServices(){
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validategetServicesInfo($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getServices($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get professions for your are field
         * 
         * POST/get_you_are_values
         * 
         * @param string $access_token
	 * @return Response
	 */
        public function getYouAreValues(){
            $response = $this->userGateway->getYouAreValues();
            return \Response::json($response);
            
        }
        
        /**
	 * get professions for your are field v2
         * 
         * POST/get_you_are_values_v2
         * 
         * @param string $access_token
	 * @return Response
	 */
        public function getYouAreValues_v2(){
            $response = $this->userGateway->getYouAreValues_v2();
            return \Response::json($response);
            
        }
        
	/**
	 * get professions for provider service provider in your are field
         * 
         * POST/get_provider_professions
         * 
         * @param string $access_token
	 * @return Response
	 */
        public function getPofessions(){
            $response = $this->userGateway->getPofessions();
            return \Response::json($response);
            
        }		
        
}
?>
