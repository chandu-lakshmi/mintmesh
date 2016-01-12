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

        ),
        'payout'=>array(
            'paypal_emailid'=>'required|email',
            'amount'=>'required',
            'password'=>'required'
        ),
        'manualPayout'=>array(
            'bank_id'=>'required',
            'amount'=>'required',
            //'password'=>'required'
        ),
        'user_bank_details_save'=>array(
            //'user' => 'required',
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required',
            'ifsc_code' => 'required',
            'address' => 'required'
        ),
        'user_bank_details_edit'=>array(
            'bank_id' => 'required'
        ),
        'user_bank_details_delete'=>array(
            'bank_id' => 'required'
        ),
        'user_banks_list'=>array(
            'user_id' => 'required'
        )
        );
}
?>
