<?php namespace Mintmesh\Gateways\API\Payment;

/**
 * This is the Social network contacts Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */

use Mintmesh\Services\Validators\API\Payment\PaymentValidator ;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;
use Mintmesh\Services\APPEncode\APPEncode ;
use Mintmesh\Repositories\API\Payment\PaymentRepository;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Services\Emails\API\User\UserEmailManager ;
use Lang,URL;
use Config;
use  \Braintree_ClientToken;
use  \Braintree_Transaction;
use Illuminate\Support\Facades\Hash;

class PaymentGateway {
        
    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';

    protected $authorizer, $appEncodeDecode, $paymentValidator,$paymentRepository,$userRepository,$neoUserRepository;
    protected $commonFormatter, $loggedinUserDetails,$referralsRepository, $userGateway;
    protected $userEmailManager;
	public function __construct(PaymentValidator $paymentValidator, 
                                    Authorizer $authorizer,
                                    CommonFormatter $commonFormatter,
                                    APPEncode $appEncodeDecode,
                                    UserRepository $userRepository,
                                    PaymentRepository $paymentRepository,
                                    ReferralsRepository $referralsRepository,
                                    UserGateway $userGateway,
                                    NeoUserRepository $neoUserRepository,
                                    UserEmailManager $userEmailManager) {
            //ini_set('max_execution_time', 500);
		$this->paymentValidator = $paymentValidator;
                $this->authorizer = $authorizer;
                $this->commonFormatter = $commonFormatter ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->paymentRepository = $paymentRepository ;
                $this->userRepository = $userRepository;
                $this->userEmailManager = $userEmailManager ;
                $this->referralsRepository =  $referralsRepository ;
                $this->userGateway=$userGateway ;
                $this->neoUserRepository = $neoUserRepository;
	}
        
        //validation on payout input
        public function validatePayoutInput($input)
        {
            return $this->doValidation('payout','MINTMESH.payment.valid');
        }
        
        //validation on payout input
        public function validateManualPayoutInput($input)
        {
            return $this->doValidation('manualPayout','MINTMESH.payment.valid');
        }
        
        //validation on  payment transaction
        public function validateTransactionInput($input)
        {
            return $this->doValidation('transaction_input','MINTMESH.payment.valid');
        }
        
        //validation on braintree payment transaction
        public function validateBTTransactionInput($input)
        {
            return $this->doValidation('braintree_tran','MINTMESH.payment.valid');
        }

