<?php

//require 'vendor/autoload.php';

namespace API\SuccessFactors;

use Mintmesh\Services\SuccessFactors\successFactorManager;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Gateways\API\SuccessFactors\SuccessFactorGateway;
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter;
use Config;
use Lang;

class successFactorController extends \BaseController {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    const AUTHENTICATION_KEY = 'HcHajxliVqjQ8Jp3Sgc8ms8UPzPyPp8jq1AQk9x9';

    protected $successFactorManager, $neoPostRepository, $userGateway, $referralsRepository, $successfactorygateway;

    public function __construct(
    successFactorManager $successFactorManager, NeoPostRepository $neoPostRepository, UserGateway $userGateway, SuccessFactorGateway $successfactorygateway, referralsRepository $referralsRepository, CommonFormatter $commonFormatter
    ) {
        $this->successFactorManager = $successFactorManager;
        $this->neoPostRepository = $neoPostRepository;
        $this->userGateway = $userGateway;
        $this->referralsRepository = $referralsRepository;
        $this->commonFormatter = $commonFormatter;
        $this->SuccessFactorGateway = $successfactorygateway;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        
    }

    public function createSFJob($reqid) {

        set_time_limit(0);
        $input['req_id'] = $reqid;
        $input['company_id'] = 1;
        $data = $this->successFactorManager->getSFJobs($input);
        //echo "<pre>";
        //print_r($data);exit;
        $postId = 0;
        $fromId = 292819;
        $userEmailId = 'gopi68@mintmesh.com';
        $companyName = 'company68';
        $companyCode = 510632;
        $row = $data;
        //foreach($data as $row){
        // print_r($row['jobCode']); 
        $relationAttrs = $neoInput = $postCompanyrelationAttrs = array();
        $neoInput['service_scope'] = "find_candidate";
        $neoInput['service_from_web'] = 1;

        $neoInput['service_name'] = $row['jobCode'];
        $neoInput['looking_for'] = $row['jobCode'];
        $neoInput['job_function'] = $row['function'];
        $neoInput['service_location'] = $row['location'];
        $neoInput['service_country'] = !empty($input['country_code']) ? $input['country_code'] : '';
        $neoInput['industry'] = $row['industry'];
        $neoInput['employment_type'] = '';
        $neoInput['experience_range'] = $row['jobGrade'];
        $neoInput['service'] = ''; //job_description
        $neoInput['position_id'] = $row['positionNumber'];
        $neoInput['requistion_id'] = $row['jobReqId'];
        $neoInput['no_of_vacancies'] = $row['numberOpenings'];

        $neoInput['service_period'] = 'immediate';
        $neoInput['service_type'] = 'global';
        $neoInput['free_service'] = 1;
        $neoInput['service_currency'] = '';
        $neoInput['service_cost'] = '';
        $neoInput['company'] = $companyName;
        $neoInput['job_description'] = ''; //job_description
        $neoInput['skills'] = $this->userGateway->getSkillsFromJobTitle($neoInput['service_name'], $neoInput['job_description']);
        $neoInput['status'] = Config::get('constants.POST.STATUSES.ACTIVE');
        $neoInput['created_by'] = $userEmailId;
        $neoInput['post_type'] = 'external';
        $relationAttrs['created_at'] = date("Y-m-d H:i:s");
        $relationAttrs['company_name'] = $companyName;
        $relationAttrs['company_code'] = $companyCode;

        $createdPost = $this->neoPostRepository->createPostAndUserRelation($fromId, $neoInput, $relationAttrs);
        if (isset($createdPost[0]) && isset($createdPost[0][0])) {
            $postId = $createdPost[0][0]->getID();
        } else {
            $postId = 0;
        }

        #map post and company
        $postCompanyrelationAttrs['created_at'] = gmdate("Y-m-d H:i:s");
        $postCompanyrelationAttrs['user_emailid'] = $userEmailId;
        if (!empty($relationAttrs['company_code'])) {
            $createdrelation = $this->neoPostRepository->createPostAndCompanyRelation($postId, $relationAttrs['company_code'], $postCompanyrelationAttrs);
        }
        //#map industry if provided
        //if (!empty($neoInput['industry'])) {
        //    $iResult = $this->referralsRepository->mapIndustryToPost($neoInput['industry'], $postId, Config::get('constants.REFERRALS.ASSIGNED_INDUSTRY'));
        //}
        //#map job_function if provided
        //if (!empty($neoInput['job_function'])) {
        //    $jfResult = $this->referralsRepository->mapJobFunctionToPost($neoInput['job_function'], $postId, Config::get('constants.REFERRALS.ASSIGNED_JOB_FUNCTION'));
        //}
        //#map employment type if provided
        //if (!empty($neoInput['employment_type'])) {
        //    $emResult = $this->referralsRepository->mapEmploymentTypeToPost($neoInput['employment_type'], $postId, Config::get('constants.REFERRALS.ASSIGNED_EMPLOYMENT_TYPE'));
        // }
        //#map experience range if provided
        //if (!empty($neoInput['experience_range'])) {
        //    $eResult = $this->referralsRepository->mapExperienceRangeToPost($neoInput['experience_range'], $postId, Config::get('constants.REFERRALS.ASSIGNED_EXPERIENCE_RANGE'));
        //}
        //break;
        //} 


        $data = array("post_id" => $postId);
        $responseCode = self::SUCCESS_RESPONSE_CODE;
        $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
        $responseMessage = array('msg' => array(Lang::get('MINTMESH.post.success')));

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }

    public function getIntegrationStatus() {
        // Receiving user input data
        $inputUserData = \Input::all();
        // Validating user input data
        $validation = $this->SuccessFactorGateway->validateIntegrationStatus($inputUserData);
        if ($validation['status'] == 'success') {
            if ($inputUserData['authentication_key'] == self::AUTHENTICATION_KEY) {
                $response = $this->SuccessFactorGateway->getIntegrationStatus($inputUserData);
                return \Response::json($response);
            } else {
                $message = array('msg' => array(Lang::get("Failure")));
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
                 return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
            }
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

}
