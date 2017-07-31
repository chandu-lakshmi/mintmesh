<?php namespace Mintmesh\Repositories\API\Enterprise;

use User;
use Company_Profile,Company_Resumes;
use Groups;
use Company_Contacts;
use Emails_Logs;
use Levels_Logs;
use Notifications_Logs;
use Config ;
use Mail ;
use DB;
use Mintmesh\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Mintmesh\Services\APPEncode\APPEncode ;
class EloquentEnterpriseRepository extends BaseRepository implements EnterpriseRepository {

    protected $user, $companyProfile, $CompanyContact,$groups;
    protected $email, $level, $appEncodeDecode, $companyResumes;
        
    const COMPANY_RESUME_STATUS = 0;
    const COMPANY_RESUME_S3_MOVED_STATUS = 1;
    const COMPANY_RESUME_AI_PARSED_STATUS = 2;
        
        public function __construct(User $user,
                                    Company_Profile $companyProfile,
                                    Company_Resumes $companyResumes,
                                    Groups $groups,
                                    Company_Contacts $CompanyContact,
                                    Emails_Logs $email, 
                                    APPEncode $appEncodeDecode){ 
                $this->user = $user;    
                $this->companyProfile = $companyProfile; 
                $this->companyResumes = $companyResumes; 
                $this->groups = $groups; 
                $this->companyContact = $CompanyContact;    
                $this->appEncodeDecode = $appEncodeDecode ;       
        }
        // creating new Enterprise user in storage
        public function createEnterpriseUser($input)
        {           
            // md5 the email id and attach with mintmesh constant for verification code
            $emailActivationCode = md5($input['emailid']."_".Config::get('constants.MINTMESH')) ;
            $user = array(
                        "firstname" => $this->appEncodeDecode->filterString($input['fullname']),
                        "emailid"   =>$this->appEncodeDecode->filterString(strtolower($input['emailid'])), 
                        "password"  =>Hash::make($input['password']),
                        "is_enterprise"  =>$this->appEncodeDecode->filterString($input['is_enterprise']),
                        "group_id"  =>$this->appEncodeDecode->filterString($input['group_id']),
                        "emailactivationcode" => $emailActivationCode
            );
            return $this->user->create($user);
        }
        
        // creating new Enterprise user Company Profile in storage
        public function createCompanyProfile($input)
        {     
            $companyProfile = array(
                        "name"          => $this->appEncodeDecode->filterString($input['company']),
                        "code"          => $input['company_code'],
                        "employees_no"  => $input['contacts_limit'],
                        "is_primary"    => '1',
                        "subscription_type"  => $input['subscription_type'],
                        "created_by"    => $input['user_id'],
                        "ip_address"    => $_SERVER['REMOTE_ADDR']
            );
            return $this->companyProfile->create($companyProfile);
        }
        
        // creating new Enterprise user Company Profile in storage
        public function updateCompanyProfile($input)
        {            
           $companyProfile = array(
                        "name"          => $this->appEncodeDecode->filterString($input['company']),
                        "industry"      => !empty($input['industry'])?$input['industry']:0,
                        "description"   => $input['description'],
//                        "description"   => !empty($input['description'])?$this->appEncodeDecode->filterString($input['description']):'',
                        "website"       => !empty($input['website'])?$input['website']:'',
                        //"employees_no"  => !empty($input['number_of_employees'])?$input['number_of_employees']:'',
                        "logo"          => !empty($input['company_logo'])?$input['company_logo']:'',
                        "status"        => !empty($input['status'])?$input['status']:'1',
                        "updated_by"    => !empty($input['user_id'])?$input['user_id']:'',
                        
             );
            if(!empty($input['company_id'])){
                Company_Profile::where ('id',$input['company_id'])->update($companyProfile); 
            }
            return true;
        }
        
        // creating new Enterprise user Company mapping in storage
        public function companyUserMapping($userId,$companyId,$randomCode)
        {   
            $sql = "insert into company_user_mapping (`company_id`,`user_id`,`code`)" ;
            $sql.=" values('".$companyId."','".$userId."','".$randomCode."')" ;
            $result = DB::statement($sql);
            return $result;
        }
        
        public function getEnterpriseUserByEmail($email) {
            return User::whereRaw('emailid = ?', array($this->appEncodeDecode->filterString(strtolower($email))))->first();
        }
        
