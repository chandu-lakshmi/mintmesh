<?php namespace Mintmesh\Repositories\API\Candidates;


interface CandidatesRepository {
    
    /*
     * Create new Enterprise user resource in storage
     */
     public function getCandidateEmailTemplates($input);
     
     
     public function getCompanyEmployees($input);
   
     
}
?>
