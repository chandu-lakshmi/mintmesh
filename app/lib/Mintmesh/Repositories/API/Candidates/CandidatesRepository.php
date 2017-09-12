<?php namespace Mintmesh\Repositories\API\Candidates;


interface CandidatesRepository {
    
    /*
     * Create new Enterprise user resource in storage
     */
     public function getCandidateEmailTemplates();
     
     public function addCandidateComment($companyId,$comment,$referenceId,$candidateId,$userId);
     
     public function addCandidateEmail($input,$arrayuser,$companyId,$referenceId,$candidateId);
     
     public function addCandidateSchedule($input,$userId,$referenceId,$candidateId,$companyId);
     
     
     public function getCandidateActivities($companyId,$referenceId,$candidateId);
     
     public function getCandidateComments($companyId,$referenceId,$candidateId);
     
     
     public function getCandidateSentEmails($referenceId,$candidateId,$companyId);
     
     public function getCandidateSchedules($companyId,$referenceId,$candidateId);
     
     public function getCandidatesTags($tag_name);
     
     
     public function addCandidateTags($companyId, $id, $referenceId, $candidateId, $userId);
     
     
     public function getCandidateTags($companyId,$referenceId,$candidateId);
     
     
     public function deleteCandidateTag($companyId, $id, $referenceId, $candidateId, $userId);
   
     
}
?>