        public function getUserCompanyMap($userId) {
            $pushResult = DB::select("SELECT company_user_mapping.company_id,company_user_mapping.code, company.name, company.logo,
                company.industry, company.logo 
                FROM company_user_mapping
                LEFT JOIN users
                ON users.id = company_user_mapping.user_id 
                LEFT JOIN company
                ON company.id = company_user_mapping.company_id 
                where users.id=? limit 1",array($userId)) ;
            return $pushResult[0];
        }
        
         public function createNewBucket($userId, $companyId, $bucketName, $createdAt){
            
            $result = false;
            $ipAddress = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:0;
            if (!empty($userId) && !empty($companyId) && !empty($bucketName))
            {   
                //$sql = "insert into import_contacts_buckets (`user_id`,`company_id`,`name`)" ;
                $sql = "insert into buckets (`user_id`,`company_id`,`name`,`updated_by`,`created_at`,`ip_address`)" ;
                $sql.=" values('".$userId."','".$companyId."','".$bucketName."','".$userId."','".$createdAt."','".$ipAddress."')" ;

                DB::statement($sql);
                $last_insert_id = DB::Select("SELECT LAST_INSERT_ID() as last_id"); 
                $result = $last_insert_id[0]->last_id;
            } 
            return $result; 
         }
         
         public function updateExistBucket($userId, $id, $companyId,$bucketStatus, $createdAt){
            
            $result = false;
            $ipAddress = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:0;
            if (!empty($userId) && !empty($companyId))
            {   
                //$sql = "insert into import_contacts_buckets (`user_id`,`company_id`,`name`)" ;
                $sql = "UPDATE buckets SET `updated_by`='". $userId ."', `company_id`='". $companyId ."',`status`='". $bucketStatus ."',`created_at`='".$createdAt."',`ip_address`='".$ipAddress."' WHERE `id` = '".$id."'";
                DB::statement($sql);
                $last_insert_id = DB::Select("SELECT LAST_INSERT_ID() as last_id"); 
                $result = $last_insert_id[0]->last_id;
            } 
            return $result; 
         }
         
        public function isBucketExist($userId, $companyId, $bucketName){
            
            $response = false;
            if (!empty($userId) && !empty($companyId) && !empty($bucketName))
            {  
                //$sql = "select COUNT(id) as count from import_contacts_buckets where user_id = '".$userId."' and company_id = '".$companyId."' and name like '".$bucketName."' " ;
                $sql = "select COUNT(id) as count from buckets where user_id = '".$userId."' and company_id = '".$companyId."' and name like '".$bucketName."' and status = 1 " ;
                //echo $sql;exit;
                $result = DB::Select($sql);
                $response = $result[0]->count;
            } 
            return $response; 
         } 
        
        // Import Contacts on Web
        public function importContactsOnWeb($input, $userId, $bucketId, $companyId, $instanceId)
        {    
            $fireQuery = $result = false;
            if (!empty($userId) && !empty($bucketId) && !empty($companyId))
            {
                $contactsList = $this->getImportContactByEmailId($userId, $bucketId, $companyId);
                $contactIsExist = array();
               
                foreach ($contactsList as $obj){
                    $contactIsExist[] = $obj->emailid;     
                }
                  
                $sql = "insert into import_contacts_web (`user_id`,`company_id`,`bucket_id`,`instance_id`,`firstname`,`lastname`,`emailid`,`contact_number`,`other_id`,`status`)values" ;
                foreach ($input as $key=>$val)
                {
                    $otherId   = !empty($val['employee_idother_id'])?$val['employee_idother_id']:'';
                    $cellPhone = !empty($val['cell_phone'])?$val['cell_phone']:'';
                    $status    = !empty($val['status'])?$val['status']:'';
                    
                    if(!empty($val['first_name'])&&!empty($val['last_name'])&&filter_var($val['email_id'], FILTER_VALIDATE_EMAIL))
                    {
                        $firstName = $this->appEncodeDecode->filterString($val['first_name']);
                        $lastName  = $this->appEncodeDecode->filterString($val['last_name']);
                        $emailId   = $val['email_id'];
                        
                        if(!in_array($val['email_id'], $contactIsExist)){   
                            $sql.="('".$userId."','".$companyId."','".$bucketId."','".$instanceId."','".$firstName."',";
                            $sql.="'".$lastName."','".$emailId."','".$cellPhone."','".$otherId."','".$status."')," ;
                            $fireQuery = true;
                        }
                    } 
                }
                $query  = rtrim($sql,',');
                $result = ($fireQuery)?DB::statement($query):$fireQuery;
            } 
            return $result;    
        }
        // upload Contacts on Web
        public function uploadContacts($input, $userId, $bucketId, $companyId, $importFileId, $availableNo)
        {     
            $ipAddress = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:0;
            $createdAt = gmdate("Y-m-d H:i:s");
            if (!empty($userId) && !empty($bucketId) && !empty($companyId))
            {
                $contactsList = $this->getImportContactByEmailId($userId, $bucketId, $companyId);
                $contactIsExist = $updatedRows = $result = array();
                //checking email id already exist or not  
                foreach ($contactsList as $obj){
                    $emailidLower = !empty($obj->emailid) ? strtolower($obj->emailid) :'';
                    $contactIsExist[$emailidLower] = $obj->employeeid;    
                }
                $sql = "insert into contacts 
                        (`user_id`,`company_id`,`import_file_id`,`firstname`,`lastname`,`emailid`,`phone`,`employeeid`,`status`, `updated_by`,`created_at`,`created_by`,`ip_address`) values " ;
                $inrt_sql = '';	
                $updatedRows = $insertResult = $i = 0;	
                $limitExceeded = 0;
                
                foreach ($input as $key=>$val)
                {                             
                    $employeeId  = !empty($val['employee_idother_id']) ? $val['employee_idother_id'] : '';
                    $cellPhone   = !empty($val['cell_phone']) ? $val['cell_phone'] : '';
                    $lastName    = !empty($val['last_name']) ? $val['last_name'] : '';
                    $status      = !empty($val['status']) ?  ucfirst(strtolower($val['status'])): 'Active';
                    $emailId     = strtolower(trim($val['email_id']));
                    
                    #string contains at least 3 character. set default status as Active.
                    $substr = substr($status, 0, 3);
                    if($substr=='Act'){
                        $status = 'Active';
                    } else if($substr=='Ina'){
                        $status = 'Inactive';
                    } else if($substr=='Sep'){
                        $status = 'Separated';
                    } else {
                        $status = 'Active';
                    }
                     
                    if(!empty($val['first_name']) && filter_var($emailId, FILTER_VALIDATE_EMAIL))
                    {                    
                        $firstName = $this->appEncodeDecode->filterString($val['first_name']);
                        $lastName  = $this->appEncodeDecode->filterString($val['last_name']);
                        $usersArr  = User::select('id')->where('emailid',$emailId)->get();
                        $users_id  = !empty($usersArr[0])?$usersArr[0]->id:0;
                        
                        if(!array_key_exists(strtolower($val['email_id']), $contactIsExist)){ 
                            $employeeId = ($employeeId!='' && in_array($employeeId, $contactIsExist))?'':$employeeId; 
                            #limit exceeded flag
                            if(empty($availableNo)){
                                $limitExceeded = 1;
                            }
                            #check available contacts count here
                            if(!empty($availableNo) || $status == 'Separated'){
                                $inrt_sql.="('".$users_id."','".$companyId."','".$importFileId."','".$firstName."','".$lastName."','".$emailId."','".$cellPhone."',";
                                $inrt_sql.="'".$employeeId."','".$status."','".$userId."','".$createdAt."','".$userId."','".$ipAddress."')," ;
                                $i++;
                                $insertResult++;
                                if($status != 'Separated'){
                                    $availableNo--;
                                }    
                            }    
                        } else {
                            
                            $allowToUpdate = TRUE;
                            if ($status == 'Active' || $status == 'Inactive') {
                                #check contact current status here.
                                $currentStatus = $this->checkContactCurrentStatusByEmailId($companyId, $emailId);
                                if(empty($currentStatus)){
                                    #check available contact count here.
                                    if($availableNo){
                                        $availableNo--;
                                        #status enable in users table for allow to login
                                        $sql= "update users set status=1 WHERE `emailid` = '".$emailId."' ";
                                        DB::statement($sql);
                                    } else {
                                        $allowToUpdate = FALSE;
                                    }
                                } 
                            }
                            
                            $employeeId = ($employeeId!='' && in_array($employeeId, $contactIsExist))?(($contactIsExist[strtolower($emailId)]==$employeeId)?$employeeId:''):$employeeId;
                            
                            $sqlQuery = "UPDATE contacts SET `user_id`='".$users_id."', `import_file_id` = '".$importFileId."',`firstname`='".$firstName."',`lastname`='".$lastName."',";
                            $sqlQuery.= " `phone`='".$cellPhone."',`employeeid`='".$employeeId."',`updated_by`='".$userId."'";
                            #check update status
                            if($allowToUpdate){
                              $sqlQuery.=" ,`status`='".$status."' ";
                            } else {
                                $limitExceeded = 1;
                            }
                            $sqlQuery.= " WHERE `emailid` = '".$emailId."' and`company_id` ='".$companyId."'";
                            
                            DB::statement($sqlQuery);
                            $updatedRows++;
                        }
                    }
                    
                    if($i==500 && $inrt_sql!=''){
                            DB::statement($sql.trim($inrt_sql,','));
                            $inrt_sql = '';
                            $i=0;
                    }
                }
               
                if($inrt_sql!=''){
                    DB::statement($sql.trim($inrt_sql,','));
                    $i=0;
                  }
                  DB::statement("insert into buckets_contacts (contact_id,bucket_id,company_id) SELECT c.id,'".$bucketId."' AS bucket_id,c.company_id
                                        FROM contacts c
                                        LEFT JOIN buckets_contacts bc ON bc.contact_id=c.id AND bc.bucket_id='".$bucketId."'
                                        WHERE c.import_file_id='".$importFileId."' AND bc.contact_id IS NULL");
                
                $result['insert'] = $insertResult;
                $result['update'] = $updatedRows;
		$result['importFileId'] = $importFileId;
		$result['limitExceeded'] = $limitExceeded;
            } 
            return $result;    
        }
        
        // get Import Contact By EmailId
        public function getImportContactByEmailId($userId, $bucketId, $companyId){   
            return DB::table('contacts')
                ->select('emailid', 'employeeid')
                ->where('company_id', '=', $companyId)->get();
        }
        
        public function getCompanyBucketsList($params){
            $sql = 'SELECT b.name AS bucket_name,b.id as bucket_id, COUNT(DISTINCT bc.contact_id) AS count,b.company_id as company_id 
                    FROM buckets b
                    LEFT JOIN buckets_contacts bc ON bc.bucket_id=b.id AND bc.company_id="'.$params['company_id'].'"
                    WHERE 1 AND (b.company_id = "'.$params['company_id'].'" OR b.company_id = "0") AND b.status = "1"
                    GROUP BY b.id';
            $result = DB::select($sql);     
        return $result;
           /*return DB::table('buckets')
                   ->select('name as bucket_name','id as bucket_id')
                   ->where('company_id', '=', 0)
                   ->orWhere('company_id','=',$params['company_id'])->get();*/
        }
        public function contactsCount($params) {
           $sql = DB::table('contacts')
                                ->where('company_id', '=', $params['company_id'])->get();
                                //->where('user_id','=',$params['user_id'])
                                //->where('bucket_id','=',$params['bucket_id'])->get();
           $result = DB::select("select FOUND_ROWS() as total_count");     
           return $result[0];
        }
        
        public function getImportContactsList($params){
                
                
                $search = $this->appEncodeDecode->filterString(strtolower($params['search'])); 

                $sql = 'SELECT SQL_CALC_FOUND_ROWS c.id AS record_id, 
                    c.firstname, c.lastname, c.emailid, c.phone, c.employeeid, c.status,
                    case when u.id is not null then 1 else 0 end as download_status
                    FROM contacts c ';
                if(!empty($params['bucket_id']))
                $sql.= ' LEFT JOIN buckets_contacts bc ON c.id=bc.contact_id';
                
                $sql.= " LEFT JOIN users u ON u.emailid=c.emailid and u.is_enterprise!='1' 
                        where c.company_id='".$params['company_id']."' " ;
                
                if(!empty($params['bucket_id'])){
                    $sql.= " AND bc.bucket_id = '".$params['bucket_id']."' " ;
                }
                
                if (!empty($search) && $search == 'no') {
                    
                    $sql.= " and u.is_enterprise is null ";
                    
                } else if(!empty($search)){
                     
                    $sql.= " AND (c.emailid like '%".  $search."%'";
                    $sql.= " OR c.phone like '%".  $search."%'";
                    $sql.= " OR c.firstname like '%".  $search."%'";
                    $sql.= " OR c.lastname like '%".  $search."%'";
                    $sql.= " OR c.employeeid like '%".  $search."%'";
                    
                    if($search =='yes' || $search =='ye'){
                        $sql.= " OR u.is_enterprise like '%0%'";
                        $sql.= " OR u.is_enterprise like '%2%'";
                    }
                    
                    $sql.= " OR c.status like '%".  $search."%')";
                } 
               
                $sql .= "order by status";
                if($params['sort'] == 'desc'){
                    $sql .= " desc";
                }
                $page = $params['page_no'];
                if (!empty($page)){
                    $page   = $page-1 ;
                    $offset = $page*50 ;
                    $sql.=  " limit ".$offset.",50 ";
                } 
                //echo $sql;exit;
                $result['Contacts_list'] = DB::select($sql);
                $result['total_records'] = DB::select("select FOUND_ROWS() as total_count");
            return $result;    
        }
        
        public function getImportContactsListCount($params){
                
                $search = $this->appEncodeDecode->filterString(strtolower($params['search']));            
                $sql = 'SELECT  count(u.id) as total_downloads FROM contacts c ';
                
                if(!empty($params['bucket_id']))
                $sql.= ' LEFT JOIN buckets_contacts bc ON c.id=bc.contact_id';
                
                $sql.= " LEFT JOIN users u ON u.emailid=c.emailid and u.is_enterprise!='1'
                         where c.company_id='".$params['company_id']."' " ;
                
                 if(!empty($params['bucket_id'])){
                     $sql.= " AND bc.bucket_id = '".$params['bucket_id']."' " ;
                 }
                
                if (!empty($search) && $search == 'no') {
                    
                    $sql.= " and u.is_enterprise is null ";
                    
                } else if(!empty($search)){
                     
                    $sql.= " AND (c.emailid like '%".  $search."%'";
                    $sql.= " OR c.phone like '%".  $search."%'";
                    $sql.= " OR c.firstname like '%".  $search."%'";
                    $sql.= " OR c.lastname like '%".  $search."%'";
                    $sql.= " OR c.employeeid like '%".  $search."%'";
                    
                    if($search =='yes' || $search =='ye'){
                        $sql.= " OR u.is_enterprise like '%0%'";
                        $sql.= " OR u.is_enterprise like '%2%'";
                    }
                    
                    $sql.= " OR c.status like '%".  $search."%')";
                } 
                //echo $sql;exit;
                $result = DB::select($sql);
                
            return $result;    
        }
        
        public function getCompanyContactsListById($params){
            
            $result =  array();
            foreach ($params['invite_contacts'] as $key=>$val)
                {
                    $result[] = DB::table('contacts')
                                ->where('company_id', '=', $params['company_id'])
                                ->where('id','=',$val)
                                ->whereRaw('status NOT Like "%Separated%" ')
                                ->get();
                }
            return $result;   
        }
        
        public function getInstanceId() {
            return DB::table('import_contacts_instance')->insertGetId(array());
        }
        public function getContactsListByFileId($companyId, $importFileId){    
            return DB::table('contacts')
                   ->select('id','firstname','lastname','emailid','phone','employeeid','status')
                   ->where('company_id', '=', $companyId)
                   ->where('import_file_id', '=', $importFileId)->get();
        }
        public function getCompanyDetailsByCode($companyCode=0){    
            return DB::table('company')
                   ->select('logo','id','name','employees_no')
                   ->where('code', '=', $companyCode)->get();
        }
        
        public function companyInvitedCount($userId, $companyId, $filterLimit)
        {   
            $sql = "select COUNT(DISTINCT to_email) AS count from emails_logs " ;
            $sql.=" where  emails_types_id = 5 and related_code ='".$companyId."' and created_at >= '".$filterLimit."'" ;
           
            $result = DB::Select($sql);
            return $result;
        }
        
        public function appActiveUserCount($userId, $companyId, $filterLimit)
        {   
            $sql = "select count(DISTINCT a.user_id) as count from user_activity_logs a
                    left join users u on u.id = a.user_id 
                    left join contacts c on c.emailid = u.emailid
                    where  application_type=1 and c.company_id='".$companyId."' and c.status='Active' and  a.created_at >= '".$filterLimit."' " ;
           //echo $sql;exit;
            $result = DB::Select($sql);
            return $result;
        }
       
        
        
        public function updateUserStatus($contactsId=0)
        {
            $query ="update users u  inner join contacts c on c.emailid = u. emailid set u.status=1
                    where c.id='".$contactsId."' ";
            DB::statement($query);
            return true;
        }   
        public function deleteUserOauthAccessTokens($contactsId=0)
        {   
            $result= FALSE;
            if($contactsId){
                $sql = "select tok.id,u.id as user_id from users u
                        inner join contacts c on c.emailid = u.emailid
                        inner JOIN oauth_sessions s
                        ON s.owner_id = u.id 
                        inner JOIN oauth_access_tokens tok
                        ON tok.session_id = s.id 
                        where c.id='".$contactsId."'" ;
               //echo $sql;exit;
                $result = DB::Select($sql);
                
                foreach ($result as $value) {
                    
                    $this->deleteOauthRefreshTokens($value->id);
                    $this->deleteOauthAccessTokens($value->id);
                    $this->deleteOauthSessions($value->user_id);
                    
                }
                $query ="update users u  inner join contacts c on c.emailid = u. emailid set u.status=0,u.group_status=0
                        where c.id='".$contactsId."' ";
                DB::statement($query);
            }
            return true;
        }
        
        public function deleteOauthRefreshTokens($accessTokenId) {
            $sql = "delete FROM oauth_refresh_tokens WHERE access_token_id = '".$accessTokenId."'";
            $result = DB::statement($sql);
            return $result;
        }
        public function deleteOauthAccessTokens($oauthAccessTokensId) {
            $sql    = "delete FROM oauth_access_tokens WHERE id = '".$oauthAccessTokensId."'";
            $result = DB::statement($sql);
            return $result;
        }
        public function deleteOauthSessions($userId) {
            
            $sql = "delete FROM oauth_sessions 
                    WHERE owner_id = '".$userId."'";
            $result = DB::statement($sql);
            return $result;
        }
        
        
        public function updateContactsList($input) {   
            $input['employeeid'] = strtoupper($input['other_id']);
            $input['phone'] = $input['contact_number'];
            $fields = '';
            $field_set= array('employeeid','firstname','lastname','phone','status');
            foreach($input as $k=>$v){   
                $v = "'".$v."'";
                if(in_array($k,$field_set))
                $fields .= $k.'='.$v.',';
            }
            if($fields != ''){
                $sql = "update contacts set ".trim($fields,',')." where id='".$input['record_id']."'";
                $result1 = DB::Statement($sql);
            }
            if($result1){
//                DB::table('import_contacts_web')->where('id', '=', $input['record_id'])->increment('edit_count');
                $sql = "select emailid,id from contacts where id='".$input['record_id']."'";
                $result = DB::select($sql);   
                return $result;

            }
         
        }
        
        public function updateContact($input) {
            $input['updated_at'] = gmdate('Y-m-d H:i:s');
            $input['updated_by'] = $input['user_id'];
            $fields = '';
            $field_set= array('employeeid','firstname','lastname','phone','status','updated_at','updated_by');
            foreach($input as $k=>$v){
                $v = "'".$v."'";
                if(in_array($k,$field_set))
                $fields .= $k.'='.$v.',';
            }
            if($fields != ''){
                $sql = "update contacts set ".trim($fields,',')." where id='".$input['id']."'";
                $result = DB::Statement($sql);
                DB::statement("insert into buckets_contacts (contact_id,bucket_id,company_id) values('".$input['id']."','".$input['bucket_id']."','".$input['company_id']."')");
            }return $result;
        }
        
        public function checkEmployeeId($input)
        {
            if(!empty($input['other_id'])){
            return DB::table('contacts')
                   ->where('company_id', '=', $input['company_id'])
                   ->where('employeeid', '=', $input['other_id'])
                   ->where('id', '!=', $input['record_id'])->get(); 
            }
        }
        public function checkEmpId($input) {
            if(!empty($input['other_id'])){
            return DB::table('contacts')
                   ->where('company_id', '=', $input['company_id'])
                   ->where('employeeid', '=', $input['other_id'])->get(); 
            }
        }
        public function deleteContact($record) {   
            $sql="select emailid from contacts where id='".$record."'";
            $result = DB::select($sql);   
            $sql = DB::table('contacts')->where('id', $record)->delete();
            if($sql){
                 return $result;
              }
            
        }
        
        public function ediStatus($input,$record) {
           $sql = "update contacts set status='".trim($input['status'])."' where id='".$record."'";
           $result = DB::Statement($sql);
           if($result){
//           DB::table('contacts')->where('id', '=', $record)->increment('edit_count');
           return DB::table('contacts')
                   ->select('emailid')
           ->where('id', '=', $record)->get(); 
           }
           else{
               return false;
           }
        }
        public function getFileId($inputFile,$user_id) {
			$data =  array();
			$inputFileInfo      = pathinfo($inputFile);
			$data['file_name'] = $inputFileInfo['filename'];
			$data['file_path'] = $inputFile;
			$data['file_ext'] = !empty($inputFileInfo['extension']);
			$data['system_componet_id'] = '1';
			$data['updated_at'] = gmdate('Y-m-d H:i:s');
			$data['updated_by'] = $user_id;
			$data['created_at'] = gmdate('Y-m-d H:i:s');
			$data['created_by'] = $user_id;
			$data['ip_address'] = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.1.1.0';
            return DB::table('files')->insertGetId($data);
        } 
        
        public function addContact($input) {
                $input['created_at'] = gmdate('Y-m-d H:i:s');
		$input['created_by'] = $input['user_id'];
		$input['ip_address'] = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.1.1.0';
                $sql = "insert into contacts (`user_id`,`company_id`,`import_file_id`,`firstname`,`lastname`,`emailid`,`phone`,`status`,`employeeid`,`updated_at`,`updated_by`,`created_at`,`created_by`,`ip_address`)" ;
                $sql.=" values('".$input['user_id']."','".$input['company_id']."',' ','".$input['firstname']."','".$input['lastname']."','".$input['emailid']."','".$input['phone']."','".$input['status']."','".$input['employeeid']."'," ;
                $sql.= " ' ',' ','".$input['created_at']."', '".$input['created_by']."', '".$input['ip_address']."') ";
                $result = DB::statement($sql);
                if($result){
                  $last_insert_id = DB::Select("SELECT LAST_INSERT_ID() as last_id"); 
                $contactId = $last_insert_id[0]->last_id;
                DB::statement("insert into buckets_contacts (contact_id,bucket_id,company_id) values ( '".$contactId."','".$input['bucket_id']."','".$input['company_id']."') ");
                }
                return $result;
            
            
        }
        
        public function checkContact($input) {
            $sql = "SELECT c.id, IFNULL(bc.bucket_id,0) AS bucket_id
                    FROM contacts c
                    LEFT JOIN buckets_contacts bc ON bc.contact_id = c.id  AND bc.company_id='".$input['company_id']."' AND bc.bucket_id='".$input['bucket_id']."'
                    WHERE c.emailid='".$input['emailid']."'  AND c.company_id='".$input['company_id']."'
                    LIMIT 1";
            $result = DB::select($sql);
            return $result;
                        
        }
        
        public function getCompanyDetails($id) {
             return DB::table('company')
                 ->where('id', '=', $id)->get(); 
            
        }
        
        public function addPermissions($groupId, $permArray, $loginUserId, $input) {
        $type = isset($input['type']) ? $input['type'] : 1;
        if ($groupId && $permArray) {
            DB::table('groups_permissions')
                    ->where('groups_id', '=', $groupId)
                    ->where('type', '=', $type)
                    ->delete();
            if (is_array($permArray)) {
                foreach ($permArray as $a => $b) {
//                    if ($b != 0) {
                        DB::table('groups_permissions')->insert(
                                array(
                                    'groups_id' => $groupId,
                                    'permissions_id' => $a,
                                    'permission' => $b,
                                    'type' => $type,
                                    'last_modified_by' => $loginUserId
                                )
                        );
//                    }
                }
                if (isset($input['child']) && is_array($input['child'])) {
                    foreach ($input['child'] as $k => $v) {

                        foreach ($v as $k2 => $v2) {
                            DB::table('groups_permissions')->insert(
                                    array(
                                        'groups_id' => $groupId,
                                        'permissions_id' => $k2,
                                        'permission' => $v2,
                                        'type' => $type,
                                        'last_modified_by' => $loginUserId
                                    )
                            );
                        }
                    }
                }
                return true;
            }
        } else {
            return false;
        }
    }
    
    public function getPermissions() {
        $result = array();
        $data = array();
        $result = self::getTabs();
        $data['permissions'] = $result;
        return $data;
    }
    
    static function getTabs($userid = 0) {

        $userTabs = array();

        /* new query writen by shankar anand */
        $result = DB::select("SELECT p.id,p.name,p.type AS utype,p.parent_id,p.top_parent_id,p.level as length
									FROM permissions p
									WHERE p.internal='2' AND p.status='1'
									ORDER BY p.parent_id ASC");
        foreach ($result as $row) {
            if (array_key_exists($row->parent_id, $userTabs)) {
                $userTabs[$row->parent_id]['children'][$row->id]['id'] = $row->id;
                $userTabs[$row->parent_id]['children'][$row->id]['label'] = $row->name;
                $userTabs[$row->parent_id]['children'][$row->id]['type'] = $row->utype;
                $userTabs[$row->parent_id]['children'][$row->id]['length'] = $row->length;
                //$userTabs[$row->parent_id]['children'][$row->id]['access']=$row->tabper;
            } else {
                $userTabs[$row->id]['id'] = $row->id;
                $userTabs[$row->id]['label'] = $row->name;
                $userTabs[$row->id]['type'] = $row->utype;
                $userTabs[$row->id]['length'] = $row->length;
                //$userTabs[$row->id]['access']=$row->tabper;
                if($row->utype == 'select'){
                    $userTabs[$row->id]['options'] = array(array('id' => '1','label' => 'Manager'),array('id' => '2','label' => 'Employee'),array('id' => '3','label' => 'Client'),array('id' => '4','label' => 'Others'));
                    
                }
            }
        }
        foreach ($userTabs as $i => $u) {
            $temp = array();
            if (isset($u['children'])) {
                foreach ($u['children'] as $tempUser) {
                    if (isset($tempUser['children'])) {
                        $tempUser['children'] = self::sort_usertab($tempUser['children']);
                    }

                    array_push($temp, $tempUser);
                }
            }

            $userTabs[$i]['children'] = $temp;
        }
       
        $res = self::sortTwoDimenArray($userTabs, 'label', $order = 'asc', $natsort = FALSE, $case_sensitive = FALSE);
        return $res;
    }

    /**
     * sort_usertab
     *
     * function to sort the user tab
     * 
     * @access	public
     * @return	object
     */
    static function sort_usertab($data) {
        $aa = array();
        $j = 0;
        foreach ($data as $i => $u) {

            array_push($aa, $u);
        }
        return $aa;
    }
    
     /**
     * sortTwoDimenArray
     *
     * function to sort the two dimensional array
     * 
     * @access	public
     * @return	object
     */
        
    static function sortTwoDimenArray($array, $index, $order = 'asc', $natsort = FALSE, $case_sensitive = FALSE) {
        if (is_array($array) && count($array) > 0) {
            foreach (array_keys($array) as $key)
                $temp[$key] = $array[$key][$index];
//            if (!$natsort)
//                ($order == 'asc') ? asort($temp) : arsort($temp);
//            else {
//                ($case_sensitive) ? natsort($temp) : natcasesort($temp);
//                if ($order != 'asc')
//                    $temp = array_reverse($temp, TRUE);
//            }                              

            foreach (array_keys($temp) as $key)
                (is_numeric($key)) ? $sorted[] = $array[$key] : $sorted[$key] = $array[$key];
            return $sorted;
        }
        return $array;
    }
    
    public function getGroupPermissions($group_id, $input) {
        $type = isset($input['type']) ? $input['type'] : 1;
        $perm = array();
        $permArray = array();
        $compPermArray = array();
        if ($group_id && is_numeric($group_id)) {

            $perm = DB::select("select p.id, p.name as label,p.code,up.permission,p.parent_id,p.`type`,p.parent_id
                                from groups_permissions up 
                                left join permissions p on p.id=up.permissions_id
                                left join groups g on g.id=up.groups_id
                                WHERE up.groups_id='" . $group_id . "' ");
            if ($perm) {
                foreach ($perm as $k => $v) {
                    if ($v->parent_id != '0') {
                        $p_id = $v->parent_id . '_' . $v->id;
                        $permArray[$p_id] = $v->permission;
                    } else
                        $permArray[$v->id] = $v->permission;
                }
            }
        }
        return $permArray;
    }
    
     public function getUserPermissions($group_id, $input='') {
        $type = isset($input['type']) ? $input['type'] : 1;
        $perm = array();
        $permArray = array();
        $compPermArray = array();
        if ($group_id && is_numeric($group_id)) {

            $perm = DB::select("select p.id,p.name as label,p.code,up.permission,p.parent_id,p.`type`,p.parent_id
                                from groups_permissions up 
                                left join permissions p on p.id=up.permissions_id
                                left join groups g on g.id=up.groups_id
                                WHERE up.groups_id='" . $group_id . "' ");
            if ($perm) {
                foreach ($perm as $k => $v) {
                    if ($v->parent_id != '0') {
                        $p_id = $v->parent_id . '_' . $v->id;
                        $permArray[$v->code] = $v->permission;
                    } else
                        $permArray[$v->code] = $v->permission;
                }
            }
        }
        return $permArray;
    }
    
    public function addUser($input) {
        
        // md5 the email id and attach with mintmesh constant for verification code
            $emailActivationCode = md5($input['emailid']."_".Config::get('constants.MINTMESH')) ;
           $date = gmdate('Y-m-d H:i:s');
           if($input['status'] == 'Active'){
               $input['status'] = '1';
           }else{
                 $input['status'] = '0';
           }
            $user = array(
                        "firstname" => $this->appEncodeDecode->filterString($input['fullname']),
                        "emailid"   =>$this->appEncodeDecode->filterString(strtolower($input['emailid'])), 
                        "group_status"   =>$this->appEncodeDecode->filterString(strtolower($input['status'])), 
                        "status"   =>    '1', 
                        "is_enterprise"  =>$this->appEncodeDecode->filterString($input['is_enterprise']),
                        "group_id"  =>$this->appEncodeDecode->filterString($input['group_id']),
                        "emailactivationcode" => $emailActivationCode
            );
            $result[] = $this->user->create($user);
            return $result;
           
      }
      public function editingUser($input) {
//            $sql = "update users set firstname='".$input['fullname']."',status='".$input['status']."',emailid='".$input['emailid']."',group_id='".$input['group_id']."' where id='".$input['user_id']."'";
//            $result = DB::statement($sql);
//            if($result){
//             return $input['user_id'];
//            }
           $input['group_status'] = $input['status'];
            $fields = '';
            $field_set= array('firstname','group_status','emailid','group_id');
            foreach($input as $k=>$v){
                $v = "'".$v."'";
                if(in_array($k,$field_set))
                $fields .= $k.'='.$v.',';
            }
            if($fields != ''){
                $sql = "update users set ".trim($fields,',')." where id='".$input['user_id']."'";
                $result = DB::Statement($sql);
            }
            return $input['user_id'];
      }
    public function getUsers($companyCode,$groupId) {
            return DB::select("select DISTINCT(u.id) as user_id,u.firstname as name, u.emailid, u.status,u.group_status,u.resetactivationcode from users u inner join company_user_mapping c on c.user_id=u.id where c.code='".$companyCode."' and u.group_id='".$groupId."' ORDER BY firstname");  
    }
    
   // creating new Enterprise user Company Profile in storage
    public function createGroup()
    {        
      $groups = array(
                    "name" => "admin",
                    "ip_address"    => $_SERVER['REMOTE_ADDR'],
                    "is_primary"    => '1',
                    "status"        => "Active"
       );
       return $this->groups->create($groups);
     }
     
     public function updateGroup($input,$companyId) {
         $sql = "update groups set created_by='".$input['user_id']."',company_id='".$companyId."' where id='".$input['group_id']."'";
         return $result = DB::statement($sql);
         
     }
     
     public function addGroup($input) {
         $ipaddress = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.1.1.0';
         $sql = "insert into groups(`name`,`company_id`,`created_by`,`ip_address`,`status`) values('".$input['name']."','".$input['company_id']."','".$input['user_id']."','".$ipaddress."','".$input['status']."')";
         $r = DB::statement($sql);
         if($r){
         $result = DB::select("SELECT LAST_INSERT_ID() as last_id");  
         return $result;
         }
     }
     
     public function getGroups($input) {
         if($input['group_name'] == 'admin'){
             $sql = "select DISTINCT g.* from groups g where g.company_id='".$input['company_id']."'";
         }else{
             $sql = "select DISTINCT g.* from groups g left join users u on g.id=u.group_id
                     WHERE u.id='" . $input['user_id'] . "'";
         }
         $sql .= " ORDER BY name";
         return $result = DB::SELECT($sql);
     }
     
     public function getGroup($id) {
         return DB::SELECT("select g.name from groups g where g.id='".$id."'");
     }
     public function adminPermissions($input) {
          
         for($i = 1; $i <= 8; $i++) {     
         DB::table('groups_permissions')->insert(
                                array(
                                    'groups_id' => $input['group_id'],
                                    'permissions_id' => $i,
                                    'permission' => '1',
                                    'type' => '1',
                                    'last_modified_by' => $input['user_id']
                                )
                        );
         }
         
         
     }
     
      public function editGroup($input) {
            $sql = "update groups set name='".$input['name']."',status='".$input['status']."' where id='".$input['group_id']."'";
            $r = DB::statement($sql);
            if($r){
             return DB::table("groups")
                     ->where('id','=',$input['group_id'])->get();
      }
      }
      
      public function checkGroup($input) {
          return DB::table('groups')
                  ->where('name', '=', $input['name'])
                  ->where('id','!=', $input['id'])
                  ->where('company_id', '=', $input['company_id'])->get();
          
      }
      
      public function getEmailActivationCode($input) {
            return DB::table('users')
                  ->where('resetactivationcode', '=', $input['code'])->get();
          
      }
      
      public function checkUser($input) {
           return DB::table('users')
                  ->where('emailid', '=', $input['emailid'])
                  ->where('id','!=', $input['user_id'])->get();
      }
      
      public function checkGroupStatus($groupId) {
              return DB::table('groups')
                  ->where('id', '=', $groupId)->get();
      }
      
      public function updateCompanyLogo($input) {
//          return Company_Profile::where ('created_by',$input['created_by'])->update('logo' => $input['photo']); 
             $sql = DB::table('company')
                    ->where('created_by', $input['user_id'])  
                    ->limit(1)  // optional - to ensure only one record is updated.
                    ->update(array('logo' => $input['photo'])); 
             if($sql){
                 return DB::Select("select id from company where created_by = '".$input['user_id']."'");
             }
      }
      
      public function updateUser($input) {
            return DB::table('users')
                    ->where('id', $input['user_id'])  
                    ->limit(1)  // optional - to ensure only one record is updated.
                    ->update(array('firstname' => $input['name']));
          
      }
      public function getEnterpriseUsers() {
          return DB::table('users')
                  ->where('is_enterprise','=','1')->get();
          
      }
      public function updateNewPermission($input) {     
         DB::table('groups_permissions')->insert(
                                array(
                                    'groups_id' => $input['group_id'],
                                    'permissions_id' => '8',
                                    'permission' => '1',
                                    'type' => '1',
                                    'last_modified_by' => $input['user_id']
                                )
                        );
         
     }
     
     public function getEnterpriseCompanies() {
        return DB::SELECT("SELECT c.id as cid,c.created_by, IFNULL(g.id,0) AS gid FROM company c
                            LEFT JOIN groups g ON g.company_id=c.id AND g.name='admin'
                        where 1");
     }
     
     public function getUsersGroupId($input) {
        return DB::statement("UPDATE users u
                            INNER JOIN company_user_mapping cu ON cu.user_id=u.id 
                            SET u.group_id='".$input['group_id']."'
                            WHERE 1 and cu.company_id='".$input['company_id']."'");
     }
     
     public function deleteGroupPermissions($groups_id) {
        return DB::statement("DELETE FROM groups_permissions WHERE groups_id ='".$groups_id."'");
     }
     
    public function getContactByEmailId($userId,$companyId){   
            return DB::table('contacts')  
            ->where('emailid', '=', $userId)
            ->where('company_id', '=', $companyId)->get();
    }
    
    public function getContactById($id){   
            return DB::table('contacts')  
            ->where('id', '=', $id)->get();
    }
    
    public function getAccessCode($input) {
        return DB::table('company_access')  
            ->where('access_code', '=', $input['lic_no'])
            ->where('status', '=','0')->get();
    }
    
    public function updateAccessCodeTable($input,$companyId) {
         $sql = "update company_access set status='1',company_id='".$companyId."' where access_code='".$input['lic_no']."'";
         return $result = DB::statement($sql);
    }
    
    public function appDownloadCount($companyId) {
         $sql = "select count(*) as count from contacts where company_id='".$companyId."' and status='Active' and emailid IN (select emailid from users)";
         return $result = DB::SELECT($sql);
    }
    public function appActiveContactsCount($companyId) {
         $sql = "select count(*) as count from contacts where company_id='".$companyId."' and status='Active' ";
         return $result = DB::SELECT($sql);
    }
    #log the company subscriptions
    public function addCompanySubscriptionsLog($companyId=0, $employeesNo=0, $startDate='', $endDate='')
    {   
        $result = FALSE;
        if(!empty($companyId) && !empty($employeesNo)){
            $sql = "insert into company_subscriptions (`company_id`,`employees_no`,`start_date`,`end_date`)" ;
            $sql.=" values('".$companyId."', '".$employeesNo."', '".$startDate."', '".$endDate."')" ;
            $result = DB::statement($sql);
        }
        return $result;
    }
    
    public function getCompanyPurchasedContacts($companyCode='') {
        $result = FALSE;
        if(!empty($companyCode)){
            $sql = "select sum(s.employees_no) as count
                from company_subscriptions s 
                left join company c on s.company_id = c.id 
                where c.code = '".$companyCode."'";
            $sqlResult = DB::SELECT($sql);
            if (isset($sqlResult[0])){
                $result = $sqlResult[0]->count;
            }
        }
        return $result;
    }
    
    public function getCompanyActiveOrInactiveContactsCount($companyCode='') {
        $result = FALSE;
        if(!empty($companyCode)){
            $sql = "select count(t.id) as count
                from contacts t
                left join company c on t.company_id = c.id 
                where c.code = '".$companyCode."' and (t.status = 'Active' or t.status='Inactive')";
            $sqlResult = DB::SELECT($sql);
            if (isset($sqlResult[0])){
                $result = $sqlResult[0]->count;
            }
        }
        return $result;
    }
    
    public function getCompanySubscriptions($companyCode='') {
        $result = FALSE;
        if(!empty($companyCode)){
         $sql = "select t.name,a.access_code,s.employees_no,s.start_date,s.end_date
                from company_subscriptions s 
                left join company c on s.company_id = c.id 
                left join subscription_types t on t.id = c.subscription_type
                left join company_access a on a.company_id = c.id 
                where c.code = '".$companyCode."'";
         $result = DB::SELECT($sql);
        }
        return $result;
    }
    
    public function addHcm($companyId, $hcmName) {
        
        $date = gmdate("Y-m-d H:i:s");
        $return = 0;
        $sql = "insert into hcm(`name`,`status`,`created_at`) values('".$hcmName."','1','".$date."')";
        $result = DB::statement($sql);
        if($result){
            $hcmId  = DB::select("SELECT LAST_INSERT_ID() as hcm_id"); 
            $hcmId  = !empty($hcmId[0]->hcm_id)?$hcmId[0]->hcm_id:'';
            $data   = $this->hcmConfig($hcmId, $companyId);
            $return = $hcmId;
        }
        return $return;
    }
    
    public function hcmConfig($hcmId, $companyId) {
        
        $date = gmdate("Y-m-d H:i:s");
        $return = 0;
        $sql = "insert into hcm_config_properties(`hcm_id`,`company_id`,`config_name`,`config_value`,`created_at`)
                values
                ('".$hcmId."','".$companyId."','DCNAME','','".$date."'),
                ('".$hcmId."','".$companyId."','USERNAME','','".$date."'),
                ('".$hcmId."','".$companyId."','PASSWORD','','".$date."')";
        $return = DB::Statement($sql);
        return $return;
    }
    
    public function setHcmConfigProperties($hcmId=0, $companyId=0, $hcmAry=array()) {
        $result = array();
        $date = gmdate("Y-m-d H:i:s");
        $result['insert'] = FALSE;
        foreach ($hcmAry as $value) {
           $configName     = $value['name'];
           $configValue    = $value['value'];

           if(!empty($configValue)){
            $checkHcm = $this->getHcmConfigProperties($hcmId, $companyId, $configName);
                if(!empty($checkHcm)){
                   $sql = "update hcm_config_properties set config_value='".$configValue."' where hcm_id='".$hcmId."' and company_id = '".$companyId."'  and config_name='".$configName."' ";
                   $result[] = DB::statement($sql);
                }else{
                   $sql = "insert into hcm_config_properties(`hcm_id`,`company_id`,`config_name`,`config_value`,`created_at`)
                            values ('".$hcmId."','".$companyId."','".$configName."','".$configValue."','".$date."')";
                   $result[] = DB::statement($sql);
                   $result['insert'] = TRUE;
                }
           }
        }
        return  $result;
    }
    
    public function getHcmList($companyId='', $hcmId='') {
        $result = FALSE;
        if(!empty($companyId)){
         $sql = "select h.hcm_id, h.name, c.config_name, c.config_value, s.status from
                hcm_config_properties c
                left join hcm h on h.hcm_id = c.hcm_id
                left join hcm_jobs j on j.hcm_id = c.hcm_id
                left join company_hcm_jobs s on s.hcm_jobs_id = j.hcm_jobs_id
                where c.company_id='".$companyId."'";
            #get single hcm details    
            if(!empty($hcmId)){
                $sql .=" and c.hcm_id = '".$hcmId."'";
            }
         $result = DB::SELECT($sql);
        }
        return $result;
    }
    
    public function getCompanyConnectedUser($companyCode='') {
        return DB::table('company_user_mapping')  
                ->limit(1)
                ->where('code', '=', $companyCode)->get();
    }
    
    public function checkCompanyHcmJobs($companyId='', $hcmJobsId) {
        return DB::table('company_hcm_jobs')  
                ->where('company_id', '=', $companyId)
                ->where('hcm_jobs_id', '=', $hcmJobsId)->get();
    }
    public function getCompanyMappingFieldsCount($companyId='', $company_hcm_jobs_id) {
         return DB::table('company_hcm_jobs_fields_mapping')  
                ->where('company_id', '=', $companyId)
                ->where('company_hcm_jobs_id', '=', $company_hcm_jobs_id)->count();
    }
    public function getCompanyConfigProperties($companyId,$hcm_id) {
         return DB::table('hcm_config_properties')
                 ->select('config_name','config_value')
                ->where('company_id', '=', $companyId)
                ->where('hcm_id', '=', $hcm_id)->get();
    }  
    public function integrateCompany($input = array()) {
         $result = DB::table('company_idp')->insert(
                                array(
                                    'company_id'    => $input['company_id'],
                                    'user_id'       => $input['user_id'],
                                    'company_code'  => $input['company_code'],
                                    'idp_signin_url'    => $input['signin_url'],
                                    'idp_signout_url'   => $input['signout_url'],
                                    'idp_issuer'        => $input['idp_issuer'],
                                    'idp_cert'          => $input['certificate'],
                                    'idp_file_name'     => $input['idp_file_name'],
                                    'idp_file_content'  => $input['idp_file_content'],
                                    'status'     => $input['status'],
                                    'created_at' => $input['createdAt']
                                )
                        );
         if($result){
           return DB::table('company_idp')  
                ->where('company_code', '=', $input['company_code'])->get();
         }
    }
         
    public function checkCompanyIntegration($companyCode='') {
        return DB::table('company_idp')  
                ->where('company_code', '=', $companyCode)->get();
    }
    
    public function getHcmPartners() {
        return DB::table('hcm')
                ->select('hcm_id','name')
                ->get();
    }
    
    public function getHcmConfigProperties($hcmId='', $companyId='', $configName='') {
        return DB::table('hcm_config_properties')  
                ->where('hcm_id', '=', $hcmId)
                ->where('company_id', '=', $companyId)
                ->where('config_name', '=', $configName)->get();
    }
    
    public function getCompanyHcmJobs($hcmId='', $companyId='') {
        $result = FALSE;
        if(!empty($hcmId)){
        $sql = "select c.* from company_hcm_jobs c
                left join hcm_jobs j on j.hcm_jobs_id = c.hcm_jobs_id 
                where j.hcm_id = '".$hcmId."' and company_id = '".$companyId."'";
          $result = DB::SELECT($sql);
        } 
        return $result;
    }
    public function updateHcmRunStatus($companyHcmJobsId='', $hcmRunStatus='') {
        $result = FALSE;
        $hcmRunStatus = ($hcmRunStatus=='enable')?1:0;
        
        if(!empty($companyHcmJobsId)){
            
           $sql = "select * from company_hcm_jobs where company_hcm_jobs_id='".$companyHcmJobsId."'";
           $result = DB::statement($sql);
           $frequency = !empty($result[0]->frequency)?$result[0]->frequency:0;
           //$frequency = 300;
           $frequency = "+".$frequency." second";
           $lastDate = gmdate("Y-m-d H:i:s");
           $nextDate = gmdate('Y-m-d H:i:s', strtotime($frequency));
           
           $sql = "update company_hcm_jobs set status='".$hcmRunStatus."' where company_hcm_jobs_id='".$companyHcmJobsId."'";
           $result = DB::statement($sql);
        }
        return $result;
    }
    
     public function getCompanyAllContacts($params){
                $sql = 'SELECT SQL_CALC_FOUND_ROWS c.id AS record_id, 
                    c.firstname, 
                    c.lastname, c.emailid, c.phone, c.employeeid, c.status
                        FROM contacts c ';
                if(!empty($params['bucket_id']))
                $sql.= ' LEFT JOIN buckets_contacts bc ON c.id=bc.contact_id';
                
               // $sql = "select SQL_CALC_FOUND_ROWS id as record_id, firstname, lastname, emailid, phone, employeeid, status from contacts ";
                $sql.= " where c.company_id='".$params['company_id']."' AND c.status!='Separated' " ;
                 if(!empty($params['bucket_id'])){
                     $sql.= " AND bc.bucket_id = '".$params['bucket_id']."' " ;
                 }
               // $sql.= !empty($params['bucket_id'])?" and id IN (select contact_id from buckets_contacts where bucket_id = '".$params['bucket_id']."') ":'';
                
                if(!empty($params['search'])){
                    $sql.= " AND (c.emailid like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%')";
                }
                 $sql .= " GROUP BY c.id ";
                $sql .= "order by status";
                if($params['sort'] == 'desc'){
                    $sql .= " desc";
                }
                $page = $params['page_no'];
                if (!empty($page))
                {
                    $page = $page-1 ;
                    $offset  = $page*50 ;
                    $sql.=  " limit ".$offset.",50 ";
                } 
                $result['Contacts_list'] = DB::select($sql);
                $result['total_records'] = DB::select("select FOUND_ROWS() as total_count");
            return $result;    
        }
        
        public function updateEnterpriseUser($email='', $groupid='' ,$isEnterprise='') {
            return DB::table('users')
                    ->where('emailid',$email)  
                    ->update(array('is_enterprise' => $isEnterprise,'group_id'=>$groupid));
          
      }
        public function updateConfiguration($input=array()) {
            $result =  DB::table('company_idp')
                    ->where('id',$input['id'])  
                    ->update(array('idp_signin_url'     =>  $input['signin_url'],
                                    'idp_signout_url'   =>  $input['signout_url'],
                                    'idp_issuer'        =>  $input['idp_issuer'],
                                    'idp_cert'          =>  $input['certificate'],
                                    'idp_file_name'     =>  $input['idp_file_name'],
                                    'idp_file_content'  =>  $input['idp_file_content'],
                                    'status'            =>  $input['status']
                                ));
           if($result){
           return DB::table('company_idp')  
                ->where('id', '=', $input['id'])->get();
         }
      }
      
      public function getAllCompaniesData() {
        return DB::table('company')
                ->select('id','employees_no','created_at')
                ->get();
    }
    
    public function checkContactCurrentStatusById($recordId=''){
        $result = 0;
        if($recordId){
            $sql = "select id from contacts where id=".$recordId." and (status = 'Active' or status='Inactive') " ;
            $result = DB::Select($sql);
        }
        return $result;
    }
    public function checkContactCurrentStatusByEmailId($companyId='',$emailId=''){
        $result = 0;
        if(!empty($companyId) && !empty($emailId)){
            $sql = "select id from contacts where company_id ='".$companyId."' and emailid='".$emailId."' and (status = 'Active' or status='Inactive')" ;
            $result = DB::Select($sql);
        }
        return $result;
    }
    public function checkIfTheUserIsAdmin($companyId='',$emailId=''){
        $result = 0;
        if(!empty($companyId) && !empty($emailId)){
            $sql = "select u.id from users u 
                    inner join company c on c.created_by = u.id
                    where u.emailid='".$emailId."' and c.id='".$companyId."'" ;
            $result = DB::Select($sql);
        }
        return $result;
    }
    
    // creating new Enterprise user Company mapping in storage
    public function insertInCompanyResumes($companyId=0, $resumeName='', $userId=0, $source=0, $gotReferred=0)
    {   
        $createdAt = gmdate("Y-m-d H:i:s");
        $companyResumes = array(
                        "company_id"   => $companyId,
                        "file_original_name"  => $resumeName,
                        "status"        => self::COMPANY_RESUME_STATUS,
                        "file_from"     => $source,
                        "got_referred_id"  => $gotReferred,
                        "created_by"    => $userId,
                        "created_at"    => $createdAt
            );
        return $this->companyResumes->create($companyResumes);
    }
    // creating new Enterprise user Company mapping in storage
    public function updateCompanyResumes($documentId=0, $filesource='')
    {   
        $updatedAt = gmdate("Y-m-d H:i:s");
        $companyResumes = array(
                        "file_source"   => $filesource,
                        "status"        => self::COMPANY_RESUME_S3_MOVED_STATUS,
                        "updated_at"    => $updatedAt
            );
        if($documentId){
            $results = Company_Resumes::where ('id',$documentId)->update($companyResumes); 
        }
        
       return $results;
    }
    
    public function getNotParsedCompanyResumesByStatus($status=1) {
        $return = FALSE;
        if($status){
            $return = Company_Resumes::where ('status',$status)->get();
        }
        return $return;
    }
    
    public function updateCompanyResumesWithGotReferredId($documentId = 0, $gotReferredId = 0)
    {   
        $results = FALSE;
        $updatedAt = gmdate("Y-m-d H:i:s");
        $companyResumes = array(
                        "got_referred_id"   => $gotReferredId,
                        "updated_at"        => $updatedAt
            );
        if($documentId){
            $results = Company_Resumes::where ('id',$documentId)->update($companyResumes); 
        }
       return $results;
    }
    
    public function getCompanyContactsId($emailId = '', $companyCode = '') {
        
        $result = 0;
        if(!empty($companyCode) && !empty($emailId)){
            $sql = "select n.id from contacts n
                    left join company m on m.id = n.company_id  
                    where n.emailid='".$emailId."' and m.code='".$companyCode."' " ;
            $result = DB::Select($sql);
        }
        return $result;
    }
    
    public function updateCompanyContactPhoneNumber($contactId = 0, $phone = '') {
        
        $result = FALSE;
        if(!empty($contactId) && !empty($phone)) {
            $result =  DB::table('contacts')
                    ->where('id', $contactId)  
                    ->update(array('phone' => $phone));
        }
        return $result; 
    }
    
    public function getCompanyList() {
        $sql = 'select code,name from company';
        return $result = DB::Select($sql);
    }
}
