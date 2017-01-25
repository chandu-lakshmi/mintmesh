<?php
namespace Mintmesh\Services\IntegrationManager;

use Mintmesh\Services\APPEncode\APPEncode;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Guzzle\Http\Client as guzzleClient;
use DB,Config,Queue,Lang;

class IntegrationManager {
    protected  $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    protected  $userRepository, $guzzleClient;
    const SUCCESS_RESPONSE_CODE = 200;
    const API_END_POINT = 'API_END_POINT';
    const DCNAME = 'DCNAME';
    const USERNAME = 'USERNAME';
    const PASSWORD = 'PASSWORD';

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
                ->select('hcm_jobs_id','company_id','frequency','last_processed_at','next_process_at')
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
        $lastprocessedDate = gmdate("Y-m-d\TH:i:s", strtotime($companyJobDetail->last_processed_at));

        $returnRequestData[self::API_END_POINT]  = $returnRequestData[self::DCNAME].$JobDetails->job_endpoint.$JobDetails->job_additional_params;
        $returnRequestData[self::API_END_POINT] .= '&$select='.$JobDetails->job_params;
        $returnRequestData[self::API_END_POINT] .= '&$filter=lastModifiedDateTime ge datetime\''.$lastprocessedDate.'\'';

        return $returnRequestData;
    }

    public function intiateRequest($company_hcm_job_id) {

        $companyJobDetail        = $this->getCompanyHcmJobbyId($company_hcm_job_id);
        $JobDetails              = $this->getJobDetail($companyJobDetail->hcm_jobs_id);
        $companyJobConfigDetails = $this->getCompanyJobConfigs($JobDetails->hcm_id, $companyJobDetail->company_id);
        $requestParams           = $this->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);

        // do request to hcm endpoints
        $endPoint = $requestParams[self::API_END_POINT];
        //$endPoint = 'https://apisalesdemo8.successfactors.com:443/odata/v2/JobRequisition?$format=json&$select=jobCode,function,location,industry,jobGrade,positionNumber,jobReqId,numberOpenings,classificationType,currency&$filter=lastModifiedDateTime ge datetime\'2016-11-22T17:19:28\'';
        
        $username = $requestParams[self::USERNAME];
        $password = $requestParams[self::PASSWORD];
        
        $request = $this->guzzleClient->get($endPoint);
        
        $request->setAuth($username, $password);
        $response = $request->send();


        if($response->isSuccessful() && $response->getStatusCode() == self::SUCCESS_RESPONSE_CODE) {
            $this->processResponseData($response->getBody(), $companyJobDetail->hcm_jobs_id, $companyJobDetail->company_id);
            $this->updateLastProcessedTime($company_hcm_job_id, $companyJobDetail);
            exit;
        } else {
            echo $response->getInfo();
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
         
        $hcmJobId = $jobId;
        $companyId = $companyId;
        $bucketId = 1;
        $fromId = $postId = 0;
        $companyDetails = $this->getCompanyDetails($companyId);
        $companyDetails = $companyDetails[0];
        $companyName    = $companyDetails->name;//'company68';
        $companyCode    = $companyDetails->code;//510632;
        $userDetails    = $this->getUserDetails($companyDetails->created_by);
        $userDetails    = $userDetails[0];
        $userEmailId    = !empty($userDetails->emailid)?$userDetails->emailid:'';//'gopi68@mintmesh.com';
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

                $neoInput =  $relationAttrs  = $postCompanyrelationAttrs = $neoCompanyBucketContacts = array();
                $JobMappingFields = $this->getJobMappingFields($hcmJobId, $companyId); 
                foreach ($JobMappingFields as $field){

                    $neoInput[$field->destination_key] =  !empty($row[$field->source_key])?$row[$field->source_key]:'';

                }
                $neoInput['service_scope']      = "find_candidate";
                $neoInput['service_from_web']   = 1;
                if(empty($neoInput['employment_type'])) {
                    $neoInput['employment_type']    = 'PERMANENT';
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
                $neoInput['post_type']          = 'external';
                $relationAttrs['created_at']    = date("Y-m-d H:i:s");
                $relationAttrs['company_name']  = $companyName;
                $relationAttrs['company_code']  = $companyCode;
                //print_r($neoInput).exit;

                $createdPost = $this->createPostAndUserRelation($fromId, $neoInput, $relationAttrs);
                if (isset($createdPost[0]) && isset($createdPost[0][0])) {
                    $postId = $createdPost[0][0]->getID();
                } else {
                    $postId = 0;
                }
                #map post and company
                $postCompanyrelationAttrs['created_at']     = gmdate("Y-m-d H:i:s");
                $postCompanyrelationAttrs['user_emailid']   = $userEmailId;
                if (!empty($relationAttrs['company_code'])) {
                    $createdrelation = $this->createPostAndCompanyRelation($postId, $relationAttrs['company_code'], $postCompanyrelationAttrs);
                }
                $neoCompanyBucketContacts = $this->getImportContactsList($params);
                foreach ($neoCompanyBucketContacts['Contacts_list'] as $contact => $contacts) {
                    $pushData = array();
                    if($contacts->status != 'Separated'){

                        #creating included Relation between Post and Contacts 
                        $pushData['postId']         = $postId;
                        $pushData['bucket_id']      = $params['bucket_id'];
                        $pushData['contact_emailid']= $contacts->emailid;
                        $pushData['company_code']   = $params['company_code'];
                        $pushData['user_emailid']   = $userEmailId;
                        $pushData['notification_msg'] = $notificationMsg;
                        Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
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

    public function updateLastProcessedTime($company_hcm_job_id, $companyJobDetail) {
        $last_processed_at = $companyJobDetail->next_process_at;
        $next_processed_at = strtotime($companyJobDetail->next_process_at) + $companyJobDetail->frequency;
        $next_processed_at = date("Y-m-d H:i:s", $next_processed_at);

        $sql = 'UPDATE company_hcm_jobs SET last_processed_at = \''.$last_processed_at.'\',next_process_at = \''.$next_processed_at.'\' WHERE company_hcm_jobs_id ='. $company_hcm_job_id ;
        DB::Statement($sql);
        
        \Log::info("SF JobId $company_hcm_job_id for Company $companyJobDetail->company_id Processed at $companyJobDetail->last_processed_at Successfully");
        return true;
    }

}

