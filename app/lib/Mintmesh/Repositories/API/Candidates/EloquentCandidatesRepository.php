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
}
