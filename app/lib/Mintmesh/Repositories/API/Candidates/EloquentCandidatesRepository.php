<?php namespace Mintmesh\Repositories\API\Candidates;

use Config, Question, Exam, Question_Option;
use Mail, DB, Candidate_Email_Templates, Question_Bank;
use Exam_Question;
use Mintmesh\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Mintmesh\Services\APPEncode\APPEncode ;

class EloquentCandidatesRepository extends BaseRepository implements CandidatesRepository {

    protected $exam, $question, $candidateEmailTemplates, $questionOption;
        
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const DEFAULT_CANDIDATE_ACTIVITY_STATUS = 1;


    public function __construct(
                                Question $question,
                                Exam $exam,
                                Exam_Question $examQuestion,
                                Question_Option $questionOption,
                                Question_Bank $questionBank,
                                Candidate_Email_Templates $candidateEmailTemplates,
                                APPEncode $appEncodeDecode
                                ){      
                $this->candidateEmailTemplates = $candidateEmailTemplates;    
                $this->exam = $exam;    
                $this->examQuestion = $examQuestion;    
                $this->question = $question;    
                $this->questionOption  = $questionOption;    
                $this->questionBank    = $questionBank;    
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
                DB::statement($sql); 
                $lastInsertId = DB::table('candidate_comments')
                     ->where('company_id', $companyId)   
                    ->orderBy('id', 'desc')
                     ->take(1)   
                    ->get();
                $lId = $lastInsertId[0]->id;
                #add Candidate Activity Logs here
                $moduleType   = 3;
                $activityText = $comment;//'Comment Added';
                $activityLog  = $this->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText, $comment);
                
