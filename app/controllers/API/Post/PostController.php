<?php

namespace API\Post;

use Mintmesh\Gateways\API\Post\PostGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang,
    Response;
use Config;
use View;

class PostController extends \BaseController {

    public function __construct(PostGateway $PostGateway) {

        $this->PostGateway = $PostGateway;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        //return $this->EnterpriseGateway->getUserlist();
    }

    /**
     * Posting a job
     * 
     * POST/job_post
     * 
     * @param string $access_token the access token of enterprise user
     * @param string $job_title 
     * @param string $job_function 
     * @param string $location the location of the job to be posted
     * @param string $industry
     * @param string $employee_type
     * @param string $experience_range
     * @param string $position_id
     * @param string $requistion_id
     * @param string $job_description
     * @param string $free_job Job free 0|1
     * @param string $job_currency Job curreny     
     * @param string $job_period Job Period    
     * @param string $job_cost Job cost
     * @param string $job_type Job Type global
     * @param string $bucket_id  bucket id
     * @return Response
     */
    public function postJob() {
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->PostGateway->validatePostJobInput($inputUserData);
        if ($validation['status'] == 'success') {
            return \Response::json($this->PostGateway->postJob($inputUserData));
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    /**
     * Get Posts
     * 
     * POST/jobs_list
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param string $request_type 1(free)|0(paid)|2(all)
     * @param string $search_for 
     * @param string $page_no
     * @return Response
     */
    public function jobsList() {
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->PostGateway->validatejobsListInput($inputUserData);
        if ($validation['status'] == 'success') {
            return \Response::json($this->PostGateway->jobsList($inputUserData));
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    /**
     * Get Job Details
     * 
     * POST/job_details
     * 
     * @param string $access_token The Access token of a user
     * @param string $post_id
     * @return Response
     */
    public function jobDetails() {
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->PostGateway->validatejobDetailsInput($inputUserData);
        if ($validation['status'] == 'success') {
            return \Response::json($this->PostGateway->jobDetails($inputUserData));
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    /**
     * Get referral Details
     * 
     * POST/referral_details
     * 
     * @param string $access_token The Access token of a user
     * @param string $post_id
     * @param string $status DECLINED|ACCEPTED
     * @return Response
     */
    public function jobReferralDetails() {
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->PostGateway->jobReferralDetailsInput($inputUserData);
        if ($validation['status'] == 'success') {
            return \Response::json($this->PostGateway->jobreferralDetails($inputUserData));
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }
    
     /**
	 * process post
         * 
         * POST/process_job
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user from where the request has come
         * @param string $referred_by
         * @param string $post_way one|round
         * @param string $status accepted|declined 
         * @param string $post_id
         * @param string $relation_count
         * @param string $referred_by_phone
	 * @return Response
	 */
        public function processJob()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->processJobInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->processJob($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
     /**
	 * awaiting Action for post
         * 
         * POST/awaiting_action
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user from where the request has come
         * @param string $referred_by
         * @param string $awaiting_action_status ACCEPTED|INTERVIEWED|OFFERMADE|HIRED
         * @param string $post_id
         * @param string $relation_count
         * @param string $referred_by_phone
	 * @return Response
	 */
        public function awaitingAction()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->awaitingActionInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->awaitingAction($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
     /**
	 * Reward details for post
         * POST/job_rewards
         * 
         * @param string $access_token The Access token of a user
         * @param string $post_id
	 * @return Response
	 */
        public function jobRewards()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->jobRewardsInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->jobRewards($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        
     /**
	 * add Campaign for posts
         * 
         * POST/add_campaign
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user from where the request has come
	 * @return Response
	 */
        public function addCampaign()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->addCampaignInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->addCampaign($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * view Campaign for posts
         * 
         * POST/view_campaign
         * 
         * @param string $access_token The Access token of a user
         * @param string $from_user from where the request has come
	 * @return Response
	 */
        public function viewCampaign()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->PostGateway->viewCampaignInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->PostGateway->viewCampaign($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
         /**
        * Get campaigns list
        * @POST/campaigns_list 
        * 
        * @return Response
        */
        public function campaignsList() {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->PostGateway->campaignsList($inputUserData);
            return \Response::json($response);
        }
        
}

?>
