<?php
namespace API\Enterprise;
use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Repositories\API\User\UserRepository;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response;
use Config ;
use View;


class EnterpriseController extends \BaseController {

        
	public function __construct(EnterpriseGateway $EnterpriseGateway,UserGateway $userGateway,UserRepository $userRepository){
            
                    $this->EnterpriseGateway = $EnterpriseGateway;
                    $this->userGateway = $userGateway;
                    $this->userRepository = $userRepository;
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
	 * Create new enterprise user entry
         * 
         * POST/user
         * 
	 * @param string $fullname The fullname of a user
	 * @param string $company The companyname of a user
         * @param string $emailid The email id of a user
         * @param string $password The password of a user profile  
         * 
	 * @return Response
	 */
	public function createEnterpriseUser()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateCreateUserInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                $returnResponse = \Response::json($this->EnterpriseGateway->createEnterpriseUser($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
        /**
	 * Create new enterprise Company entry
         * 
         * POST/user
         * 
	 * @param string $company Name of the company
	 * @param string $industry The industry of a user
	 * @param string $description Description of the company
         * @param string $website Website url of the company
         * @param string $company_id Id of the company
         * @param string $company_logo logo of the company
         * @param string $size size of the company
         * @param string $code code of the comapany
         * @param string $number_of_employees number of employees in company
         * 
	 * @return Response
	 */
	public function updateCompanyProfile()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateCompanyProfileInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                // creating entry in mysql DB
                $returnResponse = \Response::json($this->EnterpriseGateway->updateCompanyProfile($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            return $returnResponse;
	}
        
        
        
        /**
	 * Activate a user account
         * 
         * GET/Email Verification
         * 
         * 
	 * @return Response
	 */
        public function emailVerification()
        {
           
           // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEmailVerificationToken($inputUserData);
            if($validation['status'] == 'success') 
            {
                $returnResponse = \Response::json($this->EnterpriseGateway->emailVerification($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            return $returnResponse;
           
        }
        
         /**
	 * Authenticate an enterprise login
         * 
         * POST/login
         * 
	 * @param string $username The email id of an enterprise user
         * @param string $password The password of an enterprise user profile
         * 
	 * @return Response
	 */
	public function enterpriseLogin()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseLoginInput($inputUserData);
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->verifyEnterpriseLogin($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
        /**
	 * Authenticate an enterprise special autologin
         * 
         * POST/login
         * 
	 * @param string $code the verify emailid random code
         * @param string $emailid 
         * 
	 * @return Response
	 */
	public function enterpriseSpecialLogin()
	{
            // Receiving user input data
            $inputUserData = \Input::all();            
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseSpecialLoginInput($inputUserData);            
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->verifyEnterpriseLogin($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
        /**
        * Authenticate an enterprise get User Details
        * 
        * POST/login
        * @param string  $access_token The Access token of a user   
        * @return Response
        */
	public function getUserDetails()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseGetUserDetails($inputUserData);            
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->enterpriseGetUserDetails($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
      /**
        * Authenticate an enterprise Upload Contacts
        * 
        * POST/login
        * @param string  $access_token The Access token of a user  
        * @param file    $contacts_file Upload Excel file
        * @param integer $company_id
        * @param integer $is_bucket_new if new bucket
        * @param string  $bucket_name
        * @param integer $bucket_id
        * 
        * @return Response
        */
	public function enterpriseContactsUpload()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseContactsUpload($inputUserData);            
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->enterpriseContactsUpload($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
        
      /**
        * Authenticate an enterprise company bucket list
        * 
        * POST/login
        * @param string  $access_token The Access token of a user  
        * @param integer $company_id
        * 
        * @return Response
        */
	public function enterpriseBucketsList()
	{
            // Receiving user input data
            $inputUserData = \Input::all();            
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseBucketsList($inputUserData);            
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->enterpriseBucketsList($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
      /**
        * Authenticate an enterprise Upload Contacts
        * 
        * POST/login
        * @param string  $access_token The Access token of a user  
        * @param integer $company_id
        * 
        * @return Response
        */
	public function enterpriseContactsList()
	{                 
            // Receiving user input data
            $inputUserData = \Input::all();            
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseContactsList($inputUserData);            
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->enterpriseContactsList($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
      /**
        * Authenticate an enterprise Contacts Email Invitations
        * 
        * POST/login
        * @param string  $access_token The Access token of a user  
        * @param integer $company_id
        * 
        * @return Response
        */
	public function enterpriseContactsEmailInvitation()
	{
            // Receiving user input data
            $inputUserData = \Input::all();            
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEnterpriseEmailInvitations($inputUserData);            
            if($validation['status'] == 'success') {
                $returnResponse = \Response::json($this->EnterpriseGateway->enterpriseContactsEmailInvitation($inputUserData));
            } else {
                    // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
	}
        
         /**
	 * Send reset password link to users email id
         * 
         * POST/forgot_password
         * 
         * @param $emailid
         * 
	 * @return Response
	 */
        public function forgotPassword()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateForgotPasswordInput($inputUserData);
            if($validation['status'] == 'success') {
                return \Response::json($this->EnterpriseGateway->sendForgotPasswordEmail($inputUserData));
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
            $validation = $this->EnterpriseGateway->validateResetPasswordInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->resetPassword($inputUserData);
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
        *  Authenticate an mintmesh user view their connected Companies List
        * 
        * POST/connectedCompaniesList
        * @param string  $access_token The Access token of a user   
        * @return Response connected Companies List
        */
        public function connectedCompaniesList(){
               $response = $this->EnterpriseGateway->connectedCompaniesList();
            return \Response::json($response);
        }
        
         /**
	 * mobile user can able to connect company 
         * 
         * POST/connect_to_company
         * 
         * @param string $company_code company code
	 * @return Response
	 */
        public function connectToCompany()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateConnectToCompanyInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->connectToCompany($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
         /**
	 * web user can able to view dashboard 
         * 
         * POST/view_dashboard
         * 
	 * @return Response
	 */
        public function viewDashboard()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateViewDashboardInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->viewDashboard($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
         /**
        *  Authenticate an enterprise user view company's details
        * 
        * POST/get_company_profile
        * @param string  $access_token The Access token of a user   
        * @return Response company's profile
        */
        public function getCompanyProfile(){
               $response = $this->EnterpriseGateway->getCompanyProfile();
            return \Response::json($response);
        }
        
         /**
        *  update contacts list 
        * 
        * POST/update_contacts_list
        * @param string  $access_token The Access token of a user   
        * @param string  $record_id recordid of record to be edited 
        * @param string  $firstname
        * @param string  $lastname
        * @param string  $other_id
        * @param string  $status
        * @param string  $contact_number
        * @return Response updated contacts list
        */
        public function updateContactsList(){
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateupdateContactsList($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->updateContactsList($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
         /**
        *  delete contacts from list 
        * 
        * POST/delete_contact
        * @param string  $access_token The Access token of a user   
        * @param string  $record_id recordid of record to be deleted 
        * @return Response 
        */
        public function deleteContactAndEditStatus(){
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateDeleteContactAndEditStatus($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->deleteContactAndEditStatus($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
     
        /**
        *  edits in contact list
        * 
        * POST/delete_contact
        * @return Response 
        */
        public function otherEditsInContactList() {
             $inputUserData = \Input::all();
             if($inputUserData['action'] == 'invite'){
              return $this->enterpriseContactsEmailInvitation();
             }  else {
                  return $this->deleteContactAndEditStatus();
             }     
        }
        
        /**
        * Create New Bucket 
        * 
        * POST/create_bucket
        * @param string  $access_token The Access token of a user   
        * @param string  $bucket_name new bucket name 
        * @param string  $company_code company code
        * @return Response new bucket id
        */
        public function createBucket(){
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateupdateCreateBucket($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->createBucket($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        /**
        * validate Contacts File Headers
        * 
        * POST/create_bucket
        * @param string  $access_token The Access token of a user   
        * @param string  $file_name input file name 
        * @return Response validated status
        */
        public function validateContactsFileHeaders(){
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateFileInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->validateContactsFileHeaders($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        /**
        * user upload the Contacts
        * 
        * POST/create_bucket
        * @param string  $access_token The Access token of a user   
        * @param string  $file_name input file name 
        * @return Response validated status
        */
        public function uploadContacts(){
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateUploadContactsInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->uploadContacts($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
        /**
        * user add Contact
        * 
        * POST/add_contact
        * @param string  $access_token The Access token of a user   
        * @param string  $file_name input file name 
        * @return Response validated status
        */
        public function addContact(){
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateAddContactInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->addContact($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
                
        /**
        *  get permissions list
        * 
        * POST/permissions
        * @param string  $access_token The Access token of a user   
        * @return 
        */
        public function getPermissions(){
            $response = $this->EnterpriseGateway->getPermissions();
            return \Response::json($response);
        }
        
        /**
        * Get permissions for the user
        * @POST/ get_user_permissions 
        * 
        * @return Response
        */
        public function getUserPermissions() {
            $response = $this->EnterpriseGateway->getUserPermissions();
            return \Response::json($response);
        }
        
        private function addingUser() {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateAddingUserInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                $returnResponse = \Response::json($this->EnterpriseGateway->addingUser($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
        }
        
        private function editingUser(){
           // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEditingUserInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                $returnResponse = \Response::json($this->EnterpriseGateway->editingUser($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
            
        }


        /**
	 * Add user to company
         * 
         * POST/add_user
         * 
	 * @param string $fullname The fullname of a user
	 * @param string $location
         * @param string $emailid The email id of a user
         * @param string $designation The designation of the user
         * @param string $group_id
         * @param string $action 0|1
         * @param string $user_id when action is 1
         * @param string $status 
         * 
	 * @return Response
	 */
        public function addUser() {
             $inputUserData = \Input::all();
             if($inputUserData['action'] == '0'){
              return $this->addingUser();
             }  else {
                  return $this->editingUser();
             }     
        }
          
         /**
	 * Add user to company
         * 
         * POST/add_group
         * 
	 * @param string $name The name of the group
	 * @param string $status 
         * @param string $permission
         * @param string $action 0|1
         * @param string $group_id  
         * 
	 * @return Response
	 */
        public function addGroup() {
             $inputUserData = \Input::all();
             if($inputUserData['action'] == '0'){
              return $this->addingGroup();
             }  else {
                  return $this->editingGroup();
             }     
        }
        
        
        private function addingGroup() {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateAddGroupInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                $returnResponse = \Response::json($this->EnterpriseGateway->addingGroup($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
        }
        
        private function editingGroup() {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateEditGroupInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                $inputUserData['login_source'] = Config::get('constants.MNT_LOGIN_SOURCE') ;
                // creating entry in mysql DB
                $returnResponse = \Response::json($this->EnterpriseGateway->editingGroup($inputUserData));
            } else {
                // returning validation failure
                $returnResponse = \Response::json($validation);
            }
            
            return $returnResponse;
        }
        
        /**
        * Get groups
        * @POST/ get_groups
        * @return Response
        */
        public function getGroups(){
            $response = $this->EnterpriseGateway->getGroups();
            return \Response::json($response);
        }
        
         /**
	 * set user's password
         * 
         * POST/set_password
         * 
         * @param string $code The reset password code
         * @param string $password The new password of a user account
         * @param string $password_confirmation password confirmation field
         * 
	 * @return Response
	 */
        public function setPassword()
        {      
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateSetPasswordInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->setPassword($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * update enterprise user details
         * 
         * POST/update_user
         * 
         * @param string $name 
         * @param string $emailid
         * @param string $flag 0|1
         * @param string $photo
         * @param string $photo_org_name
	 * @return Response
	 */
        public function updateUser() {
             // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateupdateUserInput($inputUserData);
            if($validation['status'] == 'success') {
               $response = $this->EnterpriseGateway->updateUser($inputUserData);
               return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        public function updateNewPermission(){
            $response = $this->EnterpriseGateway->updateNewPermission();
            return \Response::json($response);
        }
             
        
        /**
	 * Deactivate a post
         * 
         * POST/deactivate_post
         * 
         * @param string $access_token The Access token of a user
	 * @param string $post_id Id of the post
         * 
	 * @return Response
	 */
	public function deactivatePost()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->EnterpriseGateway->validateDeactivatePostInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->EnterpriseGateway->deactivatePost($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
}
?>
