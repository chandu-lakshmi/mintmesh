<?php 
namespace Mintmesh\Services\Validators\Api\SMS;
use Mintmesh\Services\Validators\Validator;
class SMSValidator extends Validator {
    public static $rules = array(
        'send_sms' => array(
                'numbers'      => 'required',
                'sms_type'   => 'required'
             ),
        'send_otp' => array(
                'sms_type'   => 'required'
             ),
        'verify_otp' => array(
                'otp'   => 'required'
             ),
        
        );
}
?>
