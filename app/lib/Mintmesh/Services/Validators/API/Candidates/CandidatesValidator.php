<?php

namespace Mintmesh\Services\Validators\Api\Candidates;

use Mintmesh\Services\Validators\Validator;

class CandidatesValidator extends Validator {

    public static $rules = array(
        'get_candidate_email_templates' => array(
            'company_code'       => 'required',
        )
    );

}
?>
