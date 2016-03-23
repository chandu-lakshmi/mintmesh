<?php namespace Mintmesh\Gateways\API\SMS;

/**
 * This is the Seek Referrals Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */

use Mintmesh\Repositories\API\SMS\SMSRepository;
use Mintmesh\Services\Validators\API\SMS\SMSValidator ;
use Mintmesh\Repositories\API\User\UserRepository;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;
use Mintmesh\Services\APPEncode\APPEncode ;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Lang;
use Queue;
//use Twilio ;//Aloha\Twilio\Twilio
use Config ;
use Log;
use Authy\AuthyApi as AuthyApi;
class SMSGateway {
    
    protected $smsValidator, $userRepository,$smsRepository;  
    protected $authorizer, $appEncodeDecode;
    protected $commonFormatter, $loggedinUserDetails,$neoUserRepository,$neoLoggedInUserDetails;
    
	public function __construct(smsRepository $smsRepository, 
                                    smsValidator $smsValidator, 
                                    UserRepository $userRepository,
                                    NeoUserRepository $neoUserRepository,
                                    Authorizer $authorizer,
                                    CommonFormatter $commonFormatter,
                                    APPEncode $appEncodeDecode) {
                //ini_set('max_execution_time', 500);
		$this->smsRepository = $smsRepository;
                $this->smsValidator = $smsValidator;
                $this->userRepository = $userRepository;
                $this->neoUserRepository = $neoUserRepository;
                $this->authorizer = $authorizer;
                $this->commonFormatter = $commonFormatter ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->loggedinUserDetails = $this->getLoggedInUser();
                $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
        }
        
