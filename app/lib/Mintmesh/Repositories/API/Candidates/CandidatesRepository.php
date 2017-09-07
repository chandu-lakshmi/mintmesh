<?php namespace Mintmesh\Repositories\API\Candidates;


interface CandidatesRepository {
    
    /*
     * Create new Enterprise user resource in storage
     */
     public function getCandidateEmailTemplates($input);
     
     public function addCandidateComment($companyId,$comment,$referenceId,$candidateId,$userId);
     
     public function addCandidateEmail($input,$arrayuser,$companyId,$referenceId,$candidateId);
     
     public function addCandidateSchedule($input,$userId,$referenceId,$candidateId,$companyId);
     
     
     public function getCandidateActivities($companyId,$referenceId,$candidateId);
     
     public function getCandidateComments($companyId,$referenceId,$candidateId);
     
     
     public function getCandidateSentEmails($referenceId,$candidateId,$companyId);
   
     
}
?>
