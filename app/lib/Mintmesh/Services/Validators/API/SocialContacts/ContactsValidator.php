<?php 
namespace Mintmesh\Services\Validators\Api\SocialContacts;
use Mintmesh\Services\Validators\Validator;
class ContactsValidator extends Validator {
    public static $rules = array(
        'create' => array(
                'emailid'      => 'required|unique:users|email',
                'firstname'   => 'required',
                'lastname'    => 'required',
                'password' => 'required|min:6|confirmed',
                'password_confirmation' => 'required|min:6'),
        'login' => array(
            'username'      => 'required',
            'password' => 'required'
        ),
        'fb_login' => array(
            'fb_access_token'      => 'required'
        ),
        'forgot_password' => array(
            'emailid'      => 'required|email|exists:users,emailid'
        ),
        'reset_password' => array(
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'code'=>"required"        )
                
        );
}
?>
