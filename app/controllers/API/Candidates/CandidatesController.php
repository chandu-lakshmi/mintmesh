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
    
    /**
     * Get Posts
     * 
     * POST/get_Candidate_Tags
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function getCandidatesTags() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCandidatesTagsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidatesTags($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/add_Candidate_Tags
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function addCandidateTags() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateaddCandidatesTagsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addCandidateTags($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/get_Candidate_Tags
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function getCandidateTags() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCandidateTagsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidateTags($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    public function testEmail() {
        // Receiving user input data
        $inputUserData = \Input::all();
        return \Response::json($this->candidatesGateway->testEmail($inputUserData));;
    }
    /**
     * Get Posts
     * 
     * POST/delete_Candidate_Tag
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function deleteCandidateTag() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validatedeleteCandidateTagInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->deleteCandidateTag($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/addCandidatePersonalStatus
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function addCandidatePersonalStatus() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateaddCandidatePersonalStatusInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addCandidatePersonalStatus($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Posts
     * 
     * POST/getCandidatePersonalStatus
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $reference_id 
     * @param string $referral_status
     * @return Response
     */
    public function getCandidatePersonalStatus() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCandidatePersonalStatusInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCandidatePersonalStatus($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Question Types
     * 
     * POST/get_question_types
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @return Response
     */
    public function getQuestionTypes() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetQuestionTypesInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getQuestionTypes($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Get Question Libraries
     * 
     * POST/get_question_libraries
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @return Response
     */
    public function getQuestionLibraries() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetQuestionLibrariesInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getQuestionLibraries($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Add Question
     * 
     * POST/add_question
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param string $question  
     * @param integer $question_type  
     * @return Response
     */
    public function addQuestion() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateAddQuestionInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addQuestion($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     * Edit Question
     * 
     * POST/edit_question
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param string $question  
     * @param integer $question_type  
     * @return Response
     */
    public function editQuestion() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateEditQuestionInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->editQuestion($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    
    /**
     *View Question
     * 
     * POST/view_question
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code 
     * @param integer $question_id  
     * @return Response
     */
    public function viewQuestion() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateViewQuestionInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->viewQuestion($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     *Add_Edit_Exam
     * 
     * POST/add_edit_exam
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code  
     * @return Response
     */
    public function addEditExam() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateAddEditExamInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->addEditExam($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     *Edit_Exam
     * 
     * POST/edit_exam
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code  
     * @return Response
     */
    public function viewExam() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateEditExamInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->viewExam($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     *Edit_Exam_Settings
     * 
     * POST/edit_exam_settings
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code  
     * @return Response
     */
    public function editExamSettings() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateEditExamSettingsInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->editExamSettings($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    /**
     *get questions list
     * 
     * POST/get_questions_list
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code  
     * @return Response
     */
    public function getQuestionsList() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validateGetQuestionsListInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getQuestionsList($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    
    /**
     *get questions list
     * 
     * POST/get_company_assessments_list
     * 
     * @param string $access_token The Access token of a user
     * @param string $company_code  
     * @return Response
     */
    public function getCompanyAssessmentsList() {
        
        $return = '';
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->candidatesGateway->validategetCompanyAssessmentsListInput($inputUserData);
        if ($validation['status'] == 'success') {
            $return = \Response::json($this->candidatesGateway->getCompanyAssessmentsList($inputUserData));
        } else {
            // returning validation failure
            $return = \Response::json($validation);
        }
    return $return;
    }
    
    
    
    
}

?>
