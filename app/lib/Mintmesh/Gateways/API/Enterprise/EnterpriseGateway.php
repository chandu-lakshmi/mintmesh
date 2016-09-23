<?php

namespace Mintmesh\Gateways\API\Enterprise;

/**
 * This is the Enterprise Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */
use Mintmesh\Repositories\API\Enterprise\EnterpriseRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\Post\NeoPostRepository;
use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Mintmesh\Repositories\API\Enterprise\NeoEnterpriseRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Mintmesh\Services\Validators\API\Enterprise\EnterpriseValidator;
use Mintmesh\Services\Emails\API\User\UserEmailManager;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\APPEncode\APPEncode;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use Lang,
    Excel;
use Config;
use OAuth;
use URL,
    Queue;
use Cache;

class EnterpriseGateway {

    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';

    protected $userRepository, $enterpriseRepository, $enterpriseValidator, $userFileUploader, $commonFormatter, $authorizer, $appEncodeDecode, $neoEnterpriseRepository;
    protected $allowedHeaders, $allowedExcelExtensions, $createdNeoUser, $referralsGateway, $contactsRepository;

    public function __construct(EnterpriseRepository $enterpriseRepository, 
                                NeoEnterpriseRepository $neoEnterpriseRepository, 
            UserGateway $userGateway, 
            ReferralsGateway $referralsGateway, 
            UserRepository $userRepository, 
            NeoUserRepository $neoUserRepository, 
            NeoPostRepository $neoPostRepository, 
            Authorizer $authorizer, 
            EnterpriseValidator $enterpriseValidator, 
            UserFileUploader $userFileUploader, 
            UserEmailManager $userEmailManager, 
            CommonFormatter $commonFormatter, 
            APPEncode $appEncodeDecode,
            ContactsRepository $contactsRepository
    ) {

        $this->enterpriseRepository = $enterpriseRepository;
        $this->neoEnterpriseRepository = $neoEnterpriseRepository;
        $this->userGateway = $userGateway;
        $this->referralsGateway = $referralsGateway;
        $this->userRepository = $userRepository;
        $this->neoUserRepository = $neoUserRepository;
        $this->neoPostRepository = $neoPostRepository;
        $this->authorizer = $authorizer;
        $this->enterpriseValidator = $enterpriseValidator;
        $this->userFileUploader = $userFileUploader;
        $this->userEmailManager = $userEmailManager;
        $this->commonFormatter = $commonFormatter;
        $this->appEncodeDecode = $appEncodeDecode;
        $this->contactsRepository = $contactsRepository;
        $this->allowedHeaders         = array('employee_idother_id', 'first_name', 'last_name', 'email_id', 'cell_phone', 'status');
        $this->allowedExcelExtensions = array('csv', 'xlsx', 'xls');
    }

    // validation on  user inputs for creating a enterprise user
    public function validateCreateUserInput($input) {
        return $this->doValidation('enterpriseUserCreate', 'MINTMESH.user.valid');
    }

    // validation on validate Email Token Verification
    public function validateEmailVerificationToken($input) {
        return $this->doValidation('validateEmailVerificationToken', 'MINTMESH.user.valid');
    }
    
    // validation on validate Email Token Verification
    public function validateEnterpriseGetUserDetails($input) {
        return $this->doValidation('enterpriseGetUserDetails', 'MINTMESH.user.valid');
    }

    // validation on  Company Profile inputs for creating a Company Profile
    public function validateCompanyProfileInput($input) {
        return $this->doValidation('validateCompanyProfileInput', 'MINTMESH.user.valid');
    }

    // validation on enterprise inputs for authenticating a enterprise user
    public function validateEnterpriseLoginInput($input) {
        return $this->doValidation('enterpriseLogin', 'MINTMESH.user.valid');
    }

    public function validateEnterpriseSpecialLoginInput($input) {
        return $this->doValidation('enterprise_special_login', 'MINTMESH.user.valid');
    }

    public function validateEnterpriseContactsUpload($input) {
        return $this->doValidation('enterpriseContactsUpload', 'MINTMESH.user.valid');
    }

    public function validateEnterpriseBucketsList($input) {
        return $this->doValidation('enterpriseBucketsList', 'MINTMESH.user.valid');
    }

    public function validateEnterpriseContactsList($input) {
        return $this->doValidation('enterpriseContactsList', 'MINTMESH.user.valid');
    }

    public function validateEnterpriseEmailInvitations($input) {
        return $this->doValidation('enterpriseEmailInvitations', 'MINTMESH.user.valid');
    }

    //validation on forgot password input
    public function validateForgotPasswordInput($input) {
        return $this->doValidation('forgot_password', 'MINTMESH.forgot_password.valid');
    }

    //validation on reset password input
    public function validateResetPasswordInput($input) {
        return $this->doValidation('reset_password', 'MINTMESH.reset_password.valid');
    }
    //validation on Connect To Company input
    public function validateConnectToCompanyInput($input) {
        return $this->doValidation('connect_to_company', 'MINTMESH.reset_password.valid');
    }
    //validation on Company Details input
    public function validateViewCompanyDetailsInput($input) {
        return $this->doValidation('view_company_details', 'MINTMESH.reset_password.valid');
    }
    //validation on View Dashboard input
    public function validateViewDashboardInput($input) {
        return $this->doValidation('view_dashboard', 'MINTMESH.reset_password.valid');
    }
    //validation on update contact list input
    public function validateupdateContactsList($input) {
        return $this->doValidation('update_contact_list', 'MINTMESH.user.valid');
    }
    //validation on delete contact  input
    public function validateDeleteContactAndEditStatus($input) {
        return $this->doValidation('delete_contact', 'MINTMESH.user.valid');
    }
    //validation on Create new Bucket input
    public function validateupdateCreateBucket($input) {
        return $this->doValidation('create_bucket', 'MINTMESH.user.valid');
    }
    //validation on contacts input file
    public function validateFileInput($input) {
        return $this->doValidation('contacts_file', 'MINTMESH.user.valid');
    }
    //validation on contacts input file
    public function validateUploadContactsInput($input) {
        return $this->doValidation('upload_contacts', 'MINTMESH.user.valid');
    }
    //validation on add contact input
    public function validateAddContactInput($input) {
        return $this->doValidation('add_contact', 'MINTMESH.user.valid');
    }

