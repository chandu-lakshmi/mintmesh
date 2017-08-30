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
            
}

?>
