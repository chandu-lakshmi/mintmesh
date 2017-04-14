<?php
namespace Mintmesh\Services\IntegrationManager;

use Mintmesh\Services\APPEncode\APPEncode;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Guzzle\Http\Client as guzzleClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Message\ResponseInterface;
use lib\Parser\MyEncrypt;
use DB,Config,Queue,Lang;

class IntegrationManager {
    protected  $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    protected  $userRepository, $guzzleClient;
    public $requestParams = array();
    const SUCCESS_RESPONSE_CODE = 200;
    const API_END_POINT = 'API_END_POINT';
    const DCNAME = 'DCNAME';
    const USERNAME = 'USERNAME';
    const PASSWORD = 'PASSWORD';
    const API_LOCAL_URL = 'https://apisalesdemo8.successfactors.com/odata/v2/JobRequisitionLocale';

    public function __construct() {
                $this->db_user = Config::get('database.connections.neo4j.username');
                $this->db_pwd = Config::get('database.connections.neo4j.password');
                $this->db_host = Config::get('database.connections.neo4j.host');
                $this->db_port = Config::get('database.connections.neo4j.port');
                $this->client = new NeoClient($this->db_host, $this->db_port);
                $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
                $this->neoEnterpriseUser = $this->db_user;
                $this->guzzleClient = new guzzleClient();
                $this->appEncodeDecode = new APPEncode();
    }

    private function getCompanyHcmJobbyId($company_hcm_job_id) {
        // get the job data from company_hcm_jobs
        $result = DB::table('company_hcm_jobs')
                ->select('hcm_jobs_id','company_id','frequency','last_processed_at','next_process_at','status')
                ->where('company_hcm_jobs_id', '=', $company_hcm_job_id)->first();

        return $result;

    }

    private function getJobDetail($jobId) {
        // getting the job configs from hcm_jobs table
        $result = DB::table('hcm_jobs')
                ->select('hcm_id','job_name','job_endpoint','job_params','job_additional_params')
                ->where('hcm_jobs_id', '=', $jobId)
                ->where('status', '=', '1')->first();
        return $result;
    }

    private function getCompanyJobConfigs($hcmId, $companyId) {
        $result = DB::table('hcm_config_properties')
                ->select('config_name','config_value')
                ->where('hcm_id', '=', $hcmId)
                ->where('company_id', '=', $companyId)->get();
        return $result;
    }
    
    private function getJobMappingFields($hcmJobId, $companyId) {
        $result = DB::table('company_hcm_jobs_fields_mapping')
                ->select('source_key','destination_key')
                ->where('company_hcm_jobs_id', '=', $hcmJobId)
                ->where('company_id', '=', $companyId)->get();
        return $result;
    }
    
    public function getCompanyDetails($id) {
            return DB::table('company')
                ->where('id', '=', $id)->get(); 
    }
    public function getUserDetails($id) {
            return DB::table('users')
                ->where('id', '=', $id)->get(); 
    }
    
    public function getNeoUserByEmailId($userEmailId) {
            $return = array();
            $queryString = "match (u:User) where u.emailid='".$userEmailId."' return u";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();         
            if (isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
           return $return;
        }

    public function composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails) {
        // composing request params and configs
        $returnRequestData = array();
        // composing endpoing
        foreach($companyJobConfigDetails as $dataValue) {
            $returnRequestData[$dataValue->config_name] = $dataValue->config_value;
        }

        // converting last processed datetime to UTC timezone
         $lastprocessedDate = gmdate("Y-m-d\TH:i:s\Z", strtotime($companyJobDetail->last_processed_at));

        $returnRequestData[self::API_END_POINT]  = $returnRequestData[self::DCNAME].$JobDetails->job_endpoint.$JobDetails->job_additional_params;
        $returnRequestData[self::API_END_POINT] .= '&$select='.$JobDetails->job_params;
        $returnRequestData[self::API_END_POINT] .= '&$filter=lastModifiedDateTime ge datetimeoffset\''.$lastprocessedDate.'\' and internalStatus eq \'Approved\'';
                                                    
        return $returnRequestData;
    }

