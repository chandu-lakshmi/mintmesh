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
use DB, Config, Queue, Lang;

class ZenefitsManager extends IntegrationManager {

    protected $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    protected $userRepository, $guzzleClient;
    public $requestParams = array();

    const SUCCESS_RESPONSE_CODE = 200;
    const API_END_POINT = 'API_END_POINT';
    const AuthorizationHeader = 'Authorization';
    const API_LOCAL_URL = 'https://api.zenefits.com/core/';

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
    
    public function insertContacts($company_hcm_job_id) {
        
        $integrationManager      = new IntegrationManager();
        $companyJobDetail        = $integrationManager->getCompanyHcmJobbyId($company_hcm_job_id);
        #scheduler enabled or disabled here 
        if(!empty($companyJobDetail) && $companyJobDetail->status == '1'){
            $JobDetails              = $integrationManager->getJobDetail($companyJobDetail->hcm_jobs_id);
            $companyJobConfigDetails = $integrationManager->getCompanyJobConfigs($JobDetails->hcm_id, $companyJobDetail->company_id);
            $requestParams           = $integrationManager->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);
            $this->requestParams     = $requestParams;

            $return = $integrationManager->doRequest($requestParams);
            $this->processResponseData($return, $companyJobDetail->hcm_jobs_id, $companyJobDetail->company_id);
            $integrationManager->updateLastProcessedTime($company_hcm_job_id, $companyJobDetail);
        }  
        return TRUE;
    }


    public function processResponseData($responseBody, $jobId, $companyId) {

        $array   = json_decode($responseBody, TRUE);
        $empInfo = $return = array();
        if (isset($array['data'])) {
            $return = $array['data']['data'];
            foreach ($return as $key => $value) {
                
                $workEmail  = !empty($value['work_email'])?$value['work_email']:'';
                $status     = !empty($value['status'])?$value['status']:'';
                
                if($workEmail){
                    $empInfo[$key]['first_name'] = !empty($value['first_name'])?$value['first_name']:'';
                    $empInfo[$key]['last_name']  = !empty($value['last_name'])?$value['last_name']:'';
                    $empInfo[$key]['work_email'] = !empty($value['work_email'])?$value['work_email']:'';
                    $empInfo[$key]['work_phone'] = !empty($value['work_phone'])?$value['work_phone']:'';
                    $empInfo[$key]['id']         = !empty($value['id'])?$value['id']:'';
                    if ($status == "active") {
                        $empInfo[$key]['status'] = "Active";
                    } elseif ($status == "deleted" || $status == "terminated" || $status == "leave_of_absence" || $status == "requested" || $status == "setup" || $status == "") {
                        $empInfo[$key]['status'] = "seaparated";
                    }
                }
            }
            $this->insertEmpinfoIntoContacts($empInfo, $companyId);
        }
        return true;
    }

    public function insertEmpinfoIntoContacts($empInfo, $companyId) {
       
        $input = array();
        #get company details here
        $companyDetails = $this->getCompanyDetails($companyId);
        $companyDetails = !empty($companyDetails[0])?$companyDetails[0]:'';
        $companyCode    = $companyDetails->code;
        $userId         = $companyDetails->created_by;
        $userData       = $this->getUserByUserId($userId);
        $this->user     = !empty($userData[0])?$userData[0]:'';
        $userEmailid    = $this->user->emailid;
        $bucketId       = "1";
        
        foreach ($empInfo as $key => $value) {
            
            $query = "Select * from contacts where emailid = '" . $value['work_email'] . "' AND company_id ='". $companyId ."'";
            $existEailidAndCompanyId = DB::select($query);

            if (count($existEailidAndCompanyId) == 0) {
                
                $query = "INSERT INTO contacts (firstname, lastname, emailid, phone, company_id, import_file_id, employeeid, status, updated_by,created_by, ip_address)
                            VALUES ('" . $value['first_name'] . "', '" . $value['last_name'] . "', '" . $value['work_email'] . "','" . $value['work_phone'] . "', '" . $companyId . "', '0', '" . $value['id'] . "', '" . $value['status'] . "', '0', '0', '0')";
                DB::Statement($query);
                #Assign Bucket
                $lastInsertId = DB::getPdo()->lastInsertId();
                
                $bucket_insert = "INSERT INTO buckets_contacts (bucket_id, contact_id, company_id) VALUES ('" . $bucketId . "','" . $lastInsertId . "','" . $companyId . "')";
                DB::Statement($bucket_insert);
                #create contact  
                $pushData = array();
                $pushData['firstname']          = $value['first_name'];;
                $pushData['lastname']           = $value['last_name'];
                $pushData['emailid']            = $this->appEncodeDecode->filterString(strtolower($value['work_email']));
                $pushData['contact_number']     = !empty($value['work_phone']) ? $value['work_phone'] : '';;
                $pushData['other_id']           = !empty($value['id']) ? $value['id'] : '';
                $pushData['status']             = !empty($value['status']) ? $value['status'] : 'unknown';;
                $pushData['bucket_id']          = $bucketId;
                $pushData['company_code']       = $companyCode;
                $pushData['loggedin_emailid']   = $userEmailid;
                Queue::push('Mintmesh\Services\Queues\CreateEnterpriseContactsQueue', $pushData, 'IMPORT');
                        
            } else if (count($existEailidAndCompanyId) > 0) {
                $query = "UPDATE contacts SET firstname = '".$value['first_name']."', lastname = '".$value['last_name']."', phone = '".$value['work_phone']."', status= '".$value['status']."' WHERE id ='". $existEailidAndCompanyId[0]->id."'";
                DB::Statement($query);
            }

           \Log::info("Successfully".print_r($value,1));
        }
        return true;
    }
    
    public function getCompanyDetails($id) {
        return DB::table('company')
            ->where('id', '=', $id)->get(); 
    }
    
    public function getUserByUserId($userId=0){    
        return DB::table('users')
            ->select('firstname','emailid','status')
            ->where('id', '=', $userId)->get();
    }      

}
