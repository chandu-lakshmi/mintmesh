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
        
        public function getCandidateEmailTemplates($param) {
            
            // md5 the email id and attach with mintmesh constant for verification code
//            $emailActivationCode = md5($input['emailid']."_".Config::get('constants.MINTMESH')) ;
//            $user = array(
//                        "firstname" => $this->appEncodeDecode->filterString($input['fullname']),
//                        "emailid"   =>$this->appEncodeDecode->filterString(strtolower($input['emailid'])), 
//                        "password"  =>Hash::make($input['password']),
//                        "is_enterprise"  =>$this->appEncodeDecode->filterString($input['is_enterprise']),
//                        "group_id"  =>$this->appEncodeDecode->filterString($input['group_id']),
//                        "emailactivationcode" => $emailActivationCode
//            );
//            return $this->candidateEmailTemplates->create($user);
            //$emailTemplates = Candidate_Email_Templates::all();
           $sql = 'SELECT id,company_id,subject,body FROM candidate_email_templates where status=1';
           return  $selectRel = DB::Select($sql);
           // return $emailTemplates;
            
        }
        public function getCompanyEmployees($param){
           //print_r($param);
            $sql = 'select cs.user_id,cs.firstname,cs.lastname,cs.emailid from company c
                    right join contacts cs ON (c.id=cs.company_id)
                    where c.code="'.$param['company_code'].'"';
           return  $selectRel = DB::Select($sql);
            
        }
        

         public function addCandidateComment($param,$userId){
           
             $sql = 'select c.id from company c where c.code="'.$param['company_code'].'"';
             $selectRel = DB::Select($sql);
             $company_id = $selectRel[0]->id;
             
            
             $sql = "insert into candidate_comments (`company_id`,`reference_id`,`candidate_id`,`comment`,`created_by`,`created_at`)" ;
            $sql.=" values('".$company_id."','".$param['reference_id']."','".$param['candidate_id']."','".$this->appEncodeDecode->filterString($param['comment'])."','".$userId."','".gmdate('Y-m-d H:i:s')."')" ;
            $result = DB::statement($sql);
            
             $sql_log = "insert into candidate_activity_logs (`company_id`,`reference_id`,`candidate_id`,`module_type`,`status`,`activity_text`,`created_by`,`created_at`)" ;
            $sql_log.=" values('".$company_id."','".$param['reference_id']."','".$param['candidate_id']."','3','1','Comment Added','".$userId."','".gmdate('Y-m-d H:i:s')."')" ;
            
            $result = DB::statement($sql_log);
            
            
            return $result;
            
            //return  $this->candidateComments->create($arrayComment);
           
            
        }
        
        public function addCandidateEmail($param,$arrayuser){
             $sql = 'select c.id from company c where c.code="'.$param['company_code'].'"';
             $selectRel = DB::Select($sql);
             $company_id = $selectRel[0]->id;
             $fromname = '';
             $fromname = $arrayuser['firstname'].' '.$arrayuser['lastname'];
             
             $custom_subject = '';
             if(!empty($param['custom_subject'])){
                 $custom_subject = $param['custom_subject'];
             }
            
             $sql = "insert into candidate_sent_emails (`company_id`,`reference_id`,`candidate_id`,`to`,`to_name`,`from`,`subject`,`custom_subject`,`body`,`attachment_id`,`created_by`,`created_at`)" ;
            $sql.=" values('".$company_id."','".$param['reference_id']."','".$param['candidate_id']."','".$param['to']."','".$param['to_name']."','".$fromname."','".$this->appEncodeDecode->filterString($param['subject'])."','".$custom_subject."','".$this->appEncodeDecode->filterString($param['body'])."','','".$arrayuser['id']."','".gmdate('Y-m-d H:i:s')."')" ;
            $result = DB::statement($sql);
            
             $sql_log = "insert into candidate_activity_logs (`company_id`,`reference_id`,`candidate_id`,`module_type`,`status`,`activity_text`,`created_by`,`created_at`)" ;
            $sql_log.=" values('".$company_id."','".$param['reference_id']."','".$param['candidate_id']."','2','1','Email Sent','".$arrayuser['id']."','".gmdate('Y-m-d H:i:s')."')" ;
            
            $result = DB::statement($sql_log);
            
            
            return $result;
            
            //return  $this->candidateComments->create($arrayComment);
           
            
        }
        
        
        
         public function addCandidateSchedule($param,$userId){
          
             $sql = 'select c.id from company c where c.code="'.$param['company_code'].'"';
             $selectRel = DB::Select($sql);
             $company_id = $selectRel[0]->id;
             
            

             $sql = "insert into candidate_schedule (`company_id`,`reference_id`,`candidate_id`,`schedule_for`,`attendees`,`interview_date`,`interview_from_time`,`interview_to_time`,`interview_time_zone`,`interview_location`,`notes`,`created_by`,`created_at`)" ;
            $sql.=" values('".$company_id."','".$param['reference_id']."','".$param['candidate_id']."','".$param['schedule_for']."','".$param['attendees']."','".$param['interview_date']."','".$this->appEncodeDecode->filterString($param['interview_from_time'])."','".$this->appEncodeDecode->filterString($param['interview_to_time'])."','".$this->appEncodeDecode->filterString($param['interview_time_zone'])."','".$this->appEncodeDecode->filterString($param['interview_location'])."','".$this->appEncodeDecode->filterString($param['notes'])."','".$userId."','".gmdate('Y-m-d H:i:s')."')" ;
            $result = DB::statement($sql);
            
             $sql_log = "insert into candidate_activity_logs (`company_id`,`reference_id`,`candidate_id`,`module_type`,`status`,`activity_text`,`created_by`,`created_at`)" ;
            $sql_log.=" values('".$company_id."','".$param['reference_id']."','".$param['candidate_id']."','1','1','".$param['schedule_for']." Schedule','".$userId."','".gmdate('Y-m-d H:i:s')."')" ;
            
            $result = DB::statement($sql_log);
            
            
            return $result;
            
            //return  $this->candidateComments->create($arrayComment);
           
            
        }
        
        public function getCandidateActivities($param) {
            //print_r($param);
            /* 
           $sql = 'select c.id from company c where c.code="'.$param['company_code'].'"';
             $selectRel = DB::Select($sql);
             $company_id = $selectRel[0]->id; */
           
          $sql = "SELECT cal.id,cal.company_id,cal.reference_id,cal.candidate_id,cmt.module_name,cal.activity_text,concat(u.firstname,'',u.lastname) as created_by,cal.created_at
FROM candidate_activity_logs cal
INNER JOIN candidate_module_types cmt ON (cmt.id=cal.module_type)
INNER JOIN users u ON (u.id=cal.created_by) where company_id = '".$param['company_id']."' AND reference_id= '".$param['reference_id']."' order by id desc ";
           return  $selectRel = DB::Select($sql);
           // return $emailTemplates;
            
        }
        
        public function getCandidateComments($param) {
           
          $sql = "SELECT cc.id,cc.comment,concat(u.firstname,'',u.lastname) as created_by,cc.created_at from candidate_comments cc INNER JOIN users u ON (u.id=cc.created_by) where cc.company_id = '".$param['company_id']."' AND cc.reference_id= '".$param['reference_id']."' order by id desc ";
           return  $selectRel = DB::Select($sql);
           // return $emailTemplates;
            
        }
        public function getCandidateSentEmails($param) {
           
           $sql = "SELECT cse.id,cse.to_name,cse.from,cet.subject,cse.custom_subject,cse.body, CONCAT(u.firstname,'',u.lastname) AS created_by,cse.created_at
FROM candidate_sent_emails cse
INNER JOIN candidate_email_templates cet ON (cet.id=cse.subject)
INNER JOIN users u ON (u.id=cse.created_by) where cse.company_id = '".$param['company_id']."' AND cse.reference_id= '".$param['reference_id']."' order by id desc ";
           return  $selectRel = DB::Select($sql);
           // return $emailTemplates;
            
        }
        
        
}
