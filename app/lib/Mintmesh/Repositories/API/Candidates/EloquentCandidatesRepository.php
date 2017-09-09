<?php namespace Mintmesh\Repositories\API\Candidates;

use User;
use Company_Profile,Company_Resumes;
use Groups;
use Company_Contacts;
use Emails_Logs;
use Levels_Logs;
use Notifications_Logs, Candidate_Email_Templates;
use Config ;
use Mail ;
use DB;
use Mintmesh\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Mintmesh\Services\APPEncode\APPEncode ;
class EloquentCandidatesRepository extends BaseRepository implements CandidatesRepository {

    protected $user, $companyProfile, $CompanyContact,$groups;
    protected $email, $level, $appEncodeDecode, $companyResumes, $candidateEmailTemplates;
        
    const COMPANY_RESUME_STATUS = 0;
    const COMPANY_RESUME_S3_MOVED_STATUS = 1;
    const COMPANY_RESUME_AI_PARSED_STATUS = 2;
    const DEFAULT_CANDIDATE_ACTIVITY_STATUS = 1;


    public function __construct(User $user,
                                    Company_Profile $companyProfile,
                                    Company_Resumes $companyResumes,
                                    Groups $groups,
                                    Company_Contacts $CompanyContact,
                                    Emails_Logs $email,
                                    Candidate_Email_Templates $candidateEmailTemplates,
                                    APPEncode $appEncodeDecode){ 
                $this->user = $user;    
                $this->companyProfile = $companyProfile; 
                $this->companyResumes = $companyResumes; 
                $this->candidateEmailTemplates = $candidateEmailTemplates; 
                $this->groups = $groups; 
                $this->companyContact = $CompanyContact;    
                $this->appEncodeDecode = $appEncodeDecode ;       
        }
        
        public function getCandidateEmailTemplates() {
            
           $sql = 'SELECT id, company_id, subject, body FROM candidate_email_templates where status=1';
           return  $selectRel = DB::Select($sql);
        }
       