                $return = $this->getLastInsertComment($lId);
                 
                
            }
            return $return;
        }
        
        public function addCandidateActivityLogs($companyId = '', $referenceId = '', $candidateId = '', $userId = '', $moduleType = '', $activityText = '', $activityComment = '') {
            
            $return     = FALSE;
            $createdAt  = gmdate('Y-m-d H:i:s');
            $status     = self::DEFAULT_CANDIDATE_ACTIVITY_STATUS;
            $activityText    =  $this->appEncodeDecode->filterString($activityText);
            $activityComment =  $this->appEncodeDecode->filterString($activityComment);
            if($companyId){
                #insert Candidate Activity Logs here
                $sql = "INSERT INTO candidate_activity_logs (`company_id`, `reference_id`, `candidate_id`, `module_type`, `status`, `activity_text`, `comment`, `created_by`, `created_at`)" ;
                $sql.=" VALUES(".$companyId.", ".$referenceId.", ".$candidateId.", ".$moduleType.", ".$status.", '".$activityText."', '".$activityComment."',".$userId.", '".$createdAt."')" ;
                $result     = DB::statement($sql);
                $lastInsert = DB::Select("SELECT LAST_INSERT_ID() as last_id");
                if(isset($lastInsert[0]) && !empty($lastInsert[0])){
                    $return = $lastInsert[0]->last_id;
                }
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

                $sql = "INSERT INTO candidate_sent_emails (`company_id`, `reference_id`, `candidate_id`, `to`, `to_name`, `from`, `from_name`, `subject`, `custom_subject`, `body`, `attachment_id`, `created_by`, `created_at`)" ;
                $sql.=" VALUES('".$companyId."', '".$referenceId."', '".$candidateId."', '".$candidateEmail."', '".$candidateName."', '".$userEmail."','".$userName."', '".$subjectId."', '".$emailSubject."', '".$emailBody."', '', '".$userId."', '".$createdAt."')" ;
                DB::statement($sql);
                
                $lastInsertId = DB::table('candidate_sent_emails')
                     ->where('company_id', $companyId)   
                    ->orderBy('id', 'desc')
                     ->take(1)   
                    ->get();
                $lId = $lastInsertId[0]->id;
                #add Candidate Activity Logs here
                $moduleType   = 2;
                $activityText = 'Email Sent';
                $activityLog  = $this->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText, $emailSubject);
               $return = $this->getlastInsertEmail($lId);
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
                $timeZone   = !empty($param['interview_time_zone']) ? $param['interview_time_zone'] : '';
                $notes      = $this->appEncodeDecode->filterString($notes);
                $timeZone   = $this->appEncodeDecode->filterString($timeZone);
                $fromTime   = $this->appEncodeDecode->filterString(date("H:i", strtotime($param['interview_from_time'])));
                $toTime     = $this->appEncodeDecode->filterString(date("H:i", strtotime($param['interview_to_time'])));
                $location   = $this->appEncodeDecode->filterString($param['interview_location']);
                
                $sql = "INSERT INTO candidate_schedule (`company_id`,`reference_id`,`candidate_id`,`schedule_for`,`attendees`,`interview_date`,`interview_from_time`,`interview_to_time`,`interview_time_zone`,`interview_location`,`notes`,`created_by`,`created_at`)" ;
                $sql.=" VALUES('".$companyId."', '".$referenceId."', '".$candidateId."', '".$scheduleFor."', '".$attendees."', '".$date."', '".$fromTime."', '".$toTime."', '".$timeZone."', '".$location."', '".$notes."', '".$userId."', '".$createdAt."')" ;
                DB::statement($sql);
                
                $lastInsertId = DB::table('candidate_schedule')
                     ->where('company_id', $companyId)   
                    ->orderBy('id', 'desc')
                     ->take(1)   
                    ->get();
                $lId = $lastInsertId[0]->id;
                
                #add Candidate Activity Logs here
                $moduleType   = 1;
                $activityText = $param['schedule_for'];
                $activityLog  = $this->addCandidateActivityLogs($companyId, $referenceId, $candidateId, $userId, $moduleType, $activityText);
                $return = $this->getlastInsertSchedules($lId);
            }
            return $return;  
        }
        
        public function getCandidateActivities($companyId = 0, $referenceId = 0, $candidateId = 0) {
           
            $result = '';
            if($companyId) {
                $sql = "SELECT cal.id, cal.company_id, cal.reference_id, cal.candidate_id, cmt.module_name, cal.activity_text, cal.comment, concat(u.firstname,'',u.lastname) as created_by,cal.created_at
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
                $sql = "SELECT cse.id, cse.to, cse.to_name, cse.from, cse.from_name, cet.subject, cse.custom_subject, cse.body, CONCAT(u.firstname,'',u.lastname) AS created_by,cse.created_at
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
        
        public function getLastInsertComment($lId){
             $sql = "SELECT cc.id, cc.comment, concat(u.firstname,'',u.lastname) as created_by, cc.created_at from candidate_comments cc INNER JOIN users u ON (u.id=cc.created_by) where cc.id ='".$lId."' ";
            return $return = DB::Select($sql); 
        }
        
        public function getlastInsertEmail($lId) {
            $sqlE = "SELECT cse.id, cse.to, cse.to_name, cse.from_name, cse.from, cet.subject, cse.custom_subject, cse.body, CONCAT(u.firstname,'',u.lastname) AS created_by,cse.created_at
                        FROM candidate_sent_emails cse
                        INNER JOIN candidate_email_templates cet ON (cet.id=cse.subject)
                        INNER JOIN users u ON (u.id=cse.created_by) where cse.id = '".$lId."' ";
            return $return = DB::Select($sqlE); 
            
        }
        
        public function getlastInsertSchedules($lId) {
             $sqls = "SELECT cs.id, cs.schedule_for, cs.attendees, cs.interview_date, cs.interview_from_time, cs.interview_to_time, cs.interview_time_zone, cs.interview_location, cs.notes, CONCAT(u.firstname,'',u.lastname) AS created_by,cs.created_at FROM candidate_schedule cs
                        INNER JOIN users u ON (u.id=cs.created_by) where cs.id = '".$lId."'  ";
            return  $return = DB::Select($sqls);
            
        }
        
        public function getCandidatesTags($tag_name) {
            $result = '';
            //if($companyId) {
                $sql = "SELECT id as tag_id,tag_name from candidates_tags_list where tag_name LIKE '%".$tag_name."%'  ";
                $result = DB::Select($sql);
            //}
            return $result;
        }
          
       public function addCandidateTags($companyId = 0, $tag_id = '', $referenceId = 0, $candidateId = 0, $userId = 0){
             
            $return = FALSE;
            if(!empty($companyId) && (!empty($referenceId) || !empty($candidateId))){ 
                #add Candidate Comment here
                $sql = "INSERT INTO candidate_tags (`company_id`, `reference_id`, `candidate_id`, `tag_id`, `created_by`, `created_at`)" ;
                $sql.=" VALUES(".$companyId.", ".$referenceId.", ".$candidateId.", '".$tag_id."', ".$userId.", '".gmdate('Y-m-d H:i:s')."')" ;
                $return = DB::statement($sql); 
                $lastInsert = DB::Select("SELECT LAST_INSERT_ID() as last_id");
                if(isset($lastInsert[0]) && !empty($lastInsert[0])){
                    $return = $lastInsert[0]->last_id;
                }
                
            }
            return $return;
        } 
        
         public function getCandidateTags($companyId = 0, $referenceId = 0, $candidateId = 0){
            $result = '';
            if($companyId) {
                $sql = "SELECT ct.id, ctl.id as tag_id, ctl.tag_name, ct.created_at from candidate_tags ct INNER JOIN candidates_tags_list ctl ON (ctl.id=ct.tag_id) where ct.company_id = '".$companyId."'  ";
                if(!empty($candidateId)){
                    $sql .=" AND ct.candidate_id= '".$candidateId."' ";
                }
                $sql .=" order by id desc ";
                $result = DB::Select($sql);
            }
            return $result;  
        }
        
        
     public function deleteCandidateTag($companyId = 0, $id = '', $referenceId = 0, $candidateId = 0, $userId = 0) {
            $result = '';
            //if($companyId) {
                $sql = "delete from candidate_tags where id ='".$id."' ";
                $result = DB::Select($sql);
            //}
            return true;
        }
        
     public function addCandidatePersonalStatus($companyId = 0,$referenceId = 0, $candidateId = 0, $userId = 0,$status_name){
            $return = array('status' => FALSE);
            if(!empty($companyId) && (!empty($referenceId) || !empty($candidateId))){ 
                $status_sql = "SELECT * from candidate_personal_info_status where  company_id = '".$companyId."'  ";
                if(!empty($referenceId)){
                    $status_sql .=" AND reference_id= '".$referenceId."' ";
                }
                $queryresult = DB::Select($status_sql);
                if($queryresult){
                   $sql = "UPDATE candidate_personal_info_status SET status_name='".$status_name."',updated_at='".gmdate('Y-m-d H:i:s')."' where id='".$queryresult[0]->id."'" ;
                DB::statement($sql);
                $return = array('status' => true,'msg' => 'Successfully Updated');
                }else{
                     $sql = "INSERT INTO candidate_personal_info_status (`company_id`, `reference_id`, `candidate_id`, `status_name`, `created_by`, `created_at`)" ;
                $sql.=" VALUES(".$companyId.", ".$referenceId.", ".$candidateId.", '".$status_name."', ".$userId.", '".gmdate('Y-m-d H:i:s')."')" ;
                DB::statement($sql);
                $return = array('status' => true,'msg' => 'Successfully Created');
                }
               
            }
            return $return;
        }    
        
     public function getCandidatePersonalStatus($companyId = 0,$referenceId = 0, $candidateId = 0){
         $return = FALSE;
          if(!empty($companyId) && (!empty($referenceId) || !empty($candidateId))){ 
                $status_sql = "SELECT id,status_name from candidate_personal_info_status where  company_id = '".$companyId."'  ";
                if(!empty($referenceId)){
                    $status_sql .=" AND reference_id = '".$referenceId."' ";
                }
                $return = DB::Select($status_sql);
          }    
          return $return;
     }
     
    public function getQuestionTypes($companyCode = '') {

        $result =  DB::table('question_type')
                    ->select('idquestion_type', 'name', 'description')
                    ->where('status', self::STATUS_ACTIVE)
                    ->get();
        return  $result;
    }
    
    public function getQuestionLibrariesList($companyCode = '') {

        $result =  DB::table('question_library')
                    ->select('idquestion_library', 'name')
                    ->where('status', self::STATUS_ACTIVE)
                    ->get();
        return  $result;
    }
    
    public function addQuestion($qstInput = array(), $companyId = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $addQuestion = array(
                    "company_id"        => $companyId,
                    "question_type"     => $this->appEncodeDecode->filterString($qstInput['qst_type']),
                    "question"          => $this->appEncodeDecode->filterString($qstInput['question']),
                    "question_notes"    => $this->appEncodeDecode->filterString($qstInput['qst_notes']),
                    "question_value"    => $qstInput['qst_value'],
                    "is_answer_required"    => $qstInput['is_ans_req'],
                    "has_multiple_answers"  => $qstInput['has_multi_ans'],
                    "status"        => self::STATUS_ACTIVE,
                    "created_at"    => $createdAt
                );
        return $this->question->create($addQuestion);
    }
    
    public function addQuestionBank($qstInput = array(), $companyId = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $addQuestionBank = array(
                    "company_id"    => $companyId,
                    "idquestion_library"   => $qstInput['library_id'],
                    "idquestion"    => $qstInput['question_id'],
                    "status"        => self::STATUS_ACTIVE,
                    "created_at"    => $createdAt
                );
        return $this->questionBank->create($addQuestionBank);
    }
    
    public function editQuestionBank($qstInput = array(), $qstBankId = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $editQuestionBank = array(
                    "idquestion_library"   => $qstInput['library_id'],
                    "status"        => $qstInput['status'],
                    "updated_at"    => $createdAt
                );
        if(!empty($qstBankId)){
               $return = Question_Bank::where ('idquestion_bank', $qstBankId)->update($editQuestionBank); 
            }
        return $return;
    }
    
    public function addQuestionOption($qstInput = array(), $questionId = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $addQuestionOption = array(
                        "idquestion"   => $questionId,
                        "option"            => $this->appEncodeDecode->filterString($qstInput['option']),
                        "is_correct_answer" => $qstInput['is_correct_ans'],
                        "status"        => self::STATUS_ACTIVE,
                        "created_at"    => $createdAt
                    );
        return $this->questionOption->create($addQuestionOption);
    }
    
    public function editQuestion($qstInput = array(), $questionId = 0)
    {   
        $return = FALSE;
        $createdAt   = gmdate('Y-m-d H:i:s');
        $editQuestion = array(
                    "question_type"   => $this->appEncodeDecode->filterString($qstInput['qst_type']),
                    "question"          => $this->appEncodeDecode->filterString($qstInput['question']),
                    "question_notes"    => $this->appEncodeDecode->filterString($qstInput['qst_notes']),
                    "question_value"    => $qstInput['qst_value'],
                    "is_answer_required"    => $qstInput['is_ans_req'],
                    "has_multiple_answers"  => $qstInput['has_multi_ans'],
                    "updated_at"        => $createdAt
        );
        if(!empty($questionId)){
               $return = Question::where ('idquestion', $questionId)->update($editQuestion); 
            }
        return $return;
    }
    
    public function editQuestionOption($qstInput = array(), $optionId = 0)
    {   
        $return = FALSE;
        $createdAt   = gmdate('Y-m-d H:i:s');
        $editQuestionOption = array(
                        "option"            => $this->appEncodeDecode->filterString($qstInput['option']),
                        "is_correct_answer" => $qstInput['is_correct_ans'],
                        "status"        => $qstInput['status'],
                        "updated_at"    => $createdAt
                    );
        if(!empty($optionId)){
               $return = Question_Option::where ('idquestion_option', $optionId)->update($editQuestionOption); 
            }
        return $return;
    }
    
    public function getQuestion($questionId = 0){
        
        $result =  DB::table('question')
                    ->select('question.question', 'question.question_value', 'question.question_notes', 'question.question_type')
                    ->where('question.idquestion', $questionId)
                    ->get();   
        return $result;
    }
    
    public function getQuestionOptions($questionId = 0){
        
        $result =  DB::table('question_option')
                        ->select('idquestion_option as option_id', 'option', 'is_correct_answer')
                        ->where('idquestion', $questionId)
                        ->where('status', self::STATUS_ACTIVE)
                        ->get();
        return $result;
    }
    
    public function getQuestionLibraries($questionId = 0){
        
        $result =  DB::table('question_bank')
                    ->select('question_bank.idquestion_bank as qst_bank_id', 'question_bank.idquestion_library as library_id', 'question_library.name as library_name')
                    ->join('question_library', 'question_bank.idquestion_library', '=', 'question_library.idquestion_library')
                    ->where('question_bank.idquestion', $questionId)
                    ->where('question_bank.status', self::STATUS_ACTIVE)
                    ->get();   
        return $result;
    }
    
    public function addExam($examInput = array(), $companyId = 0, $createdBy = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $addExamArr  = array(
                        "company_id"   => $companyId,
                        "name"         => $this->appEncodeDecode->filterString($examInput['exam_name']),
                        "description_url"  => $this->appEncodeDecode->filterString($examInput['desc_url']),
                        "max_duration"     => $examInput['exam_dura'],
                        "idexam_type"      => $examInput['exam_type'],
                        "work_experience"  => $examInput['work_exp'],
                        "created_by"   => $createdBy,
                        "created_at"   => $createdAt
                    );
        return $this->exam->create($addExamArr);
    }
    
    public function editExam($examInput = array(), $examId = 0, $updatedBy = 0)
    {   
        $return = FALSE;
        $createdAt    = gmdate('Y-m-d H:i:s');
        $editExamArr  = array(
                        "name"         => $this->appEncodeDecode->filterString($examInput['exam_name']),
                        "description_url"  => $this->appEncodeDecode->filterString($examInput['desc_url']),
                        "max_duration"     => $examInput['exam_dura'],
                        "idexam_type"      => $examInput['exam_type'],
                        "work_experience"  => $examInput['work_exp'],
                        "updated_by"   => $updatedBy,
                        "updated_at"   => $createdAt
                    );
        if(!empty($examId)){
               $return = Exam::where ('idexam', $examId)->update($editExamArr); 
        }
        return $return;
    }
    
    public function addExamQuestion($examId = 0, $questionId = 0, $createdBy = 0, $questionValue = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $addExamDetailsArr  = array(
                        "idexam"         => $examId,
                        "idquestion"     => $questionId,
                        "question_value" => $questionValue,
                        "created_by"   => $createdBy,
                        "created_at"   => $createdAt
                    );
        return $this->examQuestion->create($addExamDetailsArr);
    }
    
    public function removeExamQuestion($examQuestionId = 0, $createdBy = 0)
    {   
        $createdAt   = gmdate('Y-m-d H:i:s');
        $removeExamDetailsArr  = array(
                        "status" => self::STATUS_INACTIVE,
                        "updated_by"   => $createdBy,
                        "updated_at"   => $createdAt
                    );
        if(!empty($examQuestionId)){
               $return = Exam_Question::where ('idexam_question', $examQuestionId)->update($removeExamDetailsArr); 
        }
        return $return;
    }
    
    public function editExamSettings($examInput = array(), $examId = 0, $updatedBy = 0)
    {   
        $return = FALSE;
        $createdAt    = gmdate('Y-m-d H:i:s');
        $editExamArr  = array(
                        "max_duration"  => $examInput['exam_dura'],
                        "is_active"     => $examInput['is_active'],
                        "exam_url"      => $this->appEncodeDecode->filterString($examInput['exam_url']),
                        "min_marks"     => $examInput['min_marks'],
                        "start_date_time"     => $examInput['str_date'],
                        "end_date_time"       => $examInput['end_date'],
                        "is_auto_screening"   => $examInput['auto_scr'],
                        "enable_full_screen"  => $examInput['full_scr'],
                        "shuffle_questions"   => $examInput['shuffle'],
                        "reminder_emails"     => $examInput['reminder'],
                        "confirmation_email"  => $examInput['confirm'],
                        "password_protected"  => $examInput['pass_protect'],
                        "password"     => $this->appEncodeDecode->filterString($examInput['password']),
                        "updated_by"   => $updatedBy,
                        "updated_at"   => $createdAt
                    );
        if(!empty($examId)){
               $return = Exam::where ('idexam', $examId)->update($editExamArr); 
        }
        return $return;
    }
    
    public function getQuestionsList($companyId = 0, $page = 0){
        
        $start = $end = 0;
        if (!empty($page)){
            $end = $page-1 ;
            $start = $end*10 ;
        }
        $result =  DB::table('question')
                    ->select('idquestion','question', 'question_value', 'question_type')
                    ->where('company_id', $companyId)
                    ->limit(10)->skip($start)
                    ->get();
        return $result;
    }
    
    public function getExamDetails($examId = 0){
        
        $result =  DB::table('exam as e')
                    ->select('e.idexam','e.idexam_type','e.name as exam_name','e.exam_url','e.description_url','e.work_experience','e.start_date_time','e.end_date_time',
                            'e.is_active','e.is_auto_screening','e.password_protected','e.password','e.min_marks','e.enable_full_screen','e.shuffle_questions',
                            'e.reminder_emails','e.created_at','e.updated_at','e.created_by','e.updated_by','t.name as exam_type_name','r.name as experience_name')
                    ->join('exam_type as t', 'e.idexam_type', '=', 't.idexam_type')
                    ->join('experience_ranges as r', 'e.work_experience', '=', 'r.id')
                    ->where('e.idexam', $examId)
                    ->get();
        return $result;
    }
    
    
    public function getCompanyAssessmentsList($companyId = 0,$name=''){
        $result = '';
        if(!empty($name) && !empty($companyId)){
        $result =  DB::table('exam')
                    ->select('exam.idexam','exam.name')
                    ->where('exam.company_id', $companyId)
                    ->where('exam.is_active',1)
                    ->where('exam.name', 'LIKE', '' . $name . '%')
                    ->get();
        }
       return $result;
    }
    
    
    public function editQuestionOptionInactiveAll($questionId = 0)
    {   
        $return = FALSE;
        $editQuestionOption = array("status" => self::STATUS_INACTIVE);
        if(!empty($optionId)){
               $return = Question_Option::where ('idquestion', $questionId)->update($editQuestionOption); 
            }
        return $return;
    }
    
    public function editQuestionBankInactiveAll($questionId = 0){   
        $return = FALSE;
        $editQuestionBank = array("status" => self::STATUS_INACTIVE);
        if(!empty($optionId)){
               $return = Question_Bank::where ('idquestion', $questionId)->update($editQuestionBank); 
            }
        return $return;
    }
    
    public function deleteQuestion($questionId = 0){   
        $return = FALSE;
        $delQuestion = array("status" => self::STATUS_INACTIVE);
        if(!empty($questionId)){
               $return = Question::where ('idquestion', $questionId)->update($delQuestion); 
            }
        return $return;
    }
    

    
    public function getCompanyAssessmentsAll($companyId = 0){
        $result = '';
        if(!empty($companyId)){
          $status_sql = "SELECT e.idexam,`e`.`max_duration`, `r`.`name`, `e`.`name`, `e`.`idexam_type`, `e`.`is_active`, `u`.`firstname`, `e`.`created_at`,(select count(*) from exam_question as eq where eq.idexam = e.idexam and eq.`status`=1)  as qcount FROM `exam` AS `e` INNER JOIN `experience_ranges` AS `r` ON `e`.`work_experience` = `r`.`id` INNER JOIN `users` AS `u` ON `e`.`created_by` = `u`.`id` WHERE `e`.`company_id` = '".$companyId."'";
           $result = DB::Select($status_sql);  
        }
       return $result; 
    }
    public function getExamQuestionList($examId = 0){
        $result =  DB::table('exam_question as e')
                    ->select('e.idexam_question as exam_question_id','q.idquestion as question_id','q.question', 'q.question_type as question_type_name', 'e.question_value')
                    ->join('question as q', 'e.idquestion', '=', 'q.idquestion')
                    ->where('e.idexam', $examId)
                    ->get();
       return $result;
    }    
        
}
