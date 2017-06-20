<?php
use Mintmesh\Services\APPEncode\APPEncode;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB as D;
use Config as C;
use User as U;
use Company_Resumes as CR;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
use lib\Parser\DocxConversion;
use lib\Parser\PdfParser;
use lib\Parser\MyEncrypt;
class job2 extends Command {

    protected $neoEnterpriseUser, $db_user, $db_pwd, $client, $db_host, $db_port, $userFileUploader;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'job2:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GotReferred creation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->db_user = Config::get('database.connections.neo4j.username');
        $this->db_pwd = Config::get('database.connections.neo4j.password');
        $this->db_host = Config::get('database.connections.neo4j.host');
        $this->db_port = Config::get('database.connections.neo4j.port');
        $this->client = new NeoClient($this->db_host, $this->db_port);
        $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
        $this->neoEnterpriseUser = $this->db_user;
        $this->userFileUploader = new UserFileUploader();
        $this->appEncodeDecode = new APPEncode();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire() {
        
        DB::statement("insert into cron_details (type) values('job2')");
        $dir = __DIR__;
        $dir_array = explode('/', $dir, -2);
	$dir_str = implode('/',$dir_array);
        $directory = $dir_str.'/uploads/mail_resumes/';
        $neoInput = array();
        $mails = DB::SELECT("SELECT cm.*,ct.fn_or,fn_re,ct.cnt_type FROM cm_mails cm 
            inner join cm_attachments ct on ct.cm_mails_id=cm.id
                where cm.flag = '0' LIMIT 1");
        foreach ($mails as $mail) {
            $id = $mail->id;
            if(file_exists($directory.'/'.$mail->fn_re)){
                $parsefiles = $this->parseFile($directory.'/'.$mail->fn_re,$mail->cnt_type);
                if(isset($parsefiles['email']) && count($parsefiles['email']) > 0){
                   $from = $parsefiles['email'][0];
                   $neoInput['referral_name'] = $parsefiles['name'][0];
                }else{
                    $from = $mail->from;
                    $neoInput['referral_name'] = substr($from, 0, strpos($from, '@'));
                }
                $reply_vals = array(); 
                $mail_params = explode("@", $mail->to);
                if(is_array($mail_params) && count($mail_params) > 0 ){
                        //$reply_user=explode('<',$mail->from);
                        $u_id = $mail_params[0];
                        $mess_vals = explode("+", $u_id);
                        foreach($mess_vals as $m_vals){
                                $r = explode("=", $m_vals);
                                if(isset($r[0]) && isset($r[1])){
                                        $reply_vals[$r[0]] = $r[1];
                                }
                        }
                } 
                $mail_parse_ref = isset($reply_vals['ref'])?MyEncrypt::decrypt_blowfish($reply_vals['ref'],C::get('constants.MINTMESH_ENCCODE')):0;  	
                $mail_parse_ref_val = array_map('intval',explode('_',$mail_parse_ref));	
                $neoInput['post_id'] = isset($mail_parse_ref_val[0])?$mail_parse_ref_val[0]:0;  
                
                $postStatus = $this->getPost($neoInput);
                if(isset($postStatus->status) && $postStatus->status == 'ACTIVE'){
                    //check candidate for job it is not decline
                    $checkCand_Not_exist = $this->checkCandidate($neoInput,$from);
                    if($checkCand_Not_exist){
                        $neoInput['uploaded_by_p2'] = '1';
                        $neoInput['referred_by_id'] = $refById = isset($mail_parse_ref_val[1])?$mail_parse_ref_val[1]:0;  
                        $checkRel     = $this->checkRel($neoInput);
                        if(!empty($checkRel[0]) && isset($checkRel[0][0])){
                            $companyCode  = !empty($checkRel[0][0]->company_code)?$checkRel[0][0]->company_code:'';
                            #check user Separated Status here
                            $separatedStatus = $this->checkReferredUserSeparatedStatus($refById, $companyCode);
                            if($separatedStatus){
                                
                                $gotReferredId = 0;
                                DB::statement("UPDATE cm_mails c set c.flag = '1' where c.id='" . $id . "'");
                                $neoInput['referred_for'] = $checkRel[0][0]->user_emailid;
                                $neoInput['referred_by']  = $relReferredBy = !empty($checkRel[0][1]->emailid) ? $checkRel[0][1]->emailid : '';
                                $checkUser = $this->checkUser($from);
                                if(!empty($checkUser[0]) && isset($checkUser[0][0])){
                                    $neoInput['referral'] = $checkUser[0][0];
                                }else{
                                    $createUser = $this->createUser($from,$neoInput);
                                    $neoInput['referral'] = $createUser[0][0];
                                }
                                $renamedFileName = '';
                                $resumeFile = $directory.$mail->fn_re;
                                if(file_exists($resumeFile)){
                                    
                                    #set resume original name
                                    $resumeName = $mail->fn_or;
                                    #get company details by code
                                    $companyDetails = $this->getCompanyDetailsByCode($companyCode);
                                    $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
                                    #get Referred user details
                                    $sqlUser = $this->getUserByEmail($relReferredBy);        
                                    $userId  = !empty($sqlUser->id)?$sqlUser->id:0;
                                    $source  = 4;
                                    #insert company resumes in company resumes table
                                    $insertResult = $this->insertInCompanyResumes($companyId, $resumeName, $userId, $source);
                                    $documentId   = $insertResult->id;
                                    #upload the file to s3
                                    $this->userFileUploader->source = $resumeFile ;
                                    $this->userFileUploader->destination = public_path().Config::get('constants.UPLOAD_RESUME').$companyId.'/' ;
                                    $this->userFileUploader->documentid  = $documentId;
                                    $renamedFileName = $this->userFileUploader->moveResume($resumeFile);
                                    if($renamedFileName){
                                        #form s3 path here
                                        $s3Path = Config::get('constants.S3_DOWNLOAD_PATH').$companyId.'/'.$renamedFileName;
                                        $renamedFileName = $s3Path;
                                    }
                                    /*$this->userFileUploader->source = $directory.$mail->fn_re;
                                    $this->userFileUploader->destination = Config::get('constants.S3BUCKET_NON_MM_REFER_RESUME');
                                    $renamedFileName = $this->userFileUploader->uploadToS3BySource($directory.$mail->fn_re);*/
                                }    
                                $neoInput['document_id'] = $documentId;
                                $neoInput['resume_path'] = $renamedFileName;
                                $neoInput['resume_original_name'] = $mail->fn_or;
                                $neoInput['created_at'] = gmdate('Y-m-d H:i:s');
                                $neoInput['awaiting_action_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                                $neoInput['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                                $neoInput['relation_count'] = '1';
                                $neoInput['one_way_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                                $neoInput['completed_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING');
                                $neoInput['awaiting_action_by'] = $neoInput['referred_for'];
                                $queryString = "Match (p:Post),(u:User)
                                                    where ID(p)=". $neoInput['post_id'] ." and u.emailid='" . $neoInput['referral'] . "'
                                                    create unique (u)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED');
                                if (!empty($neoInput)) {
                                    $queryString.="{";
                                    foreach ($neoInput as $k => $v) {
                                        $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                                    }
                                $queryString = rtrim($queryString, ",");
                                $queryString.="}";
                                }
                                $queryString.="]->(p) set p.total_referral_count = p.total_referral_count + 1, r.resume_parsed=0  return count(p)";

                                $query = new CypherQuery($this->client, $queryString);
                                $result = $query->getResultSet();
                                if(isset($result[0]) && !empty($result[0][1])){
                                    $gotReferredId = $result[0][1];
                                }
                                #updte s3 path in company resumes table
                                $updateResult    = $this->enterpriseRepository->updateCompanyResumes($documentId, $renamedFileName, $gotReferredId);
                                
                            }else{
                                DB::statement("UPDATE cm_mails c set c.flag = '2' where c.id='" . $id . "'");

                            }
                        }else{
                            DB::statement("UPDATE cm_mails c set c.flag = '2' where c.id='" . $id . "'");

                        }
                    }else{
                        DB::statement("UPDATE cm_mails c set c.flag = '2' where c.id='" . $id . "'");
                         //send mail
                        //                $dataSet    = array();
                        //                $email_sent = '';
                        ////                $fullName   = $emailData['to_firstname'] . ' ' . $emailData['to_lastname'];
                        ////                $dataSet['name']                = $fullName;
                        //                $dataSet['email']               = $mail->from;
                        ////                $dataSet['fromName']            = $emailData['from_firstname'];
                        ////                $dataSet['company_name']        = $emailData['company_name'];
                        ////                $dataSet['company_logo']        = '';
                        ////                $dataSet['emailbody']           = 'just testing';
                        ////                $dataSet['send_company_name']   = $emailData['company_name'];
                        ////                $dataSet['reply_to']            = $emailData['reply_to'];//'karthik.jangeti+jid=55+ref=66@gmail.com';
                        //        
                        //                // set email required params
                        //                $this->userEmailManager->templatePath   = Lang::get('MINTMESH.email_template_paths.enterprise_contacts_invitation');
                        //                $this->userEmailManager->emailId        = $dataSet['email'];//target email id
                        //                $this->userEmailManager->dataSet        = $dataSet;
                        //                $this->userEmailManager->subject        = 'test error';
                        ////                $this->userEmailManager->name           = $fullName;
                        //                $email_sent = $this->userEmailManager->sendMail();
                        //        
                        //                //for email logs
                        ////                $fromUserId  = $emailData['from_userid'];
                        ////                $fromEmailId = $emailData['from_emailid'];
                        ////                $companyCode = $emailData['company_code'];
                        ////                $ipAddress   = $emailData['ip_address'];
                        //                //log email status
                        //                $emailStatus = 0;
                        //                if (!empty($email_sent)) {
                        //                    $emailStatus = 1;
                        //                }
                        //                $emailLog = array(
                        //                'emails_types_id'   => 7,
                        ////                'from_user'         => $fromUserId,
                        ////                'from_email'        => $fromEmailId,
                        //                'to_email'          => $this->appEncodeDecode->filterString(strtolower($dataSet['email'])),
                        ////                'related_code'      => $companyCode,
                        //                'sent'              => $emailStatus,
                        //                'ip_address'        => $ipAddress
                        //                );
                        //                $this->userRepository->logEmail($emailLog);
                    }
                }else{
                        DB::statement("UPDATE cm_mails c set c.flag = '2' where c.id='" . $id . "'");
                }
            }else{
                    DB::statement("UPDATE cm_mails c set c.flag = '2' where c.id='" . $id . "'");
            }
        } 
        
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments() {
        return [
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions() {
        return [
        ];
    }
    
    public function checkUser($emailid){
        $queryString = "MATCH (u:User) where u.emailid='".$emailid."' return u.emailid";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
     public function createUser($emailid,$neoInput){
//        $email = $this->appEncodeDecode->filterString(strtolower($emailid));
        $queryString = "CREATE (u:User) SET u.emailid='".$emailid."',u.firstname='".$neoInput['referral_name']."',u.fullname='".$neoInput['referral_name']."' ";
        if(!empty($neoInput['phone_no']) && isset($neoInput['phone_no'])){
        $queryString .= ",u.phone='".$neoInput['phone_no']."' ";
        }
        $queryString .="return u.emailid";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
 
    public function checkRel($neoInput){
        $queryString = "MATCH (p:Post)-[r:INCLUDED]->(u:User) where ID(p)=".$neoInput['post_id']." and ID(u)=".$neoInput['referred_by_id']." return r,u";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if($result->count() != 0){
            return $result;
        }else{
            $queryString = "MATCH (u:User)-[r:POSTED]->(p:Post) where ID(p)=".$neoInput['post_id']." and ID(u)=".$neoInput['referred_by_id']." return r,u";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if($result->count() != 0){
                return $result;
            }else{
            return false;
        }
        }
    }

    private function parseFile($target_file,$imageFileType){
//        $target_file='mail_attchments/wmt_go_100/146_13279623941563562580_2016_ACME_Swoop_Scope_of_Work_1.doc';
//		$imageFileType='docx';
			if ($imageFileType == 'pdf') {
				$pdfObj = new PdfParser();
				
				$resumeText = $pdfObj->parseFile($target_file);
				// $resumeText = $pdfObj->getText();
			} else {
				$docObj = new DocxConversion($target_file);
				$resumeText = $docObj->convertToText();
			}
              $records = APPEncode::getParserValues($resumeText);
              return $records;
    }
    
    public function checkCandidate($neoInput,$from) {
        $queryString = "MATCH (u:User)-[r:GOT_REFERRED]->(p:Post) where u.emailid='".$from."' and ID(p)=".$neoInput['post_id']." and r.status<>'DECLINED' return r";
        $query = new CypherQuery($this->client, $queryString);
         $result = $query->getResultSet();
        if(count($result)>0)
            return false;
        else {      
            return true;    
        }
    }
    public function getPost($neoInput){
        $return = FALSE;
        $queryString = "MATCH (p:Post) where ID(p)=".$neoInput['post_id']." return p";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if(!empty($result[0]) && !empty($result[0][0])){
            $return = $result[0][0];
        }
        return $return;
    }
    
    public function checkReferredUserSeparatedStatus($refById='' ,$companyCode='') {
        
        $return = TRUE;
        #get user details by reference id and post id
        $neoUser     = $this->getUserByNeoID($refById);
        $userDetails = !empty($neoUser[0][0]) ? $neoUser[0][0] : '';
        if(isset($neoUser[0]) && isset($neoUser[0][0])){
            #get company details by code
            $companyDetails = $this->getCompanyDetailsByCode($companyCode);
            $companyId = isset($companyDetails[0]) ? $companyDetails[0]->id : 0;
            #get Contact Current Status By EmailId
            $currentStatus = $this->checkContactCurrentStatusByEmailId($companyId ,$userDetails->emailid);
            if(empty($currentStatus)){
                #if user is separated
                $return = false;
            }
        }
        return $return;
    }
    
    public function getUserByNeoID($neoId=0) {
        
        $return = 0;
        if(!empty($neoId)){
            $queryString = "MATCH (u:User) where ID(u)=".$neoId."  return u";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result;
            }
        }
        return $return; 
    }
    
    public function getCompanyDetailsByCode($companyCode=0){    
            return DB::table('company')
                   ->select('logo','id','name','employees_no')
                   ->where('code', '=', $companyCode)->get();
        }
    
    public function checkContactCurrentStatusByEmailId($companyId='',$emailId=''){
        $result = 0;
        if(!empty($companyId) && !empty($emailId)){
            $sql = "select id from contacts where company_id ='".$companyId."' and emailid='".$emailId."' and (status = 'Active' or status='Inactive')" ;
            $result = DB::Select($sql);
        }
        return $result;
    }
    
    public function getUserByEmail($email) {
        return u::whereRaw('emailid = ?', array($this->appEncodeDecode->filterString(strtolower($email))))->first();            
    }
    
    // creating new Enterprise user Company mapping in storage
    public function insertInCompanyResumes($companyId=0, $resumeName='', $userId=0, $source=0, $gotReferred=0)
    {   
        $createdAt = gmdate("Y-m-d H:i:s");   
        $companyResumes = array(
                        "company_id"   => $companyId,
                        "file_original_name"  => $resumeName,
                        "status"        => 0,
                        "file_from"     => $source,
                        "got_referred_id"  => $gotReferred,
                        "created_by"    => $userId,
                        "created_at"    => $createdAt
            );
        return CR::create($companyResumes);
    }
    // creating new Enterprise user Company mapping in storage
    public function updateCompanyResumes($documentId=0, $filesource='', $gotReferredId = 0)
    {   
        $updatedAt = gmdate("Y-m-d H:i:s");
        $companyResumes = array(
                        "file_source"       => $filesource,
                        "status"            => 1,
                        "got_referred_id"   => $gotReferredId,
                        "updated_at"        => $updatedAt
            );
        if($documentId){
            $results = CR::where ('id',$documentId)->update($companyResumes); 
        }
        
       return $results;
    }
        
}
