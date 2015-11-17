<?php 
namespace Mintmesh\Services\Validators\Api\Payment;
use Mintmesh\Services\Validators\Validator;
class PaymentValidator extends Validator {
    public static $rules = array(
        'braintree_tran' => array(
                'amount'      => 'required',
                'nonce'   => 'required',
                'mm_transaction_id' => 'required',
                'post_id' => 'required'
             ),
        'transaction_input'=>array(
            'mm_transaction_id' => 'required'
        )
        
        
        );
}
?>
