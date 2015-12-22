<?php
namespace API\Payment;
use Mintmesh\Gateways\API\Payment\PaymentGateway;
use Illuminate\Support\Facades\Redirect;
use OAuth;
use Auth;
use Lang, Response;
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
            return \Response::json($response);
        }
        
        
        
        


}
?>
