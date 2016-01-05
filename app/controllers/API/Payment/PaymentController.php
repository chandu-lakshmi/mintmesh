<?php
namespace API\Payment;
use Mintmesh\Gateways\API\Payment\PaymentGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response,View;
use Config ;

class PaymentController extends \BaseController {

        
	public function __construct(PaymentGateway $paymentGateway)
	{
		$this->paymentGateway = $paymentGateway;
        }
	
        
        /**
	 * generate braintree client token
         * 
         * POST/generate_bt_token
         * 
         * @param string $access_token The Access token of a user
         * @param string $mm_transaction_id
	 * @return Response
	 */
        public function generateBTToken()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validateTransactionInput($inputUserData);
            if($validation['status'] == 'success') {
                $result = $this->paymentGateway->generateBtClientToken();
                return \Response::json($result);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
        /**
	 * generate citrus bill
         * 
         * GET/generate_citrus_bill
         * 
         * @param string $access_token
         * @param string $mm_transaction_id
	 * @return Response
	 */
        public function generateCitrusBill()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validateTransactionInput($inputUserData);
            if($validation['status'] == 'success') {
                $result = $this->paymentGateway->citrusBillGenerator($inputUserData);
                return \Response::json($result);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
        /**
	 * save user bank details
         *  
         * @param string $access_token
         * @param string $bank_name
         * @param string $account_name
         * @param string $account_number
         * @param string $ifsc_code
         * @param string $address
         * 
	 * @return Response
	 */
        public function saveUserBank()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validatesaveUserBank($inputUserData);
            if($validation['status'] == 'success') {
                $result = $this->paymentGateway->saveUserBank($inputUserData);
                return \Response::json($result);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
        /**
	 * save user bank details
         *  
         * @param string $access_token
         * @param string $bank_id
         * 
	 * @return Response
	 */
        public function editUserBank()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validateeditUserBank($inputUserData);
            if($validation['status'] == 'success') {
                $result = $this->paymentGateway->editUserBank($inputUserData);
                return \Response::json($result);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * delete user bank details
         * 
         * @param string $access_token
         * @param string $bank_id
         * 
	 * @return Response
	 */
        public function deleteUserBank()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validatedeleteUserBank($inputUserData);
            if($validation['status'] == 'success') {
                $result = $this->paymentGateway->deleteUserBank($inputUserData);
                return \Response::json($result);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * delete user bank details
         * 
         * @param string $access_token
         * 
	 * @return Response
	 */
        public function listUserBanks()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $result = $this->paymentGateway->listUserBanks($inputUserData);
            return \Response::json($result);
        }
        
        /**
	 * braintree transaction
         * 
         * POST/bt_transaction
         * 
         * @param string $access_token The Access token of a user
         * @param string $nonce
         * @param string $amount
         * @param string $mm_transaction_id
         * @param string $post_id
         * @param string $relation_id
	 * @return Response
	 */
        public function braintreeTransaction()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validateBTTransactionInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->paymentGateway->processBTTransaction($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
            
        }
        
        /**
	 * citrus transaction
         * 
         * POST/citrus_transaction
         * 
         * @param string $access_token
         * @param string $mm_transaction_id
         * @param string $post_id
         * @param string $relation_id
         * @param string $status
	 * @return Response
	 */
        public function citrusTransaction()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->paymentGateway->processCitrusTransaction($inputUserData);
            //citrus need a view file as return url
            if ($response['status_code'])
            {
                return View::make('landings/citrusReturn', array('data'=>$response['data']));
            }
            else
            {
                return  \Response::json($response);
            }
            
            //return \Response::json($response);
        }
        
        /**
	 * payout
         * 
         * POST/payout
         * 
         * @param string $access_token
         * @param string $paypal_emailid
         * @param string $amount
         * @param string $password user password
	 * @return Response
	 */
        public function payout()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validatePayoutInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->paymentGateway->paypalPayout($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * manula payout
         * 
         * POST/manulapayout
         * 
         * @param string $access_token
         * @param string $bank_id
         * @param string $amount
         * @param string $password user password
	 * @return Response
	 */
        public function manualPayout()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            // Validating user input data
            $validation = $this->paymentGateway->validateManualPayoutInput($inputUserData);
            if($validation['status'] == 'success') {
                $response = $this->paymentGateway->manualPayout($inputUserData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
        
        /**
	 * get payouts
         * 
         * POST/get_payouts
         * 
         * @param string $access_token The Access token of a user
         * @param string $page page number
	 * @return Response
	 */
        public function getPayoutTransactions()
        {
            // Receiving user input data
            $inputUserData = \Input::all();
            $response = $this->paymentGateway->getPayoutTransactions($inputUserData);
            return \Response::json($response);
            
        }
        
        


}
?>
