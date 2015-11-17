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
use Lang,URL;
use Config;
use  \Braintree_ClientToken;
use  \Braintree_Transaction;
class PaymentGateway {
    
    protected $authorizer, $appEncodeDecode, $paymentValidator,$paymentRepository,$userRepository,$neoUserRepository;
    protected $commonFormatter, $loggedinUserDetails,$referralsRepository, $userGateway;
	public function __construct(PaymentValidator $paymentValidator, 
                                    Authorizer $authorizer,
                                    CommonFormatter $commonFormatter,
                                    APPEncode $appEncodeDecode,
                                    UserRepository $userRepository,
                                    PaymentRepository $paymentRepository,
                                    ReferralsRepository $referralsRepository,
                                    UserGateway $userGateway,
                                    NeoUserRepository $neoUserRepository) {
            //ini_set('max_execution_time', 500);
		$this->paymentValidator = $paymentValidator;
                $this->authorizer = $authorizer;
                $this->commonFormatter = $commonFormatter ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->paymentRepository = $paymentRepository ;
                $this->userRepository = $userRepository;
                
                $this->referralsRepository =  $referralsRepository ;
                $this->userGateway=$userGateway ;
                $this->neoUserRepository = $neoUserRepository;
               
	}
        
        //validation on braintree payment transaction
        public function validateBTTransactionInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->paymentValidator->passes('braintree_tran')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.payment.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->paymentValidator->getErrors(), array()) ;
        }
        
         //validation on  payment transaction
        public function validateTransactionInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->paymentValidator->passes('transaction_input')) {
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
        
        public function getBTClientToken()
        {
            return Braintree_ClientToken::generate();
        }
        public function processBTTransaction($input=array())
        {
            $amount = $input['amount'];
            $nonce = $input['nonce'] ;
            $result = Braintree_Transaction::sale([
            'amount' => $amount,
            'paymentMethodNonce' => $nonce
            ]);
            if (!empty($result->success))
            {
                $input['status'] = Config::get('constants.PAYMENTS.STATUSES.SUCCESS') ;
            }
            else
            {
                $input['status'] = Config::get('constants.PAYMENTS.STATUSES.FAILED') ;
            }
            $this->paymentRepository->updatePaymentTransaction($input) ;
            if (!empty($result->success))
            {
                $transactionDetails = $this->paymentRepository->getTransactionById($input['mm_transaction_id']) ;;
                //update post payment status
                if (!empty($transactionDetails))
                {
                    $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus($transactionDetails->relation_id,Config::get('constants.PAYMENTS.STATUSES.SUCCESS'));
                    //send notifications to the respective people
                    $sendNotes = $this->processPostPaymentCompletion($transactionDetails);
                    
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
            return md5(time().$extraParam) ;
        }
        
        public function generateCitrusBill($amount=0, $percentage=0,$transactionId=0)
        {
            $totalAmount = 0 ;
            if (!empty($amount) && !empty($percentage))
            {
               $perAmount = ($percentage/100)*$amount;
               $totalAmount = $amount+$perAmount ;
            }
            if (!empty($amount))
            {
                $totalAmount = !empty($totalAmount)?$totalAmount:$amount ;
                $access_key = Config::get('constants.CITRUS.ACCESS_KEY') ;//"QZVFK264G28QZ6NJ43KB"; //put your own access_key - found in admin panel     
                $secret_key = Config::get('constants.CITRUS.SECRET_KEY'); //put your own secret_key - found in admin panel     
                $return_url = URL::to("/")."/v1/payment/citrus_transaction"; //put your own return_url.php here.    
                //$return_url = "http://202.63.105.86/mintmesh/returnUrl.php";
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
                              'returnUrl' => $return_url);     
                return $bill ;
            }
            
        }
        
        public function processCitrusTransaction($input)
        {
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
            /*   $verification_data =  $txID                         
                                    . $TxStatus
                                    . $amount
                                    . $pgTxnNo
                                    . $issuerRefNo                        
                                    . $authIdCode                        
                                    . $firstName                        
                                    . $lastName
                                    . $pgRespCode                        
                                    . $addressZip;     
            $signature = hash_hmac('sha1', $verification_data, $secret_key);
            */
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
                  $this->paymentRepository->updatePaymentTransaction($input) ;
                  if (!empty($transactionDetails))
                  {
                      $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus($transactionDetails->relation_id,Config::get('constants.PAYMENTS.STATUSES.SUCCESS'));
                  
                      $sendNotes = $this->processPostPaymentCompletion($transactionDetails);
                  }
                    $pt_input = array();
                    $pt_input['response'] = json_encode($input) ;
                    $pt_input['mm_transaction_id'] = $input['TxId'] ;
                    $pt_res = $this->paymentRepository->logPayment($pt_input);
                    return $this->commonFormatter->formatResponse(200, "success", Lang::get('MINTMESH.payment.success'), array()) ;								      
                }										    
              else {
                  $pt_input = array();
                  $pt_input['response'] = json_encode($input) ;
                  $pt_input['mm_transaction_id'] = $input['TxId'] ;
                  $pt_res = $this->paymentRepository->logPayment($pt_input);
                  $input['status'] = Config::get('constants.PAYMENTS.STATUSES.FAILED')  ;
                  $this->paymentRepository->updatePaymentTransaction($input) ;
                  return $this->commonFormatter->formatResponse(406, "error", Lang::get('MINTMESH.payment.failed'), array()) ;  
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
                $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $transactionDetails->for_user, 11, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->from_user),1) ;
                //send notification to via person
                $this->userGateway->sendNotification($loggedinUserDetails, $neoLoggedInUserDetails, $transactionDetails->to_user, 12, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->for_user),1) ;
                //send battle card to u1 containing u3 details
                $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $loggedinUserDetails->emailid, 20, array('extra_info'=>str_replace(",","",$transactionDetails->service_id)), array('other_user'=>$transactionDetails->for_user),1) ;
                
                return true ;
            }
            else
            {
                return false ;
            }
            
        }
        
    
}
?>
