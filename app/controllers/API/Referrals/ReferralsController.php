<?php
namespace API\Referrals;
use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response;
use Config ;
use View;
class ReferralsController extends \BaseController {

        
	public function __construct(ReferralsGateway $referralsGateway)
	{
		$this->referralsGateway = $referralsGateway;
        }

        /**
	 * Create new user entry
         * 
         * POST/user
         * 
         * @param string $access_token The Access token of a user
	 * @param string $service Service Text
         * @param string $service_scope Service scope get_service|provide_service|find_candidate|find_job
         * @param string $service_cost Service cost
         * @param string $free_service Service free 0|1
         * @param string $web_link Service web url
         * @param string $service_location Service location
         * @param string $service_period Service Period
         * @param string $service_currency Service curreny
         * @param string $service_type Service Type global|in_location|change_location
         * @param string $changed_location location where the service should be posted
         * @param string $excluded_list Users that are to be excluded
         * 
	 * @return Response
	 */
	public function seekServiceReferral()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validateServiceSeekReferralInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->referralsGateway->seekServiceReferral($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * Mark a post as read for a user
         * 
         * POST/close_post
         * 
         * @param string $access_token The Access token of a user
	 * @param string $post_id Id of the post
         * 
	 * @return Response
	 */
	public function closePost()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validateClosePostInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->referralsGateway->closePost($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
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
            $validation = $this->referralsGateway->validateClosePostInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->referralsGateway->deactivatePost($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        
        /**
	 * edit a post
         * 
         * POST/edit_post
         * 
         * @param string $access_token The Access token of a user
	 * @param string $post_id Id of the post
         * 
	 * @return Response
	 */
	public function editPost()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyGetPostDetails($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->referralsGateway->editPost($inputUserData));
            } else {
                // returning validation failure
                return \Response::json($validation);
            }
	}
        
        
        /**
	 * Get Latest Posts
         * 
         * POST/get_latest_posts
         * 
	 * @param string $access_token
         * 
	 * @return Response
	 */
	public function getLatestPosts()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            return \Response::json($this->referralsGateway->getLatestPosts($inputUserData));
	}
        
        /**
	 * Get Posts
         * 
         * POST/get_posts
         * 
	 * @param string $access_token The Access token of a user
         * @param string $request_type get_service|provide_service
         * @param string $page_no
         * 
	 * @return Response
	 */
	public function getPosts()
	{
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validateGetPostsInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->referralsGateway->getPosts($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
	}
        
        /**
	 * refer a person for a post
         * 
         * POST/refer_contact
         * 
         * @param string $access_token The Access token of a user
         * @param string $refering
         * @param string $refer_to
         * @param string $post_id
         * @param string $message
         * @param string $bestfit_message
	 * @return Response
	 */
        public function referContact()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->validatereferContact($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->referContact($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get a post details
         * 
         * POST/get_post_details
         * 
         * @param string $access_token The Access token of a user
         * @param string $post_id
	 * @return Response
	 */
        public function getPostDetails()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyGetPostDetails($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getPostDetails($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get a post references
         * 
         * POST/get_post_references
         * 
         * @param string $access_token The Access token of a user
         * @param string $post_id
         * @param string $limit
         * @param string $page_no
	 * @return Response
	 */
        public function getPostReferences()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyGetPostDetails($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getPostReferences($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
         /**
	 * get my referrals
         * 
         * POST/get_my_referrals
         * 
         * @param string $access_token The Access token of a user
         * @param string $post_id
	 * @return Response
	 */
        public function getMyReferrals()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyGetPostDetails($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getMyReferrals($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * process post
         * 
         * POST/process_post
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user from wher the request has come
         * @param string $referred_by
         * @param string $post_way one|round
         * @param string $status accepted|declined 
         * @param string $post_id
         * @param string $relation_count
	 * @return Response
	 */
        public function processPost()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyProcessPostDetails($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->processPost($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get post status details
         * 
         * POST/get_post_status_details
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user from wher the request has come
         * @param string $referred_by person who referred
         * @param string $referral person who got referred
         * @param string $relation_count relation count
         * @param string $post_id
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
	 * get all referrals
         * 
         * POST/get_all_referrals
         * 
         * @param string $access_token
         * @param string $page
	 * @return Response
	 */
        public function getAllReferrals()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->referralsGateway->getAllReferrals($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * peoples search
         * 
         * POST/search_people
         * 
         * @param string $access_token
         * @param string $job_function
         * @param string $company
         * @param string $industry
         * @param string $location
         * @param json $skills encoded json of ids of skills
	 * @return Response
	 */
        public function searchPeople()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->referralsGateway->searchPeople($inputUserData);
            return \Response::json($response);
        }
        
        /**
	 * get mutual people
         * 
         * POST/get_mutual_people
         * 
         * @param string $access_token The Access token of a user
         * @param string $other_email
	 * @return Response
	 */
        public function getMutualPeople()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->referralsGateway->verifyMutualPeople($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->referralsGateway->getMutualPeople($inputUserData);
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
        
        public function testIndex()
        {
            $this->referralsGateway->testIndex();
        }
        
        
}
?>
