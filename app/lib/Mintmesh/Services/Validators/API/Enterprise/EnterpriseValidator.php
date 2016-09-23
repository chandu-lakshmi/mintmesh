<?php 
namespace Mintmesh\Services\Validators\Api\Enterprise;
use Mintmesh\Services\Validators\Validator;
class EnterpriseValidator extends Validator {
    public static $rules = array(
        'enterpriseUserCreate' => array(
                'fullname'              => 'required',
                'company'               => 'required',
                'emailid'               => 'required|unique:users|email',
                'password'              => 'required|min:6'),
        'validateEmailVerificationToken' => array(
                'token'                  => 'required',
                'client_id'              => 'required',
                'client_secret'          => 'required'),
        'enterpriseLogin'  => array(
                'username'               => 'required',
                'password'               => 'required'),
        'enterprise_special_login' => array(
                'token'                 => 'required',
                'username'              => 'required',
                'client_id'             => 'required',
                'client_secret'         => 'required'),
        'validateCompanyProfileInput' => array(
                'company'               => 'required',
                'industry'              => 'required',
                'website'               => 'required'),
        'validateCompanyProfileInput' => array(
                'company'               => 'required',
                'industry'              => 'required',
                'website'               => 'required'),
        'enterpriseContactsUpload' => array(
                'contacts_file'         => 'required',
                'is_bucket_new'         => 'required',
                'bucket_id'             => 'required',
                'company_id'            => 'required',
                'company_code'          => 'required',
                'bucket_name'           => 'required'),
        'enterpriseBucketsList' => array(
                'company_id'            => 'required'),
        'enterpriseGetUserDetails' => array(
                'access_token'            => 'required'),
        'enterpriseContactsList' => array(
                'company_id'            => 'required'),
        'enterpriseEmailInvitations' => array(
                'company_id'            => 'required',
                'invite_contacts'       => 'required',
                'email_subject'         => 'required',
                'email_body'            => 'required'),
        'forgot_password' => array(
            'emailid'      => 'required|email|exists:users,emailid'
        ),
        'reset_password' => array(
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'code'=>"required"        
            ),
         'process_job'    => array(
                'from_user'      => 'required',
                'referred_by'    =>'required|email',
                'post_id'        =>'required',
                'relation_count' =>'required',
                'status'         =>'required'
        ),
         'awaiting_action'    => array(
                'from_user'      =>'required',
                'referred_by'    =>'required|email',
                'post_id'        =>'required',
                'relation_count' =>'required',
                'awaiting_action_status' =>'required'
        ),
         'connect_to_company'    => array(
                'company_code'   =>'required'   
        ),
         'view_company_details'    => array(
                'company_code'   =>'required'   
        ),
         'view_dashboard'    => array(
                'company_code'   =>'required'   
        ),
        'update_contact_list' =>array(
                'record_id'        => 'required'
        ),
        'delete_contact'      => array(
                'record_id'        => 'required'
        ),
        'create_bucket' =>array(
                'company_code'        => 'required',
                'bucket_name'         => 'required'
        ),
        'contacts_file' =>array(
                'file_name'         => 'required'
        ),
        'upload_contacts' =>array(
            'company_code'      => 'required',
            'contacts'          => 'required'
        ),
        'add_contact' =>array(
            'company_id'      => 'required',
            'bucket_id'         => 'required',
            'firstname'         => 'required',
            'lastname'          => 'required',  
            'emailid'           => 'required',  
            'other_id'          => 'required'
        )
        );
    
}
?>