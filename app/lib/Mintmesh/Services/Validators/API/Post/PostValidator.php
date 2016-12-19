<?php

namespace Mintmesh\Services\Validators\Api\Post;

use Mintmesh\Services\Validators\Validator;

class PostValidator extends Validator {

    public static $rules = array(
        'job_post' => array(
            'job_title'       => 'required',
            'job_function'    => 'required',
            'location'        => 'required',
            'industry'        => 'required',
            'employment_type' => 'required',
            'experience_range'=> 'required',
            'job_description' => 'required',
            'job_period'      => 'required',
            'bucket_id'       => 'required',
            'job_type'        => 'required',
            'company_code'    => 'required'
        ),
        'jobs_list' => array(
            'company_code'    => 'required',
            'request_type'    => 'required'
        ),
        'job_details' => array(
            'id' => 'required',
            'company_code'    => 'required'
        ),
        'referral_details' => array(
            'post_id'         => 'required'
        ),
        'add_campaign' => array(
            'campaign_name'         => 'required',
            'campaign_type'         => 'required',
            'company_code'          => 'required',
        ),
        'view_campaign' => array(
            'company_code'          => 'required',
            'campaign_id'          => 'required',
        ),
        'refer_candidate' => array(
            'emp_email_id'      =>   'required',
            'firstname'         =>   'required',
            'emailid'           =>   'required',
            'department'        =>   'required',
            'cv'                =>   'required'
        ),
        'multiple_awaiting_action' => array(
            'id'                =>    'required',
            'awaiting_action_status' =>  'required'
        ),
        'apply_job' => array(
            'fullname'         =>   'required',
            'emailid'           =>   'required',
            'cv'                =>   'required',
        ),
        'apply_jobs_list' => array(
            'reference_id'     =>   'required'
        ),
        'job_post_from_campaigns' => array(
            'job_title'       => 'required',
            'job_function'    => 'required',
            'location'        => 'required',
            'industry'        => 'required',
            'employment_type' => 'required',
            'experience_range'=> 'required',
            'job_description' => 'required',
            'job_period'      => 'required',
            'job_type'        => 'required',
        ),
        'add_bucket_contacts_to_post'  => array(
            'post_id'        => 'required',
            'bucket_id'      => 'required'
        )
    );

    //conditional checks for you_are field in referrals
    /*
      public static $conditional_rules = array('c_rule1');
      public static $conditional_rules_keys = array('c_rule1'=>'service_scope');
      public static $conditional_rules_values = array('c_rule1'=>'find_candidate');
      public static $c_rule1 = array('industry','job_function','experience_range','company','employment_type');

     */
}
?>