    public function doValidation($validatorFilterKey, $langKey) {
        //validator passes method accepts validator filter key as param
        if ($this->enterpriseValidator->passes($validatorFilterKey)) {
            /* validation passes successfully */
            $message = array('msg' => array(Lang::get($langKey)));
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $data = array();
        } else {
            /* Return validation errors to the controller */
            $message = $this->enterpriseValidator->getErrors();
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $data = array();
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }

    /**
     * Input array file to Symfony file object.
     * @return File Object
     */
    public function createFileObject($file) {
        return new \Symfony\Component\HttpFoundation\File\UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
    }
//    
    /**
     * Get enterprise User Details.
     * @return Response
     */
    public function enterpriseGetUserDetails($input) {

        $userDetails = $company = $user = array(); 
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
      
        if($this->loggedinUserDetails){
            $userId             = $this->loggedinUserDetails->id;
            $user['emailid']    = $this->loggedinUserDetails->emailid;
            $user['firstname']  = $this->loggedinUserDetails->firstname;
            
            $responseData = $this->enterpriseRepository->getUserCompanyMap($userId);
            
            $company['name']        = $responseData->name;
            $company['company_id']  = $responseData->company_id;
            $company['code']        = $responseData->code;
            $company['logo']        = $responseData->logo;
                       
            $userDetails['user']    = $user;
            $userDetails['company'] = $company;

            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage = Lang::get('MINTMESH.user.profile_success');
            $data = $userDetails;
        } else {
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = Lang::get('MINTMESH.user.user_not_found');
            $data = array();
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function createEnterpriseUser($input) {
        $userCount = 0;
        if (empty($userCount)) {
            $responseMessage = $responseCode = $responseStatus = "";
            $responseData = array();
            $input['is_enterprise'] = 1;

            // creating company random code
            $randomCode = $this->getRandomCode();
            $input['company_code'] = $randomCode;

            //Inserting user details entry in mysql DB
            $createdUser = $this->enterpriseRepository->createEnterpriseUser($input);
            //Inserting user node in neo4j
            $neoEnterpriseUser = $this->createNeoUser($input);
            //cheking user succefully created in mysql DB
            if (!empty($createdUser)) {

                //Inserting company name entry in mysql DB
                $input['user_id'] = $createdUser['id'];
                $createdCompany = $this->enterpriseRepository->createCompanyProfile($input);
                // create a node for company in neo4j
                $neoEnterpriseCompany = $this->createNeoCompany($input, $createdCompany);
                if (!empty($createdCompany)) {
                    //Mapping user and company entry in mysql DB 
                    $data = $this->enterpriseRepository->companyUserMapping($createdUser->id, $createdCompany->id, $randomCode);
                }
                if (!empty($neoEnterpriseCompany) && !empty($neoEnterpriseUser)) {
                    //Creating relation between user and company in neo4j
                    $data = $this->neoEnterpriseRepository->mapUserCompany($neoEnterpriseUser->emailid, $neoEnterpriseCompany->companyCode);
                }

                //send email to user with activation Code
                $activationCode = $this->userGateway->base_64_encode($createdUser->created_at, $createdUser->emailactivationcode);

                // set email required params
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.enterprise_welcome');
                $this->userEmailManager->emailId = $input['emailid'];
                $dataSet = array();
                $dataSet['name'] = $input['fullname'];
                $dataSet['desktop_link'] = Config::get('constants.MM_ENTERPRISE_URL') . "/email-verify?token=" . $activationCode;
                $dataSet['email'] = $input['emailid'];
                $dataSet['company'] = $input['company'];
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.enterprise_user_email_subjects.welcome');
                $this->userEmailManager->name = $input['fullname'];
                $email_sent = $this->userEmailManager->sendMail();

                //log email status
                $emailStatus = 0;
                if (!empty($email_sent)) {
                    $emailStatus = 1;
                }
                $emailLog = array(
                    'emails_types_id' => 1,
                    'from_user' => 0,
                    'from_email' => '',
                    'to_email' => $this->appEncodeDecode->filterString(strtolower($input['emailid'])),
                    'related_code' => $activationCode,
                    'sent' => $emailStatus,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                );
                $this->userRepository->logEmail($emailLog);

                $responseMessage = Lang::get('MINTMESH.user.create_success');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $responseData = array();
            } else {
                $responseMessage = Lang::get('MINTMESH.user.create_failure');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $responseData = array();
            }

            $message = array('msg' => array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData);
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.sms.user_exist')));
            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
        }
    }

    /*
     * Email Verification a user
     */

    public function emailVerification($input) {
        $responseCode = $responseMsg = $message = $data = "";
        $responseData = array();
        $response = $this->userGateway->activateUser($input['token']);

        if (!empty($response['data']['emailid'])) {

            $returnResponse = $this->userRepository->getUserByEmail($response['data']['emailid']);
            $responseData = $this->enterpriseRepository->getUserCompanyMap($returnResponse['id']);

            $input['username'] = $returnResponse['emailid'];
            $input['emailid'] = $returnResponse['emailid'];
            $input['client_id'] = $input['client_id'];
            $input['client_secret'] = $input['client_secret'];
            $input['grant_type'] = 'special_grant';

            $response = $this->loginCall($input);

            $userDetails = (array) json_decode($response, TRUE);
            $userDetails['data']['company'] = $responseData;

            if ($userDetails['status'] == 'success') {
                $responseMessage = Lang::get('MINTMESH.user.create_success');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                $responseData = $userDetails['data'];
            } else {
                $responseMessage = Lang::get('MINTMESH.user.create_success_login_fail');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                $responseData = array();
            }
        } else {
            // returning failure message 
            $responseMessage = array(Lang::get('MINTMESH.user.failure'));
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $responseData = array();
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $responseData);
    }

    /**
     * Store a newly created Company Profile resource in storage.
     *
     * @return Response
     */
    public function updateCompanyProfile($input) {
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $responseCode = $responseMsg = $responseMessage = $responseData = "";
        if (isset($input['images']) && !empty($input['images'])) {

            $images = $input['images'];
            foreach ($images as $val) {
                $val = $this->createFileObject($val);
                $originalFileName = $val->getClientOriginalName();
                //upload the file
                $this->userFileUploader->source = $val;
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET_COMPANY_IMAGES');
                $renamedFileName = $this->userFileUploader->uploadToS3();
                $val = $renamedFileName;
                $responseData['images'][] = $val;
            }
        }
        if (isset($input['company_logo']) && !empty($input['company_logo'])) {
            $fileName = $this->createFileObject($input['company_logo']);
            $originalFileName = $fileName->getClientOriginalName();
            //upload the file
            $this->userFileUploader->source = $fileName;
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_COMPANY_LOGO');
            $renamedFileName = $this->userFileUploader->uploadToS3();
            $input['company_logo'] = $renamedFileName;
            $responseData['company_logo'] = $renamedFileName;
        }
        if (isset($input['logo_image']) && !empty($input['logo_image'])) {
            //upload the file
            $this->userFileUploader->source =  $input['logo_image'];
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_COMPANY_LOGO');
            $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['logo_image']);
            $input['company_logo'] = $renamedFileName;
            $responseData['company_logo'] = $renamedFileName;
        }
         if (isset($input['logo_image_s3']) && !empty($input['logo_image_s3'])) {
            //upload the file
            $responseData['company_logo'] = $input['logo_image_s3'];
        }
        if (isset($input['photos_s3']) && !empty($input['photos_s3'])) {
            $images = $input['photos_s3'];
            foreach ($images as $val) {
                //upload the file
                $responseData['images'][] = $val;}
        }
        if (isset($input['photos']) && !empty($input['photos'])) {
            $images = $input['photos'];
            foreach ($images as $val) {
                //upload the file
                $this->userFileUploader->source = $val;
                $this->userFileUploader->destination = Config::get('constants.S3BUCKET_COMPANY_IMAGES');
                $renamedFileName = $this->userFileUploader->uploadToS3BySource($val);
                $val = $renamedFileName;
                $responseData['images'][] = $val;}
        }
        if (isset($input['referral_bonus_file']) && !empty($input['referral_bonus_file'])) {
            //upload the file
            $this->userFileUploader->source =  $input['referral_bonus_file'];
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_FILE');
            $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['referral_bonus_file']);
            $responseData['referral_bonus_file'] = $renamedFileName;
            $responseData['referral_org_name'] = $input['referral_org_name'];
        }
        
         if (isset($input['referral_bonus_file_s3']) && !empty($input['referral_bonus_file_s3'])) {
            $responseData['referral_bonus_file'] = $input['referral_bonus_file_s3'];
            $responseData['referral_org_name'] = $input['referral_org_name_s3'];
        }
       $input['user_id'] = $this->loggedinUserDetails->id;
        $createdCompany = $this->enterpriseRepository->updateCompanyProfile($input);
        //updating neo4j for company node

        $updateNeoCompany = $this->updateCompanyNode($input, $responseData);

        $responseData['company_code'] = $input['code'];
        if ($createdCompany) {
            // returning success message
            $message = array('msg' => array(Lang::get('MINTMESH.user.edit_success')));
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMessage = self::SUCCESS_RESPONSE_MESSAGE;
            $responseData = $responseData;
        } else {
            //  returning failure message
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage = array('msg' => array(Lang::get('MINTMESH.user.edit_failure')));
            $responseData = array();
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $responseData);
    }

    public function loginCall($input = array()) {
        $response = array();
        if (!empty($input)) {
            $url = url('/') . "/v1/enterprise/special_login";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        return $response;
    }

    /*
     * actually verifying the user input
     * 
     * @return Response
     */

    public function verifyEnterpriseLogin($inputUserData = array()) {
        $oauthResult = "";
        $userDetails = array();
        // actually authenticating user with oauth
        try {
            $oauthResult = $this->authorizer->issueAccessToken();
        } catch (\Exception $e) {
            $oauthResult['error_description'] = $e->getMessage();
        }
        //check if access code is returned by oauth
        if (isset($oauthResult['access_token'])) {
            $loggedinUserDetails = $this->enterpriseRepository->getEnterpriseUserByEmail($inputUserData['username']);
            if (!empty($loggedinUserDetails)) {

                $responseData = $this->enterpriseRepository->getUserCompanyMap($loggedinUserDetails['id']);
                $userDetails['emailid'] = $loggedinUserDetails['emailid'];
                $userDetails['firstname'] = $loggedinUserDetails['firstname'];
                $userDetails['emailid'] = $loggedinUserDetails['emailid'];

                if ($loggedinUserDetails['emailverified'] == 1) {

                    // returning success message
                    $oauthResult['user'] = $userDetails;
                    $oauthResult['company'] = $responseData;
                    $message = array(Lang::get('MINTMESH.login.login_success'));
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                    $data = $oauthResult;
                } else {
                    //  returning failure message
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                    $message = array(Lang::get('MINTMESH.login.email_inactive'));
                    $data = array();
                }
            } else {
                // returning failure message                      
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array(Lang::get('MINTMESH.login.email_inactive'));
                $data = $oauthResult;
            }
        } else {
            // returning failure message                      
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array($oauthResult['error_description']);
            $data = array();
        }
        $message = array('msg' => $message);
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }

    // generate alphanumeric random code
    public function getRandomCode() {
        $an = "0123456789";
        $su = strlen($an) - 1;
        $code = substr($an, rand(0, $su), 1) .
                substr($an, rand(0, $su), 1) .
                substr($an, rand(0, $su), 1) .
                substr($an, rand(0, $su), 1) .
                substr($an, rand(0, $su), 1) .
                substr($an, rand(0, $su), 1);
        return $code;
    }

    private function createNeoUser($input) {
        $neoEnterpriseUser = "";
        //check for existing node in neo4j
        $neoEnterprise = $this->neoEnterpriseRepository->getNodeByEmailId($input['emailid']);

        $neoUserInput['fullname']       = $input['fullname'];
        $neoUserInput['emailid']        = $input['emailid'];
        $neoUserInput['is_enterprise']  = $input['is_enterprise'];
        if (empty($neoEnterprise)) {
            $neoEnterpriseUser = $this->neoEnterpriseRepository->createEnterpriseUser($neoUserInput);
        } else {
                //change user label
                $neoEnterpriseUser =  $this->neoUserRepository->changeUserLabel($input['emailid']) ;
                if (!empty($neoEnterpriseUser)){
                    $neoEnterpriseUser =  $this->neoUserRepository->updateUser($neoUserInput) ;
                }
        }
        return $neoEnterpriseUser;
    }

    private function createNeoCompany($input, $createdCompany) {
        $neoEnterpriseCompany = "";
        $neoInputCompany = array();
        $neoInputCompany['name'] = $input['company'];
        $neoInputCompany['mysql_id'] = $createdCompany['id'];
        $neoInputCompany['companyCode'] = $input['company_code'];
        // creating company label
        if (empty($neoEnterpriseCompany)) {
            $neoEnterpriseCompany = $this->neoEnterpriseRepository->createCompany($neoInputCompany);
        }
        return $neoEnterpriseCompany;
    }

    private function updateCompanyNode($input, $responseData) {
        $updateNeoCompany = "";
        $images = !empty($responseData['images']) ? implode(',', $responseData['images']) : "";
        $company_logo = !empty($responseData['company_logo']) ? $responseData['company_logo'] : "";
        $file = !empty($responseData['referral_bonus_file']) ? $responseData['referral_bonus_file'] : "";
        $file_org_name = !empty($responseData['referral_org_name']) ? $responseData['referral_org_name'] : "";
        $updateNeoCompany = $this->neoEnterpriseRepository->updateCompanyLabel($input['code'], $input['company'], $input['website'], $input['number_of_employees'], $company_logo, $images, $input['description'],$input['industry'],$file,$file_org_name);
         #map industry if provided
         if (!empty($input['industry'])) {
            $iResult = $this->neoEnterpriseRepository->mapIndustryToCompany($input['industry'], $input['code'], Config::get('constants.REFERRALS.ASSIGNED_INDUSTRY'));
            }
        return $updateNeoCompany;
    }

    /**
     * Store a enterprise Contacts Upload resource in storage.
     *
     * @return Response
     */
    public function enterpriseContactsUpload($input) {
        //get the logged in user details
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $arrResults = array();
        $createdAt = gmdate("Y-m-d H:i:s");
        //print_r($input).exit;
        $inputFile = $this->createFileObject($input['contacts_file']);
        //$inputFile = $input['contacts_file'];
        $inputFileExtension = $inputFile->getClientOriginalExtension();
        $inputFileSize = $inputFile->getClientSize();
        $userId = $this->loggedinUserDetails->id;
        $companyId = $input['company_id'];
        $companyCode = $input['company_code'];
        $bucketId = $input['bucket_id'];
        $fileMaxSize = Config::get('constants.EXCEL_MAX_SIZE'); //file size max 1MB
        $allowedExcelExtensions = array('csv', 'xlsx', 'xls');
        $allowedHeaders = array('employee_idother_id', 'first_name', 'last_name', 'email_id', 'cell_phone', 'status');

        //cheking file size and file format              
        if (in_array($inputFileExtension, $allowedExcelExtensions) && $inputFileSize <= $fileMaxSize) {

            $arrResults = Excel::load($inputFile)->all(); //reading input excel file here
            //print_r($arrResults).exit;
            $firstRow = $arrResults->first()->toArray();
            $validHeaders = true;
            //comparing headers here
            foreach ($allowedHeaders as $value) {
                if (!array_key_exists($value, $firstRow)) {
                    $validHeaders = false;
                }
            }
            //cheking file header validations
            if ($validHeaders) {

                $arrResults = $arrResults->toArray();
                //creating new bucket 
                if (!empty($input['is_bucket_new'])) {
                    $bucketId = $this->enterpriseRepository->createNewBucket($userId, $companyId, $input['bucket_name'],$createdAt);
                    $neoNewBucket = $this->creatNeoNewBucket($input, $bucketId);
                    $input['bucket_id'] = $bucketId;
                }

                foreach ($arrResults as $key => $val) {
                    if ($val['email_id']) {
                        $arrUniqueResults[$val['email_id']] = $val;
                    }
                }
                //$instanceId = $this->enterpriseRepository->getInstanceId(); //getting Instance Id
                //create file record
		$importFileId = $this->enterpriseRepository->getFileId($inputFile,$userId);
                //importing contacts to Mysql db
                $resultsSet = $this->enterpriseRepository->uploadContacts($arrUniqueResults, $userId, $bucketId, $companyId, $importFileId);


                if (!empty($resultsSet)) {
                    //get the Import Contacts List By Instance Id
                    //$contactsList = $this->enterpriseRepository->getImportContactsListByInstanceId($userId, $bucketId, $companyId, $instanceId);
                    
                    //get the Import Contacts List By Import File Id
                    $contactsList = $this->enterpriseRepository->getContactsListByFileId($companyId, $importFileId);
                   
                    //Creating relation between company and bucket in neo4j
                    $neoCompanyBucketContacts = array();
                    $neoCompanyBucketContacts = $this->enterpriseContactsList($input);
                    //Creating relation between company and bucket in neo4j
                    $records_count = $neoCompanyBucketContacts['data']['total_records'][0];
                    $neoCompanyBucketRelation = $this->createCompanyBucketRelation($input, $records_count->total_count);

                    //Creating relation between bucket and contacts in neo4j
                    foreach ($contactsList as $key => $value) {
                        $pushData = array();
                        $pushData['firstname'] = $value->firstname;
                        $pushData['lastname'] = $value->lastname;
                        $pushData['emailid'] = $value->emailid;
                        $pushData['contact_number'] = $value->phone;
                        $pushData['other_id'] = $value->employeeid;
                        $pushData['status'] = $value->status;
                        $pushData['bucket_id'] = $bucketId;
                        $pushData['company_code'] = $companyCode;
                        $pushData['loggedin_emailid'] = $this->loggedinUserDetails->emailid;
                       
                        //$this->createContactNodes($pushData);
                        //$this->checkToCreateEnterpriseContactsQueue($pushData['firstname'],$pushData['lastname'],$pushData['emailid'],$pushData['contact_number'],$pushData['other_id'],$pushData['status'],$pushData['bucket_id'],$pushData['company_code'],$pushData['loggedin_emailid']);

                        Queue::push('Mintmesh\Services\Queues\CreateEnterpriseContactsQueue', $pushData, 'IMPORT');
                    }
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = array(Lang::get('MINTMESH.enterprise.import_contacts_success'));
                } else {
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                    $message = array(Lang::get('MINTMESH.enterprise.import_contacts_failure'));
                }
            } else {
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $message = array(Lang::get('MINTMESH.enterprise.invalid_headers'));
            }
        } else {
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.invalid_file_format'));
        }

        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }

    /**
     * View a enterprise Buckets List.
     *
     * @return Response
     */
    public function enterpriseBucketsList($input) {
        
        $bucketsListArr = $resultsSetArr = array();    
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        $params['user_id'] = $this->loggedinUserDetails->id;
        $params['company_id'] = $input['company_id'];
        $resultsSetArr = $this->enterpriseRepository->getCompanyBucketsList($params); //get the import contact list

        if ($resultsSetArr) { 
            foreach ($resultsSetArr as $result){
                $record = array();
                $record['bucket_id']   = $result->bucket_id;
                $record['bucket_name'] = $result->bucket_name;
                $record['count']       = $result->count;
                array_push($bucketsListArr,$record);
            }  
            $totalCountObj = $this->enterpriseRepository->contactsCount($params);

            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_success'));
            $data['buckets_list'] = $bucketsListArr;
            $data['total_count']  = $totalCountObj->total_count;
        } else {
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_failure'));
            $data = array();
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);
    }

    /**
     * View a enterprise Contacts List.
     *
     * @return Response
     */
    public function enterpriseContactsList($input) {
        $params = array();
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        $params['user_id'] = $this->loggedinUserDetails->id;
        $params['company_id'] = $input['company_id'];
        $params['bucket_id'] = !empty($input['bucket_id']) ? $input['bucket_id'] : 0;
        $params['page_no'] = !empty($input['page_no']) ? $input['page_no'] : 0;
        $params['search'] = !empty($input['search']) ? $input['search'] : 0;
        $params['sort'] = !empty($input['sort']) ? $input['sort'] : '';
        $resultsSet = $this->enterpriseRepository->getImportContactsList($params); //get the import contact list
        if ($resultsSet) {
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_success'));
            $data = $resultsSet;
        } else {
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_failure'));
            $data = array();
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);
    }

    private function creatNeoNewBucket($input, $bucketId) {

        $neoNewBucket = "";
        $input['default'] = 'dynamic';
        $neoNewBucket = $this->neoEnterpriseRepository->createNeoNewBucket($input, $bucketId);
        return $neoNewBucket;
    }

    private function createCompanyBucketRelation($input, $records_count) {
        $this->neoLoggedInEnterpriseUserDetails = $this->neoEnterpriseRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
        $neoCompanyBucketRelation = "";
        $relationAttrs = array();
        $relationAttrs['user_emailid'] = $this->neoLoggedInEnterpriseUserDetails->emailid;
        $relationAttrs['no_of_contacts'] = $records_count;
        $relationAttrs['created_at'] = gmdate("Y-m-d H:i:s");
        $neoCompanyBucketRelation = $this->neoEnterpriseRepository->createCompanyBucketRelation($input['company_id'], $input['bucket_id'], $relationAttrs);
        return $neoCompanyBucketRelation;
    }

    public function checkToCreateEnterpriseContactsQueue($firstname = '', $lastname = '', $emailid = '', $contact_number = '', $other_id = '', $status = '', $bucket_id = '', $company_code = '', $loggedin_emailid = '') {
        if (!empty($firstname) ||
                !empty($lastname) ||
                !empty($emailid) ||
                !empty($contact_number) ||
                !empty($bucket_id)) {
            $contactNode = array();
            $contactNode['firstname'] = $firstname;
            $contactNode['lastname'] = $lastname;
            $contactNode['emailid'] = $emailid;
            $contactNode['contact_number'] = $contact_number;
            $contactNode['other_id'] = $other_id;
            $contactNode['status'] = $status;
            $contactNode['bucket_id'] = $bucket_id;
            $contactNode['company_code'] = $company_code;
            $contactNode['loggedin_emailid'] = $loggedin_emailid;

            $createResult = $this->createContactNodes($contactNode);
        }
    }

    public function createContactNodes($contactNode = array()) {
        
        $neoInput = $relationAttrs  = array();
        $neoInput['firstname']      = $contactNode['firstname'];
        $neoInput['lastname']       = $contactNode['lastname'];
        $neoInput['emailid']        = $contactNode['emailid'];
        $neoInput['contact_number'] = $contactNode['contact_number'];
        $neoInput['employeeid']     = $contactNode['other_id'];
        $neoInput['status']         = $contactNode['status'];
        
        $relationAttrs['company_code']      = $contactNode['company_code'];
        $relationAttrs['loggedin_emailid']  = $contactNode['loggedin_emailid'];
        $relationAttrs['created_at']        = gmdate("Y-m-d H:i:s");
         //\Log::info("<<<<<<<<<<<<<<<< In Queue >>>>>>>>>>>>>".print_r($neoInput,1));
        try {
            $this->neoEnterpriseRepository->createContactNodes($contactNode['bucket_id'], $neoInput, $relationAttrs);
            $this->neoEnterpriseRepository->companyAutoConnect($neoInput['emailid'], $relationAttrs);
        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }

    /**
     * View a enterprise Contacts Email Invitations.
     *
     * @return Response
     */
    public function enterpriseContactsEmailInvitation($input) {
        $params = array();
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        $params['user_id'] = $this->loggedinUserDetails->id;
        $params['from_user_name'] = $this->loggedinUserDetails->firstname;
        $params['user_email'] = $this->loggedinUserDetails->emailid;
        $params['company_id'] = $input['company_id'];
        $emailSubject = $input['email_subject'];
        $emailBody = $input['email_body'];
        $params['invite_contacts'] = explode(',', $input['invite_contacts']);
        $params['ip_address'] = $_SERVER['REMOTE_ADDR'];

        $contactList = $this->enterpriseRepository->getCompanyContactsListById($params); //get the import contact list by Ids
        $company = $this->enterpriseRepository->getCompanyDetails($params['company_id']);
        if (!empty($contactList)) {
            foreach ($contactList as $key => $value) {
                $pushData = array();
                if(!empty($company)){
                    foreach ($company as $k=>$v){
                        $pushData['company_name'] = $v->name;
                        $pushData['company_logo'] = $v->logo;
                    }
                }
                $pushData['firstname'] = $value[0]->firstname;
                $pushData['lastname'] = $value[0]->lastname;
                $pushData['emailid'] = $value[0]->emailid;
                $pushData['email_subject'] = 'Invitation to Referral Rewards Program from '.$pushData['company_name'];
                $pushData['email_body'] = $emailBody;
                //for email logs
                $pushData['from_user_id']    = $params['user_id'];
                $pushData['from_user_name']    = $params['from_user_name'];
                $pushData['from_user_email'] = $params['user_email'];
                $pushData['company_code']    = $params['company_id'];
                $pushData['ip_address']      = $params['ip_address'];
                Queue::push('Mintmesh\Services\Queues\EmailInvitationEnterpriseContactsQueue', $pushData, 'IMPORT');
            }

            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.success'));
        } else {
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.error'));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }

    public function enterpriseSendContactsEmailInvitation($inputEmailData) {
        $dataSet = array();
        $fullName = $inputEmailData['firstname'] . ' ' . $inputEmailData['lastname'];
        $dataSet['company_name'] = $inputEmailData['company_name'];
        $dataSet['company_logo'] = !empty($inputEmailData['company_logo'])?$inputEmailData['company_logo']:'https://www.owbaz.com/images/default-company-logo.jpg';
        $dataSet['name'] = $fullName;
        $dataSet['email'] = $inputEmailData['emailid'];
        $dataSet['emailbody'] = $inputEmailData['email_body'];
        $dataSet['fromName']  = $inputEmailData['from_user_name'];
        $dataSet['send_company_name'] = $inputEmailData['company_name'];
        //for email logs
        $fromUserId  = $inputEmailData['from_user_id'];
        $fromEmailId = $inputEmailData['from_user_email'];
        $companyCode = $inputEmailData['company_code'];
        $ipAddress   = $inputEmailData['ip_address'];
        // set email required params
        $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.enterprise_contacts_invitation');
        $this->userEmailManager->emailId = $inputEmailData['emailid'];
        $this->userEmailManager->dataSet = $dataSet;
        $this->userEmailManager->subject = $inputEmailData['email_subject'];
        $this->userEmailManager->name = $fullName;
        $email_sent = $this->userEmailManager->sendMail();
        //log email status
        $emailStatus = 0;
        if (!empty($email_sent)) {
            $emailStatus = 1;
        }
        $emailLog = array(
            'emails_types_id'   => 5,
            'from_user'         => $fromUserId,
            'from_email'        => $fromEmailId,
            'to_email'          => $this->appEncodeDecode->filterString(strtolower($inputEmailData['emailid'])),
            'related_code'      => $companyCode,
            'sent'              => $emailStatus,
            'ip_address'        => $ipAddress
        );
        $this->userRepository->logEmail($emailLog);
    }

    /*
     * send forgot password email to users
     */

    public function sendForgotPasswordEmail($input) {
        if (!empty($input)) {
            //get user details
            $userDetails = $this->userRepository->getUserByEmail($input['emailid']);
            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($input['emailid']);
            if (!empty($userDetails) && $userDetails['emailverified'] == 1) {
                // set email required params
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.forgot_password');
                $this->userEmailManager->emailId = $input['emailid'];
                $dataSet = array();
                $dataSet['name'] = $neoUserDetails['fullname'];
                //set reset code
                //set timezone of mysql if different servers are being used
                //date_default_timezone_set('America/Los_Angeles');
                $currentTime = date('Y-m-d h:i:s');
                $email = md5($input['emailid']);
                $code = $this->userGateway->base_64_encode($currentTime, $email);
                $dataSet['hrs'] = Config::get('constants.MNT_USER_EXPIRY_HR');
                $dataSet['link'] = Config::get('constants.MM_ENTERPRISE_URL') . "/reset_password?resetcode=" . $code; //comment it for normal flow of deep linki.e without http
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.forgot_password');
                $this->userEmailManager->name = $neoUserDetails['fullname'];
                $email_sent = $this->userEmailManager->sendMail();
                //log email status
                $emailStatus = 0;
                if (!empty($email_sent)) {
                    $emailStatus = 1;
                }
                $emailLog = array(
                    'emails_types_id' => 2,
                    'from_user' => 0,
                    'from_email' => '',
                    'to_email' => !empty($userDetails) ? $userDetails->emailid : '',
                    'related_code' => $code,
                    'sent' => $emailStatus,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                );
                $this->userRepository->logEmail($emailLog);
                //update code in users table
                $inputdata = array('user_id' => $userDetails->id,
                    'resetactivationcode' => $code);
                $this->userRepository->updateUserresetpwdcode($inputdata);
                if (!empty($email_sent)) {

                    $message = array('msg' => array(Lang::get('MINTMESH.forgot_password.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array());
                } else {
                    $message = array('msg' => array(Lang::get('MINTMESH.forgot_password.error')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
                }
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.forgot_password.activate_user')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            }
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.forgot_password.error')));
            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
        }
    }

    /*
     * reset enteprise user password
     */

    public function resetPassword($input) {
        $decodedString = $this->userGateway->base_64_decode($input['code']);
        $sentTime = $decodedString['string1'];
        $email = $decodedString['string2'];
        //to get resetactivationcode 
        $passwordData = $this->userRepository->getresetcodeNpassword($email);
        if (!empty($email) && !empty($passwordData) && $passwordData->resetactivationcode == $input['code']) {
            //set timezone of mysql if different servers are being used
            //date_default_timezone_set('America/Los_Angeles');
            $expiryTime = date('Y-m-d H:i:s', strtotime($sentTime . " +" . Config::get('constants.MNT_USER_EXPIRY_HR') . " hours"));
            //check if expiry time is valid
            if (strtotime($expiryTime) > strtotime(gmdate('Y-m-d H:i:s'))) {
                $post = array();
                $post['email'] = $email;
                $post['password'] = $input['password'];
                // update status of the user to active
                $updateCount = $this->userRepository->resetPassword($post);
                if (!empty($updateCount)) {
                    //get user details
                    $userDetails = $this->userRepository->getUserByEmail($passwordData->emailid);
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($passwordData->emailid);
                    $currentTime = gmdate('Y-m-d H:i:s');
                    $code = $this->userGateway->base_64_encode($currentTime, $email);
                    //send mail
                    $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.enterprise_reset_password_success');
                    $this->userEmailManager->emailId = $passwordData->emailid;
                    $dataSet = array();
                    $dataSet['name'] = $neoUserDetails['fullname'];
                    $this->userEmailManager->dataSet = $dataSet;
                    $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.reset_password_success');
                    $this->userEmailManager->name = $neoUserDetails['fullname'];
                    $email_sent = $this->userEmailManager->sendMail();
                    //log email status
                    $emailStatus = 0;
                    if (!empty($email_sent)) {
                        $emailStatus = 1;
                    }
                    $emailLog = array(
                        'emails_types_id' => 2,
                        'from_user' => 0,
                        'from_email' => '',
                        'to_email' => !empty($userDetails) ? $userDetails['emailid'] : '',
                        'related_code' => $code,
                        'sent' => $emailStatus,
                        'ip_address' => $_SERVER['REMOTE_ADDR']
                    );
                    $this->userRepository->logEmail($emailLog);

                    $message = array('msg' => array(Lang::get('MINTMESH.reset_password.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array());
                } else {
                    $message = array('msg' => array(Lang::get('MINTMESH.reset_password.failed')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
                }
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.reset_password.invalid')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            }
        } else {
            if (empty($passwordData->resetactivationcode)) {
                $message = array('msg' => array(Lang::get('MINTMESH.reset_password.codeexpired')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.reset_password.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            }
        }
    }

    public function viewCompanyDetails($input){
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId = $this->loggedinUserDetails->emailid;
        $companyCode = $input['company_code'];
        
        $returnDetails  = $return = $data = array();
        // get the logged in user company details here
        $companyDetails = $this->neoEnterpriseRepository->viewCompanyDetails($userEmailId, $companyCode);
        if(!empty($companyDetails[0])){
            
            $company = $companyDetails[0][0];
            $user    = $companyDetails[0][1];
            $returnDetails['name']         = !empty($company->name)?$company->name:'';
            $returnDetails['images']       = !empty($company->images)?array_filter(explode(',',$company->images)):'';
            $returnDetails['industry']     = !empty($company->industry)?$company->industry:'';  
            $returnDetails['website']      = !empty($company->website)?$company->website:'';
            $returnDetails['username']     = !empty($user->fullname)?$user->fullname:'';
            $returnDetails['description']  = !empty($company->description)?$company->description:'';
            $returnDetails['company_logo'] = !empty($company->logo)?$company->logo:'';
            $returnDetails['number_of_employees'] = !empty($company->size)?$company->size:'';
            $returnDetails['referral_bonus_file'] = !empty($company->referral_bonus_file)?$company->referral_bonus_file:'';
            $returnDetails['referral_org_name']   = !empty($company->referral_bonus_org_name)?$company->referral_bonus_org_name:'';

            $data['companyDetails'] = $returnDetails;
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.no_details')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function connectedCompaniesList(){
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId = $this->loggedinUserDetails->emailid;
         
        $companyDetailsResult = $returnDetails  = $return = $data = array();
        // get the logged in user company details here
        $companyDetailsResult = $this->neoEnterpriseRepository->connectedCompaniesList($userEmailId);
        if(!empty($companyDetailsResult)){
            
            foreach ($companyDetailsResult as $companyDetails) {
                $industry = '';
                $company  = $companyDetails[0];
                
                if(!empty($company->industry)){
                    $industry = $this->userRepository->getIndustryName($company->industry);
                }  
                $returnDetails['company_name']  = $company->name;
                $returnDetails['company_logo']  = !empty($company->logo)?$company->logo:'';
                $returnDetails['company_code']  = $company->companyCode;
                $returnDetails['industry_name'] = $industry;  
                $return[] = $returnDetails;
            }
            
            $data = array("companyList" => array_values($return));
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.no_details')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function connectToCompany($input){
        
        $result = FALSE;
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId = $this->loggedinUserDetails->emailid;
        $companyCode = $input['company_code'];
        
        $isCompanyExist = $this->neoEnterpriseRepository->isCompanyExist($companyCode);
         
        if(!empty($isCompanyExist)){
            
            $connected = $this->neoEnterpriseRepository->checkCompanyUserConnected($userEmailId, $companyCode);
            
            if ($connected == 0) {
                $relationType = 'CONNECTED_TO_COMPANY';
                $result       = $this->neoEnterpriseRepository->mapUserCompany($userEmailId, $companyCode, $relationType);
            } 
            if ($result){
                $responseCode     = self::SUCCESS_RESPONSE_CODE;
                $responseMsg      = self::SUCCESS_RESPONSE_MESSAGE;
                $message          = array('msg' => array(Lang::get('MINTMESH.enterprise.success')));
            } else { 
               $responseCode   = self::SUCCESS_RESPONSE_CODE;
               $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
               $message        = array('msg' => array(Lang::get('MINTMESH.companyDetails.company_already_connected')));   
            }
        } else {
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $message        = array('msg' => array(Lang::get('MINTMESH.companyDetails.company_not_exists')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }
    
    public function viewDashboard($input){
        $return = $data = array();
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId = $this->loggedinUserDetails->emailid;
        $userId      = $this->loggedinUserDetails->id;
        $companyCode = $input['company_code'];
        $filterLimit = !empty($input['filter_limit'])?$input['filter_limit']:'';
        $requestType = !empty($input['request_type'])?$input['request_type']:'';
        
        if($filterLimit == 360){
            $year = date('Y');
            $filterLimit = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $year)); //current year first month    
        } elseif ($filterLimit == 30) {
            $filterLimit = date('Y-m-d H:i:s', strtotime('-1 month'));
        } else {
            $filterLimit = date('Y-m-d H:i:s', strtotime('-1 week'));
        }
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0])?$companyDetails[0]->id:0;
            
        switch ($requestType) {
                case 'PROGRESS':
                    $data = $this->getCompanyUserPostProgress($userEmailId, $userId, $companyCode, $companyId, $filterLimit);
                    break;
                case 'REFERRALS':
                    $data = $this->getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit);
                    break;
                case 'HIRED':
                    $data = $this->getCompanyUserPostHires($userEmailId, $companyCode, $filterLimit);
                    break;
                default:
                    $postCounts     = $this->getCompanyUserPostCounts($userEmailId, $companyCode);
                    $postProgress   = $this->getCompanyUserPostProgress($userEmailId, $userId, $companyCode, $companyId, $filterLimit);
                    $postReferrals  = $this->getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit);
                    $postHires      = $this->getCompanyUserPostHires($userEmailId, $companyCode);
                    $topReferrals   = $this->getCompanyUserTopReferrals($userEmailId, $companyCode);
                    $aryOne   = array_merge($postCounts, $postProgress);
                    $aryTwo   = array_merge($postReferrals, $postHires);
                    $aryThree = array_merge($aryOne, $aryTwo);
                    $data     = array_merge($aryThree, $topReferrals);
                    break;
                }
          
        if(!empty($data)){
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.no_details')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function getCompanyUserPostCounts($userEmailId, $companyCode){
        
        $return = $aryStatus = $aryResult = array();
        // get the logged in user all posts counts here
        $resultPosts = $this->neoEnterpriseRepository->getCompanyUserPosts($userEmailId, $companyCode);
        
        if(!empty($resultPosts)){
            $postCount  = !empty($resultPosts['count'])?$resultPosts['count']:0;
            $aryResult  = !empty($resultPosts['result'])?$resultPosts['result']:$aryResult;
            
            $totReferralCount = $totAcceptedCount = $totHiredCount = $totinterviewedCount = 0;
            foreach ($aryResult as $posts) {
                $objPosts = $posts[1];
                
                $hiredCount    = !empty($objPosts->referral_hired_count)?$objPosts->referral_hired_count:0;
                $acceptedCount = !empty($objPosts->referral_accepted_count)?$objPosts->referral_accepted_count:0;
                $referralCount = !empty($objPosts->total_referral_count)?$objPosts->total_referral_count:0;
                $interviewedCount = !empty($objPosts->referral_interviewed_count)?$objPosts->referral_interviewed_count:0;
                
                $totHiredCount       += $hiredCount;
                $totAcceptedCount    += $acceptedCount;
                $totReferralCount    += $referralCount;
                $totinterviewedCount += $interviewedCount;
                
            }
            
            $aryStatus['referral_count']    = max($totReferralCount,0);
            $aryStatus['accepted_count']    = max($totAcceptedCount,0);
            $aryStatus['interviewed_count'] = max($totinterviewedCount,0);
            $aryStatus['hired_count']       = max($totHiredCount,0);
             
            $return = array('post_counts' => $postCount, 'status_count' =>$aryStatus);
        } 
        return $return;
    }
    
    public function getCompanyUserPostProgress($userEmailId, $userId, $companyCode, $companyId, $filterLimit){
        $return = $response = array();
        $rewardsCount = $contactsCount = $jobsReachCount = 0;
        $filterLimit    = empty($filterLimit)?date('Y-m-d H:i:s', strtotime('-1 month')):$filterLimit;//default 30 days
        //CONTACTS ENGAGEMENT
        $companyInvitedCount = $this->enterpriseRepository->companyInvitedCount($userId, $companyId, $filterLimit);
        $downloadedCount     = $this->enterpriseRepository->companyInvitedCount($userId, $companyId, $filterLimit, TRUE);
        
        $companyInvitedCount = !empty($companyInvitedCount[0]->count)?$companyInvitedCount[0]->count:0;
        $downloadedCount     = !empty($downloadedCount[0]->count)?$downloadedCount[0]->count:0;
        //CONTACTS ENGAGEMENT COUNT
        if(!empty($companyInvitedCount)){
            $contactsCount = round((($downloadedCount/$companyInvitedCount)*100),2);
        }
                
        $resultPosts = $this->neoEnterpriseRepository->getCompanyUserPosts($userEmailId, $companyCode, $filterLimit);
        if (!empty(count($resultPosts))) {
            $postDetails = $aryResult = array();
            $totalAcceptedCount = $totalReferralCount = $allPostsReadCount = $postReadCount =0;
            $postsCount  = !empty($resultPosts['count'])?$resultPosts['count']:0;
            $aryResult   = !empty($resultPosts['result'])?$resultPosts['result']:$aryResult;
            
            foreach ($aryResult as $post) {
                $postDetails = $this->referralsGateway->formPostDetailsArray($post[1]);
                $freeService = isset($postDetails['free_service']) ? $postDetails['free_service'] : '';
                $postId      = isset($postDetails['post_id']) ? $postDetails['post_id'] : '';
                //------ each post read count starts here -----------------------------------
                $postRelation = $relResult = array();
                $postRelation = $this->neoEnterpriseRepository->getPostInvitedCount($postId);
                $relCount   = !empty($postRelation['count'])?$postRelation['count']:0;
                $relResult  = !empty($postRelation['result'])?$postRelation['result']:$relResult;
                
                $totReadStatus = $readStatus = 0;
                foreach ($relResult as $rel) 
                {
                    $readStatus = !empty($rel[0]->post_read_status)?1:0;
                    $totReadStatus += $readStatus;
                }
                if(!empty($relCount)){
                    $postReadCount      = round(($totReadStatus/$relCount),2);
                }
                $allPostsReadCount  += $postReadCount;
                //------ each post read count ends here -------------------------------------------
                
                //------- each post referral and accepted counts count starts here ----------------------
                if($freeService == '0'){
                    $referralCount      = !empty($postDetails['total_referral_count']) ? $postDetails['total_referral_count'] : 0;
                    $acceptedCount      = !empty($postDetails['referral_accepted_count']) ? $postDetails['referral_accepted_count'] : 0;
                    $totalReferralCount += $referralCount;
                    $totalAcceptedCount += $acceptedCount;
                }
                //------- each post referral and accepted counts count ends here ----------------------
            }
             //JOBS REACH TOTAL COUNT        
            if(!empty($postsCount)){
                $jobsReachCount  = round((($allPostsReadCount/$postsCount)*100),2);
            }
            
            //REWARDS CLAIMED TOTAL COUNT
            if(!empty($totalReferralCount)){
                $rewardsCount  = round((($totalAcceptedCount/$totalReferralCount)*100),2);
            }
        }
        
        $response = array('contacts'=>$contactsCount,
                          'jobs'=>$jobsReachCount,
                          'rewards'=>$rewardsCount);
          
        $return = array('post_progress' =>$response);
        return $return;
    }
    
    public function getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit){
        
        $return = $returnDetails = $referralDetails = $returnReferralDetails = array();
        $postDetails = $this->neoEnterpriseRepository->getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit);
        if(!empty($postDetails)){
            
            foreach($postDetails as $post){
                $postDetails     = $this->referralsGateway->formPostDetailsArray($post[0]);
                $referralDetails = $this->neoEnterpriseRepository->getReferralDetails($postDetails['post_id']);
                
                if(!empty($referralDetails)){
                    
                    foreach($referralDetails as $details){
                        $referralName   ='';
                        $nonMMUser      = new \stdClass();
                        $userDetails    = $this->referralsGateway->formPostDetailsArray($details[0]);
                        $postRelDetails = $this->referralsGateway->formPostDetailsArray($details[1]);
                        $postDetails    = $this->referralsGateway->formPostDetailsArray($details[2]);
                       
                        // get the Non Mintmesh name
                        if(empty($userDetails['fullname']) && !empty($postRelDetails['referred_by'])){
                            
                            if(!empty($userDetails['emailid'])){
                                    $nonMMUser = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                              } elseif (!empty($userDetails['phone'])) {
                                    $nonMMUser = $this->contactsRepository->getImportRelationDetailsByPhone($postRelDetails['referred_by'], $userDetails['phone']);
                              }
                              $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                            
                        }  else {
                              $referralName = $userDetails['fullname'];
                        }
                        $neoReferredByDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                        
                        $returnDetails['job_title']      = $postDetails['service_name'];
                        $returnDetails['status']         = $postRelDetails['one_way_status'];
                        $returnDetails['created_at']     = \Carbon\Carbon::createFromTimeStamp(strtotime($postRelDetails['created_at']))->diffForHumans();
                        $returnDetails['referral']       = !empty($referralName)?$referralName:'The contact';
                        $returnDetails['referral_img']   = !empty($userDetails['dp_renamed_name'])?$userDetails['dp_renamed_name']:'';
                        $returnDetails['referred_by']    = $neoReferredByDetails['fullname'];
                        $returnDetails['referred_by_img']= $neoReferredByDetails['dp_renamed_name'];
                        $returnDetails['service_cost']   = !empty($postDetails['service_cost'])?$postDetails['service_cost']:0;
                        
                        $returnReferralDetails[]    = $returnDetails;
                    }
                }
            }
        }
        return $return = array('post_referrals' =>$returnReferralDetails);
    }
    
    public function getCompanyUserPostHires($userEmailId, $companyCode, $filterLimit=''){
          
        $returnDetails  = $return = $referralDetails = $returnHiresDetails = array();
        $filterLimit    = empty($filterLimit)?date('Y-m-d H:i:s', strtotime('-1 month')):$filterLimit;//default 30 days
        $postDetails    = $this->neoEnterpriseRepository->getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit);
        if(!empty($postDetails)){
            
            foreach($postDetails as $post){
                $postDetails     = $this->referralsGateway->formPostDetailsArray($post[0]);
                $referralDetails = $this->neoEnterpriseRepository->getReferralDetails($postDetails['post_id']);
                if(!empty($referralDetails)){
                    
                    foreach($referralDetails as $details){
                        
                        $referralName   ='';
                        $nonMMUser      = new \stdClass();
                        $userDetails    = $this->referralsGateway->formPostDetailsArray($details[0]);
                        $postRelDetails = $this->referralsGateway->formPostDetailsArray($details[1]);
                        $postDetails    = $this->referralsGateway->formPostDetailsArray($details[2]);
                        
                      if(!empty($postRelDetails['awaiting_action_status']) && $postRelDetails['awaiting_action_status'] === 'HIRED'){
                          
                          // get the Non Mintmesh name
                            if(empty($userDetails['fullname']) && !empty($postRelDetails['referred_by'])){

                                if(!empty($userDetails['emailid'])){
                                        $nonMMUser = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                                  } elseif (!empty($userDetails['phone'])) {
                                        $nonMMUser = $this->contactsRepository->getImportRelationDetailsByPhone($postRelDetails['referred_by'], $userDetails['phone']);
                                  }
                                  $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";

                            }  else {
                                  $referralName = $userDetails['fullname'];
                            }
                            $neoReferredByDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                        
                            $returnDetails['job_title']      =  $postDetails['service_name'];
                            $returnDetails['status']         =  $postRelDetails['one_way_status'];
                            $returnDetails['created_at']     =  \Carbon\Carbon::createFromTimeStamp(strtotime($postRelDetails['created_at']))->diffForHumans();
                            $returnDetails['referral']       =  !empty($referralName)?$referralName:'The contact';
                            $returnDetails['referral_img']   =  !empty($userDetails['dp_renamed_name'])?$userDetails['dp_renamed_name']:'';
                            $returnDetails['referred_by']    =  $neoReferredByDetails['fullname'];
                            $returnDetails['referred_by_img']=  $neoReferredByDetails['dp_renamed_name'];
                            $returnDetails['service_cost']   =  !empty($postDetails['service_cost'])?$postDetails['service_cost']:0;

                            $returnHiresDetails[]   =   $returnDetails;
                        }  
                    }
                }
            }
        }
        return $return = array('post_hires' =>$returnHiresDetails);
    }
    
    public function getCompanyUserTopReferrals($userEmailId, $companyCode){
           
        $returnTopReferrals = $topReferrals = array();
        $topReferrals = $this->neoEnterpriseRepository->getCompanyUserTopReferrals($userEmailId);//get the top referrals list here
        if(!empty($topReferrals)){
            foreach($topReferrals as $referral){
                $record = array();
                $designation = '';
                $referralsCount  = $referral[1];    
                $referralUser    = $referral[2]; 
                //get user designation here
                if (!empty($referralUser) && $referralUser->completed_experience == '1'){
                    $result = $this->neoEnterpriseRepository->getDesignation($referralUser->emailid);
                    foreach ($result[0] as $obj) {
                        $designation = $obj->name;   
                    }
                } 
                //set the return response here
                $record['name']     = !empty($referralUser->fullname)?$referralUser->fullname:'';
                $record['image']    = !empty($referralUser->dp_renamed_name)?$referralUser->dp_renamed_name:'';
                $record['designation'] = !empty($designation)?$designation:'';
                $record['count']       = $referralsCount;
                $returnTopReferrals[]  = $record;
            }
        }
       return $return = array('top_referrals' =>$returnTopReferrals);
    }
    
    public function getCompanyProfile(){     
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId = $this->loggedinUserDetails->emailid;
         
        $returnDetails  = $return = $data = array();
        // get the logged in user company details here
        $companyDetails = $this->neoEnterpriseRepository->getCompanyProfile($userEmailId);
        if(!empty($companyDetails[0])){

            $company = $companyDetails[0][0];        
            $returnDetails['name']         = $company->name;
            $returnDetails['company_code']  = $company->companyCode;
            $returnDetails['company_logo'] = $company->logo;
            $data['companyDetails'] = $returnDetails;
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.no_details')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function updateContactsList($input) {
        $checkEmployeeId = $this->enterpriseRepository->checkEmployeeId($input);
        if(!$checkEmployeeId)
        {
            $updated = $this->enterpriseRepository->updateContactsList($input);
            if(!empty($updated))
            {
                $neoupdated = $this->neoEnterpriseRepository->updateContactsList($updated[0]->emailid,$input);
            }
            if($updated){
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.success')));
            }else{
              $message = array('msg' => array(Lang::get('MINTMESH.editContactList.failure')));
            }
        }
        else{
            
             $message = array('msg' => array(Lang::get('MINTMESH.editContactList.invalidempid')));
        }   
        return $this->commonFormatter->formatResponse(200, "success", $message);
    }
    
    public function deleteContactAndEditStatus($input) {
        $recordid  = explode(',', $input['record_id']);
        if($input['action'] == 'delete'){
        foreach($recordid as $record){
        $deleted = $this->enterpriseRepository->deleteContact($record);
        }
        if($deleted){
//        $neodeleted = $this->neoEnterpriseRepository->deleteContact($deleted[0]->emailid);
          $message = array('msg' => array(Lang::get('MINTMESH.deleteContact.success')));
        }else{
            $message = array('msg' => array(Lang::get('MINTMESH.deleteContact.failure')));
        }
        }
        if($input['action'] == 'status'){
            foreach($recordid as $record){
            $editedstatus = $this->enterpriseRepository->ediStatus($input,$record);
            if($editedstatus){
             $neoEditedStatus = $this->neoEnterpriseRepository->editStatus($input,$editedstatus[0]->emailid);
            }
        }
            if($editedstatus){
                $message = array('msg' => array(Lang::get('MINTMESH.editStatus.success')));
            }else{
                $message = array('msg' => array(Lang::get('MINTMESH.editStatus.failure')));
            }
        }
          return $this->commonFormatter->formatResponse(200, "success", $message);
    }
    public function createBucket($input){ 
        
        $response = $data = $setData = array();
        $createdAt = gmdate("Y-m-d H:i:s");
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId    = $this->loggedinUserDetails->emailid;
        $userId         = $this->loggedinUserDetails->id;
        $companyCode    = !empty($input['company_code'])?$input['company_code']:0;
        $bucketName     = !empty($input['bucket_name'])?$input['bucket_name']:'';
        // get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
        $isBucketExist  = $this->enterpriseRepository->isBucketExist($userId, $companyId, $bucketName);
        //check if bucket name already existed
        if(empty($isBucketExist)){
            //create new bucket in MySql here
            $bucketId = $this->enterpriseRepository->createNewBucket($userId, $companyId, $bucketName, $createdAt);
            if($bucketId){
                //set data for Neo4j bucket creation here
                $setData['default']         = 'dynamic';
                $setData['created_at']      = $createdAt;
                $setData['bucket_name']     = $bucketName;
                $setData['user_emailid']    = $userEmailId;
                $setData['no_of_contacts']  = 0;
                $this->neoEnterpriseRepository->createNeoNewBucket($setData, $bucketId);//create the new bucket node in Neo4j db
                $this->neoEnterpriseRepository->createCompanyBucketRelation($companyId, $bucketId, $setData);//create relation between company node and bucket node 
                //set the response data here
                $response['bucket_name']    = $bucketName;
                $response['bucket_id']      = $bucketId;
                $data = $response;
                $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.bucket_success')));
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.bucket_failure')));
            }
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.bucket_exsisted')));
        }  
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function validateContactsFileHeaders($input){ 
        
        $responseCode       = self::ERROR_RESPONSE_CODE;
        $responseMsg        = self::ERROR_RESPONSE_MESSAGE;
        $inputFile          = !empty($input['file_name'])?$input['file_name']:'';
        
        if(!empty($inputFile)){
            $inputFileInfo      = pathinfo($inputFile);
            $inputFileExtension = $inputFileInfo['extension'];
        }
        //cheking file format here             
        if (!empty($inputFile) && in_array($inputFileExtension, $this->allowedExcelExtensions)) {
            //reading input excel file here
            $arrResults     = Excel::load($inputFile)->first()->toArray();
            $validHeaders   = TRUE;
            //comparing headers here
            foreach ($this->allowedHeaders as $value) {
                if (!array_key_exists($value, $arrResults)) {
                    $validHeaders = FALSE;
                }
            }
            if($validHeaders){
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg  = self::SUCCESS_RESPONSE_MESSAGE;
                $message      = array('msg' => array(Lang::get('MINTMESH.user.valid')));
             } else {
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.invalid_headers')));
             }       
        }else {
            $message = array('msg' => array(Lang::get('MINTMESH.editContactList.file_format').'csv, xlsx, xls'));
        } 
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }
    
    public function processUploadContacts($inputParams){ 
        
        $return = FALSE;
        $contactsList = array();
        $companyId    =  $inputParams['company_id'];
        $userId       =  $inputParams['user_id'];     
        $bucketId     =  $inputParams['bucket_id'];
        $inputFile    =  $inputParams['file_name'];
        $companyCode  =  $inputParams['company_code'];
        
        if(!empty($inputFile)){
            $inputFileInfo      = pathinfo($inputFile);
            $inputFileExtension = $inputFileInfo['extension'];
        }
        //cheking file format here             
        if (!empty($inputFile) && in_array($inputFileExtension, $this->allowedExcelExtensions)) {
            //reading input excel file here
            $arrResults     = Excel::load($inputFile)->all();
            $firstRow       = $arrResults->first()->toArray();
            $validHeaders   = TRUE;
            //comparing headers here
            foreach ($this->allowedHeaders as $value) {
                if (!array_key_exists($value, $firstRow)) {
                    $validHeaders = FALSE;
                }
            }            
            //check header validations here  
            if($validHeaders){
                $arrResults = $arrResults->toArray();
                //create file record
		$importFileId = $this->enterpriseRepository->getFileId($inputFile,$userId);
                //filtering to make unique email ids for avoiding duplicate entry's 
                foreach ($arrResults as $key => $val) {
                    if ($val['email_id']) {
                        $arrUniqueResults[$val['email_id']] = $val;
                    }
                }
                //importing contacts to Mysql db
                $resultsSet   = $this->enterpriseRepository->uploadContacts($arrUniqueResults, $userId, $bucketId, $companyId, $importFileId);    
                //get the Import Contacts List By Instance Id
                $contactsList = $this->enterpriseRepository->getContactsListByFileId($companyId, $importFileId);
                    
                if (!empty($contactsList)) {    
                    //Creating relation between bucket and contacts in neo4j
                    foreach ($contactsList as $key => $value) {
                        $pushData = array();
                        $pushData['firstname']      = $value->firstname;
                        $pushData['lastname']       = $value->lastname;
                        $pushData['emailid']        = $value->emailid;
                        $pushData['contact_number'] = $value->phone;
                        $pushData['other_id']       = $value->employeeid;
                        $pushData['status']         = $value->status;
                        $pushData['bucket_id']      = $bucketId;
                        $pushData['company_code']     = $companyCode;
                        $pushData['loggedin_emailid'] = $this->loggedinUserDetails->emailid;

                        Queue::push('Mintmesh\Services\Queues\CreateEnterpriseContactsQueue', $pushData, 'IMPORT');
                    }
                }
                $return = TRUE;
            }      
        }
        return $return;
    }
    public function uploadContacts($input){ 
        
        $result = array();
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userId          = $this->loggedinUserDetails->id;
        $companyCode     = !empty($input['company_code'])?$input['company_code']:0;
        // get the logged in user company details with company code here
        $companyDetails  = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId       = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
        $contacts        = !empty($input['contacts'])?$input['contacts']:array();
       
            foreach ($contacts as $key => $value) {
                $inputParams = array();
                $inputParams['company_id']   = $companyId;
                $inputParams['user_id']      = $userId;
                $inputParams['bucket_id']    = $key;
                $inputParams['file_name']    = $value;
                $inputParams['company_code'] = $companyCode;
                $result[] = $this->processUploadContacts($inputParams); 
            }
        if(!empty($result)){
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.editContactList.success')));
        } else {
           $responseCode    = self::ERROR_RESPONSE_CODE;
           $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
           $message = array('msg' => array(Lang::get('MINTMESH.editContactList.failure')));
        }   
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }
    
    public function addContact($input){ 
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userId      = $this->loggedinUserDetails->id;
        $inputParams =$relationAttrs = array();
        $inputParams['company_id']   = $input['company_id'];
        $inputParams['user_id']      = $userId;
        $inputParams['bucket_id']    = $input['bucket_id'];
        $inputParams['firstname']    = !empty($input['firstname'])?$input['firstname']:'';      
        $inputParams['lastname']     = !empty($input['lastname'])?$input['lastname']:'';      
        $inputParams['emailid']      = !empty($input['emailid'])?$input['emailid']:'';      
        $inputParams['phone']        = !empty($input['phone'])?$input['phone']:'';      
        $inputParams['status']       = !empty($input['status'])?$input['status']:'unknown';  
        $inputParams['employeeid']   = $input['other_id'];      
         
        $relationAttrs['company_code']     = $input['company_code'];
        $relationAttrs['loggedin_emailid'] = $this->loggedinUserDetails->emailid;
        $relationAttrs['created_at']       = gmdate("Y-m-d H:i:s");
        
        $neoInput['firstname']   = $input['firstname'];
        $neoInput['lastname']    = $input['lastname'];
        $neoInput['phone']       = !empty($input['phone'])?$input['phone']:'';          
        $neoInput['emailid']     = $input['emailid'];
        $neoInput['employeeid']  = $input['other_id'];
        $neoInput['status']      = !empty($input['status'])?$input['status']:'unknown';  
        $checkContact = $this->enterpriseRepository->checkContact($inputParams);
        if(empty($checkContact))
        {
             $checkEmployeeId = $this->enterpriseRepository->checkEmpId($input);
             if(!$checkEmployeeId)
             {
                $result    = $this->enterpriseRepository->addContact($inputParams); 
                $neoResult = $this->neoEnterpriseRepository->createContactNodes($input['bucket_id'],$neoInput,$relationAttrs);
                $neoResult = $this->neoEnterpriseRepository->companyAutoConnect($neoInput['emailid'],$relationAttrs);
                if(!empty($result)){ 
                    $responseCode    = self::SUCCESS_RESPONSE_CODE;
                    $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                    $message = array('msg' => array(Lang::get('MINTMESH.addContact.success')));
                }
                else {
                    $responseCode    = self::ERROR_RESPONSE_CODE;
                    $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                    $message = array('msg' => array(Lang::get('MINTMESH.addContact.failure')));
                } 
              }else{
                    $responseCode    = self::ERROR_RESPONSE_CODE;
                    $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                    $message = array('msg' => array(Lang::get('MINTMESH.editContactList.invalidempid')));
              } 
        }
        else if($checkContact[0]->bucket_id == '0'){
            $inputParams['id'] = $checkContact[0]->id;
            $update = $this->enterpriseRepository->updateContact($inputParams);
            $neoUpdate = $this->neoEnterpriseRepository->updateContactNode($input['bucket_id'],$neoInput,$relationAttrs);
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addContact.contactUpdated')));
         }
        else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addContact.contactExists')));
        }     
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }
}

?>
