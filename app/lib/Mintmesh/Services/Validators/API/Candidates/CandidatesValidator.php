<?php

namespace Mintmesh\Services\Validators\Api\Candidates;

use Mintmesh\Services\Validators\Validator;

class CandidatesValidator extends Validator {

    public static $rules = array(
        'get_candidate_email_templates' => array(
            'company_code'  => 'required',
        ),
        'get_candidate_details' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required'
        ),
        'get_company_employees' => array(
            'company_code'  => 'required',
        ),

        'add_candidate_schedule' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required',
            'candidate_id'  => 'required',
            'schedule_for'  => 'required',
            'attendees'  => 'required',
            'interview_date'  => 'required',
            'interview_from_time'  => 'required',
            'interview_to_time'  => 'required',
            'interview_time_zone'  => 'required',
            'interview_location'  => 'required',
            'notes'  => 'required'
        ),
        'add_candidate_email' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required',
            'candidate_id'  => 'required',
            'to'  => 'required',
            'subject'  => 'required',
            'body'  => 'required'
        ),
        'add_candidate_comment' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required',
            'candidate_id'  => 'required',
            'comment'  => 'required'
        ),
        'get_candidate_activities' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required'
        )
              
    );

}
?>
