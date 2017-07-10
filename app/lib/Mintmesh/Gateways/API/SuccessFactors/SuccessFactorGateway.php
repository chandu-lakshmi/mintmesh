<?php

namespace Mintmesh\Gateways\API\SuccessFactors;

/**
 * This is the Enterprise Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */
use Mintmesh\Repositories\API\Enterprise\EnterpriseRepository;
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Mintmesh\Repositories\API\Enterprise\NeoEnterpriseRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Mintmesh\Services\Validators\API\Enterprise\EnterpriseValidator;
use Mintmesh\Services\Validators\Api\SuccessFactors\IntegrationValidator;
use Mintmesh\Services\Emails\API\User\UserEmailManager;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\APPEncode\APPEncode;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use lib\MyExcel\MyExcel;
use Lang,
    Excel;
use Config;
use OAuth;
use URL,
    Queue;
use Cache;

class SuccessFactorGateway {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    const REFRESH_TOKEN = 'refresh_token';
    const AUTHORIZATION = 'Authorization';
    const CREATED_IN = 'created_in';
    const HCM_ID = 1;

    protected $userRepository, $enterpriseRepository, $enterpriseValidator, $integrationValidator, $userFileUploader, $commonFormatter, $authorizer, $appEncodeDecode, $neoEnterpriseRepository;
    protected $allowedHeaders, $allowedExcelExtensions, $createdNeoUser, $referralsGateway, $contactsRepository, $referralsRepository, $myExcel;

    public function __construct(EnterpriseRepository $enterpriseRepository, NeoEnterpriseRepository $neoEnterpriseRepository, UserGateway $userGateway, ReferralsGateway $referralsGateway, ReferralsRepository $referralsRepository, UserRepository $userRepository, NeoUserRepository $neoUserRepository, NeoPostRepository $neoPostRepository, Authorizer $authorizer, EnterpriseValidator $enterpriseValidator, IntegrationValidator $integrationValidator, UserFileUploader $userFileUploader, UserEmailManager $userEmailManager, CommonFormatter $commonFormatter, APPEncode $appEncodeDecode, ContactsRepository $contactsRepository, MyExcel $myExcel
    ) {

        $this->enterpriseRepository = $enterpriseRepository;
        $this->neoEnterpriseRepository = $neoEnterpriseRepository;
        $this->userGateway = $userGateway;
        $this->referralsRepository = $referralsRepository;
        $this->referralsGateway = $referralsGateway;
        $this->userRepository = $userRepository;
        $this->neoUserRepository = $neoUserRepository;
        $this->neoPostRepository = $neoPostRepository;
        $this->authorizer = $authorizer;
        $this->enterpriseValidator = $enterpriseValidator;
        $this->integrationValidator = $integrationValidator;
        $this->userFileUploader = $userFileUploader;
        $this->userEmailManager = $userEmailManager;
        $this->commonFormatter = $commonFormatter;
        $this->appEncodeDecode = $appEncodeDecode;
        $this->contactsRepository = $contactsRepository;
        $this->myExcel = $myExcel;
        $this->allowedHeaders = array('employee_idother_id', 'first_name', 'last_name', 'email_id', 'cell_phone', 'status');
        $this->validHeaders = array('Employee ID/Other ID', 'First Name', 'Last Name', 'Email ID', 'Cell Phone', 'Status');
        $this->allowedExcelExtensions = array('csv', 'xlsx', 'xls');
    }

    // validation on  user inputs for creating a enterprise user
    public function validateIntegrationStatus($input) {
        return $this->doValidation('integrationStatus', 'MINTMESH.user.valid');
    }

    public function doValidation($validatorFilterKey, $langKey) {
        //validator passes method accepts validator filter key as param
        if ($this->integrationValidator->passes($validatorFilterKey)) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get($langKey)));
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $data = array();
        } else {
            /* Return validation errors to the controller */
            $message = $this->integrationValidator->getErrors();
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $data = array();
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }
    
    public function getIntegrationStatus($input) {
        
        $hcm_id = self::HCM_ID;
        $returnAry = $data = $getHcmList = $response = array();
        $companyCode    = !empty($input['company_id'])?$input['company_id']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0;
        #get company HCMs List here
        $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcm_id);
        if(!empty($getHcmJobs)){
        $getHcmstatus = !empty($getHcmJobs[0]->status)?$getHcmJobs[0]->status:0;
        $getHcmstatus = !empty($getHcmstatus)?'enable':'disable';
        $company_id = $getHcmJobs[0]->company_id;
        $company_hcm_jobs_id = $getHcmJobs[0]->company_hcm_jobs_id;
        $response['hcmJobs'] = "Success";
        $getHcmMappingFieldsCount = $this->enterpriseRepository->getCompanyMappingFieldsCount($companyId,$company_hcm_jobs_id);
        if($getHcmMappingFieldsCount == 14){
            $response['hcmMappingFieldsCount'] = "Success";
        }else{
            $response['hcmMappingFieldsCount'] = "Failure";
        }
        $getHcmConfigProperties = $this->enterpriseRepository->getCompanyConfigProperties($companyId,$hcm_id);
        if(!empty($getHcmConfigProperties)){
        $response['hcmConfigProperties'] = "Success";
        }else{
            $response['hcmConfigProperties'] = "Failure";
        }
        }else{
            $response['hcmJobs'] = "Failure";
        }
        return $response;
    }

     
}
?>

