<?php
namespace API\SMS;
use Mintmesh\Gateways\API\SMS\SMSGateway; 
use Config ;
/*use Mintmesh\Gateways\API\Payment\PaymentGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response;
use Config ;
*/
class SmsController extends \BaseController {

        protected $sms ;
	public function __construct(SMSGateway $smsGateway)
	{
            $this->smsGateway = $smsGateway;
        }
	
        
        /**
	 * send sms
         * 
         * POST/send_sms
         * 
         * @param string $access_token
	 * @return Response
	 */
        public function sendSMS()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->smsGateway->validateSMSInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->smsGateway->sendSMS($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * send otp
         * 
         * POST/send_otp
         * 
         * @param string $access_token
         * @param string $sms_type
	 * @return Response
	 */
        public function sendOTP()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->smsGateway->validateOTPInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->smsGateway->sendOTP($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * verify otp
         * 
         * POST/verify_otp
         * 
         * @param string $access_token
         * @param string $otp
	 * @return Response
	 */
        public function verifyOTP()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->smsGateway->validateVerifyOTPInput($inputUserData);
            if($validation['status'] == 'success') 
            {
                return \Response::json($this->smsGateway->verifyOTP($inputUserData));
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        
        
        


}
?>
