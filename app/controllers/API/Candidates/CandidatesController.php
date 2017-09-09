<?php

namespace API\Candidates;

use Mintmesh\Gateways\API\Candidates\CandidatesGateway;
use Response;

class CandidatesController extends \BaseController {

    protected $candidatesGateway;
    public function __construct(CandidatesGateway $candidatesGateway) {

        $this->candidatesGateway = $candidatesGateway;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        
    }

    /**
     * Get Posts
     * 
     * POST/get_candidate_email_templates
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @return Response
     */
    public function getCandidateEmailTemplates() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetCandidateEmailTemplatesInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateEmailTemplates($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/get_candidate_details
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $referred_id 
     * @return Response
     */
    public function getCandidateDetails() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetCandidateDetailsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateDetails($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/add_candidate_schedule
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function addCandidateSchedule() {

        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateAddCandidateScheduleInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addCandidateSchedule($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/add_candidate_email
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function addCandidateEmail() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateAddCandidateEmailInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addCandidateEmail($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/add_candidate_comment
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function addCandidateComment() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateAddCandidateCommentInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addCandidateComment($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/get_candidate_activities
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function getCandidateActivities() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetCandidateActivitiesInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateActivities($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/get_candidate_tag_jobs_list
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function getCandidateTagJobsList() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetCandidateTagJobsListInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateTagJobsList($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/add_candidate_tag_jobs
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function addCandidateTagJobs() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateAddCandidateTagJobsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addCandidateTagJobs($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
     /**
     * Get Posts
     * 
     * POST/get_candidate_comments
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function getCandidateComments() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCandidateCommentsActivitiesInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateComments($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    
     /**
     * Get Posts
     * 
     * POST/get_candidate_sent_emails
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function getCandidateSentEmails() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCandidateSentEmailsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateSentEmails($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/get_candidate_referral_list
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id | $candidate_id | $contact_id
     * @return Response
     */
    public function getCandidateReferralList() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetCandidateReferralListInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateReferralList($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
         
    /**
     * Get Posts
     * 
     * POST/get_candidate_schedules
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @return Response
     */
    public function getCandidateSchedules() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCandidateSchedulesActivitiesInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateSchedules($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/edit_candidate_referral_status
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function editCandidateReferralStatus() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateEditCandidateReferralStatusInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->editCandidateReferralStatus($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
}

?>
