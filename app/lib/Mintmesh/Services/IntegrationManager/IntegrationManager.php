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
    
   const SUCCESS_RESPONSE_CODE = 200;
    const API_END_POINT = 'API_END_POINT';
    const DCNAME = 'DCNAME';
    const USERNAME = 'USERNAME';
    const PASSWORD = 'PASSWORD';
    const AuthorizationHeader = 'Authorization';
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

    protected function getCompanyHcmJobbyId($company_hcm_job_id) {
        // get the job data from company_hcm_jobs
        $result = DB::table('company_hcm_jobs')
                ->select('hcm_jobs_id','company_id','frequency','last_processed_at','next_process_at','status')
                ->where('company_hcm_jobs_id', '=', $company_hcm_job_id)->first();

        return $result;

    }

    protected function getJobDetail($jobId) {
        // getting the job configs from hcm_jobs table
        $result = DB::table('hcm_jobs')
                ->select('hcm_id','job_name','job_endpoint','job_params','job_additional_params')
                ->where('hcm_jobs_id', '=', $jobId)
                ->where('status', '=', '1')->first();
        return $result;
    }

    protected function getCompanyJobConfigs($hcmId, $companyId) {
        $result = DB::table('hcm_config_properties')
                ->select('config_name','config_value')
                ->where('hcm_id', '=', $hcmId)
                ->where('company_id', '=', $companyId)->get();
        return $result;
    }
    
    protected function getJobMappingFields($hcmJobId, $companyId) {
        $result = DB::table('company_hcm_jobs_fields_mapping')
                ->select('source_key','destination_key')
                ->where('company_hcm_jobs_id', '=', $hcmJobId)
                ->where('company_id', '=', $companyId)->get();
        return $result;
    }
    
    protected function getCompanyDetails($id) {
            return DB::table('company')
                ->where('id', '=', $id)->get(); 
    }
    protected function getUserDetails($id) {
            return DB::table('users')
                ->where('id', '=', $id)->get(); 
    }
    
    protected function getNeoUserByEmailId($userEmailId) {
            $return = array();
            $queryString = "match (u:User) where u.emailid='".$userEmailId."' return u";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();         
            if (isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
           return $return;
        }

    protected function composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails) {
        // composing request params and configs
        $returnRequestData = array();
        // composing endpoing
        foreach($companyJobConfigDetails as $dataValue) {
            $returnRequestData[$dataValue->config_name] = $dataValue->config_value;
        }

        // converting last processed datetime to UTC timezone
         $lastprocessedDate = gmdate("Y-m-d\TH:i:s\Z", strtotime($companyJobDetail->last_processed_at));

        if(array_key_exists(self::DCNAME, $returnRequestData)){
        $returnRequestData[self::API_END_POINT] = $returnRequestData[self::DCNAME] . $JobDetails->job_endpoint . $JobDetails->job_additional_params;
        $returnRequestData[self::API_END_POINT] .= '&$select=' . $JobDetails->job_params;
        $returnRequestData[self::API_END_POINT] .= '&$filter=lastModifiedDateTime ge datetimeoffset\'' . $lastprocessedDate . '\' and internalStatus eq \'Approved\'';
        }else{
            $returnRequestData[self::API_END_POINT] = $JobDetails->job_endpoint;
        }
        return $returnRequestData;
    }

    
    protected function updateLastProcessedTime($company_hcm_job_id, $companyJobDetail) {
        $last_processed_at = $companyJobDetail->next_process_at;
        $next_processed_at = strtotime($companyJobDetail->next_process_at) + $companyJobDetail->frequency;
        $next_processed_at = date("Y-m-d H:i:s", $next_processed_at);

        $sql = 'UPDATE company_hcm_jobs SET last_processed_at = \''.$last_processed_at.'\',next_process_at = \''.$next_processed_at.'\' WHERE company_hcm_jobs_id ='. $company_hcm_job_id ;
        DB::Statement($sql);
        
        \Log::info("SF JobId $company_hcm_job_id for Company $companyJobDetail->company_id Processed at $companyJobDetail->last_processed_at Successfully");
        return true;
    }
    
    protected function doRequest($requestParams) {
        // do request to hcm endpoints
       $endPoint = $requestParams[self::API_END_POINT];
        $request = $this->guzzleClient->get($endPoint);
        if (array_key_exists(self::USERNAME, $requestParams)) {
            
            $username = $requestParams[self::USERNAME];
            $password = $requestParams[self::PASSWORD];
            \Log::info("SF Endpoint hit : $endPoint");
            $request->setAuth($username, $password);
        } else if (array_key_exists(self::AuthorizationHeader, $requestParams)) {
            $accesToken = $requestParams[self::AuthorizationHeader];
            \Log::info("Zenefits Endpoint hit : $endPoint");
            $request->setHeader(self::AuthorizationHeader, $accesToken);
        }

        try {
            $response = $request->send();
            
            if ($response->isSuccessful() && $response->getStatusCode() == self::SUCCESS_RESPONSE_CODE) {
                return $response->getBody();
            } else {
                \Log::info("Error while getting response : $response->getInfo()");
            }
        } catch (ClientErrorResponseException $exception) {
         
            $responseBody = $exception->getResponse()->getBody(true);
        }

    }
    
    protected function doPost($data, $endPoint) {
        
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
    
    
}

