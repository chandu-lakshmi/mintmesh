<?php 
namespace Mintmesh\Services\Validators\Api\User;
use Mintmesh\Services\Validators\Validator;
class UserValidator extends Validator {
    public static $rules = array(
        'create' => array(
                'emailid'      => 'required|unique:users|email',
                'firstname'   => 'required',
                'lastname'    => 'required',
                'phone'    => 'required',
                'phone_country_name'    => 'required',
                'deviceToken' => 'required',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required|min:6'),
        'create_v2' => array(
                'emailid'               => 'required|unique:users|email',
                'firstname'             => 'required',
                'lastname'              => 'required',
                'phone'                 => 'required',
                'phone_country_name'    => 'required',
                'location'              => 'required',
                'deviceToken'           => 'required',
                'password'              => 'required|min:6|confirmed',
                'password_confirmation' => 'required|min:6'),
        'login' => array(
            'username'      => 'required',
            'password' => 'required',
            'deviceToken' => 'required'
        ),
        'edit_profile'=>array(
            'dpImage'   => 'image',
            'info_type'=>'required'
        ),
        'special_login' => array(
            'code'      => 'required',
            'emailid' => 'required',
            'deviceToken' => 'required'
        ),
        'fb_login' => array(
            'fb_access_token'      => 'required'
        ),
        'forgot_password' => array(
            'emailid'      => 'required|email|exists:users,emailid'
        ),
        'get_single_notification' => array(
            'emailid' => 'required|email',
            'push_id' => "required",
            'notification_type'=>'required'
        ),
        'get_user_by_email' => array(
            'emailid' => 'required|email'
        ),
        'refer_contact'=>array(
            'request_to' => 'required|email',
            'request_for' => 'required|email',
            'request_type' => 'required'
        ),
        'reset_password' => array(
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'code'=>"required"        
            ),
        'check_reset_password' => array(
            'code'=>"required"       
            ),
        'complete_profile' => array(
                'dpImage'   => 'image',
                'position'      => 'required',
                'company'   => 'required',
                'industry'    => 'required',
                'location'    => 'required',
                'job_function' => 'required',
                'you_are'   => 'required'
            ),
        'complete_profile_v2' => array(
                'dpImage'   => 'image',
                'you_are'   => 'required',
                'location'    => 'required',
                'to_be_referred'=>'required',
                'dpImage'=>'required'
            ),
        'connection_request'=>array(
            'emails'=>'required'
        ),
        'connection_accept'=>array(
            'from_email'=>'required|email'
        ),
        'close_notification'=>array(
            'push_id'=>'required',
            'notification_type'=>'required',
            'request_type'=>'required'
        ),
        'logout'=>array(
            'deviceToken'=>'required'
        ),
        'get_notifications'=>array(
            'notification_type'=>'required'
        ),
        'get_users_by_location'=>array(
            'location'=>'required'
        ),
        'specific_level_info'=>array(
            'level_id'=>'required'
        ),
        'refer_my_contact'=>array(
            'referring'=>'required',
            'refer_to'=>'required'
            
        ),
        'validate_phone_existance'=>array(
            'phone'=>'required'
        ),
        'get_reference_flow'=>array(
            'base_rel_id'=>'required'
        ),
        'change_password'=>array(
            'password_old'=>'required|min:6',
            'password_new' => 'required|min:6|confirmed',
            'password_new_confirmation' => 'required|min:6'
        ) ,
        'check_user_password'=>array(
            'password'=>'required'
        ),
        'get_services'=>array(
            'service_type'=>'required',
            'user_country'=>'required',
            'search'=>'required'
        )
        );
    //conditional checks for you_are field in user profile
        public static $conditional_rules = array('c_rule1','c_rule2','c_rule3','c_rule4','c_rule5','c_rule6','c_rule7','c_rule8');
        public static $conditional_rules_keys = array('c_rule1'=>'you_are','c_rule2'=>'you_are','c_rule3'=>'you_are','c_rule4'=>'you_are','c_rule5'=>'you_are','c_rule6'=>'you_are','c_rule7'=>'you_are', 'c_rule8'=>'to_be_referred');
        public static $conditional_rules_values = array('c_rule1'=>'1','c_rule2'=>'2','c_rule3'=>'3','c_rule4'=>'4','c_rule5'=>'5','c_rule6'=>'6','c_rule7'=>'7','c_rule8'=>'1');
        public static $c_rule1 = array('industry','job_function','position','company');
        public static $c_rule2 = array('industry','position','company');
        public static $c_rule3 = array('profession');
        public static $c_rule4 = array('industry','company');
        public static $c_rule5 = array('college','course');
        public static $c_rule6 = array();
        public static $c_rule7 = array('position','company');
        public static $c_rule8 = array('services');
}
?>