    public function intiateRequest($company_hcm_job_id) {

        $companyJobDetail        = $this->getCompanyHcmJobbyId($company_hcm_job_id);
        if(!empty($companyJobDetail) && $companyJobDetail->status == '1'){
            $JobDetails              = $this->getJobDetail($companyJobDetail->hcm_jobs_id);
            $companyJobConfigDetails = $this->getCompanyJobConfigs($JobDetails->hcm_id, $companyJobDetail->company_id);
            $requestParams           = $this->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);

            //$endPoint = 'https://apisalesdemo8.successfactors.com:443/odata/v2/JobRequisition?$format=json&$select=jobCode,function,location,industry,jobGrade,positionNumber,jobReqId,numberOpenings,classificationType,currency&$filter=lastModifiedDateTime ge datetime\'2016-11-22T17:19:28\'';
            //$endPoint = 'https://apisalesdemo8.successfactors.com/odata/v2/JobRequisitionLocale(1683)?%24format=json';
            //$requestParams[self::API_END_POINT] = $endPoint;
            $this->requestParams = $requestParams;
            $return = $this->doRequest($requestParams);
            $this->processResponseData($return, $companyJobDetail->hcm_jobs_id, $companyJobDetail->company_id);
            $this->updateLastProcessedTime($company_hcm_job_id, $companyJobDetail);
        }  
    }

    public function processResponseData($responseBody, $jobId, $companyId) {
        // insert to mintmesh data
        //echo $jobId."\n";
        //echo $companyId."\n";
        //echo $responseBody."\n";
        //echo "End : ".date("Y-m-d H:i:s")."\n";
        $array  = json_decode($responseBody,TRUE);
        
        $return = array();
        if(isset($array['d']) && isset($array['d']['results'])){
            $return = $array['d']['results'];
            $this->createJob($return, $companyId, $jobId);
        }
        
        return true;
    }

    
    
     public function createJob($dataAry, $companyId, $jobId) {
         
        $hcmJobId       = $jobId;
        $companyId      = $companyId;
        $bucketId       = 1;
        $fromId = $postId = 0;
        $dfText = 'See Job Description';
        $companyDetails = $this->getCompanyDetails($companyId);
        $companyDetails = $companyDetails[0];
        $companyName    = $companyDetails->name;//'company68';
        $companyCode    = $companyDetails->code;//510632;
        $companyLogo    = $companyDetails->logo;//510632;
        $userDetails    = $this->getUserDetails($companyDetails->created_by);
        $userDetails    = $userDetails[0];
        $userEmailId    = !empty($userDetails->emailid)?$userDetails->emailid:'';//'gopi68@mintmesh.com';
        $userFirstname  = !empty($userDetails->firstname)?$userDetails->firstname:'';//'gopi68@mintmesh.com';
         
        $notificationMsg = Lang::get('MINTMESH.notifications.messages.27');
        $params['company_code']  = $companyCode;
        $params['bucket_id']     = $bucketId;
        $params['company_id']    = $companyId;

        if(!empty($userEmailId)){
            $neoUser        = $this->getNeoUserByEmailId($userEmailId);
            $fromId         = !empty($neoUser->getID())?$neoUser->getID():'';//292819;
        }
        if(!empty($dataAry)){
            foreach($dataAry as $row){
                $inviteCount = 0;
                $neoInput =  $relationAttrs  = $postCompanyrelationAttrs = $neoCompanyBucketContacts = array();
                $JobMappingFields = $this->getJobMappingFields($hcmJobId, $companyId); 
                foreach ($JobMappingFields as $field){

                    $neoInput[$field->destination_key] =  !empty($row[$field->source_key])?$row[$field->source_key]:'';

                }
                $neoInput['service_scope']      = "find_candidate";
                $neoInput['service_from_web']   = 1;
                if(empty($neoInput['employment_type'])) {
                    $cfTime ='';  
                    if(!empty($neoInput['classification_time'])){
                       $cfTime = $neoInput['classification_time'];
                       $cfTime = ', '.$cfTime;
                       unset($neoInput['classification_time']);
                    }
                    $neoInput['employment_type']    = 'PERMANENT'.$cfTime;
                }
                $neoInput['service_period']     = 'immediate';
                $neoInput['service_type']       = 'global';
                $neoInput['free_service']       = 1;
                if(empty($neoInput['service_currency'])) {
                    $neoInput['service_currency']   = 'USD';
                }
                $neoInput['service_cost']       = '';
                $neoInput['company']            = $companyName;
                $neoInput['job_description']    = '';//job_description
                $neoInput['skills']             = '';
                $neoInput['status']             = Config::get('constants.POST.STATUSES.ACTIVE');
                $neoInput['created_by']         = $userEmailId;
                $neoInput['bucket_id']          = $bucketId;
                $neoInput['post_type']          = 'external';
                $neoInput['hcm_type']           = 'success factors';
                
                $neoInput['job_function']       = (!empty($neoInput['job_function']) && !filter_var($neoInput['job_function'], FILTER_VALIDATE_INT))?$neoInput['job_function']:$dfText;
                $neoInput['experience_range']   = !empty($neoInput['experience_range'])?$neoInput['experience_range']:$dfText;
                $neoInput['industry']           = !empty($neoInput['industry'])?$neoInput['industry']:$dfText;
                
                $relationAttrs['created_at']    = date("Y-m-d H:i:s");
                $relationAttrs['company_name']  = $companyName;
                $relationAttrs['company_code']  = $companyCode;
                #get the extra information
                $reqId = $neoInput['requistion_id'];
                $isNotExisted = $this->checkJobExistedWithReqIdOrNot($reqId);
            if($isNotExisted){
                if(!empty($reqId)){
                    $response = ''; 
                    $endPoint       = self::API_LOCAL_URL.'?$format=json&$filter=jobReqId eq '.$reqId;
                    $requestParams  = $this->requestParams;
                    //$endPoint = 'https://apisalesdemo8.successfactors.com/odata/v2/JobRequisitionLocale?$format=json&$filter=jobReqId eq 1682';
                    $requestParams[self::API_END_POINT] = $endPoint;
                    
                    $response       = $this->doRequest($requestParams);
                    $responseAry    = json_decode($response,TRUE);
                    //print_r($responseAry).exit;
                    if(isset($responseAry['d']) && isset($responseAry['d']['results']) && isset($responseAry['d']['results'][0])){
                        $localAry = $responseAry['d']['results'][0];
                        $neoInput['service_name']    = !empty($localAry['jobTitle'])?$localAry['jobTitle']:!empty($localAry['externalTitle'])?$localAry['externalTitle']:'';
                        $neoInput['job_description'] = !empty($localAry['jobDescription'])?$localAry['jobDescription']:!empty($localAry['externalJobDescription'])?$localAry['externalJobDescription']:'';
                    }
                }
                //print_r($neoInput).exit;
                $createdPost = $this->createPostAndUserRelation($fromId, $neoInput, $relationAttrs);
                if (isset($createdPost[0]) && isset($createdPost[0][0])) {
                    $postId = $createdPost[0][0]->getID();
                } else {
                    $postId = 0;
                }
                if(!empty($postId)){
                    #map post and Rewards
                    $rewardsAttrs = array();
                    $rewardsAttrs['post_id']        = $postId;
                    $rewardsAttrs['rewards_type']   = 'free';
                    $rewardsAttrs['type']           = 'discovery';
                    $rewardsAttrs['currency_type']  = 1;
                    $rewardsAttrs['rewards_value']  = 0;
                    $rewardsAttrs['created_at']     = gmdate("Y-m-d H:i:s");
                    $rewardsAttrs['created_by']     = $userEmailId;
                    $createdRewards = $this->createRewardsAndPostRelation($postId, $rewardsAttrs); 
                    #map post and company
                    $postCompanyrelationAttrs['created_at']     = gmdate("Y-m-d H:i:s");
                    $postCompanyrelationAttrs['user_emailid']   = $userEmailId;
                    if (!empty($relationAttrs['company_code'])) {
                        $createdrelation = $this->createPostAndCompanyRelation($postId, $relationAttrs['company_code'], $postCompanyrelationAttrs);
                    }
                    #for reply emailid 
                    $replyToName = Config::get('constants.MINTMESH_SUPPORT.REFERRER_NAME');
                    $replyToHost = Config::get('constants.MINTMESH_SUPPORT.REFERRER_HOST');
                    
                    $neoCompanyBucketContacts = $this->getImportContactsList($params);
                    //$inviteCount = !empty($neoCompanyBucketContacts['total_records'][0]->total_count)?$neoCompanyBucketContacts['total_records'][0]->total_count:0;
                    foreach ($neoCompanyBucketContacts['Contacts_list'] as $contact => $contacts) {
                        $pushData = array();
                        if($contacts->status != 'Separated'){

                            #creating included Relation between Post and Contacts 
                            $pushData['postId']             = $postId;
                            $pushData['bucket_id']          = $params['bucket_id'];
                            $pushData['contact_emailid']    = $contacts->emailid;
                            $pushData['company_code']       = $params['company_code'];
                            $pushData['user_emailid']       = $userEmailId;
                            $pushData['notification_msg']   = $notificationMsg;
                            $pushData['notification_log']   = 1;//for log the notification or not
                            Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                            
                            #send email notifications to all the contacts
                            $refId      = $refCode = 0;
                            $emailData  = array();
                            $refId      = $this->getUserNodeIdByEmailId($contacts->emailid);
                            $refCode                        = MyEncrypt::encrypt_blowfish($postId.'_'.$refId,Config::get('constants.MINTMESH_ENCCODE'));
                            $replyToData                    = '+ref='.$refCode;
                            $emailData['company_name']      = $companyName;
                            $emailData['company_code']      = $companyCode;
                            $emailData['post_id']           = $postId;
                            $emailData['post_type']         = $neoInput['post_type'];
                            $emailData['company_logo']      = $companyLogo;
                            $emailData['to_firstname']      = $contacts->firstname;
                            $emailData['to_lastname']       = $contacts->lastname;
                            $emailData['to_emailid']        = $contacts->emailid;
                            $emailData['from_userid']       = $fromId;
                            $emailData['from_emailid']      = $userEmailId;
                            $emailData['from_firstname']    = $userFirstname;
                            $emailData['ip_address']        = '192.168.1.1';
                            $emailData['ref_code']          = $refCode;
                            $emailData['reply_to']          = $replyToName.$replyToData.$replyToHost;
                          Queue::push('Mintmesh\Services\Queues\SendJobPostEmailToContactsQueue', $emailData, 'Notification');
                          $inviteCount+=1;
                        }
                    }
                    $this->updatePostInviteCount($postId, $inviteCount);
                }    
            }
          }
        }
        return true;
     }

     public function createPostAndUserRelation($fromId, $neoInput = array(), $relationAttrs = array()) {
        $queryString = "MATCH (u:User:Mintmesh)
                            WHERE ID(u) = " . $fromId . "
                            CREATE (p:Post ";
        if (!empty($neoInput)) {
            $queryString.="{";
            foreach ($neoInput as $k => $v) {
                if ($k == 'created_by')
                    $v = strtolower($v);
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=")<-[:" . Config::get('constants.REFERRALS.POSTED');
        if (!empty($relationAttrs)) {
            $queryString.="{";
            foreach ($relationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]-(u) set p.created_at='" . date("Y-m-d H:i:s") . "' ";
        $queryString.=" , p.invited_count=0, p.total_referral_count=0, p.referral_accepted_count=0, p.referral_declined_count=0, p.referral_hired_count=0, p.referral_interviewed_count=0,p.unsolicited_count=0 return p";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }

    public function createPostAndCompanyRelation($postId = '', $companyCode = '', $postCompanyrelationAttrs = array()) {
        $queryString = "Match (p:Post),(c:Company)
                                    where ID(p)=" . $postId . " and c.companyCode='" . $companyCode . "'
                                    create unique (p)-[:" . Config::get('constants.POST.POSTED_FOR');
        if (!empty($postCompanyrelationAttrs)) {
            $queryString.="{";
            foreach ($postCompanyrelationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(c) return c";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    public function getImportContactsList($params) {
                $sql = 'SELECT SQL_CALC_FOUND_ROWS c.id AS record_id,
                    c.firstname,
                    c.lastname, c.emailid, c.phone, c.employeeid, c.status
                        FROM contacts c ';
                if(!empty($params['bucket_id']))
                $sql.= ' LEFT JOIN buckets_contacts bc ON c.id=bc.contact_id';

                $sql.= " where c.company_id='".$params['company_id']."' " ;
                 if(!empty($params['bucket_id'])){
                     $sql.= " AND bc.bucket_id = '".$params['bucket_id']."' " ;
                 }

                $sql .= " GROUP BY c.id ";
                $sql .= "order by status";

                //echo $sql;exit;
                $result['Contacts_list'] = DB::select($sql);
                $result['total_records'] = DB::select("select FOUND_ROWS() as total_count");
            return $result;
    }
    
    public function updatePostInviteCount($jobid = "", $invitecount = "") {
        if (!empty($jobid)) {
            $queryString = "match (p:Post) where ID(p)=" . $jobid . " set p.invited_count=" .$invitecount. " return p";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }
    
    public function checkJobExistedWithReqIdOrNot($reqId='') {
        $return = TRUE;
        if (!empty($reqId)) {
            $queryString = "match (p:Post{hcm_type:'success factors'}) where p.requistion_id='".$reqId."' return p";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
            if (isset($result[0]) && isset($result[0][0])) {
                    $nodeId = $result[0][0];
                    $return = FALSE;
                }
        } 
        return $return;
    }
    
    public function addSFCandidateIdToUserNode($userNodeId='', $candidateId='') {
        if (!empty($userNodeId) && !empty($candidateId)) {
            $queryString = "match (u:User) where ID(u)=" . $userNodeId . " set u.sf_candidate_id =".$candidateId."  return u";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } 
        return TRUE;
    }
    
    public function createRewardsAndPostRelation($postId, $rewardsAttrs = array()){ 
         
        $result = array();
        $rewardsType = !empty($rewardsAttrs['type'])?$rewardsAttrs['type']:'free';
        $queryString = "MATCH (p:Post) WHERE ID(p)= " . $postId . " CREATE (n:Rewards ";
        if (!empty($rewardsAttrs)) {
            $queryString.="{";
            foreach ($rewardsAttrs as $k => $v) {
                $value =$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                if ($k == 'post_id' || $k == 'currency_type')
                    $value = str_replace("'", "", $value);
                
                $queryString.=$value;
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=")<-[:" . Config::get('constants.RELATIONS_TYPES.POST_REWARDS') . " {rewards_mode: '".$rewardsType."' } ]-(p) return p";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result;
    } 
    
    public function getUserNodeIdByEmailId($emailId ='') {
            $nodeId = 0;
            $emailId = $this->appEncodeDecode->filterString($emailId);
            $queryString = "MATCH (u:User) where u.emailid='".$emailId."'  return ID(u)";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
            if (isset($result[0]) && isset($result[0][0])) {
                    $nodeId = $result[0][0];
                }
        return  $nodeId;   
       }

    public function updateLastProcessedTime($company_hcm_job_id, $companyJobDetail) {
        $last_processed_at = $companyJobDetail->next_process_at;
        $next_processed_at = strtotime($companyJobDetail->next_process_at) + $companyJobDetail->frequency;
        $next_processed_at = date("Y-m-d H:i:s", $next_processed_at);

        $sql = 'UPDATE company_hcm_jobs SET last_processed_at = \''.$last_processed_at.'\',next_process_at = \''.$next_processed_at.'\' WHERE company_hcm_jobs_id ='. $company_hcm_job_id ;
        DB::Statement($sql);
        
        \Log::info("SF JobId $company_hcm_job_id for Company $companyJobDetail->company_id Processed at $companyJobDetail->last_processed_at Successfully");
        return true;
    }
    
    public function doRequest($requestParams) {
        // do request to hcm endpoints
        $endPoint = $requestParams[self::API_END_POINT];
        $username = $requestParams[self::USERNAME];
        $password = $requestParams[self::PASSWORD];
        \Log::info("SF Endpoint hit : $endPoint");
        $request = $this->guzzleClient->get($endPoint);
        
        $request->setAuth($username, $password);
        
        try {
            $response = $request->send();
            if($response->isSuccessful() && $response->getStatusCode() == self::SUCCESS_RESPONSE_CODE) {
                return $response->getBody();
            } else {
                \Log::info("Error while getting response : $response->getInfo()");
            }
        } catch (ClientErrorResponseException $exception) {
            $responseBody = $exception->getResponse()->getBody(true);
        }

    }
    
    public function doPost($data, $endPoint) {
        
        $requestParams  = $this->requestParams;
        #do post to hcm endpoints
        $endPoint = $requestParams[self::DCNAME].$endPoint;
        $username = $requestParams[self::USERNAME];
        $password = $requestParams[self::PASSWORD];
        \Log::info("SF Endpoint hit : $endPoint"); 

        $data    = json_encode($data);
        //print_r($data).exit;
        $request = $this->guzzleClient->post($endPoint, array('accept'=> 'application/json','Content-Type'=> 'application/json; charset=utf-8'),array());
        $request->setAuth($username, $password);
        $request->setBody($data);

        try {
            $response = $request->send();  
            if($response->isSuccessful() && $response->getStatusCode() == 201) {
                $return  = $response->getBody();
                return json_decode($return,TRUE);
            } else {
                \Log::info("Error while getting response :". $response->getInfo());
            }
        } catch (ServerErrorResponseException $exception) {
            $responseBody = $exception->getResponse()->getBody(true);
        }

    }
    
    public function jobReqForwardCandidates($jobReqId, $candidateId) {

        $data = array(
            "jobReqId"      => $jobReqId,
            "candidateId"   => $candidateId,
            "status"        => "Forwarded"
        );
        $endPoint = 'JobReqFwdCandidates?$format=JSON';
        return  $this->doPost($data, $endPoint); 
    }
    
    public function addAttachment($userDetails, $relation, $candidateId) {
        
        $contents   = '';
        $resumePath = !empty($relation['resume_path'])?$relation['resume_path']:'';
        $fileName   = !empty($relation['resume_original_name'])?$relation['resume_original_name']:'';
        
        if(!empty($resumePath)){
          //$fileName   = pathinfo($resumePath);
            $contents   = base64_encode(file_get_contents($resumePath));
        }
        $data = array(
            "userId"        => "admin", 
            "externalId"    => $candidateId,    
            "fileName"      => $fileName,
            "module"        => "CDP", 
            "description"   => "des1", 
            "fileContent"   => $contents, 
            "viewable"      => true, 
            "deletable"     => false 
        );
        $endPoint = 'Attachment?$format=JSON';
        return $this->doPost($data, $endPoint); 
    }
    
    public function createCandidate($userDetails, $relation){
      
        $contents   = '';
        $firstName  = !empty($userDetails['fullname'])?$userDetails['fullname']:!empty($userDetails['firstname'])?$userDetails['firstname']:'';
        $lastName   = !empty($userDetails['lastname'])?$userDetails['lastname']:'.';
        $emailId    = !empty($userDetails['emailid'])?$userDetails['emailid']:'';
        $phone      = !empty($userDetails['phone'])?$userDetails['phone']:'.';
        $refBy      = !empty($relation['referred_by'])?$relation['referred_by']:'';
        $country    = 'Us';
        #get non mintmesh details from contacts import relation
        if(empty($firstName) && !empty($refBy) && !empty($emailId)){
            $nonMMUser    = $this->getImportRelationDetailsByEmail($refBy, $emailId);
            $firstName    = !empty($nonMMUser->fullname)?$nonMMUser->fullname:!empty($nonMMUser->firstname)?$nonMMUser->firstname: "The contact";
        }  else {
            $firstName    = !empty($firstName)?$firstName:'The contact';
        }
        #form candidate basic details here
        $data = array(
            'firstName'     => $firstName,
            'lastName'      => $lastName,
            'country'       => $country,
            'cellPhone'     => $phone,
            'primaryEmail'  => $emailId
        );
        #attaching resume here
        $resumePath = !empty($relation['resume_path'])?$relation['resume_path']:'';
        $fileName   = !empty($relation['resume_original_name'])?$relation['resume_original_name']:'';
        #file encode
        if(!empty($resumePath)){
            $contents   = base64_encode(file_get_contents($resumePath));
        }
        #form the attachment array here
        if(!empty($contents)){
        $resumeAry = array(
                "__metadata"    => array("type" => "SFOData.Attachment"),
                "module"        => "RECRUITING",
                "moduleCategory"=> "ATTACHMENTS",
                "fileContent"   => $contents,
                "fileName"      => $fileName
                );
        $data['resume'] = $resumeAry;
        }
        $endPoint = 'Candidate?$format=JSON';
        return $this->doPost($data, $endPoint); 
    }
    
    public function processHcmJobReferral($jobDetails, $userDetails, $relation, $companyCode){
        
        #get company details by code
        $compData   = $this->getCompanyDetailsByCode($companyCode);   
        $companyId  = !empty($compData[0]->id)?$compData[0]->id:'';
        $hcmData    = $this->getCompanyHcmJobbyIdByCompanyID($companyId=187);
        $company_hcm_job_id = !empty($hcmData[0]->company_hcm_jobs_id)?$hcmData[0]->company_hcm_jobs_id:'';
        
        //$company_hcm_job_id      = 1;
        $companyJobDetail        = $this->getCompanyHcmJobbyId($company_hcm_job_id);
        $JobDetails              = $this->getJobDetail($companyJobDetail->hcm_jobs_id);
        $companyJobConfigDetails = $this->getCompanyJobConfigs($JobDetails->hcm_id, $companyJobDetail->company_id);
        $requestParams           = $this->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);
        $this->requestParams     = $requestParams;
        
        #get job requisition id, success factors candidate id and user node Id from job&user details
        $jobReqId       = !empty($jobDetails['requistion_id'])?$jobDetails['requistion_id']:'';
        $userNodeId     = !empty($userDetails['node_id'])?$userDetails['node_id']:'';
        $sfCandidateId  = !empty($userDetails['sf_candidate_id'])?$userDetails['sf_candidate_id']:'';
        if(!empty($jobReqId)){
            #create candidate here
            if(empty($sfCandidateId)){
                $candidateAry   = $this->createCandidate($userDetails, $relation);
                \Log::info("SF create Candidate hit :". print_r($candidateAry)); 
                if(isset($candidateAry['d']) && isset($candidateAry['d']['candidateId'])){
                    $candidateId = $candidateAry['d']['candidateId'];
                    $this->addSFCandidateIdToUserNode($userNodeId, $candidateId);
                }
            } else {
                $candidateId = $sfCandidateId;
            }  
            #tagging candidates here 
            if(!empty($candidateId)){
                #tagging candidate to job requisition here
                $jobReqAry   = $this->jobReqForwardCandidates($jobReqId, $candidateId);
                \Log::info("SF job Req Forward Candidates hit :". print_r($jobReqAry));
                #attaching resume to candidate here
                $attachmentAry   = $this->addAttachment($userDetails, $relation, $candidateId);
                \Log::info("SF add Attachment hit :". print_r($attachmentAry)); 
            }
        }    
    }
    
    public function getImportRelationDetailsByEmail($refBy='', $emailId=''){
        $refBy   = $this->appEncodeDecode->filterString(strtolower($refBy));
        $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
        $queryString = "match (u:User:Mintmesh)-[r:IMPORTED]-(c:User) where u.emailid='".$refBy."' and c.emailid='".$emailId."' return r order by r.created_at desc limit 1";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()){
            $result[0][0]->fullname = (empty($result[0][0]->fullname)?$result[0][0]->firstname." ".$result[0][0]->lastname:$result[0][0]->fullname);
            return $result[0][0] ;
        } else {
            return 0 ;
        }
    }
    
    public function getCompanyDetailsByCode($companyCode=0){    
        return DB::table('company')
               ->select('logo','id','name','employees_no')
               ->where('code', '=', $companyCode)->get();
    }
    
    public function getCompanyHcmJobbyIdByCompanyID($companyId=0){    
        return DB::table('company_hcm_jobs')
               ->select('company_hcm_jobs_id')
               ->where('company_id', '=', $companyId)->get();
    }
    
    
}