        public function addCandidateComment($companyId = 0, $comment = '', $referenceId = 0, $candidateId = 0, $userId = 0){
             
            $return = FALSE;
            if(!empty($companyId) && (!empty($referenceId) || !empty($candidateId))){ 
             
                $comment =  $this->appEncodeDecode->filterString($comment);
                #add Candidate Comment here
                $sql = "INSERT INTO candidate_comments (`company_id`, `reference_id`, `candidate_id`, `comment`, `created_by`, `created_at`)" ;
                $sql.=" VALUES(".$companyId.", ".$referenceId.", ".$candidateId.", '".$comment."', ".$userId.", '".gmdate('Y-m-d H:i:s')."')" ;
                $return = DB::statement($sql);
                #add Candidate Activity Logs here
                $moduleType   = 3;
                $activityText = 'Comment Added';
                $activityLog  = $this->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText);
            }
            return $return;
        }
        
        public function addCandidateActivityLogs($companyId = '', $referenceId = '', $candidateId = '', $userId = '', $moduleType = '', $activityText = '') {
            
            $return     = FALSE;
            $createdAt  = gmdate('Y-m-d H:i:s');
            $status     = self::DEFAULT_CANDIDATE_ACTIVITY_STATUS;
            if($companyId){
                #insert Candidate Activity Logs here
                $sql = "INSERT INTO candidate_activity_logs (`company_id`, `reference_id`, `candidate_id`, `module_type`, `status`, `activity_text`, `created_by`, `created_at`)" ;
                $sql.=" VALUES(".$companyId.", ".$referenceId.", ".$candidateId.", ".$moduleType.", ".$status.", '".$activityText."', ".$userId.", '".$createdAt."')" ;
                $return = DB::statement($sql);
            }
            return $return;
        }
        
        public function addCandidateEmail($candidate = array(), $logUser = array(), $companyId = 0, $referenceId = 0, $candidateId = 0){
             
            $createdAt      = gmdate('Y-m-d H:i:s');
            $userName       = $logUser['user_name'];
            $userId         = $logUser['user_id'];
            $userEmail      = $logUser['user_emailid'];
            $candidateName  = $candidate['name'];
            $candidateEmail = $candidate['email'];
            $subjectId      = $candidate['subject_id'];
            $emailSubject   = $this->appEncodeDecode->filterString($candidate['email_subject']);
            $emailBody      = $this->appEncodeDecode->filterString($candidate['email_body']);
            
            if(!empty($companyId) && (!empty($referenceId) || !empty($candidateId))){
                #insert Candidate Email details here
                $sql = "INSERT INTO candidate_sent_emails (`company_id`, `reference_id`, `candidate_id`, `to`, `to_name`, `from`, `subject`, `custom_subject`, `body`, `attachment_id`, `created_by`, `created_at`)" ;
                $sql.=" VALUES('".$companyId."', '".$referenceId."', '".$candidateId."', '".$candidateEmail."', '".$candidateName."', '".$userName."', '".$subjectId."', '".$emailSubject."', '".$emailBody."', '', '".$userId."', '".$createdAt."')" ;
                $return = DB::statement($sql);
                #add Candidate Activity Logs here
                $moduleType   = 2;
                $activityText = 'Email Sent';
                $activityLog  = $this->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText);
            }
            return $return;
        }
        
        public function addCandidateSchedule($param, $userId, $referenceId, $candidateId, $companyId){
            
            if(!empty($companyId) && (!empty($referenceId) || !empty($candidateId))){
                
                $createdAt   = gmdate('Y-m-d H:i:s');
                $attendees   = $param['attendees'];
                $scheduleFor = $param['schedule_for'];
                $date        = $param['interview_date'];
                $notes      = !empty($param['notes']) ? $param['notes'] : '';
                $notes      = $this->appEncodeDecode->filterString($notes);
                $fromTime   = $this->appEncodeDecode->filterString($param['interview_from_time']);
                $toTime     = $this->appEncodeDecode->filterString($param['interview_to_time']);
                $timeZone   = $this->appEncodeDecode->filterString($param['interview_time_zone']);
                $location   = $this->appEncodeDecode->filterString($param['interview_location']);
                
                $sql = "INSERT INTO candidate_schedule (`company_id`,`reference_id`,`candidate_id`,`schedule_for`,`attendees`,`interview_date`,`interview_from_time`,`interview_to_time`,`interview_time_zone`,`interview_location`,`notes`,`created_by`,`created_at`)" ;
                $sql.=" VALUES('".$companyId."', '".$referenceId."', '".$candidateId."', '".$scheduleFor."', '".$attendees."', '".$date."', '".$fromTime."', '".$toTime."', '".$timeZone."', '".$location."', '".$notes."', '".$userId."', '".$createdAt."')" ;
                $return = DB::statement($sql);
                #add Candidate Activity Logs here
                $moduleType   = 1;
                $activityText = $param['schedule_for']." Schedule";
                $activityLog  = $this->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText);
            }
            return $return;  
        }
        
        public function getCandidateActivities($companyId = 0, $referenceId = 0, $candidateId = 0) {
           
            $result = '';
            if($companyId) {
                $sql = "SELECT cal.id, cal.company_id, cal.reference_id, cal.candidate_id, cmt.module_name, cal.activity_text, concat(u.firstname,'',u.lastname) as created_by,cal.created_at
                        FROM candidate_activity_logs cal
                        INNER JOIN candidate_module_types cmt ON (cmt.id=cal.module_type)
                        INNER JOIN users u ON (u.id=cal.created_by) where cal.company_id = '".$companyId."' AND cal.candidate_id = '".$candidateId."' ";
                if(!empty($referenceId)){
                  $sql .=" AND cal.reference_id= '".$referenceId."' ";
                }
                $sql .=" order by id desc ";
                $result = DB::Select($sql);
            }
           return $result;  
        }
        
        public function getCandidateComments($companyId = 0, $referenceId = 0, $candidateId = 0) {
            
            $result = '';
            if($companyId) {
                $sql = "SELECT cc.id, cc.comment, concat(u.firstname,'',u.lastname) as created_by, cc.created_at from candidate_comments cc 
                        INNER JOIN users u ON (u.id=cc.created_by) where cc.company_id = '".$companyId."' ";
                if(!empty($referenceId)){
                  $sql .=" AND cc.reference_id= '".$referenceId."' ";
                }
                $sql .=" order by id desc ";
                $result = DB::Select($sql);
            }
            return $result;
        }
        
        public function getCandidateSentEmails($companyId = 0, $referenceId = 0, $candidateId = 0) {
           
            $result = '';
            if($companyId) { 
                $sql = "SELECT cse.id, cse.to_name, cse.from, cet.subject, cse.custom_subject, cse.body, CONCAT(u.firstname,'',u.lastname) AS created_by,cse.created_at
                        FROM candidate_sent_emails cse
                        INNER JOIN candidate_email_templates cet ON (cet.id=cse.subject)
                        INNER JOIN users u ON (u.id=cse.created_by) where cse.company_id = '".$companyId."' ";
                if(!empty($referenceId)){
                    $sql .=" AND cse.reference_id= '".$referenceId."' ";
                }
                $sql .=" order by id desc ";
                $result = DB::Select($sql);
            }
            return $result;
        }
        
        public function getCandidateSchedules($companyId = 0, $referenceId = 0, $candidateId = 0) {
            
            $result = '';
            if($companyId) {
                $sql = "SELECT cs.id, cs.schedule_for, cs.attendees, cs.interview_date, cs.interview_from_time, cs.interview_to_time, cs.interview_time_zone, cs.interview_location, cs.notes, CONCAT(u.firstname,'',u.lastname) AS created_by,cs.created_at FROM candidate_schedule cs
                        INNER JOIN users u ON (u.id=cs.created_by) where cs.company_id = '".$companyId."'  ";
                if(!empty($referenceId)){
                    $sql .=" AND cs.reference_id= '".$referenceId."' ";
                }
                $sql .=" order by id desc ";
                $result = DB::Select($sql);
            }
            return $result;
        }
          
}
