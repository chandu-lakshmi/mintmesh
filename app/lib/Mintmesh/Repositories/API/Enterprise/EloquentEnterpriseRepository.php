<?php namespace Mintmesh\Repositories\API\Enterprise;

use User;
use Company_Profile;
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

        protected $user, $companyProfile, $CompanyContact;
        protected $email, $level, $appEncodeDecode;
        
        public function __construct(User $user,Company_Profile $companyProfile,Company_Contacts $CompanyContact,
                                    Emails_Logs $email, APPEncode $appEncodeDecode){ 
                $this->user = $user;    
                $this->companyProfile = $companyProfile;    
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
                        "emailactivationcode" => $emailActivationCode
            );
            return $this->user->create($user);
        }
        
        // creating new Enterprise user Company Profile in storage
        public function createCompanyProfile($input)
        {     
            $companyProfile = array(
                        "name" => $this->appEncodeDecode->filterString($input['company']),
                        "code" => $input['company_code'],
                        "created_by" => $input['user_id'],
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
                        "description"   => $this->appEncodeDecode->filterString($input['description']),
                        "website"       => !empty($input['website'])?$input['website']:'',
                        "employees_no"  => !empty($input['number_of_employees'])?$input['number_of_employees']:'',
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
        public function isBucketExist($userId, $companyId, $bucketName){
            
            $response = false;
            if (!empty($userId) && !empty($companyId) && !empty($bucketName))
            {  
                //$sql = "select COUNT(id) as count from import_contacts_buckets where user_id = '".$userId."' and company_id = '".$companyId."' and name like '".$bucketName."' " ;
                $sql = "select COUNT(id) as count from buckets where user_id = '".$userId."' and company_id = '".$companyId."' and name like '".$bucketName."' " ;
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
        public function uploadContacts($input, $userId, $bucketId, $companyId, $importFileId)
        {     
            $ipAddress = !empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:0;
            $createdAt = gmdate("Y-m-d H:i:s");
            if (!empty($userId) && !empty($bucketId) && !empty($companyId))
            {
                $contactsList = $this->getImportContactByEmailId($userId, $bucketId, $companyId);
                $contactIsExist = $updatedRows = $result = array();
               
                foreach ($contactsList as $obj){
                    $contactIsExist[] = $obj->emailid;     
                }
                  
                $sql = "insert into contacts 
                        (`user_id`,`company_id`,`import_file_id`,`firstname`,`lastname`,`emailid`,`phone`,`employeeid`,`status`, `updated_by`,`created_at`,`created_by`,`ip_address`) values " ;
				$inrt_sql = '';	
				$i = 0;	
				$insertResult = 0;
				$updatedRows = 0;
                foreach ($input as $key=>$val)
                {                             
                    $employeeId  = !empty($val['employee_idother_id'])?$val['employee_idother_id']:'';
                    $cellPhone   = !empty($val['cell_phone'])?$val['cell_phone']:'';
                    $status      = !empty($val['status'])?$val['status']:'unknown';
                     
                    if(!empty($val['first_name'])&&!empty($val['last_name'])&&filter_var($val['email_id'], FILTER_VALIDATE_EMAIL))
                    {                    
                        $firstName = $this->appEncodeDecode->filterString($val['first_name']);
                        $lastName  = $this->appEncodeDecode->filterString($val['last_name']);
                        $emailId   = $val['email_id'];
                        $usersArr  = User::select('id')->where('emailid',$emailId)->get();
                        $users_id  = !empty($usersArr[0])?$usersArr[0]->id:0;
                        
                        if(!in_array($val['email_id'], $contactIsExist)){   
                            $inrt_sql.="('".$users_id."','".$companyId."','".$importFileId."','".$firstName."','".$lastName."','".$emailId."','".$cellPhone."',";
                            $inrt_sql.="'".$employeeId."','".$status."','".$userId."','".$createdAt."','".$userId."','".$ipAddress."')," ;
                            $i++;
                            $insertResult++;
                        } else {
                            $sqlQuery = "UPDATE contacts SET `user_id`='".$users_id."', `import_file_id` = '".$importFileId."',`firstname`='".$firstName."',`lastname`='".$lastName."',";
                            $sqlQuery.= " `phone`='".$cellPhone."',`employeeid`='".$employeeId."',`status`='".$status."',`updated_by`='".$userId."'";
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
            } 
            return $result;    
        }
        
        // get Import Contact By EmailId
        public function getImportContactByEmailId($userId, $bucketId, $companyId){   
            return DB::table('contacts')
                   ->select('emailid')
                   //->where('user_id', '=', $userId)
                   ->where('company_id', '=', $companyId)
                   //->where('bucket_id', '=', $bucketId)
                   ->get();
        }
        
        public function getCompanyBucketsList($params){
            $sql = 'SELECT b.name AS bucket_name,b.id as bucket_id, COUNT(DISTINCT bc.contact_id) AS count
                    FROM buckets b
                    LEFT JOIN buckets_contacts bc ON bc.bucket_id=b.id AND bc.company_id="'.$params['company_id'].'"
                    WHERE 1 AND (b.company_id = "'.$params['company_id'].'" OR b.company_id = "0")
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
                $sql = 'SELECT SQL_CALC_FOUND_ROWS c.id AS record_id, 
                    c.firstname, 
                    c.lastname, c.emailid, c.phone, c.employeeid, c.status
                        FROM contacts c ';
                if(!empty($params['bucket_id']))
                $sql.= ' LEFT JOIN buckets_contacts bc ON c.id=bc.contact_id';
                
               // $sql = "select SQL_CALC_FOUND_ROWS id as record_id, firstname, lastname, emailid, phone, employeeid, status from contacts ";
                $sql.= " where c.company_id='".$params['company_id']."' " ;
                 if(!empty($params['bucket_id'])){
                     $sql.= " AND bc.bucket_id = '".$params['bucket_id']."' " ;
                 }
               // $sql.= !empty($params['bucket_id'])?" and id IN (select contact_id from buckets_contacts where bucket_id = '".$params['bucket_id']."') ":'';
                
                if(!empty($params['search'])){
                    $sql.= " AND (c.emailid like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%'";
                    $sql.= " OR c.phone like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%'";
                    $sql.= " OR c.firstname like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%'";
                    $sql.= " OR c.lastname like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%'";
                    $sql.= " OR c.employeeid like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%'";
                    $sql.= " OR c.status like '%".  $this->appEncodeDecode->filterString(strtolower($params['search']))."%')";
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
        public function getCompanyDetailsByCode($companyCode){    
            return DB::table('company')
                   ->select('logo','id')
                   ->where('code', '=', $companyCode)->get();
        }
        
        public function companyInvitedCount($userId, $companyId, $filterLimit,$download = FALSE)
        {   
            $sql = "select COUNT(DISTINCT to_email) AS count from emails_logs " ;
            $sql.=" where from_user ='".$userId."' and emails_types_id = 5 and related_code ='".$companyId."' and created_at >= '".$filterLimit."'" ;
            if($download){ 
                $sql.=" and to_email IN (select emailid from users)" ;
            }
            $result = DB::Select($sql);
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
            
            return DB::table('contacts')
                   ->where('company_id', '=', $input['company_id'])
                   ->where('employeeid', '=', $input['other_id'])
                   ->where('id', '!=', $input['record_id'])->get(); 
           
        }
        public function checkEmpId($input) {
            return DB::table('contacts')
                   ->where('company_id', '=', $input['company_id'])
                   ->where('employeeid', '=', $input['other_id'])->get(); 
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
}