        public function validateSMSInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->smsValidator->passes('send_sms')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->smsValidator->getErrors(), array()) ;
            
        }
        
        public function validateOTPInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->smsValidator->passes('send_otp')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->smsValidator->getErrors(), array()) ;
            
        }
        
        public function validateVerifyOTPInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->smsValidator->passes('verify_otp')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->smsValidator->getErrors(), array()) ;
            
        }
        
        public function sendSMS($input)
        {
            $numbers = json_decode($input['numbers']);
            $sms_type= $input['sms_type'] ;
            $userPhone = !empty($this->neoLoggedInUserDetails->phone)?$this->neoLoggedInUserDetails->phone:'';
            $countryCodeArray = explode("-",$userPhone);
            $countryCode = !empty($countryCodeArray[0])?$countryCodeArray[0]:'';
            if (!empty($numbers) && is_array($numbers))
            {
                $successList = array();
                foreach ($numbers as $number)
                {
                    //check if the number contains country code assigned
                    if (strpos($number, "+") === false){
                        $number = $countryCode.$number ;
                    }
                    $firstName = !empty($this->loggedinUserDetails->firstname)?$this->loggedinUserDetails->firstname:'';
                    $lastName = !empty($this->loggedinUserDetails->lastname)?$this->loggedinUserDetails->lastname:'';
                    $senderName = $firstName." ".$lastName ;
                    $pushData = array() ;
                    $pushData['message'] = $senderName." discovered a great way to refer people and ask for referrals, using MintMesh.Download app from www.mintmesh.com";
                    $pushData['number'] = $number ;
                    $pushData['from'] = $this->loggedinUserDetails->emailid ;
                    $pushData['type_sms'] = $input['sms_type'];
                    Queue::push('Mintmesh\Services\Queues\SMSQueue', $pushData, 'SMS');
                }
                $data = array("success_list"=>$successList);
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.invalid_input')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
             
        }
        
        public function sendOTP($input)
        {
            $authy_api = new AuthyApi(Config::get('constants.TWILIO.AUTHY_API_KEY'), Config::get('constants.TWILIO.AUTHY_URL'));
            $sms_type= $input['sms_type'] ;
            
            $email = $this->loggedinUserDetails->emailid ;
            $countryCode='';
            $number = $phone= !empty($this->neoLoggedInUserDetails->phone)?$this->neoLoggedInUserDetails->phone:0;
             //check if phone number is already existing
            $userCount = $this->neoUserRepository->getUserByPhone($phone, $email);
            if (empty($userCount))
            {
                $splitedNumber = explode('-', $number,2);
                if (is_array($splitedNumber))
                {
                    $number = !empty($splitedNumber[1])?$splitedNumber[1]:0;
                    $countryCode = !empty($splitedNumber[0])?str_replace('+','',$splitedNumber[0]):'';
                }
                $user = $authy_api->registerUser($email, $number, $countryCode); //email, cellphone, country_code
                $smsInput=array();
                $smsInput['sms_type'] = $sms_type ;
                $smsInput['from_email'] = $email ;
                $smsInput['to_number'] = $phone ;
                $smsInput['message'] = 'OTP' ;
                if($user->ok())
                {
                       $smsInput['send_status'] = 1 ;
                       //update authy id for user
                       $updAuthy=$this->userRepository->updateAuthyId($this->loggedinUserDetails->id, $user->id());
                       $sms = $authy_api->requestSms($user->id(),array("force" => "true"));
                       if ($sms->ok())
                       {
                           $smsInput['twilio_response'] = "success" ;
                           $this->smsRepository->logSMS($smsInput);
                            $data = array();
                            $message = array('msg'=>array(Lang::get('MINTMESH.sms.otp_sent')));
                            return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                       }
                       else
                       {
                         $message="";
                         $smsInput['send_status'] = 0 ;
                         foreach($sms->errors() as $field => $message) {
                            $message = $message ;
                          }
                          if (empty($message))
                          {
                              $message = Lang::get('MINTMESH.sms.max_reached') ;
                          }
                          $smsInput['twilio_response'] = $message ;
                          $this->smsRepository->logSMS($smsInput);

                          $message = array('msg'=>array($message));
                          return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                       }
                }
                else
                {
                    $smsInput['send_status'] = 0 ;
                    foreach($user->errors() as $field => $message) {
                      $message = $message ;
                    }
                     $smsInput['twilio_response'] = $message ;
                     $this->smsRepository->logSMS($smsInput);
                     $message = array('msg'=>array($message));
                     return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                }


            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.user_exist')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
           

            
        }
        
        public function verifyOTP($input)
        {
            $email = $this->loggedinUserDetails->emailid ;
            $phone = $this->neoLoggedInUserDetails->phone ;
            //check if phone number is already existing
            $userCount = $this->neoUserRepository->getUserByPhone($phone, $email);
            if (empty($userCount))
            {
                $authy_api = new AuthyApi(Config::get('constants.TWILIO.AUTHY_API_KEY'), Config::get('constants.TWILIO.AUTHY_URL'));
                $length = strlen((string)$input['otp']);
                if (!empty($this->loggedinUserDetails->authy_id) && ($length > 6 and $length < 10) )
                {
                    try{
                        $verification = $authy_api->verifyToken($this->loggedinUserDetails->authy_id, $input['otp']);
                    }
                    catch (\AuthyFormatException $e) {
                        $message = array('msg'=>array(Lang::get('MINTMESH.sms.invalid_length')));
                        return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                    }
                    if ($verification->ok())
                    {
                        //update verified phone field
                        $updPhoneVerifed=$this->neoUserRepository->updatePhoneVerified($this->loggedinUserDetails->emailid, 1);
                        //change validate battle card status to closed
                        $closeResult = $this->userRepository->closeVerifyOtpBattleCard($email);
                        $data = array();
                        $message = array('msg'=>array(Lang::get('MINTMESH.sms.otp_validated')));
                        return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                    }
                    else
                    {
                       foreach($verification->errors() as $field => $message) {
                         $message = $message ;
                       }
                       $message = array('msg'=>array($message));
                       return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.sms.invalid_input')));
                    return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.user_exist')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
        }
        
        public function addSmsToQueue()
        {
            $message = "test message from mintmesh";
                    $smsInput=array();
                    try{
                         $result = $this->twilio->message($number,$message);
                         $smsInput['send_status'] = 1 ;
                         $successList[]=$number ;
                    } catch (\Services_Twilio_RestException $e) {
                        $smsInput['send_status'] = 0 ;
                        $result = $e ;
                    }
                    $smsInput['sms_type'] = $input['sms_type'] ;
                    $smsInput['from_email'] = $this->loggedinUserDetails->emailid ;
                    $smsInput['to_number'] = $number ;
                    $smsInput['message'] = $message ;
                    $smsInput['twilio_response'] = $result ;
                    $this->smsRepository->logSMS($smsInput);
        }
        public function getLoggedInUser()
        {
            $resourceOwnerId = $this->authorizer->getResourceOwnerId();
            return $this->userRepository->getUserById($resourceOwnerId);
        }
        
        public function sendSMSForReferring($input)
        {
            $numbers = json_decode($input['numbers']);
            $sms_type= $input['sms_type'] ;
            $other_name = !empty($input['other_name'])?$input['other_name']:"" ;
            $userPhone = !empty($this->neoLoggedInUserDetails->phone)?$this->neoLoggedInUserDetails->phone:'';
            $countryCodeArray = explode("-",$userPhone);
            $countryCode = !empty($countryCodeArray[0])?$countryCodeArray[0]:'';
            if (!empty($numbers) && is_array($numbers))
            {
                $successList = array();
                foreach ($numbers as $number)
                {
                    //check if the number contains country code assigned
                    if (strpos($number, "+") === false){
                        $number = $countryCode.$number ;
                    }
                    $firstName = !empty($this->loggedinUserDetails->firstname)?$this->loggedinUserDetails->firstname:'';
                    $lastName = !empty($this->loggedinUserDetails->lastname)?$this->loggedinUserDetails->lastname:'';
                    $senderName = $firstName." ".$lastName ;
                    $pushData = array() ;
                    $pushData['message'] = $senderName." wants to introduce you to ".$other_name." for a service, using MintMesh.Download app from www.mintmesh.com";
                    $pushData['number'] = $number ;
                    $pushData['from'] = $this->loggedinUserDetails->emailid ;
                    $pushData['type_sms'] = $input['sms_type'];
                    Queue::push('Mintmesh\Services\Queues\SMSQueue', $pushData, 'SMS');
                }
                $data = array("success_list"=>$successList);
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.sms.invalid_input')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
        }
          
     
        
    
}
?>
