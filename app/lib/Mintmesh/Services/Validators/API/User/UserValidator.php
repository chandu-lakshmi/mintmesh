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
        )
        );
}
?>
