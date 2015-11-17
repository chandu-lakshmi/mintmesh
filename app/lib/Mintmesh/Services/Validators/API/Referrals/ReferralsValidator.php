<?php 
namespace Mintmesh\Services\Validators\Api\Referrals;
use Mintmesh\Services\Validators\Validator;
class ReferralsValidator extends Validator {
    public static $rules = array(
        'seek_service_referral' => array(
                'service'      => 'required',
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
                'request_type'      => 'required'
                //'page_no'          =>'required'
             ),
        'get_post_details' => array(
                'post_id'      => 'required'
                //'page_no'          =>'required'
             ),
        'refer_contact' => array(
                'referring'      => 'required|email',
                'refer_to'          =>'required|email',
                'post_id'          =>'required'
             ),
        'process_post' => array(
                'from_user'      => 'required|email',
                'referred_by'          =>'required|email',
                'post_way'          =>'required',
                'post_id'          =>'required',
                'relation_count'          =>'required',
                'status'            =>'required'
             ),
        'post_status_details' => array(
                'from_user'      => 'required|email',
                'referred_by'          =>'required|email',
                'referral'          =>'required|email',
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
}
?>
