<?php 
namespace Mintmesh\Services\Validators\Api\Referrals;
use Mintmesh\Services\Validators\Validator;
class ReferralsValidator extends Validator {
    public static $rules = array(
        'seek_service_referral' => array(
                //'service'      => 'required',
                'looking_for'   => 'required',
                //'service_location'   => 'required',
                'service_period'    => 'required',
                'service_scope'    => 'required',
                'service_type'    => 'required',
                //'service_currency'    => 'required'
             ),
        'close_post' => array(
                'post_id'      => 'required'
             ),
        'get_posts' => array(
//                'request_type'      => 'required'
                //'page_no'          =>'required'
             ),
        'get_post_details' => array(
                'post_id'      => 'required'
                //'page_no'          =>'required'
             ),
        'refer_contact' => array(
                'referring'      => 'required',
                'refer_to'          =>'required|email',
                'post_id'          =>'required'
             ),
        'process_post' => array(
                'from_user'      => 'required',
                'referred_by'          =>'required|email',
                'post_way'          =>'required',
                'post_id'          =>'required',
                'relation_count'          =>'required',
                'status'            =>'required'
             ),
        'post_status_details' => array(
                'from_user'      => 'required|email',
                'referred_by'          =>'required|email',
                'referral'          =>'required',
                'relation_count'          =>'required',
                'post_id'          =>'required'
             ),
        'referral_contacts'=>array(
            'other_email' => 'required|email',
            'post_id' => 'required'
        ),
        'mutual_people'=>array(
            'other_email'=>'required'
        ),
        'get_referrals_cash' => array(
            'payment_reason'=>'required'
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
