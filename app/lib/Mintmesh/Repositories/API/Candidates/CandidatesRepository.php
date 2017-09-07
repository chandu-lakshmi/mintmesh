<?php namespace Mintmesh\Repositories\API\Candidates;


interface CandidatesRepository {
    
    /*
     * Create new Enterprise user resource in storage
     */
     public function getCandidateEmailTemplates($input);
     
     
     public function getCompanyEmployees($input);
     
     
     public function addCandidateComment($input,$userId);
     
     
     public function addCandidateEmail($input,$arrayuser);
     
     public function addCandidateSchedule($input,$userId);
     
     
     public function getCandidateActivities($input);
     
     public function getCandidateComments($input);
     
     
     public function getCandidateSentEmails($input);
   
     
}
?>
