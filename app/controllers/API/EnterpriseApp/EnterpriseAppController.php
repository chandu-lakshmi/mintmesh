<?php
namespace API\EnterpriseApp;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Mintmesh\Gateways\API\SocialContacts\ContactsGateway;
use Mintmesh\Gateways\API\SMS\SMSGateway;
use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
use Mintmesh\Gateways\API\Post\PostGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response;
use Config ;
use View;


class EnterpriseAppController extends \BaseController {

        
	public function __construct(
                UserGateway $userGateway,
                ReferralsGateway $referralsGateway,
                ContactsGateway $contactsGateway,
                SMSGateway $smsGateway,
                EnterpriseGateway $EnterpriseGateway,
                PostGateway $PostGateway
            ){
                $this->userGateway = $userGateway;
                $this->referralsGateway = $referralsGateway;
                $this->contactsGateway = $contactsGateway;
                $this->smsGateway = $smsGateway;
                $this->EnterpriseGateway = $EnterpriseGateway;
                $this->PostGateway = $PostGateway;
        }
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//return $this->EnterpriseGateway->getUserlist();
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
	public function create()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateCreateUserInput_v2($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['is_ent'] = TRUE;
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                return \Response::json($this->userGateway->createUser($inputUserData));
            } else {
                // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Authenticate a Enterprise user login
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
                $inputUserData['is_ent'] = TRUE;
                return \Response::json($this->userGateway->sendForgotPasswordEmail($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
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
	 * Import all contacts 
         * 
         * POST/import_contacts
         * @param string $access_token The access token received for mintmesh
         * @param array $contacts The list of contacts
         * @param string $autoconnect 0/1 to autoconnect enabled or not
         * 
	 * @return Response
	 */
        public function importContacts()
        {
            $input = \Input::all();
            if (!empty($input))
            {
                return $this->contactsGateway->processContactsImport($input);
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
	 * Import all contacts 
         * 
         * POST/invite_people
         * @param string $access_token The access token received for mintmesh
         * @param string $emails The list of emails
         * 
	 * @return Response
         */
        public function sendInvitation()
        {
            $input = \Input::all();
            if (!empty($input))
            {
                return $this->contactsGateway->processInvitations($input);
            }
        }
        
         /**
	 * send sms
         * 
         * POST/send_sms
         * 
         * @param string $access_token The Access token of a user
         * @param string $numbers
         * @param string $sms_type
	 * @return Response
	 */
        public function sendSMS()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->smsGateway->validateSMSInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->smsGateway->sendSMS($inputUserData));
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
        public function requestReference()
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
               $inputUserData['is_ent'] = TRUE;
               $response = $this->userGateway->editProfile($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
       
         /**
	 * get users invited jobs list 
         * 
         * POST/get_jobs_list
         * @param string $access_token the access token of enterprise user
         * @param string $company_code the company code of enterprise user
	 * @return Response
	 */
        public function getJobsList()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->validateGetJobsListInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->getJobsList($inputUserData);
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
	 * get referrals cash
         * 
         * POST/get_referrals_cash
         * 
         * @param string $access_token The Access token of a user
         * @param string $payment_reason
         * @param string $page page number
	 * @return Response
	 */
        public function getReferralsCash()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validateReferralsCashInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getReferralsCash($inputUserData);
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
        public function getBellNotifications()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->userGateway->validateNotificationsInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->userGateway->getBellNotifications($inputUserData);
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
            $response = $this->userGateway->getSkills_v2($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * refer a person for a post
         * 
         * POST/refer_contact
         * 
         * @param string $access_token The Access token of a user
         * @param string $referring
         * @param string $refer_to
         * @param string $post_id
         * @param string $message
         * @param string $bestfit_message
         * @param string $refer_non_mm_email
         * @param string $referring_phone_no
         * @param string $referring_user_firstname
         * @param string $referring_user_lastname
         * @param string $is_hire_candidate 1 if it is find job service scope
         * @param string $resume resume of p3 if it is hire a candidate service
         * 
	 * @return Response
	 */
        public function referContact()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validatereferContact($inputUserData);
            if($validation['status'] == 'success') {
                $inputUserData['is_ent'] = TRUE;
                $response = $this->referralsGateway->referContactV2($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
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
	 * get professions for provider service provider in your are field
         * 
         * POST/get_provider_professions
         * 
         * @param string $access_token
	 * @return Response
	 */
        public function getProfessions(){
            $response = $this->userGateway->getProfessions();
            return \Response::json($response);
            
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
        *  Authenticate an enterprise user view company's details
        * 
        * POST/view_company_details
        * @param string  $access_token The Access token of a user 
        * @param string  $company_code get the company details 
        * @return Response company's details
        */
        public function viewCompanyDetails(){
               
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateViewCompanyDetailsInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->viewCompanyDetails($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }     
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
	 * get post status details
         * for hexagonal display
         * POST/get_post_status_details
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user p1 emailid
         * @param string $referred_by person who referred
         * @param string $referral person who got referred
         * @param string $relation_count relation count
         * @param string $post_id
         * @param string $referred_by_phone in case the referral is a non mintmesh user
	 * @return Response
	 */
        public function getPostStatusDetails()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyPostStatusDetails($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getPostStatusDetails($inputUserData);
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
	 * get users invited job details
         * 
         * POST/get_job_details
         * @param string $access_token the access token of enterprise app user
	 * @return Response
	 */
        public function getJobDetails()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->validateGetJobDetailsInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->getJobDetails($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
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
         * POST/get_campaigns
         * @param string $access_token The Access token of a user
         * 
	 * @return Response
	 */
        public function getCampaignDetails()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validateGetCampaignDetails($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getCampaignDetails($inputUserData);
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
	 * get my referral contacts
         * 
         * POST/get_my_referral_contacts
         * 
         * @param string $access_token The Access token of a user
         * @param string $other_email email id of person who create the post
         * @param string $post_id
	 * @return Response
	 */
        public function getMyReferralContacts()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyreferralContacts($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getMyReferralContacts($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        /**
	 * get all my referral posts
         * 
         * POST/get_all_my_referrals
         * 
         * @param string $access_token The Access token of a user
         * @param string $company_code the company code of enterprise user
	 * @return Response
	 */
        public function getAllMyReferrals()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->validateGetJobsListInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getAllMyReferrals($inputUserData);
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
        
}
?>
