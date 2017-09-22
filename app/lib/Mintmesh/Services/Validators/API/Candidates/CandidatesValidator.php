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
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'add_candidate_schedule' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'schedule_for'  => 'required',
            'attendees'     => 'required',
            'interview_date'        => 'required',
            'interview_from_time'   => 'required',
            'interview_to_time'     => 'required',
            'interview_location'    => 'required'
        ),
        'add_candidate_email' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'subject_id'    => 'required'
        ),
        'add_candidate_comment' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'comment'       => 'required'
        ),
        'get_candidate_activities' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'get_candidate_tag_jobs_list' => array(
            'company_code'  => 'required'
        ),
        'add_candidate_tag_jobs' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'get_candidate_comments' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'get_candidate_sent_emails' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'get_candidate_referral_list' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'get_candidate_schedules' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id,contact_id',
            'candidate_id'  => 'required_without_all:reference_id,contact_id',
            'contact_id'    => 'required_without_all:reference_id,candidate_id'
        ),
        'edit_candidate_referral_status' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id',
            'candidate_id'  => 'required_without_all:reference_id',
            'referral_status'  => 'required'
        ),
        'get_candidates_tags' => array(
            'company_code'  => 'required',
            'tag_name'  => 'required'
        ),
        'add_candidate_tags' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id',
            'candidate_id'  => 'required_without_all:reference_id'
        ),
        'get_candidate_tags' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id',
            'candidate_id'  => 'required_without_all:reference_id'
        ),
        'delete_candidate_tag' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id',
            'candidate_id'  => 'required_without_all:reference_id'
        ),
        'add_candidate_personal_status' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id',
            'candidate_id'  => 'required_without_all:reference_id'
        ),
        'get_candidate_personal_status' => array(
            'company_code'  => 'required',
            'reference_id'  => 'required_without_all:candidate_id',
            'candidate_id'  => 'required_without_all:reference_id'
        ),
        'add_question' => array(
            'company_code'  => 'required',
            'question'      => 'required',
            'question_type' => 'required',
        ),
        'edit_question' => array(
            'company_code'  => 'required',
            'question_id'   => 'required',
            'question'      => 'required',
            'question_type' => 'required',
        ),
        'view_question' => array(
            'company_code'  => 'required',
            'question_id'   => 'required'
        ),
        'add_edit_exam' => array(
            'company_code'  => 'required',
            'exam_name'     => 'required',
            'exam_type'     => 'required',
            'work_experience'  => 'required',
            'exam_duration'    => 'required'
        ),
        'edit_exam_settings' => array(
            'company_code'  => 'required'
        ),
        'only_company_code' => array(
            'company_code'  => 'required'
        ),
        'get_company_assessments_list' => array(
            'company_code'  => 'required'
         ),   
        'add_edit_exam_question' => array(
            'company_code'  => 'required',
            'exam_id'       => 'required_without_all:exam_question_id',
            'question_id'   => 'required_without_all:exam_question_id',
            'exam_question_id'   => 'required_without_all:exam_id,question_id'
        ),
        'view_exam_question' => array(
            'company_code'  => 'required',
            'exam_id'       => 'required'
        ),
        'get_exam_details' => array(
            'company_code'  => 'required',
            'exam_id'       => 'required'
        ),
        'delete_question' => array(
            'company_code'  => 'required',
            'question_id'       => 'required'
        ),
        'get_company_assessments_all' => array(
            'company_code'  => 'required'
         ), 
        'get_assessment' => array(
            'company_code'  => 'required',
            'assessment_id' => 'required'
        )     
    );

}
?>