        //validation on  user bank details
        public function validatesaveUserBank($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->paymentValidator->passes('user_bank_details_save')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.payment.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->paymentValidator->getErrors(), array()) ;
        }
        //validation on edit user bank details
        public function validateeditUserBank($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->paymentValidator->passes('user_bank_details_edit')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.payment.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->paymentValidator->getErrors(), array()) ;
        }
        //validation on delete user bank details
        public function validatedeleteUserBank($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->paymentValidator->passes('user_bank_details_delete')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.payment.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->paymentValidator->getErrors(), array()) ;
        }
                
        public function generateBtClientToken()
        {
            $clientToken = $this->getBTClientToken();
            if (!empty($clientToken))
            {
                $data = array("client_token"=>$clientToken);
                $paymentInput = array();
                $paymentInput['token'] = $clientToken;
                $paymentInput['bill'] = '';
                $paymentInput['mm_transaction_id'] = !empty($input['mm_transaction_id'] )?$input['mm_transaction_id'] :'';
                $paymentInputRes = $this->paymentRepository->insertGatewayInput($paymentInput);
                return $this->commonFormatter->formatResponse(200, "success", Lang::get('MINTMESH.payment.success'), $data) ;
            }
            else
            {
                return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.error'), array()) ;
            }
        }
        public function citrusBillGenerator($input)
        {
            $transactionDetails = $this->paymentRepository->getTransactionById($input['mm_transaction_id']) ;
            if (!empty($transactionDetails))
            {
                $bill = $this->generateCitrusBill($transactionDetails->amount, $transactionDetails->comission_percentage, $input['mm_transaction_id']) ;
                if (!empty($bill))
                {
                    $paymentInput = array();
                    $paymentInput['token'] = '';
                    $paymentInput['bill'] = !empty($bill)?json_encode($bill):'';
                    $paymentInput['mm_transaction_id'] = !empty($input['mm_transaction_id'] )?$input['mm_transaction_id'] :'';
                    $paymentInputRes = $this->paymentRepository->insertGatewayInput($paymentInput);
                    return $bill ;
                    //$data = array("bill_details"=>$clientToken);
                    //return $this->commonFormatter->formatResponse(200, "success", Lang::get('MINTMESH.payment.success'), $data) ;
                }
                else
                {
                    return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.error'), array()) ;
                }
            }
            else
            {
                return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.error'), array()) ;
            }
        }
        public function saveUserBank($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails) {
                $post=array();
                $post['user']=$this->loggedinUserDetails->id ;
                $post['bank_name'] = $input['bank_name'];
                $post['account_name'] = $input['account_name'];
                $post['account_number'] = $input['account_number'];
                $post['ifsc_code'] = $input['ifsc_code'];
                $post['address'] = !empty($input['address'])?$input['address']:"";
                //check bank details exist
                $checkUserBank = $this->paymentRepository->checkUserBank($post);
                if(empty($checkUserBank)) {
                    // save user bank details
                    $saveUserBank = $this->paymentRepository->saveUserBank($post);
                    if (!empty($saveUserBank)) {
                        $message = array('msg'=>array(Lang::get('MINTMESH.save_user_bank.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                    } else {
                        $message = array('msg'=>array(Lang::get('MINTMESH.save_user_bank.failed')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.save_user_bank.details_already_exist')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.save_user_bank.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        public function editUserBank($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails) {
                $post=array();
                $post['user']=$this->loggedinUserDetails->id ;
                $post['bank_id'] = $input['bank_id'];
                $post['bank_name'] = $input['bank_name'];
                $post['account_name'] = $input['account_name'];
                $post['account_number'] = $input['account_number'];
                $post['ifsc_code'] = $input['ifsc_code'];
                $post['address'] = $input['address'];
                //check bank details exist
                $checkUserBank = $this->paymentRepository->checkUserBank($post);
                if(empty($checkUserBank)) {
                    // save user bank details
                    $editUserBank = $this->paymentRepository->editUserBank($post);
                    if (!empty($editUserBank)) {
                        $message = array('msg'=>array(Lang::get('MINTMESH.edit_user_bank.success')));
                        return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                    } else {
                        $message = array('msg'=>array(Lang::get('MINTMESH.edit_user_bank.failed')));
                        return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                    }
                } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.save_user_bank.details_already_exist')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.edit_user_bank.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        public function deleteUserBank($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails) {
                $post=array();
                $post['bank_id'] = $input['bank_id'];
                // delete user bank details
                $deleteUserBank = $this->paymentRepository->deleteUserBank($post);
                if (!empty($deleteUserBank)) {
                    $message = array('msg'=>array(Lang::get('MINTMESH.delete_user_bank.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, array()) ;
                } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.delete_user_bank.failed')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.delete_user_bank.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        public function listUserBanks($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails) {
                $post=array();
                $post['user_id'] = $this->loggedinUserDetails->id;
                // delete user bank details
                $listUserBanks = $this->paymentRepository->listUserBanks($post);
                if (!empty($listUserBanks)) {
                    $message = array('msg'=>array(Lang::get('MINTMESH.list_user_banks.success')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $listUserBanks) ;
                } else {
                    $message = array('msg'=>array(Lang::get('MINTMESH.list_user_banks.nobanksadded')));
                    return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $listUserBanks) ;
                }
            } else {
                $message = array('msg'=>array(Lang::get('MINTMESH.list_user_banks.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        public function getBTClientToken()
        {
            return Braintree_ClientToken::generate();
        }
        public function processBTTransaction($input=array())
        {
            $is_self_referred = 0;
            $loggedinUserDetails = $this->getLoggedInUser();
            $amount = $input['amount'];
            $nonce = $input['nonce'] ;
            if ($amount > 0)
            {
                $result = Braintree_Transaction::sale([
                'amount' => $amount,
                'paymentMethodNonce' => $nonce,
                'options' => [
                    'submitForSettlement' => True
                  ]
                ]);
                if (!empty($result->success))
                {
                    $input['status'] = Config::get('constants.PAYMENTS.STATUSES.SUCCESS') ;
                }
                else
                {
                    $input['status'] = Config::get('constants.PAYMENTS.STATUSES.FAILED') ;
                }
                $updtStatus = $this->paymentRepository->updatePaymentTransaction($input) ;

                if (!empty($result->success))
                {
                    
                    
                    $transactionDetails = $this->paymentRepository->getTransactionById($input['mm_transaction_id']);
                    $emailTransaction = $this->paymentRepository->insertTransactionIdBT($transactionDetails->id);
                    if ($updtStatus && $input['status'] == Config::get('constants.PAYMENTS.STATUSES.SUCCESS'))
                    {
                        //update balance cash info
                        $sqlUser = $this->userRepository->getUserByEmail($transactionDetails->to_user);
                        $mysqlUserId = $sqlUser->id ;
                        //\Log::info("-----in success brantree ------");
                        $updtBalanceInfo =  $this->updateBalanceCashInfo($transactionDetails->amount, Config::get('constants.PAYMENTS.CURRENCY.USD'), $transactionDetails->to_user, $mysqlUserId);
                    }
                    //update post payment status
                    if (!empty($transactionDetails))
                    {
                        $neoLoggedinUserDetails = $this->neoUserRepository->getNodeByEmailId($transactionDetails->from_user) ;
                        //send email to user saying user payment is success
                        $successSupportTemplate = Lang::get('MINTMESH.email_template_paths.payment_success_user');
                        $receipientEmail = $neoLoggedinUserDetails->emailid;
                        $res = ( $transactionDetails->comission_percentage / 100) * $transactionDetails->amount;
                        $tax = round($res, 2); 
                        $total = $transactionDetails->amount+$tax;
                        $emailData = array('name' => $neoLoggedinUserDetails->fullname, 
                                            'transaction_id' => $emailTransaction[0]->last_id,
                                            'date_of_payment' => date('d F Y'),
                                            'cost' => $transactionDetails->amount,
                                            'tax' => $tax,
                                            'total' => $total,
                                            'is_doller' => 1,
                                            'email'=>$neoLoggedinUserDetails->emailid);
                        $emailiSent = $this->sendPaymentSuccessEmailToUser($successSupportTemplate, $receipientEmail, $emailData);

//                        $successSupportTemplateServiceFee = Lang::get('MINTMESH.email_template_paths.payment_servicfee_success_user');
//                        $emailiSent = $this->sendPaymentSuccessEmailToUser($successSupportTemplateServiceFee, $receipientEmail, $emailData);
                       if ($transactionDetails->to_user == $transactionDetails->for_user)
                       {
                           $is_self_referred = 1 ;
                       }

                        $postUpdateDetails= $this->referralsRepository->updatePostPaymentStatus($transactionDetails->relation_id, Config::get('constants.PAYMENTS.STATUSES.SUCCESS'), $is_self_referred);
                        //send notifications to the respective people
                        $sendNotes = $this->processPostPaymentCompletion($transactionDetails);
                        //send resume attachment to p1 if post type is find_candidate
                        if (!empty($postUpdateDetails[0])){
                            if (!empty($postUpdateDetails[0][1]->resume_path) && $postUpdateDetails[0][0]->service_scope == 'find_candidate'){
                                $this->userGateway->sendAttachmentResumeToP1($transactionDetails->to_user, $loggedinUserDetails->emailid, $postUpdateDetails[0][1]->resume_path);
                            }
                        }
                    }
                    //log brain tree
                    $pt_input = array();
                    $pt_input['response'] = $result ;
                    $pt_input['mm_transaction_id'] = $input['mm_transaction_id'] ;
                    $pt_res = $this->paymentRepository->logPayment($pt_input);
                    return $this->commonFormatter->formatResponse(200, "success", Lang::get('MINTMESH.payment.success'), array()) ;
                }
                else
                {
                    //log brain tree
                    $pt_input = array();
                    $pt_input['response'] = $result ;
                    $pt_input['mm_transaction_id'] = $input['mm_transaction_id'] ;
                    $pt_res = $this->paymentRepository->logPayment($pt_input);
                    return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.failed'), array()) ;
                }
            }
            else
            {
                return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.invalid_amount'), array()) ;
            }
            
        }
        
        public function sendPaymentSuccessEmailToUser($templatePath, $emailid, $data)
        {
           $this->userEmailManager->templatePath = $templatePath;
            $this->userEmailManager->emailId = $emailid;
            $dataSet = array();
           // $dataSet['name'] = "shweta" ;
           // $dataSet['email'] = "shwetapazarey@gmail.com";
            if (!empty($data))
            {
                foreach ($data as $k=>$v)
                {
                    $dataSet[$k] = $v ;
                }
            }
            /*$dataSet['name'] = $input['firstname'];
            $dataSet['link'] = $appLink ;
            $dataSet['email'] = $input['emailid'] ;*/

           // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
            $this->userEmailManager->dataSet = $dataSet;
            if(empty($dataSet['is_doller']) && $templatePath == Lang::get('MINTMESH.email_template_paths.payment_success_user')){
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.paymentSuccess_citrus');
            } else {
                $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.paymentSuccess_braintree');
            }
            $this->userEmailManager->name = 'user';
            return $email_sent = $this->userEmailManager->sendMail();
        }
        
        public function getLoggedInUser()
        {
            try
            {
                $resourceOwnerId = $this->authorizer->getResourceOwnerId();
                return $this->userRepository->getUserById($resourceOwnerId);
            }
            catch(Exception $e)
            {
                return false ;
            }
            
            
        }
        
        public function generateTansactionId($extraParam = "")
        {
            return uniqid();
            //return  md5(time().$extraParam) ;
        }
        
        public function generateCitrusBill($amount=0, $percentage=0,$transactionId=0)
        {
            $totalAmount = 0 ;
            if (!empty($amount) && !empty($percentage))
            {
               $totalAmount = $this->calculateTotalAmount($percentage, $amount);
               /*$perAmount = ($percentage/100)*$amount;
               $totalAmount = $amount+$perAmount ;*/
            }
            if (!empty($amount))
            {
                $totalAmount = !empty($totalAmount)?$totalAmount:$amount ;
                $access_key = Config::get('constants.CITRUS.ACCESS_KEY') ;//"QZVFK264G28QZ6NJ43KB"; //put your own access_key - found in admin panel     
                $secret_key = Config::get('constants.CITRUS.SECRET_KEY'); //put your own secret_key - found in admin panel     
                $return_url = URL::to("/")."/citrusReturnUrl.php"; //put your own return_url.php here.    
                //$return_url = URL::to("/")."/v1/payment/citrus_return_url";
                //$return_url = "http://202.63.105.86/mintmesh/redirectURL.php";
                $notifyUrl = URL::secure("/")."/v1/payment/citrus_transaction" ;
                //$txn_id = time() . rand(10000,99999);   
                $txn_id = $transactionId ;
                $value = $totalAmount; //Charge amount is in INR by default    
                $data_string = "merchantAccessKey=" . $access_key."&transactionId="  . $txn_id   . "&amount="         . $value;    
                $signature = hash_hmac('sha1', $data_string, $secret_key);    
                $amount = array('value' => $value, 'currency' => 'INR');    
                $bill = array('merchantTxnId' => $txn_id,      
                              'amount' => $amount,        
                              'requestSignature' => $signature,         
                              'merchantAccessKey' => $access_key,        
                              'returnUrl' => $return_url,
                              'notifyUrl' => $notifyUrl
                               );     
                return $bill ;
            }
            
        }
        
        public function processCitrusTransaction($input)
        {
            $is_self_referred = 0;
            $secret_key = Config::get('constants.CITRUS.SECRET_KEY');     
            $input['mm_transaction_id'] = $txID = !empty($input['TxId'])?$input['TxId']:'';
            $TxStatus = !empty($input['TxStatus'])?$input['TxStatus']:'';
            $amount = !empty($input['amount'])?$input['amount']:0;
            $pgTxnNo = !empty($input['pgTxnNo'])?$input['pgTxnNo']:'';
            $issuerRefNo = !empty($input['issuerRefNo'])?$input['issuerRefNo']:'';
            $authIdCode = !empty($input['authIdCode'])?$input['authIdCode']:'';
            $firstName = !empty($input['firstName'])?$input['firstName']:'';
            $lastName = !empty($input['lastName'])?$input['lastName']:'';
            $pgRespCode = !empty($input['pgRespCode'])?$input['pgRespCode']:'';
            $addressZip = !empty($input['addressZip'])?$input['addressZip']:'';
              $verification_data =  $txID                         
                                    . $TxStatus
                                    . $amount
                                    . $pgTxnNo
                                    . $issuerRefNo                        
                                    . $authIdCode                        
                                    . $firstName                        
                                    . $lastName
                                    . $pgRespCode                        
                                    . $addressZip;     
              $a = implode(" , ",$input);
              $b = implode(" , ",  array_keys($input));
                $signature = hash_hmac('sha1', $verification_data, $secret_key);
            //get transaction details with added percentage
            $actualAmount = 0 ;
            $tranDetails = $this->paymentRepository->getTransactionById($txID);
            if (!empty($amount) && !empty($tranDetails))
            {
               $perAmount = ($tranDetails->comission_percentage/100)*$tranDetails->amount;
               $actualAmount = $tranDetails->amount+$perAmount ;
            }
            //log in payment _logs
              if (strtolower($TxStatus) == strtolower(Config::get('constants.PAYMENTS.STATUSES.SUCCESS')) && $amount==$actualAmount)
                {
                  //get transaction details
                  $transactionDetails = $tranDetails;
                  $input['status'] = Config::get('constants.PAYMENTS.STATUSES.SUCCESS') ;
                  $updtStatus = $this->paymentRepository->updatePaymentTransaction($input) ;
                  if ($updtStatus)
                  {
                      
                      
                      //update balance cash info
                      $sqlUser = $this->userRepository->getUserByEmail($transactionDetails->to_user);
                      $mysqlUserId = $sqlUser->id ;
                      $updtBalanceInfo =  $this->updateBalanceCashInfo($transactionDetails->amount, Config::get('constants.PAYMENTS.CURRENCY.INR'), $tranDetails->to_user, $mysqlUserId);
                  }
                  
                  if (!empty($transactionDetails))
                  {
                        $emailTransaction = $this->paymentRepository->insertTransactionIdCitrus($transactionDetails->id);
                        //send email to user saying user payment is success
                        $successSupportTemplate = Lang::get('MINTMESH.email_template_paths.payment_success_user');
                        $loggedinUserDetails = $this->neoUserRepository->getNodeByEmailId($transactionDetails->from_user) ;
                        $receipientEmail = $loggedinUserDetails->emailid;
                        $res = ( $transactionDetails->comission_percentage / 100) * $transactionDetails->amount;
                        $tax = round($res, 2); 
                        $total = $transactionDetails->amount+$tax;
                        $emailData = array('name' => $loggedinUserDetails->fullname, 
                                            'transaction_id' => $emailTransaction[0]->last_id,
                                            'date_of_payment' => date('d F Y'),
                                            'cost' => $transactionDetails->amount,
                                            'tax' => $tax,
                                            'total' => $total,
                                            'is_doller' => 0,
                                            'email'=>$loggedinUserDetails->emailid,
                                            'location'=>$loggedinUserDetails->location,
                                            'phone_country_name'=>$loggedinUserDetails->phone_country_name);
                        $emailiSent = $this->sendPaymentSuccessEmailToUser($successSupportTemplate, $receipientEmail, $emailData);
                        if ($transactionDetails->to_user == $transactionDetails->for_user)
                       {
                           $is_self_referred = 1 ;
                       }

                        $successSupportTemplateServiceFee = Lang::get('MINTMESH.email_template_paths.payment_servicfee_success_user');
                        $emailiSent = $this->sendPaymentSuccessEmailToUser($successSupportTemplateServiceFee, $receipientEmail, $emailData);
                        
                        $postUpdateDetails = $this->referralsRepository->updatePostPaymentStatus($transactionDetails->relation_id,Config::get('constants.PAYMENTS.STATUSES.SUCCESS'), $is_self_referred);

                        $sendNotes = $this->processPostPaymentCompletion($transactionDetails);
                        //send resume attachment to p1 if post type is find_candidate
                        if (!empty($postUpdateDetails[0])){
                            if (!empty($postUpdateDetails[0][1]->resume_path) && $postUpdateDetails[0][0]->service_scope == 'find_candidate'){
                                $this->userGateway->sendAttachmentResumeToP1($transactionDetails->to_user, $loggedinUserDetails->emailid, $postUpdateDetails[0][1]->resume_path);
                            }
                        }
                  }
                    $pt_input = array();
                    $pt_input['response'] = json_encode($input) ;
                    $pt_input['mm_transaction_id'] = $input['TxId'] ;
                    $pt_res = $this->paymentRepository->logPayment($pt_input);
                    $data = $input ;
                    //$data['citrus_success']=1;
                    return $this->commonFormatter->formatResponse(200, "success", Lang::get('MINTMESH.payment.success'), $data) ;								      
                }										    
              else {
                  $pt_input = array();
                  $pt_input['response'] = json_encode($input) ;
                  $pt_input['mm_transaction_id'] = $input['TxId'] ;
                  $pt_res = $this->paymentRepository->logPayment($pt_input);
                  $input['status'] = Config::get('constants.PAYMENTS.STATUSES.FAILED')  ;
                  $this->paymentRepository->updatePaymentTransaction($input) ;
                  $data=$input ;
                  return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.failed'), $data) ;  
              }
        }
        
        public function processPostPaymentCompletion($transactionDetails)
        {
            if (!empty($transactionDetails))
            {
                $loggedinsqlUser = $this->userRepository->getUserByEmail($transactionDetails->from_user);
                $loggedinmysqlId = $loggedinsqlUser->id ;
                $loggedinUserDetails = $this->userRepository->getUserById($loggedinmysqlId);
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($transactionDetails->from_user) ;
                
                //$loggedinUserDetails = $this->getLoggedInUser();
                //$neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                //send notification to the person who get referred to the post
                $sqlUser = $this->userRepository->getUserByEmail($transactionDetails->to_user);
                $mysqlId = $sqlUser->id ;
                $referred_by_details = $this->userRepository->getUserById($mysqlId);
                $referred_by_neo_user = $this->neoUserRepository->getNodeByEmailId($transactionDetails->to_user) ;

                $this->userRepository->logLevel(3, $transactionDetails->to_user, $transactionDetails->from_user, $transactionDetails->for_user,Config::get('constants.POINTS.SEEK_REFERRAL'));
                if($transactionDetails->for_user == $transactionDetails->to_user) {
                    $this->userGateway->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $transactionDetails->to_user, 24, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->for_user),1) ;
                } else {
                    $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $transactionDetails->for_user, 11, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->from_user),1) ;
                    //send notification to via person
                    $this->userGateway->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $transactionDetails->to_user, 12, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->for_user),1) ;
                    //send battle card to u1 containing u3 details
                    $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $loggedinUserDetails->emailid, 20, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->for_user),1) ;
                }
                return true ;
            }
            else
            {
                return false ;
            }
            
        }
        
        //paypal payout
        public function paypalPayout($input=array())
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails) {
                //check user password
                if(Hash::check($input['password'],$loggedinUserDetails->password)) {
                    //get balance cash info
                    $balanceCashInfo = $this->paymentRepository->getbalanceCashInfo($loggedinUserDetails->emailid);
                    $balanceCash = !empty($balanceCashInfo->balance_cash)?$balanceCashInfo->balance_cash:0;
                    $payoutAmount = !empty($input['amount'])?$input['amount']:0;
                    if ($balanceCash >= $payoutAmount && !empty($payoutAmount)) {
                        $paypal_client_id = Config::get('constants.PAYPAL.CLIENT_ID');
                        $paypal_client_secret = Config::get('constants.PAYPAL.CLIENT_SECRET');
                        $paypal_item_id = $this->createPaypalItemId();
                        try{
                            $apiContext = new \PayPal\Rest\ApiContext(
                                new \PayPal\Auth\OAuthTokenCredential(
                                    $paypal_client_id,     // ClientID
                                    $paypal_client_secret      // ClientSecret
                                )
                            );
                            $apiContext->setConfig(
                                array(
                                    'mode' => Config::get('constants.PAYPAL.MODE'),
                                    'validation.level' => Config::get('constants.PAYPAL.VALIDATIONLEVEL'),
                                )
                            );
                            $senderbatchId = uniqid();
                            $payouts = new \PayPal\Api\Payout();
                            $senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();
                            $senderBatchHeader->setSenderBatchId($senderbatchId)
                                ->setEmailSubject(Lang::get('MINTMESH.payout.email_subject'));
                                        $senderItem = new \PayPal\Api\PayoutItem();
                            $senderItem->setRecipientType(Lang::get('MINTMESH.payout.receipient_type'))
                                ->setNote(Lang::get('MINTMESH.payout.email_note'))
                                ->setReceiver($input['paypal_emailid'])
                                ->setSenderItemId($paypal_item_id)
                                ->setAmount(new \PayPal\Api\Currency('{
                                                    "value":'.$input['amount'].',
                                                    "currency":"USD"
                                                }'));

                            $payouts->setSenderBatchHeader($senderBatchHeader)
                                ->addItem($senderItem);
                            $request = clone $payouts;
                            try{
                                $output = $payouts->createSynchronous($apiContext);
                                if (isset($output->batch_header->batch_status) && $output->batch_header->batch_status == 'SUCCESS') {
                                    $responseMessage = Lang::get('MINTMESH.payout.success');
                                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                                    $responseData = array();
                                    //send emails
                                    //email to mintmesh support
                                    $successSupportTemplate = Lang::get('MINTMESH.email_template_paths.payout_success_admin');
                                    $receipientEmail = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                                    $emailiSent = $this->sendPayoutEmailToMintMesh($successSupportTemplate, $receipientEmail, array());
                                    //email to user
                                    $successSupportTemplate = Lang::get('MINTMESH.email_template_paths.payout_success_user');
                                    $receipientEmail = $loggedinUserDetails->emailid;
                                    $userMailData = new \stdClass();
                                    
                                    $userMailData->amount = $payoutAmount;
                                    $emailiSent = $this->sendPayoutEmailToUser($successSupportTemplate, $receipientEmail, $userMailData);
                                    //log payout
                                    $payoutInput = array();
                                    $payoutInput['from_user'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                                    $payoutInput['amount'] = $input['amount'];
                                    $payoutInput['to_mintmesh_user']= $loggedinUserDetails->emailid;
                                    $payoutInput['to_provided_user']= $input['paypal_emailid'];
                                    $payoutInput['payout_types_id'] = 1;//paypal type
                                    $payoutInput['paypal_item_id'] = $paypal_item_id;//paypal item id
                                    $payoutInput['paypal_batch_id'] = $senderbatchId;//paypal batch id
                                    $payoutInput['status'] = $output->batch_header->batch_status ;
                                    $payoutInput['service_response'] = $this->appEncodeDecode->filterString($output) ;
                                    $payoutInput['bank_id'] = "";
                                    $payoutInput['payout_transaction_id'] = uniqid();

                                    $log = $this->paymentRepository->logPayout($payoutInput);

                                    //update balance cash info
                                    //edit balance cash info
                                    $balanceCash = $balanceCashInfo->balance_cash-$payoutAmount;
                                    $bid = $balanceCashInfo->id ;
                                    $inp = array();
                                    $inp['balance_cash'] = $balanceCash ;
                                    $u = $this->paymentRepository->editBalanceCash($bid, $inp);
                                    $responseData=array("remainingCash"=>$balanceCash);
                                } else {

                                    $responseMessage = Lang::get('MINTMESH.payout.error');
                                    $responseCode = self::ERROR_RESPONSE_CODE;
                                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                                    $responseData = array();
                                    //send emails
                                    //email to mintmesh support
                                    $failureSupportTemplate = Lang::get('MINTMESH.email_template_paths.payout_failure_admin');
                                    $receipientEmail = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                                    $emailiSent = $this->sendPayoutEmailToMintMesh($failureSupportTemplate, $receipientEmail, array());
                                    //email to user
                                    $failureSupportTemplate = Lang::get('MINTMESH.email_template_paths.payout_failure_user');
                                    $receipientEmail = $loggedinUserDetails->emailid;
                                    $emailiSent = $this->sendPayoutEmailToUser($failureSupportTemplate, $receipientEmail, array());
                                    //log payout
                                    $payoutInput = array();
                                    $payoutInput['from_user'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                                    $payoutInput['amount'] = $input['amount'];
                                    $payoutInput['to_mintmesh_user']= $loggedinUserDetails->emailid;
                                    $payoutInput['to_provided_user']= $input['paypal_emailid'];
                                    $payoutInput['payout_types_id'] = 1;//paypal type
                                    $payoutInput['paypal_item_id'] = $paypal_item_id;//paypal item id
                                    $payoutInput['paypal_batch_id'] = $senderbatchId;//paypal batch id
                                    $payoutInput['status'] = $output->batch_header->batch_status ;
                                    $payoutInput['service_response'] = $this->appEncodeDecode->filterString($output) ;
                                    $payoutInput['bank_id'] = "";
                                    $payoutInput['payout_transaction_id'] = uniqid();

                                    $log = $this->paymentRepository->logPayout($payoutInput);
                                }
                                /*echo $output->batch_header->batch_status;exit;
                                print_r($output);exit;
                                print_r($output->items[0]->errors);exit;*/

                            } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                                $responseMessage = Lang::get('MINTMESH.payout.error');
                                $responseCode = self::ERROR_RESPONSE_CODE;
                                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                                $responseData = array();
                                $message = array('msg'=>array($responseMessage));
                                //log payout
                                $payoutInput = array();
                                $payoutInput['from_user'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                                $payoutInput['amount'] = $input['amount'];
                                $payoutInput['to_mintmesh_user']= $loggedinUserDetails->emailid;
                                $payoutInput['to_provided_user']= $input['paypal_emailid'];
                                $payoutInput['payout_types_id'] = 1;//paypal type
                                $payoutInput['paypal_item_id'] = $paypal_item_id;//paypal item id
                                $payoutInput['paypal_batch_id'] = $senderbatchId;//paypal batch id
                                $payoutInput['status'] = Config::get('constants.PAYPAL.STATUS.ERROR') ;
                                $payoutInput['service_response'] = $this->appEncodeDecode->filterString($ex) ;
                                $payoutInput['bank_id'] = "";
                                $payoutInput['payout_transaction_id'] = uniqid();

                                $log = $this->paymentRepository->logPayout($payoutInput);
                            }



                        }
                        catch (Exception $ex)
                        {
                            $responseMessage = Lang::get('MINTMESH.payout.error');
                            $responseCode = self::ERROR_RESPONSE_CODE;
                            $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                            $responseData = array();
                        }
                    } else {
                        $responseMessage = Lang::get('MINTMESH.payout.invalid_amount');
                        $responseCode = self::ERROR_RESPONSE_CODE;
                        $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                        $responseData = array();
                    }
                } else {
                    $responseMessage = Lang::get('MINTMESH.user.wrong_password');
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                    $responseData = array();
                }
            } else {
                $responseMessage = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $responseData = array();
            }
            
            
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
            
        }
        
        //manual payout
        public function manualPayout($input=array())
        {
            $loggedinUserDetails = $this->getLoggedInUser();
            $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;      
            if ($loggedinUserDetails) {

                //get balance cash info
                $balanceCashInfo = $this->paymentRepository->getbalanceCashInfo($loggedinUserDetails->emailid);
                $balanceCash = !empty($balanceCashInfo->balance_cash)?$balanceCashInfo->balance_cash:0;
                $payoutAmount = !empty($input['amount'])?$input['amount']:0;
                if ($balanceCash >= $payoutAmount && !empty($payoutAmount)) {
                    $responseMessage = Lang::get('MINTMESH.manualpayout.success');
                    $responseCode = self::SUCCESS_RESPONSE_CODE;
                    $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                    $responseData = array();
                    $bankDetails = $this->paymentRepository->getbankInfo($input['bank_id']);

//                    $emailiSent=1;
                    if($bankDetails) {
                        //send emails
                        //email to mintmesh support and user
                        $successSupportTemplateAdmin = Lang::get('MINTMESH.email_template_paths.manual_payout_success_admin');
                        $receipientEmailAdmin = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                        $successSupportTemplateUser = Lang::get('MINTMESH.email_template_paths.manual_payout_success_user');
                        $receipientEmailUser = $loggedinUserDetails->emailid;//Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                        $bankDetails->amount = $input['amount'];
                        $bankDetails->remaningAmount = $balanceCashInfo->balance_cash-$payoutAmount;
                        $bankDetails->name = $neoLoggedInUserDetails->firstname;
                        $bankDetails->userEmail = $neoLoggedInUserDetails->emailid;
                        $emailSentAdmin = $this->sendManualEmailToMintMesh($successSupportTemplateAdmin, $receipientEmailAdmin, $bankDetails);
                        $emailSentUser = $this->sendManualEmailToUser($successSupportTemplateUser, $receipientEmailUser, $bankDetails);
                        //log payout
                        $payoutInput = array();
                        $payoutInput['from_user'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                        $payoutInput['amount'] = $input['amount'];
                        $payoutInput['to_mintmesh_user']= $loggedinUserDetails->emailid;
                        $payoutInput['to_provided_user']= $loggedinUserDetails->emailid;
                        $payoutInput['payout_types_id'] = 2;//manual type
                        $payoutInput['paypal_item_id'] = "";//paypal item id
                        $payoutInput['paypal_batch_id'] = "";//paypal batch id
                        $payoutInput['status'] = Config::get('constants.MANUAL.STATUS.SUCESS') ;
                        $payoutInput['service_response'] = "";
                        $payoutInput['bank_id'] = $input['bank_id'];
                        $payoutInput['payout_transaction_id'] = uniqid();

                        $log = $this->paymentRepository->logPayout($payoutInput);

                        //update balance cash info
                        //edit balance cash info
                        $balanceCash = $balanceCashInfo->balance_cash-$payoutAmount;
                        $bid = $balanceCashInfo->id ;
                        $inp = array();
                        $inp['balance_cash'] = $balanceCash ;
                        $u = $this->paymentRepository->editBalanceCash($bid, $inp);
                        $responseData=array("remainingCash"=>$balanceCash);
                    } else {
                        $responseMessage = Lang::get('MINTMESH.manualpayout.error');
                        $responseCode = self::ERROR_RESPONSE_CODE;
                        $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                        $responseData = array();
                        $message = array('msg'=>array($responseMessage));
                        //log payout
                        $payoutInput = array();
                        $payoutInput['from_user'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
                        $payoutInput['amount'] = $input['amount'];
                        $payoutInput['to_mintmesh_user']= $loggedinUserDetails->emailid;
                        $payoutInput['to_provided_user']= $loggedinUserDetails->emailid;
                        $payoutInput['payout_types_id'] = 2;//paypal type
                        $payoutInput['paypal_item_id'] = "";//paypal item id
                        $payoutInput['paypal_batch_id'] = "";//paypal batch id
                        $payoutInput['status'] = Config::get('constants.MANUAL.STATUS.ERROR') ;
                        $payoutInput['service_response'] = "Bank details not found" ;
                        $payoutInput['bank_id'] = $input['bank_id'];
                        $payoutInput['payout_transaction_id'] = uniqid();

                        $log = $this->paymentRepository->logPayout($payoutInput);
                    }
                } else {
                    $responseMessage = Lang::get('MINTMESH.manualpayout.invalid_amount');
                    $responseCode = self::ERROR_RESPONSE_CODE;
                    $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                    $responseData = array();
                }

            } else {
                $responseMessage = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $responseData = array();
            }
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
        }

        public function createPaypalItemId()
        {
            return uniqid();
        }
        public function sendManualEmailToMintMesh($templatePath, $emailid, $data)
        {
           $this->userEmailManager->templatePath = $templatePath;
            $this->userEmailManager->emailId = $emailid;
            $dataSet = array();
            if (!empty($data))
            {
                foreach ($data as $k=>$v)
                {
                    $dataSet[$k] = $v ;
                }
            }
            $dataSet['name'] = "Admin";
            $dataSet['email'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
            /*$dataSet['name'] = $input['firstname'];
            $dataSet['link'] = $appLink ;
            $dataSet['email'] = $input['emailid'] ;*/

           // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
            $this->userEmailManager->dataSet = $dataSet;
            $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.payout_success_admin');
            $this->userEmailManager->name = 'admin';
            return $email_sent = $this->userEmailManager->sendMail();
            
        }
        public function sendManualEmailToUser($templatePath, $emailid, $data)
        {
           $this->userEmailManager->templatePath = $templatePath;
            $this->userEmailManager->emailId = $emailid;
            $dataSet = array();
            if (!empty($data))
            {
                foreach ($data as $k=>$v)
                {
                    $dataSet[$k] = $v ;
                }
            }
            /*$dataSet['name'] = $input['firstname'];
            $dataSet['link'] = $appLink ;
            $dataSet['email'] = $input['emailid'] ;*/

           // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
            $this->userEmailManager->dataSet = $dataSet;
            $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.payout_success_user');
            $this->userEmailManager->name = 'user';
            return $email_sent = $this->userEmailManager->sendMail();
        }
        public function sendPayoutEmailToMintMesh($templatePath, $emailid, $data)
        {
           $this->userEmailManager->templatePath = $templatePath;
            $this->userEmailManager->emailId = $emailid;
            $dataSet = array();
            if (!empty($data))
            {
                foreach ($data as $k=>$v)
                {
                    $dataSet[$k] = $v ;
                }
            }
            $dataSet['name'] = "Admin";
            $dataSet['email'] = Config::get('constants.MINTMESH_SUPPORT.EMAILID');
            /*$dataSet['name'] = $input['firstname'];
            $dataSet['link'] = $appLink ;
            $dataSet['email'] = $input['emailid'] ;*/

           // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
            $this->userEmailManager->dataSet = $dataSet;
            $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.payout_success_admin');
            $this->userEmailManager->name = 'admin';
            return $email_sent = $this->userEmailManager->sendMail();
            
        }
        public function sendPayoutEmailToUser($templatePath, $emailid, $data)
        {
           $this->userEmailManager->templatePath = $templatePath;
            $this->userEmailManager->emailId = $emailid;
            $dataSet = array();
//            $dataSet['name'] = "shweta" ;
//            $dataSet['email'] = "shwetapazarey@gmail.com";
            $dataSet['amount'] = !empty($data->amount)?$data->amount:'';
            /*$dataSet['name'] = $input['firstname'];
            $dataSet['link'] = $appLink ;
            $dataSet['email'] = $input['emailid'] ;*/

           // $dataSet['link'] = URL::to('/')."/".Config::get('constants.MNT_VERSION')."/redirect_to_app/".$appLinkCoded ;;
            $this->userEmailManager->dataSet = $dataSet;
            $this->userEmailManager->subject = Lang::get('MINTMESH.user_email_subjects.payout_success_user');
            $this->userEmailManager->name = 'user';
            return $email_sent = $this->userEmailManager->sendMail();
        }
        
         public function doValidation($validatorFilterKey, $langKey) {
             //validator passes method accepts validator filter key as param
            if($this->paymentValidator->passes($validatorFilterKey)) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get($langKey)));
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                $data = array();                
            } else {
                /* Return validation errors to the controller */
                $message = $this->paymentValidator->getErrors();
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data) ;
        }
        
        public function updateBalanceCashInfo($amount=0, $currency='', $userEmail='', $userID)
        {
            //get user country 
            $userDetails = $this->neoUserRepository->getNodeByEmailId($userEmail) ;
            $convertedAmount = 0;
            if (strtolower($userDetails->phone_country_name) =="india")//if india
            {
                
                if (!empty($currency) && $currency == Config::get('constants.PAYMENTS.CURRENCY.USD'))//if dollar then convert 
                {
                    //change from dollar to rs
                    $rsRate = Config::get('constants.PAYMENTS.CONVERSION_RATES.USD_TO_INR');
                    $convertedAmount = $this->convertUSDToINR($amount);
                }
                else
                {
                    $convertedAmount = $amount ;
                }
            }
            else //if USA
            {
                //get balance cash info
                if (!empty($currency) && $currency == Config::get('constants.PAYMENTS.CURRENCY.INR'))//if dollar then convert 
                {
                    //change from rs to USD
                    
                    $usdRate = Config::get('constants.PAYMENTS.CONVERSION_RATES.INR_TO_USD');
                    $convertedAmount = $this->convertINRToUSD($amount);
                }
                else
                {
                    $convertedAmount = $amount ;
                }
            }
            //get balance cash info
            $balanceCashInfo = $this->paymentRepository->getbalanceCashInfo($userEmail);
            if (!empty($balanceCashInfo))
            {
                //edit balance cash info
                $balanceCash = $convertedAmount+$balanceCashInfo->balance_cash ;
                $bid = $balanceCashInfo->id ;
                $inp = array();
                $inp['balance_cash'] = $balanceCash ;
                $u = $this->paymentRepository->editBalanceCash($bid, $inp);
            }
            else //insert balance cash info
            {
                //insert balance cash info
                $inp = array();
                $inp['user_id'] = $userID ;
                $inp['user_email'] = $userEmail ;
                $inp['balance_cash'] = $convertedAmount ;
                $inp['currency'] = $currency ;
                $i = $this->paymentRepository->insertBalanceCash($inp);
            }
            return ;
            
        }
        
        
        public function calculateTotalAmount($percentage, $amount)
        {
            if (!empty($percentage) && !empty($amount))
            {
                $perAmount = ($percentage/100)*$amount;
                return $totalAmount = $amount+$perAmount ;
            }
            else
            {
                return 0;
            }
        }
        public function getPayoutTransactions($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails)
            {
                $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
                $page=0;
                if (!empty($input['page']))
                {
                    $page = $input['page'];
                }
                $result = $this->paymentRepository->getPayoutTransactions($this->loggedinUserDetails->emailid, $page);
                $total_cash = 0;
                $total_cash_res = $this->paymentRepository->getPaymentTotalCash($this->loggedinUserDetails->emailid,'1');
                if (!empty($total_cash_res))
                {
                    $total_cash = $total_cash_res[0]->total_cash ;
                }
                
                if (count($result))
                {
                    $returnArray = array();
                    
                    foreach ($result as $res)
                    {
                        
                        $r['from_email'] = $res->from_user;
                        $r['my_email'] = $res->to_user ;
                        $r['created_at'] = $res->created_at ;
                        $r['amount'] = $res->amount ;
                        $r['payout_transaction_id'] = $res->payout_transaction_id ;
                        if ($res->payout_types_id == 2)
                        {
                            $r['currency']=Config::get('constants.PAYMENTS.CURRENCY.INR');
                        }
                        else
                        {
                            $r['currency']=Config::get('constants.PAYMENTS.CURRENCY.USD');
                        }
                        /*$fromUser = $this->neoUserRepository->getNodeByEmailId($res->from_user) ;
                        $fromUserDetails = $this->userGateway->formUserDetailsArray($fromUser);
                        if (!empty($fromUserDetails))
                        {
                            foreach ($fromUserDetails as $k=>$v)
                            {
                                $r['from_user_'.$k]=$v ;
                            }
                        }
                        else
                        {
                            $r['from_user_fullname']="";
                        }*/
                        $returnArray[] = $r ;
                    }

                    $data=array("payouts"=>$returnArray,"total_cash"=>$total_cash) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.payout.success_list')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
                else
                {
                    $data=array("payouts"=>array(),"total_cash"=>$total_cash) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.payout.no_result')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
            }
            else {
                $message = array('msg'=>array(Lang::get('MINTMESH.change_password.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
            
        }
        public function convertINRToUSD($amount=0)
        {
            $returnAmount = 0;
            if (!empty($amount))
            {
                $usdRate = Config::get('constants.PAYMENTS.CONVERSION_RATES.INR_TO_USD');
                $returnAmount = round($amount/$usdRate) ;
            }
            return $returnAmount ;
        }
        public function convertUSDToINR($amount=0)
        {
            $returnAmount = 0;
            if (!empty($amount))
            {
                $rsRate = Config::get('constants.PAYMENTS.CONVERSION_RATES.USD_TO_INR');
                $returnAmount = $amount*$rsRate ;
            }
            return $returnAmount ;
        }
    
}
?>
