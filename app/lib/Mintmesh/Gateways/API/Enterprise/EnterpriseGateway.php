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
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
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
use lib\MyExcel\MyExcel;
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
    const REFRESH_TOKEN = 'refresh_token';
    const AUTHORIZATION = 'Authorization';
    const CREATED_IN = 'created_in';
    const Buckets_Inactive_STATUS = 2;

    protected $userRepository, $enterpriseRepository, $enterpriseValidator, $userFileUploader, $commonFormatter, $authorizer, $appEncodeDecode, $neoEnterpriseRepository;
    protected $allowedHeaders, $allowedExcelExtensions, $createdNeoUser, $referralsGateway, $contactsRepository,$referralsRepository,$myExcel;

    public function __construct(EnterpriseRepository $enterpriseRepository, 
                                NeoEnterpriseRepository $neoEnterpriseRepository, 
            UserGateway $userGateway, 
            ReferralsGateway $referralsGateway, 
            ReferralsRepository $referralsRepository,
            UserRepository $userRepository, 
            NeoUserRepository $neoUserRepository, 
            NeoPostRepository $neoPostRepository, 
            Authorizer $authorizer, 
            EnterpriseValidator $enterpriseValidator, 
            UserFileUploader $userFileUploader, 
            UserEmailManager $userEmailManager, 
            CommonFormatter $commonFormatter, 
            APPEncode $appEncodeDecode,
            ContactsRepository $contactsRepository,
            MyExcel $myExcel
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
        $this->userFileUploader = $userFileUploader;
        $this->userEmailManager = $userEmailManager;
        $this->commonFormatter = $commonFormatter;
        $this->appEncodeDecode = $appEncodeDecode;
        $this->contactsRepository = $contactsRepository;
        $this->myExcel = $myExcel;
        $this->allowedHeaders         = array('employee_idother_id', 'first_name', 'last_name', 'email_id', 'cell_phone', 'status');
        $this->validHeaders           = array('Employee ID/Other ID', 'First Name', 'Last Name', 'Email ID', 'Cell Phone', 'Status');
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
    public function validateEnterpriseSpecialGrantLogin($input) {
        return $this->doValidation('validateEnterpriseSpecialGrantLogin', 'MINTMESH.user.valid');
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
    //validation on Create update Bucket input
    public function validateUpdateBucket($input) {
        return $this->doValidation('update_bucket', 'MINTMESH.user.valid');
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
    //validation on set password input
    public function validateSetPasswordInput($input) {
        return $this->doValidation('set_password', 'MINTMESH.set_password.valid');
    }
    //validation on deactivate post input
    public function validateDeactivatePostInput($input) {
        return $this->doValidation('deactivate_post', 'MINTMESH.deactivate_post.valid');
    }
    //validation on add user to company
    public function validateAddingUserInput($input) {
        return $this->doValidation('add_user', 'MINTMESH.user.valid');
    }
    //validation on edit user to company
    public function validateEditingUserInput($input) {
        return $this->doValidation('edit_user', 'MINTMESH.user.valid');
    }
    //validation on add group
    public function validateAddGroupInput($input) {
        return $this->doValidation('add_group', 'MINTMESH.user.valid');
    }
    //validation on edit group
    public function validateEditGroupInput($input) {
        return $this->doValidation('add_group', 'MINTMESH.user.valid');
    }
    //validation on edit group
    public function validateupdateUserInput($input) {
        return $this->doValidation('editing_user', 'MINTMESH.user.valid');
    }
    //validation on campaign details
    public function validatecampaignDetailsInput($input) {
        return $this->doValidation('campaign_details', 'MINTMESH.user.valid');
    }
   //validation on campaign details
    public function validateResendActivationLinkInput($input) {
        return $this->doValidation('resend_activation', 'MINTMESH.user.valid');
    }
   //validation on campaign details
    public function validateGetCompanySubscriptionsInput($input) {
        return $this->doValidation('company_subscriptions', 'MINTMESH.user.valid');
    }
   //validation on add_edit_hcm details
    public function validateAddEditHcmInput($input) {
        return $this->doValidation('add_edit_hcm', 'MINTMESH.user.valid');
    }
     public function validateAddEditZenefitsHcmInput($input) {
        return $this->doValidation('get_hcm_list', 'MINTMESH.user.valid');
    }
   //validation on get_hcm_list details
    public function validateGetHcmListInput($input) {
        return $this->doValidation('get_hcm_list', 'MINTMESH.user.valid');
    }
    //validation on get_zenefits_hcm_list details
    public function validateGetZenefitsHcmListInput($input) {
        return $this->doValidation('get_hcm_list', 'MINTMESH.user.valid');
    }
    //validation on configuration
    public function validateAddConfigurationInput($input) {
        return $this->doValidation('add_configuration', 'MINTMESH.user.valid');
    }
    
   //validation on get configuration
    public function validateGetConfigurationInput($input) {
        return $this->doValidation('get_configuration', 'MINTMESH.user.valid');
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
            $accessCode = $this->enterpriseRepository->getAccessCode($input);
            if(!empty($accessCode))
            {
            $input['is_enterprise'] = 1;

            // creating company random code
            $randomCode = $this->getRandomCode();
            $input['company_code'] = $randomCode;
            #get contacts limit with access code 
            $input['contacts_limit'] = !empty($accessCode[0]->contacts_limit)?$accessCode[0]->contacts_limit:0;
            $createdGroup = $this->enterpriseRepository->createGroup();
            $input['group_id'] = $createdGroup['id'];
            //Inserting user details entry in mysql DB
            $createdUser = $this->enterpriseRepository->createEnterpriseUser($input);
            $input['mysql_id'] = $createdUser['id'];
            //Inserting user node in neo4j
            $neoEnterpriseUser = $this->createNeoUser($input);
            //cheking user succefully created in mysql DB
            if (!empty($createdUser)) {
                
                #get Subscription Type Id here
                $input['subscription_type'] = $this->getSubscriptionTypeId($input['contacts_limit']);
                //Inserting company name entry in mysql DB
                $input['user_id'] = $createdUser['id'];
                $createdCompany   = $this->enterpriseRepository->createCompanyProfile($input);
                $subscriptionsLog = $this->addCompanySubscriptionsLog($createdCompany->id, $input['contacts_limit']);
                $updatedGroup   = $this->enterpriseRepository->updateGroup($input,$createdCompany->id);
                $permissions    = $this->enterpriseRepository->adminPermissions($input);
                // create a node for company in neo4j
                $neoEnterpriseCompany = $this->createNeoCompany($input, $createdCompany);
                if (!empty($createdCompany)) {
                    //Mapping user and company entry in mysql DB 
                    $data = $this->enterpriseRepository->companyUserMapping($createdUser->id, $createdCompany->id, $randomCode);
                    $updateAccessCode = $this->enterpriseRepository->updateAccessCodeTable($input,$createdCompany->id);
                }
                if (!empty($neoEnterpriseCompany) && !empty($neoEnterpriseUser)) {
                    //Creating relation between user and company in neo4j
                    $data = $this->neoEnterpriseRepository->mapUserCompany($neoEnterpriseUser->emailid, $neoEnterpriseCompany->companyCode);
                    //Creating relation between company and unsolicited node in neo4j
                    $unsolicited = $this->neoEnterpriseRepository->createUnsolicitedAndCompanyRelation($neoEnterpriseCompany->companyCode);
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
            }else{
            $message = array('msg' => array(Lang::get('MINTMESH.user.invalid_acess')));
            return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            }
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
        $responseData = $response  = array();
        #added functionality for SAML login
        if(!empty($input['grant_type'])){
            $response['data']['emailid'] = !empty($input['emailId'])?$input['emailId']:'';
            $input['token'] = 'token';//temporary token for skip validations
        }else{
            $response = $this->userGateway->activateUser($input['token']);
        }
           
        if (!empty($response['data']['emailid'])) {

            $returnResponse = $this->userRepository->getUserByEmail($response['data']['emailid']);
            $responseData = $this->enterpriseRepository->getUserCompanyMap($returnResponse['id']);
            
            if(!empty($input['company_code'])) {
                if($input['company_code'] != $responseData->code) {
                    // returning failure message
                    $responseMessage = array(Lang::get('MINTMESH.user.failure'));
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                    $responseData = array();
                    return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $responseData);
                }
            }

            $input['username']  = $returnResponse['emailid'];
            $input['emailid']   = $returnResponse['emailid'];
            $input['client_id'] = $input['client_id'];
            $input['client_secret'] = $input['client_secret'];
            $input['grant_type']    = 'special_grant';
                        
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
        $input['description'] = !empty($input['description'])?$this->appEncodeDecode->filterString($input['description']):'';
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
            $input['company_logo'] = $input['logo_image_s3'];
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
            $error_code = $e->getCode();
            $oauthResult['error_description'] = $e->getMessage();
            if(!empty($error_code)){
                $oauthResult['error_description'] = Lang::get('MINTMESH.login.contact_admin');
            } else {
                $oauthResult['error_description'] = Lang::get('MINTMESH.login.login_failure');
            }
            
        }
        
        //check if access code is returned by oauth
        if (isset($oauthResult['access_token'])) {
            $loggedinUserDetails = $this->enterpriseRepository->getEnterpriseUserByEmail($inputUserData['username']);
            $neologgedinUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid);
            if (!empty($loggedinUserDetails)) {
                if($loggedinUserDetails['is_enterprise'] == 1 || $loggedinUserDetails['is_enterprise'] == 2)
                {
                    if($loggedinUserDetails->status == 1 && $loggedinUserDetails->group_status == 1){
                        $input['group_id'] = $loggedinUserDetails->group_id;
                        $checkGroupStatus = $this->enterpriseRepository->checkGroupStatus($input['group_id']);
                    if(!empty($checkGroupStatus) && $checkGroupStatus[0]->status == 'Active'){
                        $responseData = $this->enterpriseRepository->getUserCompanyMap($loggedinUserDetails['id']);
                        $userPermissions = $this->enterpriseRepository->getUserPermissions($input['group_id'],$input);
                        $userPermissions['is_primary'] = $checkGroupStatus[0]->is_primary;
                        $userDetails['id'] = $loggedinUserDetails['id'];
                        $userDetails['firstname'] = $loggedinUserDetails['firstname'];
                        $userDetails['emailid'] = $loggedinUserDetails['emailid'];
                        $userDetails['user_dp'] = $neologgedinUserDetails['photo'];
                        if ($loggedinUserDetails['emailverified'] == 1) {

                    // returning success message
                            $oauthResult['user'] = $userDetails;
                            $oauthResult['company'] = $responseData;
                            $oauthResult['userPermissions'] = $userPermissions;
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
                    }else{
                    //  returning failure message
                        $responseCode = self::ERROR_RESPONSE_CODE;
                        $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                        $message = array(Lang::get('MINTMESH.login.inactive_group'));
                        $data = array();
                    }
                    }else{
                        $responseCode = self::ERROR_RESPONSE_CODE;
                        $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                        $message = array(Lang::get('MINTMESH.login.inactive_user'));
                        $data = array();
                    }
                }else{
                    //  returning failure message
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                    $message = array(Lang::get('MINTMESH.login.login_credentials'));
                    $data = array();
                }
               
            }
        } else {
            // returning failure message                      
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array($oauthResult['error_description']);//removing error to hide server Exception for end user
            //$message = array(Lang::get('MINTMESH.login.contact_admin'));
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
        $neoUserInput['login_source']   = $input['login_source'];
        $neoUserInput['mysql_id']       = $input['mysql_id'];
        $neoUserInput['status']         = 'Active';
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
        $neoInputCompany['size'] = $input['contacts_limit'];
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
        $updateNeoCompany = $this->neoEnterpriseRepository->updateCompanyLabel($input['code'], $input['company'], $input['website'], $company_logo, $images, $input['description'],$input['industry'],$file,$file_org_name);
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
        $inputFile = $this->createFileObject($input['contacts_file']);
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

                $arrUniqueImpId = array();
                foreach ($arrResults as $key => $val) {
                    if ($val['email_id']) {		 
                        $val['employee_idother_id'] = ($val['employee_idother_id']!='' && in_array($val['employee_idother_id'],$arrUniqueImpId))?'':$val['employee_idother_id'];
                        $arrUniqueResults[$val['email_id']] = $val;
                        
                        if($val['employee_idother_id']!='')
			$arrUniqueImpId[$val['email_id']] = $val['employee_idother_id'];
                    }
                }
		unset($arrUniqueImpId);
                #company available contacts count verification here  
                $availableNo = $this->getCompanyAvailableContactsCount($companyCode);
                
                //$instanceId = $this->enterpriseRepository->getInstanceId(); //getting Instance Id
                //create file record
                $importFileId = $this->enterpriseRepository->getFileId($inputFile,$userId);
                //importing contacts to Mysql db
                $resultsSet = $this->enterpriseRepository->uploadContacts($arrUniqueResults, $userId, $bucketId, $companyId, $importFileId, $availableNo);

                if (!empty($resultsSet)) {
                    //get the Import Contacts List By Instance Id

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
                $record['bucket_id']   = (int)$result->bucket_id;
                $record['bucket_name'] = $result->bucket_name;
                $record['count']       = $result->count;
                $record['company_id']       = $result->company_id;
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
        $params = $resultsSet = $data = array();
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        $params['user_id']      = $this->loggedinUserDetails->id;
        $params['company_id']   = $input['company_id'];
        $params['bucket_id']    = !empty($input['bucket_id']) ? $input['bucket_id'] : 0;
        $params['page_no']      = !empty($input['page_no']) ? $input['page_no'] : 0;
        $params['search']       = !empty($input['search']) ? $input['search'] : 0;
        $params['sort']         = !empty($input['sort']) ? $input['sort'] : '';
        $resultsCount   = $this->enterpriseRepository->getImportContactsListCount($params);
        $resultsSet     = $this->enterpriseRepository->getImportContactsList($params); //get the import contact list
        if ($resultsSet) {
            #get count here
            $totalDownloads = !empty($resultsCount[0]->total_downloads)?$resultsCount[0]->total_downloads:0;
            $totalRecords   = !empty($resultsSet['total_records'][0]->total_count)?$resultsSet['total_records'][0]->total_count:0;
            $resultsSet['total_records'] = array('total_count'  => $totalRecords, 'total_downloads'=> $totalDownloads);
            
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg  = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_success'));
            $data = $resultsSet;
        } else {
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg  = self::ERROR_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_failure'));
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
        $bucketId = $contactNode['bucket_id'];
        $neoInput['firstname']      = $contactNode['firstname'];
        $neoInput['lastname']       = $contactNode['lastname'];
        $neoInput['emailid']        = $contactEmailId = $contactNode['emailid'];
        $neoInput['contact_number'] = $contactNode['contact_number'];
        $neoInput['employeeid']     = $contactNode['other_id'];
        $neoInput['status']         = $status = $contactNode['status'];
        
        $relationAttrs['company_code']      = $companyCode = $contactNode['company_code'];
        $relationAttrs['loggedin_emailid']  = $emailId = $contactNode['loggedin_emailid'];
        $relationAttrs['created_at']        = gmdate("Y-m-d H:i:s");
        try {
            $this->neoEnterpriseRepository->createContactNodes($bucketId, $neoInput, $relationAttrs);
            $this->neoEnterpriseRepository->companyAutoConnect($neoInput['emailid'], $relationAttrs);
            #check company bucket active jobs and create relation between user & job
            if($status != 'Separated'){
                $connectedJobs  = $this->companyJobsAutoConnect($companyCode, $bucketId, $contactEmailId, $emailId);
            }
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
        $companyLogoWidth  = $companyLogoHeight = $companyName = $companyLogo = '';
        $this->loggedinUserDetails  = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        $params['user_id']          = $this->loggedinUserDetails->id;
        $params['from_user_name']   = $this->loggedinUserDetails->firstname;
        $params['user_email']   = $this->loggedinUserDetails->emailid;
        $params['company_id']   = $input['company_id'];
        $emailSubject           = $input['email_subject'];
        $emailBody              = $input['email_body'];
        $params['invite_contacts']  = explode(',', $input['invite_contacts']);
        $params['ip_address']       = $_SERVER['REMOTE_ADDR'];

        $contactList    = $this->enterpriseRepository->getCompanyContactsListById($params); //get the import contact list by Ids
        $company        = $this->enterpriseRepository->getCompanyDetails($params['company_id']);//get company details
        if(isset($company[0])){
            $company     = $company[0];
            $companyName = !empty($company->name) ? $company->name : '' ;
            $companyLogo = !empty($company->logo) ? $company->logo : '' ;
            #company logo Aspect Ratio details for email template
            if(!empty($company->logo)){
                $companyLogoAspectRatio = $this->referralsGateway->getImageAspectRatio($company->logo);
                $companyLogoWidth       = !empty($companyLogoAspectRatio['width']) ? $companyLogoAspectRatio['width'] : '';
                $companyLogoHeight      = !empty($companyLogoAspectRatio['height']) ? $companyLogoAspectRatio['height'] : '';
            }
        }
        
        if (!empty($contactList)) {
            foreach ($contactList as $key => $value) {
                if(isset($value[0]) && !empty($value[0]->emailid) && $value[0]->status != 'Separated'){
                    $pushData = array();
                    #company Details
                    $pushData['company_name']           = $companyName;
                    $pushData['company_logo']           = $companyLogo;
                    $pushData['company_logo_width']     = $companyLogoWidth;
                    $pushData['company_logo_height']    = $companyLogoHeight;
                    #contact details
                    $pushData['firstname']      = $value[0]->firstname;
                    $pushData['lastname']       = $value[0]->lastname;
                    $pushData['emailid']        = $value[0]->emailid;
                    $pushData['email_subject']  = 'Invitation to Referral Rewards Program from '.$pushData['company_name'];
                    $pushData['email_body']     = $emailBody;
                    //for email logs
                    $pushData['from_user_id']    = $params['user_id'];
                    $pushData['from_user_name']  = $params['from_user_name'];
                    $pushData['from_user_email'] = $params['user_email'];
                    $pushData['company_code']    = $params['company_id'];
                    $pushData['ip_address']      = $params['ip_address'];
                    Queue::push('Mintmesh\Services\Queues\EmailInvitationEnterpriseContactsQueue', $pushData, 'IMPORT');
                } 
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
        $dataSet['name']         = $fullName;
        $dataSet['email']        = $inputEmailData['emailid'];
        $dataSet['emailbody']    = $inputEmailData['email_body'];
        $dataSet['fromName']     = $inputEmailData['from_user_name'];
        $dataSet['send_company_name'] = $inputEmailData['company_name'];
        $dataSet['logo_width']  = $inputEmailData['company_logo_width'];
        $dataSet['logo_height'] = $inputEmailData['company_logo_height'];
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
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.enterprise_forgot_password');
                $this->userEmailManager->emailId = $input['emailid'];
                $dataSet = array();
                $dataSet['name'] = $neoUserDetails['fullname'];
                //set reset code
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
         $userId     = !empty($this->loggedinUserDetails->id)?$this->loggedinUserDetails->id:'';
        #log user activity here
        $companyCode = $input['company_code'];
        
        $returnDetails  = $return = $data = array();
        // get the logged in user company details here
        $companyDetails = $this->neoEnterpriseRepository->viewCompanyDetails($userEmailId, $companyCode);
        if(!empty($companyDetails[0])){
            $industry= '';
            $company = $companyDetails[0][0];
            $user    = $companyDetails[0][1];
            
            $returnDetails['name']         = !empty($company->name)?$company->name:'';
            $returnDetails['images']       = !empty($company->images)?array_filter(explode(',',$company->images)):array();
            $returnDetails['industry']     = !empty($company->industry)?$company->industry:'';  
            $returnDetails['industry_name']= !empty($company->industry)?$this->userRepository->getIndustryName($company->industry):'';  
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
        $input['time_zone'] = !empty($input['time_zone'])?$input['time_zone']:0;
        $filterLimit = !empty($input['filter_limit'])?$input['filter_limit']:'';
        $requestType = !empty($input['request_type'])?$input['request_type']:'';
        
        if($filterLimit == 360){
            $filterLimit = date('Y-m-d H:i:s', strtotime('-1 year'));    
        } elseif ($filterLimit == 30) {
            $filterLimit = date('Y-m-d H:i:s', strtotime('-1 month'));
        } else {
            $filterLimit = date('Y-m-d H:i:s', strtotime('-1 week'));
        }

        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0])?$companyDetails[0]->id:0;
            
        switch ($requestType) {
                case 'COUNTS':
                    $data = $this->getCompanyUserPostCounts($userEmailId, $companyCode);
                    break;
                case 'PROGRESS':
                    $data = $this->getCompanyUserPostProgress($userEmailId, $userId, $companyCode, $companyId, $filterLimit);
                    break;
                case 'REFERRALS':
                    $data = $this->getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit,$input,$companyId);
                    break;
                case 'HIRED':
                    $data = $this->getCompanyUserPostHires($userEmailId, $companyCode, $filterLimit,$companyId,$input);
                    break;
                case 'TOPREFERRALS':
                    $data = $this->getCompanyUserTopReferrals($userEmailId, $companyCode,$companyId);
                    break;
                default:
                    $postCounts     = $this->getCompanyUserPostCounts($userEmailId, $companyCode);
                    $postProgress   = $this->getCompanyUserPostProgress($userEmailId, $userId, $companyCode, $companyId);
                    $postReferrals  = $this->getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit,$input,$companyId);
                    $postHires      = $this->getCompanyUserPostHires($userEmailId, $companyCode,$filterLimit,$companyId,$input);
                    $topReferrals   = $this->getCompanyUserTopReferrals($userEmailId, $companyCode,$companyId);
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
        return $this->commonFormatter->formatResponse(200, "success", $message, $data, true);
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
    
    public function getCompanyUserPostProgress($userEmailId, $userId, $companyCode, $companyId, $filterLimit=''){
        $return = $response = array();
        $rewardsCount = $contactsCount = $jobsReachCount = $companyInvitedCount = 0;
        $filterLimit    = empty($filterLimit)?date('Y-m-d H:i:s', strtotime('-1 month')):$filterLimit;//default 30 days
        //CONTACTS ENGAGEMENT
        $downloadedCount        = $this->enterpriseRepository->appActiveUserCount($userId, $companyId, $filterLimit);
        $companyInvitedCount    = $this->enterpriseRepository->appActiveContactsCount($companyId);
        $downloadedCount        = !empty($downloadedCount[0]->count)?$downloadedCount[0]->count:0;
        $companyInvitedCount    = !empty($companyInvitedCount[0]->count)?$companyInvitedCount[0]->count:0;
        
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
    
    public function getCompanyUserPostReferrals($userEmailId, $companyCode, $filterLimit,$input,$companyId){
        
        $return = $returnDetails = $referralDetails = $returnReferralDetails = array();
        $postDetails = $this->neoEnterpriseRepository->getCompanyUserPostReferrals($companyCode);
        if(!empty($postDetails)){
            
            foreach($postDetails as $post){
                $postDetails     = $this->referralsGateway->formPostDetailsArray($post[0]);
                $referralDetails = $this->neoEnterpriseRepository->getReferralDetails($postDetails['post_id'], $filterLimit);
                
                if(!empty($referralDetails)){
                    
                    foreach($referralDetails as $details){
                        $referralName   ='';
                        $nonMMUser      = new \stdClass();
                        $userDetails    = $this->referralsGateway->formPostDetailsArray($details[0]);
                        $postRelDetails = $this->referralsGateway->formPostDetailsArray($details[1]);
                        $postDetails    = $this->referralsGateway->formPostDetailsArray($details[2]);
                        if(!empty($userDetails['emailid'])){
                        $referralDetails = $this->enterpriseRepository->getContactByEmailId($userDetails['emailid'],$companyId);
                        if(!empty($referralDetails)){
                        $referralName = $referralDetails[0]->firstname.' '.$referralDetails[0]->lastname;}
                        $neoReferralDetails = $this->neoUserRepository->getNodeByEmailId($userDetails['emailid']);
                        $neoReferralName = !empty($neoReferralDetails['fullname'])?$neoReferralDetails['fullname']:$neoReferralDetails['firstname'];
                        }
                        // get the Non Mintmesh name
                        if(empty($userDetails['fullname']) && !empty($postRelDetails['referred_by'])){
                            
                            if(!empty($userDetails['emailid'])){
                                    $nonMMUser = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                              } elseif (!empty($userDetails['phone'])) {
                                    $nonMMUser = $this->contactsRepository->getImportRelationDetailsByPhone($postRelDetails['referred_by'], $userDetails['phone']);
                              }
                              $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
                            
                        }  else {
                              $referralName = !empty($referralName)?$referralName:$neoReferralName;
                        }
                        $referrerDetails = $this->enterpriseRepository->getContactByEmailId($postRelDetails['referred_by'],$companyId);
                        if(!empty($referrerDetails)){
                        $referrerName = $referrerDetails[0]->firstname.' '.$referrerDetails[0]->lastname;}
                        $neoReferrerDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                        $neoReferrerName = !empty($neoReferrerDetails['fullname'])?$neoReferrerDetails['fullname']:$neoReferrerDetails['firstname'];
                        $returnDetails['job_title']      = !empty($postDetails['service_name']) ? $postDetails['service_name'] : 'See Job Description';
                        $returnDetails['status']         = $postRelDetails['one_way_status'];
                        $createdAt = $postRelDetails['created_at'];
                        $returnDetails['created_at']     = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                        $returnDetails['referral']       = !empty($referralName)?$referralName:'The contact';
                        $returnDetails['referral_img']   = !empty($userDetails['dp_renamed_name'])?$userDetails['dp_renamed_name']:'';
                        $returnDetails['referred_by']    = !empty($referrerName)?$referrerName:$neoReferrerName;
                        $returnDetails['referred_by_img']= $neoReferrerDetails['dp_renamed_name'];
                        $returnDetails['service_cost']   = !empty($postDetails['service_cost'])?$postDetails['service_cost']:0;
                        
                        $returnReferralDetails[]    = $returnDetails;
                    }
                }
            }
        }
        return $return = array('post_referrals' =>$returnReferralDetails);
    }
    
    public function getCompanyUserPostHires($userEmailId, $companyCode, $filterLimit='',$companyId='',$input){
          
        $returnDetails  = $return = $referralDetails = $returnHiresDetails = array();
        $filterLimit    = empty($filterLimit)?date('Y-m-d H:i:s', strtotime('-1 month')):$filterLimit;//default 30 days
        $postDetails    = $this->neoEnterpriseRepository->getCompanyUserPostReferrals($companyCode);
        if(!empty($postDetails)){
            
            foreach($postDetails as $post){
                $PostRewards     = array(); 
                $postDetails     = $this->referralsGateway->formPostDetailsArray($post[0]);
                $referralDetails = $this->neoEnterpriseRepository->getReferralDetails($postDetails['post_id'], $filterLimit);
                $PostRewards     = $this->getPostRewards($postDetails['post_id']);
                if(!empty($referralDetails)){
                    
                    foreach($referralDetails as $details){
                        
                        $referralName   ='';
                        $nonMMUser      = new \stdClass();
                        $userDetails    = $this->referralsGateway->formPostDetailsArray($details[0]);
                        $postRelDetails = $this->referralsGateway->formPostDetailsArray($details[1]);
                        $postDetails    = $this->referralsGateway->formPostDetailsArray($details[2]);
                        
                      if(!empty($postRelDetails['awaiting_action_status']) && $postRelDetails['awaiting_action_status'] === 'HIRED'){
                          if(!empty($userDetails['emailid'])){
                           $referralDetails = $this->enterpriseRepository->getContactByEmailId($userDetails['emailid'],$companyId);
                            if(!empty($referralDetails)){
                            $referralName = $referralDetails[0]->firstname.' '.$referralDetails[0]->lastname;}
                            $neoReferralDetails = $this->neoUserRepository->getNodeByEmailId($userDetails['emailid']);
                            $neoReferralName = !empty($neoReferralDetails['fullname'])?$neoReferralDetails['fullname']:$neoReferralDetails['firstname'];
                           } 
                            // get the Non Mintmesh name
                            if(empty($userDetails['fullname']) && !empty($postRelDetails['referred_by'])){

                                if(!empty($userDetails['emailid'])){
                                        $nonMMUser = $this->contactsRepository->getImportRelationDetailsByEmail($postRelDetails['referred_by'], $userDetails['emailid']);
                                  } elseif (!empty($userDetails['phone'])) {
                                        $nonMMUser = $this->contactsRepository->getImportRelationDetailsByPhone($postRelDetails['referred_by'], $userDetails['phone']);
                                  }
                                  $referralName = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";

                            }  else {
                                  $referralName = !empty($referralName)?$referralName:$neoReferralName;
                            }
                            $referrerDetails = $this->enterpriseRepository->getContactByEmailId($postRelDetails['referred_by'],$companyId);
                            if(!empty($referrerDetails)){
                            $referrerName = $referrerDetails[0]->firstname.' '.$referrerDetails[0]->lastname;}
                            $neoReferredByDetails = $this->neoUserRepository->getNodeByEmailId($postRelDetails['referred_by']);
                            $neoReferrerName = !empty($neoReferredByDetails['fullname'])?$neoReferredByDetails['fullname']:$neoReferredByDetails['firstname'];
                        
                            $returnDetails['job_title']      =  $postDetails['service_name'];
                            $returnDetails['status']         =  $postRelDetails['one_way_status'];
                            $createdAt = $postRelDetails['awaiting_action_updated_at'];
                            $returnDetails['created_at']     = \Carbon\Carbon::createFromTimeStamp(strtotime($createdAt))->diffForHumans();
                            $returnDetails['referral']       =  !empty($referralName)?$referralName:'The contact';
                            $returnDetails['referral_img']   =  !empty($userDetails['dp_renamed_name'])?$userDetails['dp_renamed_name']:'';
                            $returnDetails['referred_by']    =  !empty($referrerName)?$referrerName:$neoReferrerName;
                            $returnDetails['referred_by_img']=  $neoReferredByDetails['dp_renamed_name'];
                            $returnDetails['free_service']   =  !empty($postDetails['free_service'])?$postDetails['free_service']:'0';
                            $returnDetails['rewards']        =  $PostRewards;

                            $returnHiresDetails[]   =   $returnDetails;
                        }  
                    }
                }
            }
        }
        return $return = array('post_hires' =>$returnHiresDetails);
    }
    
    public function getCompanyUserTopReferrals($userEmailId, $companyCode,$companyId){
           
        $returnTopReferrals = $topReferrals = array();
        $topReferrals = $this->neoEnterpriseRepository->getCompanyUserTopReferrals($userEmailId,$companyCode);//get the top referrals list here
        if(!empty($topReferrals)){
            foreach($topReferrals as $referral){
                $record = array();
                $designation = '';
                $referralUser    = $referral[0]; 
                $referralsCount  = $referral[1];    
                
                $referrerDetails = $this->enterpriseRepository->getContactByEmailId($referralUser,$companyId);
                if(!empty($referrerDetails)){
                $referrerName = $referrerDetails[0]->firstname.' '.$referrerDetails[0]->lastname;}
                $neoReferredByDetails = $this->neoUserRepository->getNodeByEmailId($referralUser);
                $neoReferrerName = !empty($neoReferredByDetails['fullname'])?$neoReferredByDetails['fullname']:$neoReferredByDetails['firstname'];
                        
                //get user designation here
                if (!empty($neoReferredByDetails) && $neoReferredByDetails->completed_experience == '1'){
                    $result = $this->neoEnterpriseRepository->getDesignation($referralUser);
                    if(!empty($result[0])){
                        foreach ($result[0] as $obj) {
                            $designation = $obj->name;   
                        }
                    }
                } 
                //set the return response here
                $record['name']     = !empty($referrerName)?$referrerName:!empty($neoReferrerName)?$neoReferrerName:'The contact';
                $record['image']    = !empty($neoReferredByDetails->dp_renamed_name)?$neoReferredByDetails->dp_renamed_name:'';
                $record['designation'] = !empty($designation)?$designation:'';
                $record['count']       = $referralsCount;
                $returnTopReferrals[]  = $record;
            }
        }
       return $return = array('top_referrals' =>$returnTopReferrals);
    }
    
    public function getCompanyProfile(){
        
        $returnDetails  = $return = $data = $userDetails = array();
        $this->loggedinUserDetails  = $this->referralsGateway->getLoggedInUser();
        $userEmailId                = $this->loggedinUserDetails->emailid;
        $user                       = $this->neoEnterpriseRepository->getUsers($userEmailId);
        $userDetails['user_id']     = $this->loggedinUserDetails->id;
        $userDetails['user_name']   = $user->fullname;
        $userDetails['user_email']  = $user->emailid;
        $userDetails['user_dp']     = $user->photo;
        // get the logged in user company details here
        $companyDetails = $this->neoEnterpriseRepository->getCompanyProfile($userEmailId);
        if(!empty($companyDetails[0])){

            $company = $companyDetails[0][0];        
            $returnDetails['name']         = $company->name;
            $returnDetails['company_code'] = $company->companyCode;
            $returnDetails['company_logo'] = $company->logo;
            $returnDetails['employees_no'] = !empty($company->size)?$company->size:0;
            $returnDetails['company_id']   = !empty($company->mysql_id) ? $company->mysql_id : 0;
            $data['companyDetails'] = $returnDetails;
            $data['userDetails']    = $userDetails;
            $checkGroupStatus       = $this->enterpriseRepository->checkGroupStatus($this->loggedinUserDetails->group_id);
            $data['userPermissions']               = $this->getUserPermissions();
            $data['userPermissions']['is_primary'] = $checkGroupStatus[0]->is_primary;
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.success')));
        } else {
            $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.no_details')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message, $data);
    }
    
    public function updateContactsList($input) {
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $company = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails['id']);
        $input['company_id'] = $company->company_id;
        $companyCode = $company->code;
        #check if EmployeeId already exist or not.
        $checkEmployeeId = $this->enterpriseRepository->checkEmployeeId($input);
        if(!$checkEmployeeId)
        {   
            $allowToUpdate = TRUE;
            $status     = isset($input['status']) ? $input['status'] : '';
            $recordId   = isset($input['record_id']) ? $input['record_id'] : 0;
            
            if ($status == 'Active' || $status == 'Inactive') {
                #check contact current status here.
                $currentStatus = $this->enterpriseRepository->checkContactCurrentStatusById($recordId);
                if(empty($currentStatus)){
                    #if current status already Active or Inactive allow to Update.
                    $available = $this->getCompanyAvailableContactsCount($companyCode);
                    #check available contact count here.
                    if(empty($available)){
                        $allowToUpdate = FALSE;
                    } else {
                        $this->enterpriseRepository->updateUserStatus($recordId);
                    }
                } 
            } else if ($status == 'Separated'){
                $updated = $this->enterpriseRepository->deleteUserOauthAccessTokens($recordId);
            }
            #check allowToUpdate flag enable or disable.
            if($allowToUpdate){
                #update contact record in MySql
                $updated = $this->enterpriseRepository->updateContactsList($input);
                if(!empty($updated)){
                    #update contact record in Neo4j
                    $neoupdated = $this->neoEnterpriseRepository->updateContactsList($updated[0]->emailid,$input);
                }
                if($updated){
                    $message = array('msg' => array(Lang::get('MINTMESH.editContactList.success')));
                } else {
                    $message = array('msg' => array(Lang::get('MINTMESH.editContactList.failure')));
                }
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.contactsLimitExceeded')));
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
        $bucket     = !empty($input['bucket_name'])?$input['bucket_name']:'';
        $bucketName = $this->appEncodeDecode->filterString($bucket);
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
    
    public function updateBucket($input){ 
       
        $response = $data = $setData = array();
        $createdAt = gmdate("Y-m-d H:i:s");
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userEmailId    = $this->loggedinUserDetails->emailid;
        $userId         = $this->loggedinUserDetails->id;
        $companyCode    = !empty($input['company_code'])?$input['company_code']:0;
        $bucketStatus     = self::Buckets_Inactive_STATUS;//!empty($input['status'])?$input['status']:'';
        $bucketId     = !empty($input['bucket_id'])?$input['bucket_id']:'';
        // get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
       // $isBucketExist  = $this->enterpriseRepository->isBucketExist($userId, $companyId, $bucketName);
       
        if($bucketId){
            //update bucket in MySql here
            $IsDeleted = $this->enterpriseRepository->updateExistBucket($userId, $bucketId, $companyId,$bucketStatus, $createdAt);
          if(!$IsDeleted){
          $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.bucket_deleted')));
          }else{
               $message = array('msg' => array(Lang::get('MINTMESH.companyDetails.bucket_delete_fail')));
          }
        }else{
            $message = array('msg' => array(Lang::get('Bucket Id Doesnot exist')));
        }
        return $this->commonFormatter->formatResponse(200, "success", $message);
    }
    
    public function validateContactsFileHeaders($input){ 
        
        $responseCode       = self::ERROR_RESPONSE_CODE;
        $responseMsg        = self::ERROR_RESPONSE_MESSAGE;
        $inputFile          = !empty($input['file_name'])?$input['file_name']:'';
        $resultAry = array();
        if(!empty($inputFile)){
            $inputFileInfo      = pathinfo($inputFile);
            $inputFileExtension = $inputFileInfo['extension'];
        }
        #cheking file format here             
        if (!empty($inputFile) && in_array($inputFileExtension, $this->allowedExcelExtensions)) {
            #reading input excel file here
            $allDataInSheet = MyExcel::read_excel1_sheets_headers($inputFile);

            $header_excel = !empty($allDataInSheet['column_names'][0])?$allDataInSheet['column_names'][0]:'';
            foreach ($header_excel as $key => $value) {
                $id     = isset($value['id']) ? $value['id'] : '';
                $label  = isset($value['label']) ? $value['label'] : '';
                $resultAry[$id] = $label;
            }
            $header_array = array_map('trim', $this->validHeaders);
            $header_excel_array = array_map('trim', $resultAry);
            $validHeaders = array_diff_assoc($header_array, $header_excel_array);
            
            if(empty($validHeaders)){
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg  = self::SUCCESS_RESPONSE_MESSAGE;
                $message      = array('msg' => array(Lang::get('MINTMESH.user.valid')));
             } else {
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.invalid_headers')));
                \Log::info("<<<<<<<<<<<<<<<< Invalid Headers >>>>>>>>>>>>>".print_r($header_excel_array,1));
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
        #cheking file format here             
        if (!empty($inputFile) && in_array($inputFileExtension, $this->allowedExcelExtensions)) {
            #reading input excel file here
            $allDataInSheet = MyExcel::readExcel($inputFile);
            #get excel headers
            $header_array = array_map('trim', $this->validHeaders);
            $header_excel = $allDataInSheet[0][1];
            $header_excel_array = array_map('trim', $header_excel);            
            $validHeaders = array_diff_assoc($header_array, $header_excel_array);
            #check header validations here  
            if(empty($validHeaders)){
                #create file record
		$importFileId = $this->enterpriseRepository->getFileId($inputFile,$userId);
                #get excel sheet unique rows filter
		$arrUniqueResults = $this->getExcelUniqueRows($allDataInSheet);
                #company available contacts count verification here  
                $availableNo = $this->getCompanyAvailableContactsCount($companyCode);
                #importing contacts to Mysql db
                $resultsSet   = $this->enterpriseRepository->uploadContacts($arrUniqueResults, $userId, $bucketId, $companyId, $importFileId, $availableNo);    
                $employeesNo  = $resultsSet['insert'];
                #log the company subscriptions here                
                #get the Import Contacts List By Instance Id
                $contactsList = $this->enterpriseRepository->getContactsListByFileId($companyId, $importFileId);
                
                if (!empty($contactsList)) {    
                    #Creating relation between bucket and contacts in neo4j
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
                $return = $resultsSet;    
            }      
        }
        return $return;
    }
    
    public function uploadContacts($input){ 
        
        $result = $data = array();
        $data['limit_exceeded'] = FALSE;
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
            #check limit exceeded flag
            $limitExceeded = FALSE;
            foreach ($result as $value) {
                if(!empty($value['limitExceeded'])){
                    $limitExceeded = TRUE;
                }
            }
            if($limitExceeded){
                $data['limit_exceeded'] = TRUE;
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.contactsLimitExceeded')));
            }  else {
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.success')));
            }    
        } else {
           $responseCode    = self::ERROR_RESPONSE_CODE;
           $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
           $message = array('msg' => array(Lang::get('MINTMESH.editContactList.failure')));
        }   
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data);
    }
    
    public function getExcelUniqueRows($allDataInSheet) {
            
        $shift = array_shift($allDataInSheet[0]);
            #filtering to make unique email ids and employee id for avoiding duplicate entry's 
            $arrUniqueImpId = $arrUniqueResults = array();
            foreach ($allDataInSheet as $rowKey => $rowVal) {
                foreach ($rowVal as $cellKey => $cellVal) {
                    $string_email = isset($cellVal[3]) ? trim($cellVal[3]) : '';

                    if(!empty($string_email) && filter_var($string_email, FILTER_VALIDATE_EMAIL)){

                        $cellVal  = array_map('trim', $cellVal);
                        $email_id = str_replace("&nbsp;", '', $cellVal[3]);
                        $email_id = trim($email_id);

                        $cellVal[0] = ($cellVal[0] !='' && in_array($cellVal[0],$arrUniqueImpId))?'':$cellVal[0];

                        $arrUniqueResults[$email_id] = array(
                                "email_id"      => $email_id,
                                "first_name"    => isset($cellVal[1]) ? $cellVal[1] : '',
                                "last_name"     => isset($cellVal[2]) ? $cellVal[2] : '',
                                "employee_idother_id" => isset($cellVal[0]) ? $cellVal[0] : '',
                                "cell_phone"    => isset($cellVal[4]) ? $cellVal[4] : '',
                                "status"        => isset($cellVal[5]) ? $cellVal[5] : ''
                            );
                        if($cellVal[0]!='')
                        $arrUniqueImpId[$email_id] = $cellVal[0];
                    }
                }    
            }
            unset($arrUniqueImpId); 
        return $arrUniqueResults;    
    }
    
    public function addContact($input){ 
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $userId      = $this->loggedinUserDetails->id;
        $emailId     = $this->loggedinUserDetails->emailid;
        $inputParams = $relationAttrs = array();
        $companyCode = !empty($input['company_code'])?$input['company_code']:'';
        $bucketId    = !empty($input['bucket_id'])?$input['bucket_id']:'';
        $companyId   = !empty($input['company_id'])?$input['company_id']:'';
        $contactEmailId = !empty($input['emailid'])?$this->appEncodeDecode->filterString(strtolower(trim($input['emailid']))):'';
        $inputParams['company_id']   = $companyId;
        $inputParams['user_id']      = $userId;
        $inputParams['bucket_id']    = $input['bucket_id'];
        $inputParams['firstname']    = !empty($input['firstname'])?$input['firstname']:'';      
        $inputParams['lastname']     = !empty($input['lastname'])?$input['lastname']:'';      
        $inputParams['emailid']      = $contactEmailId ;      
        $inputParams['phone']        = !empty($input['phone'])?$input['phone']:'';      
        $inputParams['status']       = $status = !empty($input['status'])?$input['status']:'Active';  
        $inputParams['employeeid']   = !empty($input['other_id'])?$input['other_id']:'';      
         
        $relationAttrs['company_code']      = $companyCode;
        $relationAttrs['loggedin_emailid']  = $emailId;
        $relationAttrs['created_at']        = gmdate("Y-m-d H:i:s");
        $relationAttrs['firstname']         = !empty($input['firstname'])?$input['firstname']:'';    
        $relationAttrs['lastname']          = !empty($input['lastname'])?$input['lastname']:'';    
        $neoInput['firstname']   = $input['firstname'];
        $neoInput['lastname']    = $input['lastname'];
        $neoInput['contact_number'] = !empty($input['phone'])?$input['phone']:'';          
        $neoInput['emailid']     = $contactEmailId;
        $neoInput['employeeid']  = !empty($input['other_id'])?$input['other_id']:'';
        $neoInput['status']      = $status;  
        $checkContact = $this->enterpriseRepository->checkContact($inputParams);
        if(empty($checkContact)){
            #company available contacts count verification here  
            $availableNo = $this->getCompanyAvailableContactsCount($companyCode);
            #when status is Separated then allow to create or availableNo not zero
            if(!empty($availableNo) || $status == 'Separated'){

                $checkEmployeeId = $this->enterpriseRepository->checkEmpId($input);
                if(!$checkEmployeeId)
                {
                    $result    = $this->enterpriseRepository->addContact($inputParams); 
                    if(!empty($result)){ 
                        $employeesNo = 1;
                        $neoResult = $this->neoEnterpriseRepository->createContactNodes($input['bucket_id'],$neoInput,$relationAttrs);
                        $neoResult = $this->neoEnterpriseRepository->companyAutoConnect($neoInput['emailid'],$relationAttrs);
                        #check company bucket active jobs and create relation between user & job
                        if($status != 'Separated'){
                            $connectedJobs  = $this->companyJobsAutoConnect($companyCode, $bucketId, $contactEmailId, $emailId);
                        }
                        $responseCode    = self::SUCCESS_RESPONSE_CODE;
                        $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                        $message = array('msg' => array(Lang::get('MINTMESH.addContact.success')));
                    }else {
                        $responseCode    = self::ERROR_RESPONSE_CODE;
                        $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                        $message = array('msg' => array(Lang::get('MINTMESH.addContact.failure')));
                    } 
                }else{
                    $responseCode    = self::ERROR_RESPONSE_CODE;
                    $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                    $message = array('msg' => array(Lang::get('MINTMESH.editContactList.invalidempid')));
                } 
            }else{
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.editContactList.contactsLimitExceeded')));
            } 
        } else if($checkContact[0]->bucket_id == '0'){
            
                $inputParams['id'] = $checkContact[0]->id;
                $update = $this->enterpriseRepository->updateContact($inputParams);
                $neoUpdate = $this->neoEnterpriseRepository->updateContactNode($input['bucket_id'],$neoInput,$relationAttrs);
                #check company bucket active jobs and create relation between user & job
                if($status != 'Separated'){
                    $connectedJobs  = $this->companyJobsAutoConnect($companyCode, $bucketId, $contactEmailId, $emailId);
                }
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.addContact.contactUpdated')));
            }else{
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.addContact.contactExists')));
            }     
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }
        
    public function addPermissions($input) {
         $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
         $loggedInUserId = $this->loggedinUserDetails->id;
         $groupId = $input['group_id'];
         $permArray = $input['permission'];
         if (is_numeric($groupId) && isset($permArray) && is_array($permArray)) {
            $savePermissions = $this->enterpriseRepository->addPermissions($groupId, $permArray, $loggedInUserId, $input);
            if($savePermissions){
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.addPermissions.success')));
            }else{
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.addPermissions.failure')));
            }
         }else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addPermissions.invalidUserId')));
         }   
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, array());
    }
    
    public function getPermissions() {
        $permissions = $this->enterpriseRepository->getPermissions();  
        if($permissions){
             $data = $permissions;
             $responseCode    = self::SUCCESS_RESPONSE_CODE;
             $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
             $message = array('msg' => array(Lang::get('MINTMESH.getPermissions.success')));
        }else{
            $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.getPermissions.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);
    }
    
    public function getGroupPermissions($input) {
        $permArray = array();
        if (is_numeric($input['group_id'])) {
            $userPermissions = $this->enterpriseRepository->getGroupPermissions($input['group_id'], $input);
            if(!empty($userPermissions)){
                $data = $userPermissions;
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.getGroupPermissions.success')));
            }else{
                $data = array();
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.getGroupPermissions.failure'))); 
            }
        }else{
            $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.getGroupPermissions.invalidUserId')));
        }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);
    }
    
    public function addingUser($input) {
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $companyDetails = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails->id);
        $userCount = 0;
        if (empty($userCount)) {
            $responseMessage = $responseCode = $responseStatus = "";
            $responseData = array();
             $input['is_enterprise'] = 1;
             $input['company_code'] = $companyDetails->code;
             $input['company_id'] = $companyDetails->company_id;
             $checkUser = $this->enterpriseRepository->getEnterpriseUserByEmail($input['emailid']);
             if(empty($checkUser)){
             if (isset($input['photo']) && !empty($input['photo'])) {
            //upload the file
            $this->userFileUploader->source =  $input['photo'];
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_USER_IMAGE');
            $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['photo']);
            $input['photo'] = $renamedFileName;
            }
            //Inserting user details entry in mysql DB
            $createdUser = $this->enterpriseRepository->addUser($input);
            $input['user_id'] = $createdUser[0]->id;
           // Inserting user node in neo4j
            $neoEnterpriseUser = $this->createNeoAddUser($input);
            if (!empty($createdUser)) {
                    //Mapping user and company entry in mysql DB 
                    $data = $this->enterpriseRepository->companyUserMapping($input['user_id'],$input['company_id'], $input['company_code']);
                    $relationType = 'CONNECTED_TO_COMPANY';
                    $neoData = $this->neoEnterpriseRepository->mapUserCompany($input['emailid'], $input['company_code'],$relationType);
                if($createdUser[0]->group_status == '1'){
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.set_password');
                $this->userEmailManager->emailId = $createdUser[0]->emailid;
                $senderName =  $this->loggedinUserDetails->firstname .' via MintMesh';
                $dataSet = array();
                $dataSet['name'] = $createdUser[0]->firstname;
                $dataSet['emailid'] = $createdUser[0]->emailid;
                $dataSet['company_name'] = $companyDetails->name;
                $dataSet['send_company_name'] = $senderName;
                //set reset code
                $currentTime = date('Y-m-d h:i:s');
                $email = md5($createdUser[0]->emailid);
                $code = $this->userGateway->base_64_encode($currentTime, $email);
                $dataSet['hrs'] = Config::get('constants.USER_EXPIRY_HR');
                $dataSet['send_company_name'] = $this->loggedinUserDetails->firstname;
                $dataSet['link'] = Config::get('constants.MM_ENTERPRISE_URL') . "/reset_password?setcode=" . $code; //comment it for normal flow of deep linki.e without http
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.set_password');
                $this->userEmailManager->name = $createdUser[0]->firstname;
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
                    'to_email' => !empty($createdUser) ? $createdUser[0]->emailid : '',
                    'related_code' => $code,
                    'sent' => $emailStatus,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                );
                $this->userRepository->logEmail($emailLog);
                //update code in users table
                $inputdata = array('user_id' => $createdUser[0]->id,
                    'resetactivationcode' => $code);
                if (!empty($email_sent)) {
                    
               $this->userRepository->updateUserresetpwdcode($inputdata);
                }
                }
               $data = array();
               $data['photo'] = $neoEnterpriseUser->photo;
              $responseCode    = self::SUCCESS_RESPONSE_CODE;
              $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
              $message = array('msg' => array(Lang::get('MINTMESH.addUser.success')));
                } 
            else{
                 $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addUser.userexists'))); 
            }
             }else{
             
                $isEnterprise =  $this->userRepository->getIsEnterpriseStatus($input['emailid']);
                if($isEnterprise[0]->is_enterprise==0){
                    $isEnterprise = 2;
                }else {
                    $isEnterprise = 1;
                }
  
             $updateEnterpriseUser = $this->enterpriseRepository->updateEnterpriseUser($input['emailid'],$input['group_id'], $isEnterprise);
             $data = $this->enterpriseRepository->companyUserMapping($checkUser['id'],$input['company_id'], $input['company_code']);
             if($updateEnterpriseUser){
                $groupName = $this->enterpriseRepository->getGroup($input['group_id']);
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.user_activation');
                $this->userEmailManager->emailId = $checkUser['emailid'];
                $senderName =  $this->loggedinUserDetails->firstname .' via MintMesh';
                $dataSet = array();
                $contactDetails = $this->enterpriseRepository->getContactByEmailId($checkUser['emailid'],$input['company_id']);
                 if(!empty($contactDetails)){
                    $dataSet['name'] = $contactDetails[0]->firstname.' '.$contactDetails[0]->lastname;
                }else{
                $dataSet['name'] = $checkUser['firstname'];}
                $dataSet['group_name'] = strtoupper($groupName[0]->name);
                $dataSet['emailid'] = $checkUser['emailid'];
                $dataSet['company_name'] = $companyDetails->name;
                $dataSet['send_company_name'] = $senderName;
                //set reset code
                //set timezone of mysql if different servers are being used
                $currentTime = date('Y-m-d h:i:s');
                $email = md5($checkUser['emailid']);
//                $activationcode = $createdUser[0]['emailactivationcode'];
                $code = $this->userGateway->base_64_encode($currentTime, $email);
                $dataSet['hrs'] = Config::get('constants.USER_EXPIRY_HR');
                $companyName = explode(' ', $companyDetails->name);
                $dataSet['link'] = Config::get('constants.MM_ENTERPRISE_URL') . "/company/$companyName[0]/$companyDetails->code"; //comment it for normal flow of deep linki.e without http
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.set_password');
                $this->userEmailManager->name = $checkUser['firstname'];
                $email_sent = $this->userEmailManager->sendMail();
                //log email status
                $emailStatus = 0;
                if (!empty($email_sent)) {
                    $emailStatus = 1;
                     $this->userRepository->setActive($checkUser['id'],$checkUser['emailid']);
                }
                $emailLog = array(
                    'emails_types_id' => 1,
                    'from_user' => 0,
                    'from_email' => '',
                    'to_email' => !empty($createdUser) ? $createdUser[0]->emailid : '',
                    'related_code' => $code,
                    'sent' => $emailStatus,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                );
                $this->userRepository->logEmail($emailLog);
                //update code in users table
                $inputdata = array('user_id' => $checkUser['id'],
                    'resetactivationcode' => $code);
                if (!empty($email_sent)) {
                    
               $this->userRepository->updateUserresetpwdcode($inputdata);
                }
             
            
                  $data = array();
                $responseCode    = self::SUCCESS_RESPONSE_CODE;
                $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.addUser.success')));
             }else{
                  $data = array();
                 $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                $message = array('msg' => array(Lang::get('MINTMESH.addUser.userexists'))); 
             }
             }
        }else{
             $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addUser.userexists'))); 
        }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message,$data,array());
    }
    
    public function editingUser($input) {
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $companyDetails = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails->id);
        $userCount = 0;
            $responseMessage = $responseCode = $responseStatus = "";
            $responseData = array();
            if($input['status'] == 'Inactive'){
                $input['status'] = '0';
            }else{
                $input['status'] = '1';
            }
            $input['is_enterprise'] = 1;
             $input['company_code'] = $companyDetails->code;
             $input['company_id'] = $companyDetails->company_id;
             $input['firstname'] = $input['fullname'];
            if (isset($input['photo']) && !empty($input['photo'])) {
            //upload the file
            $this->userFileUploader->source =  $input['photo'];
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_USER_IMAGE');
            $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['photo']);
            $input['photo'] = $renamedFileName;
            }
             if (isset($input['photo_s3']) && !empty($input['photo_s3'])) {
            //upload the file
            $input['photo'] = $input['photo_s3'];
            $input['photo_org_name'] = $input['photo_org_name_s3'];
        }
            //Inserting user details entry in mysql DB
            $checkUser = $this->enterpriseRepository->checkUser($input);
           
            if(!$checkUser){
            $editedUser = $this->enterpriseRepository->editingUser($input);
            $user = $this->enterpriseRepository->getEnterpriseUserByEmail($input['emailid']);
            if(!isset($user['resetactivationcode']) && $user['group_status'] == '1'){
                $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.set_password');
                $this->userEmailManager->emailId = $user['emailid'];
                $senderName =  $this->loggedinUserDetails->firstname .' via MintMesh';
                $dataSet = array();
                $dataSet['name'] = $user['firstname'];
                $dataSet['emailid'] = $user['emailid'];
                $dataSet['company_name'] = $companyDetails->name;
                $dataSet['send_company_name'] = $senderName;
                //set reset code
                //set timezone of mysql if different servers are being used
                $currentTime = date('Y-m-d h:i:s');
                $email = md5($user['emailid']);
                $code = $this->userGateway->base_64_encode($currentTime, $email);
                $dataSet['hrs'] = Config::get('constants.USER_EXPIRY_HR');
                $dataSet['send_company_name'] = $this->loggedinUserDetails->firstname;
                $dataSet['link'] = Config::get('constants.MM_ENTERPRISE_URL') . "/reset_password?setcode=" . $code; //comment it for normal flow of deep linki.e without http
                $this->userEmailManager->dataSet = $dataSet;
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.set_password');
                $this->userEmailManager->name = $user['firstname'];
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
                    'to_email' => !empty($createdUser) ? $createdUser[0]->emailid : '',
                    'related_code' => $code,
                    'sent' => $emailStatus,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                );
                $this->userRepository->logEmail($emailLog);
                //update code in users table
                $inputdata = array('user_id' => $user['id'],
                    'resetactivationcode' => $code);
                if (!empty($email_sent)) {
                    
               $this->userRepository->updateUserresetpwdcode($inputdata);
                }
            }
            $input['user_id'] = $editedUser;
           // Inserting user node in neo4j
            if($input['status'] == '0'){
                $input['status'] = 'Inactive';
            }else{
                $input['status'] = 'Active';
            }
            $neoEnterpriseUser = $this->createNeoAddUser($input);
            if (!empty($editedUser) && !empty($input['permission'])) {
                     $this->addPermissions($input);
                }
            if($editedUser){
              $data = array();
              $data['photo'] = $neoEnterpriseUser->photo;
              $responseCode    = self::SUCCESS_RESPONSE_CODE;
              $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
              $message = array('msg' => array(Lang::get('MINTMESH.editUser.success')));
            }
            else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.editUser.failure'))); 
            }
            }else{
            $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.editUser.emailexists')));
            }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,array());
        
        
    }
    
    private function createNeoAddUser($input) {
        $neoEnterpriseUser = "";
        //check for existing node in neo4j
        $neoEnterprise = $this->neoEnterpriseRepository->getNodeByEmailId($input['emailid']);

        $neoUserInput['fullname']       = $input['fullname'];
        $neoUserInput['emailid']        = $input['emailid'];
        $neoUserInput['is_enterprise']  = $input['is_enterprise'];
        $neoUserInput['group_status']  = $input['status'];
        $neoUserInput['photo']  = isset($input['photo'])?$input['photo']:'';
        $neoUserInput['photo_org_name']  = isset($input['photo_org_name'])?$input['photo_org_name']:'';
        $neoUserInput['mysql_id']  = $input['user_id'];
        $neoUserInput['group_id']  = isset($input['group_id'])?$input['group_id']:'';
        if (empty($neoEnterprise)) {
            $neoEnterpriseUser = $this->neoEnterpriseRepository->createAddUser($neoUserInput);
        } else {
                //change user label
                $neoEnterpriseUser =  $this->neoUserRepository->changeUserLabel($input['emailid']) ;
                if (!empty($neoEnterpriseUser)){
                    $neoEnterpriseUser =  $this->neoUserRepository->updateUser($neoUserInput) ;
                }
        }
        return $neoEnterpriseUser;
    }
    private function getUsers($company,$groups,$companyid) {
       $userDetails = $postDetails = array();
       $users = $this->enterpriseRepository->getUsers( $company,$groups);
       $input['user_id'] = $this->loggedinUserDetails->id;
       foreach($users as $k=>$v){
       $neoUsers = $this->neoEnterpriseRepository->getUsers($v->emailid);
       if(!empty($v->resetactivationcode)){
        $decodedString = $this->userGateway->base_64_decode($v->resetactivationcode);
        $sentTime = $decodedString['string1'];
        $expiryTime = date('Y-m-d H:i:s', strtotime($sentTime . " +" . Config::get('constants.USER_EXPIRY_HR') . " hours"));
       //check if expiry time is valid
       if (strtotime($expiryTime) > strtotime(gmdate('Y-m-d H:i:s'))) {
           $postDetails['expired'] = (int)'0';
       }
       else{
           $postDetails['expired'] = (int)'1';
       }
       }else{
            $postDetails['expired'] = (int)'0';
       }
       $postDetails['user_id'] =  $v->user_id;
       $contactDetails = $this->enterpriseRepository->getContactByEmailId($neoUsers->emailid,$companyid);
       $postDetails['emailid'] = $neoUsers->emailid;
       if(!empty($contactDetails)){
           $postDetails['fullname'] = $contactDetails[0]->firstname.' '.$contactDetails[0]->lastname;
       }else{
       $postDetails['fullname'] = $neoUsers->fullname;}
       $postDetails['location'] = isset($neoUsers->location)?$neoUsers->location:'';
       if($v->group_status == '1'){
           $postDetails['status'] = 'Active';
       }else{
            $postDetails['status'] = 'Inactive';
       }
       $postDetails['designation'] = isset($neoUsers->designation)?$neoUsers->designation:'';
       $postDetails['photo'] = isset($neoUsers->photo)?$neoUsers->photo:'';
       $userDetails[] = $postDetails;
       }
       return $userDetails;
    }
    
    public function addingGroup($input) {
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $companyDetails = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails->id);
            $responseMessage = $responseCode = $responseStatus = "";
            $responseData = array();
             $input['user_id'] =  $this->loggedinUserDetails->id;
             $input['company_id'] = $companyDetails->company_id;
             $input['id'] = '0';
            //Inserting group details entry in mysql DB
            $checkGroup = $this->enterpriseRepository->checkGroup($input);
            if(!$checkGroup){
            $createdGroup = $this->enterpriseRepository->addGroup($input);
            $input['group_id'] = $createdGroup[0]->last_id;
            if (!empty($createdGroup)) {
                if(!empty($input['permission'])){
                    $this->addPermissions($input);
                }
                    //Mapping user and company entry in mysql DB 
                }
            if($createdGroup){
               $responseCode    = self::SUCCESS_RESPONSE_CODE;
              $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
              $message = array('msg' => array(Lang::get('MINTMESH.addGroup.success')));
            }
            else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addGroup.failure'))); 
            }
            }else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.addGroup.groupExists'))); 
            }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg,$message,array());
    }
    
     public function editingGroup($input) {
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $companyDetails = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails->id);
            $responseMessage = $responseCode = $responseStatus = "";
            $responseData = array();
            $input['user_id'] =  $this->loggedinUserDetails->id;
            $input['company_id'] = $companyDetails->company_id;
            $input['id'] = $input['group_id'];
            $checkGroup = $this->enterpriseRepository->checkGroup($input);
            if(!$checkGroup){
            //Inserting group details entry in mysql DB
            $editedGroup = $this->enterpriseRepository->editGroup($input);
            $input['group_id'] = $editedGroup[0]->id;
            if (!empty($editedGroup)) {
                if(!empty($input['permission']) && $editedGroup[0]->is_primary == '0'){
                     $this->addPermissions($input);
                }
                    //Mapping user and company entry in mysql DB 
                }else{
                 $responseCode    = self::ERROR_RESPONSE_CODE;
                 $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
                 $message = array('msg' => array(Lang::get('MINTMESH.editGroup.permissionserror'))); 
                }
            if($editedGroup){
               $responseCode    = self::SUCCESS_RESPONSE_CODE;
              $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
              $message = array('msg' => array(Lang::get('MINTMESH.editGroup.success')));
            }
            else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.editGroup.failure'))); 
            }
            }else{
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.editGroup.groupExists'))); 
            }
        
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg,$message,array());
    }
   
    public function getGroups() {
       $groupInfo = array();$groupPermissions = array();$details=array();
       $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
       $companyDetails = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails->id);
       $group = $this->enterpriseRepository->getGroup($this->loggedinUserDetails->group_id);
       $input['user_id'] = $this->loggedinUserDetails->id;
       $input['group_name'] = $group[0]->name;
       $input['company_id'] = $companyDetails->company_id;
       $groupDetails = $this->enterpriseRepository->getGroups($input);
       foreach($groupDetails as $groups){
           $details['group_id'] = $groups->id ;
           $details['name'] = $groups->name ;
           $details['status'] = $groups->status ;
           $details['is_primary'] = $groups->is_primary ;
            $details['users'] = array();
           $input['type'] = '';
           $companyDetails = $this->enterpriseRepository->getUserCompanyMap($this->loggedinUserDetails->id);
           $users = $this->getUsers( $companyDetails->code,$groups->id, $companyDetails->company_id);
           foreach($users as $u){
               if(isset($u['expired'])){
                   $expired = $u['expired'];
               }else{
                   $expired = '';
               }
               $details['users'][] = array('emailid' => $u['emailid'],'user_id' => $u['user_id'],'fullname' => $u['fullname'],
                   'location' => $u['location'],'status' => $u['status'],'designation' => $u['designation'],'photo' => $u['photo'],'expired' => $expired,
                   'admin' => ($u['emailid'] == $this->loggedinUserDetails->emailid)?1:0);
           }
           $permissions = $this->enterpriseRepository->getGroupPermissions($groups->id, $input);
           $details['permissions'] = !empty($permissions)?$permissions:json_decode ("{}");
           $details['count_of_users'] = count($users);
            $groupInfo[] = $details;
       }
       if($groupInfo){
            $data = array("groups" => array_values(($groupInfo)));
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.getGroups.success')));
       }else{
           $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.getGroups.failure')));
        }        
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);    
    }
    
    public function setPassword($input) {
        $decodedString = $this->userGateway->base_64_decode($input['code']);
        $sentTime = $decodedString['string1'];
        $email = $decodedString['string2'];
        //to get resetactivationcode 
        $passwordData = $this->userRepository->getresetcodeNpassword($email);
        if (!empty($email) && !empty($passwordData) && $passwordData->resetactivationcode == $input['code']) {
            //set timezone of mysql if different servers are being used            
            $expiryTime = date('Y-m-d H:i:s', strtotime($sentTime . " +" . Config::get('constants.USER_EXPIRY_HR') . " hours"));
            //check if expiry time is valid
            if (strtotime($expiryTime) > strtotime(gmdate('Y-m-d H:i:s'))) {
                $userDetails = $this->enterpriseRepository->getEmailActivationCode($input);
                $activation =  $this->userRepository->setActive($userDetails[0]->id,$userDetails[0]->emailid);
                $post = array();
                $post['email'] = $email;
                $post['password'] = $input['password'];
                // update status of the user to active
                $updateCount = $this->userRepository->resetPassword($post);
                if (!empty($updateCount)) {
                    $message = array('msg' => array(Lang::get('MINTMESH.set_password.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array());
                } else {
                    $message = array('msg' => array(Lang::get('MINTMESH.set_password.failed')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
                }
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.set_password.invalid')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            }
        } else {
            if (empty($passwordData->resetactivationcode)) {
                $message = array('msg' => array(Lang::get('MINTMESH.set_password.codeexpired')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            } else {
                $message = array('msg' => array(Lang::get('MINTMESH.set_password.error')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array());
            }
        }
    }
    
    public function getUserPermissions(){
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $group_id = $this->loggedinUserDetails->group_id;
        $returnDetails  = $return = $data = array();
        $input = '';
        // get the logged in user company details here
        $userPermissions = $this->enterpriseRepository->getUserPermissions($group_id,$input);
        return $userPermissions;
        if(!empty($userPermissions)){
            $data[][] = $userPermissions;
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.getUserPermissions.success')));
        } else {
            $data = array();
            $responseCode    = self::ERROR_RESPONSE_CODE;
            $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.getUserPermissions.failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);    
    }
    
    public function updateUser($input) {
        
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        $input['user_id']   = $this->loggedinUserDetails->id;
        $input['firstname'] = trim($input['name']);
        
        if (isset($input['photo']) && !empty($input['photo'])) {
            //upload the file
            $this->userFileUploader->source      =  $input['photo'];
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_USER_IMAGE');
            $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['photo']);
            $input['photo']  = $renamedFileName;
        } 
        
        if (isset($input['photo_s3']) && !empty($input['photo_s3'])) {
            //upload the file
            $input['photo']          = $input['photo_s3'];
            $input['photo_org_name'] = $input['photo_org_name_s3'];
        }
        $input['photo']          = isset($input['photo']) ? $input['photo'] : '';
        $input['photo_org_name'] = isset($input['photo_org_name']) ? $input['photo_org_name'] : '';
        
        if(isset($input['flag']) && !empty($input['flag']) && $input['flag'] == 1 && !empty($input['photo'])){
            $updatedCompanyLogo     = $this->enterpriseRepository->updateCompanyLogo($input);
            $input['id']            = $updatedCompanyLogo[0]->id; 
            $neoupdatedCompanyLogo  = $this->neoEnterpriseRepository->updateCompanyLogo($input);
        }
        $updatedUser    = $this->enterpriseRepository->updateUser($input);
        $neoUpdatedUser = $this->neoEnterpriseRepository->updateUser($input);
        
        if(!empty($neoUpdatedUser)){
           $data['user_dp'] = $neoUpdatedUser->photo;
           $responseCode    = self::SUCCESS_RESPONSE_CODE;
           $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
           $message = array('msg' => array(Lang::get('MINTMESH.updateUser.success'))); 
        }else{
           $responseCode    = self::ERROR_RESPONSE_CODE;
           $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
           $message = array('msg' => array(Lang::get('MINTMESH.updateUser.failure'))); 
        }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg,$message,$data);    
    }
    
    public function getPostRewards($postId='') {
        $aryRewards  = $rewards = array();
        $postRewards = $this->neoPostRepository->getPostRewards($postId);
            foreach ($postRewards as $value) { 
               $relObj   = $value[1];//POST_REWARDS relation
               $valueObj = $value[2];//REWARDS node
               $rewards['rewards_name']     = !empty($relObj->rewards_mode)?ucfirst($relObj->rewards_mode):'';
               $rewards['currency_type']    = !empty($valueObj->currency_type)?$valueObj->currency_type:0;
               $rewards['rewards_type']     = !empty($valueObj->rewards_type)?$valueObj->rewards_type:'';
               $rewards['rewards_value']    = !empty($valueObj->rewards_value)?$valueObj->rewards_value:'';
               $aryRewards[] = $rewards;
            }
        return $aryRewards;
    }
    
   
    public function updateNewPermission(){
        
        $companies = $this->enterpriseRepository->getEnterpriseCompanies();
         
         foreach($companies as $v){
             $input = array();
             $input['user_id']   = $v->created_by;
                if($v->gid==0){
                    $createdGroup       = $this->enterpriseRepository->createGroup();//add new group 'Admin'
                    $input['group_id']  = $createdGroup['id'];
                    $input['company_id']= $v->cid;
                    $updatedGroup       = $this->enterpriseRepository->updateGroup($input,$v->cid);//update group with user id and company id
                    $updateUser         = $this->enterpriseRepository->getUsersGroupId($input);//updating users table
                } else {
                    $input['group_id']  = $v->gid;
                    
                }
             $updateUser         = $this->enterpriseRepository->deleteGroupPermissions($input['group_id']);
             $permissions        = $this->enterpriseRepository->adminPermissions($input);   
             }
               $responseCode    = self::SUCCESS_RESPONSE_CODE;
               $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
               $message = array('msg' => array(Lang::get('MINTMESH.updateNewPermission.success'))); 
         
           return $this->commonFormatter->formatResponse($responseCode, $responseMsg,$message,array());    
    }
    
    public function deactivatePost($input)
        {
            $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $postId = $input['post_id'] ;
            $checkPermissions = $this->enterpriseRepository->getUserPermissions($this->loggedinUserDetails->group_id,$input);
            $closeJobs = !empty($checkPermissions['close_jobs'])?$checkPermissions['close_jobs']:'';
            $posts = $this->neoPostRepository->getPosts($postId);
            if($closeJobs == 1 || $posts->created_by == $userEmail)
            {
                $closed = $this->referralsRepository->deactivatePost($userEmail, $postId);
                if (!empty($closed))
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.deactivatepost.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.deactivatepost.no_posts')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                }
            }else{
                $message = array('msg'=>array(Lang::get('MINTMESH.deactivatepost.no_permissions')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            return $this->commonFormatter->formatResponse($responseCode,$responseMsg,$message,array());    
            
        }
        
     public function resendActivationLink($input){
         $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();       
         $userDetails = $this->userRepository->getUserByEmail($input['emailid']);
         $companyDetails = $this->enterpriseRepository->getUserCompanyMap($userDetails['id']);
         $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.set_password');
         $this->userEmailManager->emailId = $userDetails['emailid'];
         $senderName =  $this->loggedinUserDetails->firstname .' via MintMesh';
         $dataSet = array();
         $dataSet['name'] = $userDetails['firstname'];
         $dataSet['emailid'] = $userDetails['emailid'];
         $dataSet['company_name'] = $companyDetails->name;
         $dataSet['send_company_name'] = $senderName;
       //set reset code
       //set timezone of mysql if different servers are being used
          $currentTime = date('Y-m-d h:i:s');
          $email = md5($userDetails['emailid']);
         $code = $this->userGateway->base_64_encode($currentTime, $email);
         $dataSet['hrs'] = Config::get('constants.USER_EXPIRY_HR');
         $dataSet['link'] = Config::get('constants.MM_ENTERPRISE_URL') . "/reset_password?setcode=" . $code; //comment it for normal flow of deep linki.e without http
         $this->userEmailManager->dataSet = $dataSet;
         $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.set_password');
         $this->userEmailManager->name = $userDetails['firstname'];
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
         'to_email' => !empty($userDetails) ? $userDetails['emailid'] : '',
         'related_code' => $code,
         'sent' => $emailStatus,
         'ip_address' => $_SERVER['REMOTE_ADDR']
          );
         $this->userRepository->logEmail($emailLog);
        //update code in users table
         $inputdata = array('user_id' => $userDetails['id'],
                    'resetactivationcode' => $code);
         if (!empty($email_sent)) {
            $this->userRepository->updateUserresetpwdcode($inputdata);
            $responseCode    = self::SUCCESS_RESPONSE_CODE;
            $responseMsg     = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.success'))); 
        }else{
           $responseCode    = self::ERROR_RESPONSE_CODE;
           $responseMsg     = self::ERROR_RESPONSE_MESSAGE;
           $message = array('msg' => array(Lang::get('MINTMESH.resendActivationLink.failure'))); 
        }
         return $this->commonFormatter->formatResponse($responseCode, $responseMsg,$message,array());      
     }
     
     public function companyJobsAutoConnect($companyCode, $bucketId, $contactEmailId, $emailId) {
         
        $pushData = $jobData = array(); 
        $companyBucketJobs  =  $this->neoEnterpriseRepository->getCompanyBucketJobs($companyCode, $bucketId);
        $notificationMsg    =  Lang::get('MINTMESH.notifications.messages.27');
        if(!empty($companyBucketJobs[0])){
            #creating included Relation between Post and Contacts 
            $pushData['bucket_id']          = $bucketId;
            $pushData['contact_emailid']    = $contactEmailId;
            $pushData['company_code']       = $companyCode;
            $pushData['user_emailid']       = $emailId;
            $pushData['notification_msg']   = $notificationMsg;
            $pushData['notification_log']   = 1;//for log the notification or not
             \Log::info("<<<<<<<<<<<<<<<< In company Jobs Auto Connect >>>>>>>>>>>>>".print_r($pushData,1));
            foreach ($companyBucketJobs as $jobs){
                #creating relation with each job
                $pushData['postId']  = !empty($jobs[0])?$jobs[0]:'';
                $this->neoPostRepository->companyPostsAutoConnectWithContact($pushData);
            }
        }
        $companyBucketCampaigns  =  $this->neoEnterpriseRepository->getCompanyBucketCampaigns($companyCode, $bucketId);
        if(!empty($companyBucketCampaigns[0])){
            $jobData['bucket_id']       = $bucketId;
            $jobData['contact_emailid'] = $contactEmailId;
            $jobData['company_code']    = $companyCode;
            $jobData['user_emailid']    = $emailId;
            \Log::info("<<<<<<<<<<<<<<<< In company Campaigns Auto Connect >>>>>>>>>>>>>".print_r($jobData,1));
            foreach ($companyBucketCampaigns as $jobs){
                #creating relation with each job
                $jobData['campaign_id']     = !empty($jobs[0])?$jobs[0]:'';
                Queue::push('Mintmesh\Services\Queues\CompanyCampaignsAutoConnectWithContactQueue', $jobData, 'default');
            }
        }
     }
    
    public function getSubscriptionTypeId($employeesNo=0){
        
        if($employeesNo > 5000){
            $subscriptionTypeId = 3;
        }  elseif ($employeesNo > 50 && $employeesNo <= 5000) {
            $subscriptionTypeId = 2;
        }  else {
            $subscriptionTypeId = 1;
        }
       return $subscriptionTypeId; 
    }
    public function addCompanySubscriptionsLog($companyId='',$employeesNo=0){
        #log the company subscriptions here        
        $startDate      = gmdate("Y-m-d H:i:s");
        $endDate        = date('Y-m-d H:i:s', strtotime('+1 year'));//next year date
        $this->enterpriseRepository->addCompanySubscriptionsLog($companyId, $employeesNo, $startDate, $endDate);
    }
    public function getCompanyAvailableContactsCount($companyCode=''){
        
        $activeCount    = $this->enterpriseRepository->getCompanyActiveOrInactiveContactsCount($companyCode);
        $purchasedCount = $this->enterpriseRepository->getCompanyPurchasedContacts($companyCode);
        $available      = $purchasedCount - $activeCount;
        $available      = max($available,0);
        return $available;
    }
    
    public function getCompanySubscriptions($input){
        
        $returnAry  = $data = $return =  array();
        $companyCode    = !empty($input['company_code']) ? $input['company_code'] : '';
        $available = $this->getCompanyAvailableContactsCount($companyCode);
        $subAry    = $this->enterpriseRepository->getCompanySubscriptions($companyCode);
        
        $return['plan_type']        = !empty($subAry[0]->name) ? $subAry[0]->name : '';
        $return['licence_code']     = !empty($subAry[0]->access_code) ? $subAry[0]->access_code : '';
        $return['available_count']  = $available;
        
        foreach ($subAry as $value) {
            $licence = array();
            $licence['employees_no'] = $value->employees_no;
            $licence['start_date']   = date('M d, Y', strtotime($value->start_date));
            $licence['end_date']     = date('M d, Y', strtotime($value->end_date));
            $returnAry[]  = $licence;
        }
        $returnData = array('active_plan'=>$return,'licence_log'=>$returnAry);
        if(!empty($returnData)){
            $data = $returnData;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.enterprise.retrieve_success')));
          }else{
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.enterprise.retrieve_failure')));
          }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }
    
    public function addEditHcm($input){
        
        $message    = '';
        $returnAry  = $data = $hcmAry = $hcmConfigPropAry = array();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
        $hcmId          = !empty($input['hcm_id'])?$input['hcm_id']:'';
        $hcmUrl         = !empty($input['hcm_url'])?$input['hcm_url']:'';
        $hcmUsername    = !empty($input['hcm_username'])?$input['hcm_username']:'';
        $hcmPassword    = !empty($input['hcm_password'])?$input['hcm_password']:'';
        $hcmRunStatus   = !empty($input['hcm_run_status'])?$input['hcm_run_status']:'';
        #form the input for add and edit HCM config details
        $hcmAry[0]['name']     = 'DCNAME';
        $hcmAry[0]['value']    = $hcmUrl;
        $hcmAry[1]['name']     = 'USERNAME';
        $hcmAry[1]['value']    = $hcmUsername;
        $hcmAry[2]['name']     = 'PASSWORD';
        $hcmAry[2]['value']    = $hcmPassword;
        #process HCM config Properties here
        $hcmConfigPropAry   = $this->enterpriseRepository->setHcmConfigProperties($hcmId, $companyId, $hcmAry);
        if(!empty($hcmConfigPropAry)){
            if($hcmRunStatus){
                #get Company Hcm Jobs for company_hcm_jobs_id
                $checkHcmJobs = $this->enterpriseRepository->getCompanyHcmJobs($hcmId, $companyId);
                $companyHcmJobsId = !empty($checkHcmJobs[0]->company_hcm_jobs_id)?$checkHcmJobs[0]->company_hcm_jobs_id:'';
                if($companyHcmJobsId){
                    #update HCM schedule run status here
                    $hcmConfigPropAry   = $this->enterpriseRepository->updateHcmRunStatus($companyHcmJobsId, $hcmRunStatus);
                }    
            }
            #form the success messahe here
            if(!empty($hcmConfigPropAry['insert'])){
                $message    = Lang::get('MINTMESH.hcm_details.insert_success');
            }  else {
                $message    = Lang::get('MINTMESH.hcm_details.update_success');
            }
            #get company HCMs details here
            $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcmId);
            $getHcmstatus = !empty($getHcmJobs[0]->status)?$getHcmJobs[0]->status:0;
            $getHcmstatus = !empty($getHcmstatus)?'enable':'disable';
            $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcmId);
            $hcmDetails = $this->formatHcmResult($getHcmList);
            #form return company HCMs details
            if(!empty($hcmDetails[0])){
                $value = $hcmDetails[0];
                $returnAry['hcm_id']   = $value['hcm_id'];
                $returnAry['hcm_name'] = $value['name'];
                $returnAry['hcm_url']  = $value['DCNAME'];
                $returnAry['hcm_username'] = $value['USERNAME'];
                $returnAry['hcm_password'] = $value['PASSWORD'];
                $returnAry['hcm_status']   = $getHcmstatus; 
            }
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array($message));
        }else{
          $responseCode   = self::ERROR_RESPONSE_CODE;
          $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
          $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }  
    
    
    public function addEditZenefitsHcm($input){
        $message    = '';
        $returnAry  = $data = $hcmAry = $hcmConfigPropAry = array();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
        $hcmId          = !empty($input['hcm_id'])?$input['hcm_id']:'';
        $hcmCodeToken    = !empty($input['hcm_access_token'])?$input['hcm_access_token']:'';
        $hcmRunStatus   = !empty($input['hcm_run_status']) ? $input['hcm_run_status'] : '';
        #form the input for add and edit HCM config details
        $hcmAry[0]['name']     = 'Authorization';
        $hcmAry[0]['value']    = $hcmCodeToken;
     
        
        #process HCM config Properties here
        $hcmConfigPropAry   = $this->enterpriseRepository->setHcmConfigProperties($hcmId, $companyId, $hcmAry);
        if(!empty($hcmConfigPropAry)){
            if($hcmRunStatus){
                #get Company Hcm Jobs for company_hcm_jobs_id
                $checkHcmJobs = $this->enterpriseRepository->getCompanyHcmJobs($hcmId, $companyId);
                $companyHcmJobsId = !empty($checkHcmJobs[0]->company_hcm_jobs_id)?$checkHcmJobs[0]->company_hcm_jobs_id:'';
                if($companyHcmJobsId){
                    #update HCM schedule run status here
                    $hcmConfigPropAry   = $this->enterpriseRepository->updateHcmRunStatus($companyHcmJobsId, $hcmRunStatus);
                }    
            }
            #form the success messahe here
            if(!empty($hcmConfigPropAry['insert'])){
                $message    = Lang::get('MINTMESH.hcm_details.insert_success');
            }  else {
                $message    = Lang::get('MINTMESH.hcm_details.update_success');
            }
            #get company HCMs details here
            $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcmId);
            $getHcmstatus = !empty($getHcmJobs[0]->status) ? $getHcmJobs[0]->status : 0;
            $getHcmstatus = !empty($getHcmstatus) ? 'enable' : 'disable';
            $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcmId);
            $hcmDetails = $this->formatHcmResult($getHcmList);
            #form return company HCMs details
            if(!empty($hcmDetails[0])){
                $value = $hcmDetails[0];
                $returnAry['hcm_id']   = $value['hcm_id'];
                $returnAry['hcm_name'] = $value['name'];
                $returnAry['hcm_code_token']  = !empty($value['hcm_access_token']) ? $value['hcm_access_token'] : '';
                $returnAry['hcm_status']   = $getHcmstatus; 
            }
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array($message));
        }else{
          $responseCode   = self::ERROR_RESPONSE_CODE;
          $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
          $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }  
    
    public function addEditZenefitsAccessToken($input,$companycode){
        $input_zenefits = json_decode($input, TRUE);
        $message    = '';
        $returnAry  = $data = $hcmAry = $hcmConfigPropAry = array();
        $companyCode    = $companycode;
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
        $hcmId          = 2;//!empty($input['hcm_id'])?$input['hcm_id']:'';
        $hcmAccesToken    = !empty($input_zenefits['access_token'])? ($input_zenefits['token_type'] . ' ' . $input_zenefits['access_token']) :'';
        $hcmReferToken    = !empty($input_zenefits['refresh_token'])?$input_zenefits['refresh_token']:'';
        $hcmExpToken    = !empty($input_zenefits['expires_in'])?$input_zenefits['expires_in']:'';
        $hcmRunStatus   = !empty($input['hcm_run_status']) ? $input['hcm_run_status'] : '';
        #form the input for add and edit HCM config details
        $hcmAry[1]['name']     = self::AUTHORIZATION;
        $hcmAry[1]['value']    = $hcmAccesToken;
        $hcmAry[2]['name']     = self::REFRESH_TOKEN;
        $hcmAry[2]['value']    = $hcmReferToken;
        $hcmAry[3]['name']     = self::CREATED_IN;
        $hcmAry[3]['value']    = gmdate("Y-m-d H:i:s");
       
        #process HCM config Properties here
        $hcmConfigPropAry   = $this->enterpriseRepository->setHcmConfigProperties($hcmId, $companyId, $hcmAry);
        if(!empty($hcmConfigPropAry)){
            if($hcmRunStatus){
                #get Company Hcm Jobs for company_hcm_jobs_id
                $checkHcmJobs = $this->enterpriseRepository->getCompanyHcmJobs($hcmId, $companyId);
                $companyHcmJobsId = !empty($checkHcmJobs[0]->company_hcm_jobs_id)?$checkHcmJobs[0]->company_hcm_jobs_id:'';
                if($companyHcmJobsId){
                    #update HCM schedule run status here
                    $hcmConfigPropAry   = $this->enterpriseRepository->updateHcmRunStatus($companyHcmJobsId, $hcmRunStatus);
                }    
            }
            #form the success messahe here
            if(!empty($hcmConfigPropAry['insert'])){
                $message    = Lang::get('MINTMESH.hcm_details.insert_success');
            }  else {
                $message    = Lang::get('MINTMESH.hcm_details.update_success');
            }
            #get company HCMs details here
            $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcmId);
            $getHcmstatus = !empty($getHcmJobs[0]->status) ? $getHcmJobs[0]->status : 0;
            $getHcmstatus = !empty($getHcmstatus) ? 'enable' : 'disable';
            $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcmId);
            $hcmDetails = $this->formatHcmResult($getHcmList);
            #form return company HCMs details
            if(!empty($hcmDetails[0])){
                $value = $hcmDetails[0];
                $returnAry['hcm_id']   = $value['hcm_id'];
                $returnAry['hcm_name'] = $value['name'];
                $returnAry['hcm_access_token']  = $hcmAccesToken;
                $returnAry['hcm_refer_token']  = $hcmReferToken;
                $returnAry['hcm_status']   = $getHcmstatus; 
            }
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array($message));
        }else{
          $responseCode   = self::ERROR_RESPONSE_CODE;
          $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
          $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }
    
    public function formatHcmResult($getHcmList = array()){
        $mainAry = $hcmDetails = $return = array();
        if(!empty($getHcmList)){
            foreach ($getHcmList as $value) {
                $subAry = array();
                $subAry['hcm_id']   = $value->hcm_id;
                $subAry['name']     = $value->name;
                $subAry['status']   = $value->status;
                $subAry[$value->config_name] = $value->config_value;
                #arrange HCM details record here
                if(isset($mainAry['hcm_id']) && $mainAry['hcm_id']!= $subAry['hcm_id']){
                    $hcmDetails[] = $mainAry;
                    $mainAry = array();
                } 
                $mainAry = array_merge($mainAry, $subAry);     
            }
            #last record adding with result
            if(!empty($mainAry['hcm_id'])){
                $hcmDetails[] = $mainAry;
            }
            $return = $hcmDetails;
        }    
        return $hcmDetails;
    }
    
    public function getHcmList($input){
        
        $hcm_id = 1;
        $returnAry = $data = $getHcmList = array();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0;
        #get company HCMs List here
        $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcm_id);
        $getHcmstatus = !empty($getHcmJobs[0]->status)?$getHcmJobs[0]->status:0;
        $getHcmstatus = !empty($getHcmstatus)?'enable':'disable';
        $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcm_id);
        $hcmDetails = $this->formatHcmResult($getHcmList);
        foreach ($hcmDetails as $value) {
            $return = array();
            $return['hcm_id']   = $value['hcm_id'];
            $return['hcm_name'] = $value['name'];
            $return['hcm_url']  = $value['DCNAME'];
            $return['hcm_username'] = $value['USERNAME'];
            $return['hcm_password'] = $value['PASSWORD'];
            $return['hcm_status']   = $getHcmstatus;
            $returnAry[] = $return;
        }       
        
        if(!empty($returnAry)){
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_success')));
        }else{
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
          }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    } 
    
    //To get Zenefits HCM Details 
    public function getZenefitsHcmList($input){
        
        $hcm_id = 2;
        $returnAry = $data = $getHcmList = array();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0;
        #get company HCMs List here
        $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcm_id);
        $getHcmstatus = !empty($getHcmJobs[0]->status)?$getHcmJobs[0]->status:0;
        $getHcmstatus = !empty($getHcmstatus)?'enable':'disable';
        $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcm_id);
       $hcmDetails = $this->formatHcmResult($getHcmList);
        foreach ($hcmDetails as $value) {
            $return = array();
            $return['hcm_id']   = $value['hcm_id'];
            $return['hcm_name'] = $value['name'];
            $return['hcm_access_token']  = $value['Authorization'];
            $return['hcm_status']   = $getHcmstatus;
            $returnAry[] = $return;
        }       
        
        if(!empty($returnAry)){
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_success')));
        }else{
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
          }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    } 
    
    public function getHcmPartners(){
        
        $returnAry = $data = $hcmPartners = array();
        #get company HCM Partners List here
        $hcmPartners = $this->enterpriseRepository->getHcmPartners();
        if(!empty($hcmPartners)){
            #form company HCM Partners List here
            foreach ($hcmPartners as $value) {
                $return = array();
                $return['hcm_id']   = $value->hcm_id;
                $return['hcm_name'] = $value->name;
                $returnAry[] = $return;
            }       
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_success')));
        }else{
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }    
    
    public function companyIntegration($input){
        
        $data  =  array();
        $companyCode = !empty($input['company_code'])?$input['company_code']:'';
        #get Company Integration details here
        $checkIntegration = $this->enterpriseRepository->checkCompanyIntegration($companyCode);
       if(!empty($checkIntegration))
       {
           $idpDataObj = !empty($checkIntegration[0])?$checkIntegration[0]:'';
           $data['idp_signin_url']      = $idpDataObj->idp_signin_url;
           $data['idp_signout_url']     = $idpDataObj->idp_signout_url;
           $data['idp_issuer']          = $idpDataObj->idp_issuer;
           $data['idp_cert']            = $idpDataObj->idp_cert;
           $data['status']              = $idpDataObj->status;
           $data['idp_file_content']    = $idpDataObj->idp_file_content;
           
           $responseCode   = self::SUCCESS_RESPONSE_CODE;
           $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
           $responseMessage= array('msg' => array(Lang::get('MINTMESH.company_integration.success')));
       } else {
           $responseCode   = self::ERROR_RESPONSE_CODE;
           $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
           $responseMessage= array('msg' => array(Lang::get('MINTMESH.company_integration.failure')));
       }
       return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false); 
    }
    
    /**
     * enterprise Contacts List.
     *
     * @return Response
     */
    public function companyAllContacts($input) {
        $params = $data = $returnResult =  $returnData =array();
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser(); //get the logged in user details
        $params['user_id'] = $this->loggedinUserDetails->id;
        $params['company_id'] = $input['company_id'];
        $params['bucket_id'] = !empty($input['bucket_id']) ? $input['bucket_id'] : 0;
        $params['page_no'] = !empty($input['page_no']) ? $input['page_no'] : 0;
        $params['search'] = !empty($input['search']) ? $input['search'] : 0;
        $params['sort'] = !empty($input['sort']) ? $input['sort'] : '';
        $resultsSet = $this->enterpriseRepository->getCompanyAllContacts($params); //get the import contact list
        if ($resultsSet) {
            foreach($resultsSet['Contacts_list'] as $k=>$v){
                $returnData['firstname'] = $v->firstname;
                $returnData['lastname'] = $v->lastname;
                $returnData['emailid'] = $v->emailid;
                $returnData['status'] = 'Active';
                $returnResult[] = $returnData;
            }
            if(!empty($returnResult)){
            $data = $returnResult;
            $responseCode = self::SUCCESS_RESPONSE_CODE;
            $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_success'));
            }else{
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $message = array(Lang::get('MINTMESH.enterprise.retrieve_failure'));
                $data = array();
            }
        } else {
            $responseCode = self::ERROR_RESPONSE_CODE;
            $responseMsg = self::ERROR_RESPONSE_MESSAGE;
            $message = array(Lang::get('MINTMESH.enterprise.retrieve_failure'));
            $data = array();
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data,false);
    }
    
    public function addConfiguration($input) {
        
        $inputData = $data = array();
        $companyCode = !empty($input['company_code'])?$input['company_code']:'';
        $this->loggedinUserDetails = $this->referralsGateway->getLoggedInUser();
        #get Company Details By Code
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        
        $inputData['company_code']  = $companyCode;
        $inputData['user_id']       = $this->loggedinUserDetails->id;
        $inputData['company_id']    = $companyDetails[0]->id;
        $inputData['signin_url']    = !empty($input['signin_url'])?$input['signin_url']:'';
        $inputData['signout_url']   = !empty($input['signout_url'])?$input['signout_url']:'';
        $inputData['idp_issuer']    = !empty($input['idp_issuer'])?$input['idp_issuer']:'';
        $inputData['status']        = !empty($input['status'])?$input['status']:0;
        $inputData['createdAt']     = gmdate("Y-m-d H:i:s");
//        $inputData['white_listing'] = $input['white_listing'];
        if (isset($input['certificate_path']) && !empty($input['certificate_path'])) {
         #upload the file
         $this->userFileUploader->source        =  $input['certificate_path'];
         $this->userFileUploader->destination   = Config::get('constants.S3BUCKET_FILE');
         $renamedFileName = $this->userFileUploader->uploadToS3BySource($input['certificate_path']);
            $certificateOrgName = $input['certificate_org_name'];
            $getFileContents    = !empty($renamedFileName)?file_get_contents($renamedFileName):'';
            $inputData['certificate']       = $renamedFileName;
            $inputData['idp_file_content']  = $getFileContents;
            $inputData['idp_file_name']     = $certificateOrgName;
        }
        if (isset($input['certificate_path_s3']) && !empty($input['certificate_path_s3'])) {
            
            $renamedFileName    = $input['certificate_path_s3'];
            $certificateOrgName = $input['certificate_org_name'];
            $getFileContents    = !empty($renamedFileName)?file_get_contents($renamedFileName):'';
            $inputData['certificate']       = $renamedFileName;
            $inputData['idp_file_content']  = $getFileContents;
            $inputData['idp_file_name']     = $certificateOrgName;
        }
        if($input['action'] == 'add'){
        $addedConfiguration = $this->enterpriseRepository->integrateCompany($inputData);
            if(!empty($addedConfiguration)){
                
                $intDataObj = !empty($addedConfiguration[0])?$addedConfiguration[0]:'';
                $data['id'] = $intDataObj->id;
                $data['status']         = $intDataObj->status;
                $data['signin_url']     = $intDataObj->idp_signin_url;
                $data['signout_url']    = $intDataObj->idp_signout_url;
                $data['idp_issuer']     = $intDataObj->idp_issuer;
                $data['certificate']    = $intDataObj->idp_cert;
                $data['idp_file_name']  = $intDataObj->idp_file_name;
                
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.add_configuration.success')));
            }else{
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array(Lang::get('MINTMESH.add_configuration.failure'));
                $data = array();
            }
            
        }else{
            
            $inputData['id'] = $input['id'];
            $updateConfiguration = $this->enterpriseRepository->updateConfiguration($inputData); 
            if(!empty($updateConfiguration)){
                
                $intDataObj = !empty($updateConfiguration[0])?$updateConfiguration[0]:'';
                $data['id'] = $intDataObj->id;
                $data['status']         = $intDataObj->status;
                $data['signin_url']     = $intDataObj->idp_signin_url;
                $data['signout_url']    = $intDataObj->idp_signout_url;
                $data['idp_issuer']     = $intDataObj->idp_issuer;
                $data['certificate']    = $intDataObj->idp_cert;
                $data['idp_file_name']  = $intDataObj->idp_file_name;
                
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.edit_configuration.success')));
            }else{
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array(Lang::get('MINTMESH.edit_configuration.failure'));
                $data = array();
            }
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);  
    }
    
    public function getConfiguration($input) {
        
        $data = array(); 
        $companyCode = !empty($input['company_code'])?$input['company_code']:'';
        $configurationDetails = $this->enterpriseRepository->checkCompanyIntegration($companyCode);
        
        if(!empty($configurationDetails)){
                $idpDataObj = !empty($configurationDetails[0])?$configurationDetails[0]:'';
                $data['id'] = $idpDataObj->id;
                $data['status']         = $idpDataObj->status;
                $data['signin_url']     = $idpDataObj->idp_signin_url;
                $data['signout_url']    = $idpDataObj->idp_signout_url;
                $data['idp_issuer']     = $idpDataObj->idp_issuer;
                $data['certificate']    = $idpDataObj->idp_cert;
                $data['idp_file_name']  = $idpDataObj->idp_file_name;
                
                $responseCode   = self::SUCCESS_RESPONSE_CODE;
                $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
                $responseMessage= array('msg' => array(Lang::get('MINTMESH.configuration_details.success')));
           }else{
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $responseMessage = array(Lang::get('MINTMESH.configuration_details.failure'));
                $data = array();
           }
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);  
        }
        
    public function testLic($input) {
            
            $companiesData = $this->enterpriseRepository->getAllCompaniesData();
            foreach ($companiesData as $key => $value) {
                $companyId    = $value->id;
                $employeesNo  = $value->employees_no;
                $created_at   = $value->created_at;
                
                $startDate      = $created_at;
                $endDate        = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($startDate)));
                $this->enterpriseRepository->addCompanySubscriptionsLog($companyId, $employeesNo, $startDate, $endDate);
            }
            echo 'done';
          exit;  
        }
        
    public function addEditIcimsHcm($input){
        
        $message    = '';
        $returnAry  = $data = $hcmAry = $hcmConfigPropAry = array();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0; 
        $hcmId          = !empty($input['hcm_id'])?$input['hcm_id']:'';
        $hcmUrl         = !empty($input['hcm_url'])?$input['hcm_url']:'';
        $hcmUsername    = !empty($input['hcm_username'])?$input['hcm_username']:'';
        $hcmPassword    = !empty($input['hcm_password'])?$input['hcm_password']:'';
        $hcmRunStatus   = !empty($input['hcm_run_status'])?$input['hcm_run_status']:'';
        #form the input for add and edit HCM config details
        $hcmAry[0]['name']     = 'DCNAME';
        $hcmAry[0]['value']    = $hcmUrl;
        $hcmAry[1]['name']     = 'USERNAME';
        $hcmAry[1]['value']    = $hcmUsername;
        $hcmAry[2]['name']     = 'PASSWORD';
        $hcmAry[2]['value']    = $hcmPassword;
        #process HCM config Properties here
        $hcmConfigPropAry   = $this->enterpriseRepository->setHcmConfigProperties($hcmId, $companyId, $hcmAry);
        if(!empty($hcmConfigPropAry)){
            if($hcmRunStatus){
                #get Company Hcm Jobs for company_hcm_jobs_id
                $checkHcmJobs = $this->enterpriseRepository->getCompanyHcmJobs($hcmId, $companyId);
                $companyHcmJobsId = !empty($checkHcmJobs[0]->company_hcm_jobs_id)?$checkHcmJobs[0]->company_hcm_jobs_id:'';
                if($companyHcmJobsId){
                    #update HCM schedule run status here
                    $hcmConfigPropAry   = $this->enterpriseRepository->updateHcmRunStatus($companyHcmJobsId, $hcmRunStatus);
                }    
            }
            #form the success messahe here
            if(!empty($hcmConfigPropAry['insert'])){
                $message    = Lang::get('MINTMESH.hcm_details.insert_success');
            }  else {
                $message    = Lang::get('MINTMESH.hcm_details.update_success');
            }
            #get company HCMs details here
            $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcmId);
            $getHcmstatus = !empty($getHcmJobs[0]->status)?$getHcmJobs[0]->status:0;
            $getHcmstatus = !empty($getHcmstatus)?'enable':'disable';
            $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcmId);
            $hcmDetails = $this->formatHcmResult($getHcmList);
            #form return company HCMs details
            if(!empty($hcmDetails[0])){
                $value = $hcmDetails[0];
                $returnAry['hcm_id']   = $value['hcm_id'];
                $returnAry['hcm_name'] = $value['name'];
                $returnAry['hcm_url']  = $value['DCNAME'];
                $returnAry['hcm_username'] = $value['USERNAME'];
                $returnAry['hcm_password'] = $value['PASSWORD'];
                $returnAry['hcm_status']   = $getHcmstatus; 
            }
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array($message));
        }else{
          $responseCode   = self::ERROR_RESPONSE_CODE;
          $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
          $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
        }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }  
         //To get Icims HCM Details 
    public function getIcimsHcmList($input){
        
        $hcm_id = 3;
        $returnAry = $data = $getHcmList = array();
        $companyCode    = !empty($input['company_code'])?$input['company_code']:'';
        #get the logged in user company details with company code here
        $companyDetails = $this->enterpriseRepository->getCompanyDetailsByCode($companyCode);
        $companyId      = !empty($companyDetails[0]->id)?$companyDetails[0]->id:0;
        #get company HCMs List here
        $getHcmJobs = $this->enterpriseRepository->checkCompanyHcmJobs($companyId, $hcm_id);
        $getHcmstatus = !empty($getHcmJobs[0]->status)?$getHcmJobs[0]->status:0;
        $getHcmstatus = !empty($getHcmstatus)?'enable':'disable';
        $getHcmList = $this->enterpriseRepository->getHcmList($companyId, $hcm_id);
       $hcmDetails = $this->formatHcmResult($getHcmList);
        foreach ($hcmDetails as $value) {
             $return = array();
            $return['hcm_id']   = $value['hcm_id'];
            $return['hcm_name'] = $value['name'];
            $return['hcm_url']  = $value['DCNAME'];
            $return['hcm_username'] = $value['USERNAME'];
            $return['hcm_password'] = $value['PASSWORD'];
            $return['hcm_status']   = $getHcmstatus;
            $returnAry[] = $return;
        }       
        
        if(!empty($returnAry)){
            $data = $returnAry;
            $responseCode   = self::SUCCESS_RESPONSE_CODE;
            $responseMsg    = self::SUCCESS_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_success')));
        }else{
            $responseCode   = self::ERROR_RESPONSE_CODE;
            $responseMsg    = self::ERROR_RESPONSE_MESSAGE;
            $responseMessage= array('msg' => array(Lang::get('MINTMESH.hcm_details.retrieve_failure')));
          }
        return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $responseMessage, $data, false);    
    }
         
    
    public function unsolicitedForOldCompanies() {
        // checking for old companies without unsolicited nodes
        $checkUnsolicitedCompanies = $this->neoEnterpriseRepository->checkUnsolicitedCompanies();
        if(!empty($checkUnsolicitedCompanies)){
            foreach($checkUnsolicitedCompanies as $companies){
                //Adding unsolicited node and creating relation with company
               $this->neoEnterpriseRepository->createUnsolicitedAndCompanyRelation($companies[0]->companyCode);
               echo $companies[0]->companyCode.',';
            }
        }
        
    }
}

?>

