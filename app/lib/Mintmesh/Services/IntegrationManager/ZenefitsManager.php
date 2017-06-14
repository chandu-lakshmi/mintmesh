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
use DB,
    Config,
    Queue,
    Lang;

class ZenefitsManager extends IntegrationManager {

    protected $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    protected $userRepository, $guzzleClient, $enterpriseRepository;
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

        $integrationManager = new IntegrationManager();
        $companyJobDetail = $integrationManager->getCompanyHcmJobbyId($company_hcm_job_id);
        #scheduler enabled or disabled here 
        if (!empty($companyJobDetail) && $companyJobDetail->status == '1') {
            $JobDetails = $integrationManager->getJobDetail($companyJobDetail->hcm_jobs_id);
            $companyJobConfigDetails = $integrationManager->getCompanyJobConfigs($JobDetails->hcm_id, $companyJobDetail->company_id);
            $requestParams = $integrationManager->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);
            $this->requestParams = $requestParams;
            $expirationDate = $requestParams['created_in'];
            $toDay = strtotime("d-m-Y");
            $difference = abs($toDay - $expirationDate);
            if ($difference <= 29) {
                $return = $integrationManager->doRequest($requestParams);
            } else {
                $this->getRefreshToken($requestParams['refresh_token'], $companyJobDetail->company_id);
                $requestParams1 = $integrationManager->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);
                $this->requestParams = $requestParams1;
                $return = $integrationManager->doRequest($requestParams);
            }
            $this->processResponseData($return, $companyJobDetail->hcm_jobs_id, $companyJobDetail->company_id);
            $integrationManager->updateLastProcessedTime($company_hcm_job_id, $companyJobDetail);
        }
        return TRUE;
    }

    public function getRefreshToken($refresh_token, $ccode) {
        $data = array();
        $endPoint = "https://secure.zenefits.com/oauth2/token/";

        $data['grant_type'] = "refresh_token";
        $data['refresh_token'] = $refresh_token;
        $data['client_id'] = Config::get('constants.Zenefits_client_id');
        $data['client_secret'] = Config::get('constants.Zenefits_client_secret');
       
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $endPoint);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false); //disable SSL check
        $json_response = curl_exec($curl_handle);
        curl_close($curl_handle);
        $response = json_decode($json_response);
        if ($response) {
            $response_zenefits = $this->updateZenefitsAccessToken($response, $ccode);
            return \Response::json($response_zenefits);
        } else {
            // returning validation failure
            return \Response::json($validation);
        }
    }

    public function updateZenefitsAccessToken($zenefitsRefresstoken, $ccode) {
        $response = $zenefitsRefresstoken;
        $hcmAry = array();
        $companyId = $ccode;
        $hcmId = 2; //!empty($input['hcm_id'])?$input['hcm_id']:'';
        $hcmAccesToken = !empty($response->access_token) ? ($response->token_type . ' ' . $response->access_token) : '';
        $hcmReferToken = !empty($response->refresh_token) ? $response->refresh_token : '';
        $hcmExpToken = !empty($response->expires_in) ? $response->expires_in : '';
        $hcmAry[1]['name'] = 'Authorization';
        $hcmAry[1]['value'] = $hcmAccesToken;
        $hcmAry[2]['name'] = 'refresh_token';
        $hcmAry[2]['value'] = $hcmReferToken;
        $hcmAry[3]['name'] = 'created_in';
        $hcmAry[3]['value'] = date("d-m-Y");

        foreach ($hcmAry as $key => $value) {
           $name = $value['name'];
             $sql = "UPDATE hcm_config_properties SET config_name = '".$value['name'] ."',config_value = '". $value['value'] . "' WHERE company_id ='" . $companyId ."' AND hcm_id ='" . $hcmId ."' AND config_name = '" . $name ."'";
             DB::Statement($sql); 
        }
       
       
    }
  
    public function processResponseData($responseBody, $jobId, $companyId) {

        $array = json_decode($responseBody, TRUE);
        $empInfo = $return = array();
        if (isset($array['data'])) {
            $return = $array['data']['data'];
            foreach ($return as $key => $value) {
                $workEmail = !empty($value['work_email']) ? $value['work_email'] : '';
                $status = !empty($value['status']) ? $value['status'] : '';
                if ($workEmail) {
                    if (($status != "requested") || ($status != "setup") || ($status != "")) {
                        $empInfo[$key]['first_name'] = !empty($value['first_name']) ? $value['first_name'] : '';
                        $empInfo[$key]['last_name'] = !empty($value['last_name']) ? $value['last_name'] : '';
                        $empInfo[$key]['work_email'] = !empty($value['work_email']) ? $value['work_email'] : '';
                        $empInfo[$key]['work_phone'] = !empty($value['work_phone']) ? $value['work_phone'] : '';
                        $empInfo[$key]['id'] = !empty($value['id']) ? $value['id'] : '';
                        if ($status == "active") {
                            $empInfo[$key]['status'] = "Active";
                        } elseif ($status == "deleted" || $status == "terminated") {
                            $empInfo[$key]['status'] = "Separated";
                        } elseif ($status == "leave_of_absence") {
                            $empInfo[$key]['status'] = "Inactive";
                        }
                    }
                }
            }
            $this->insertEmpinfoIntoContacts($empInfo, $companyId);
        }
        return true;
    }

    public function insertEmpinfoIntoContacts($empInfo, $companyId) {

        $input = array();
        $integrationManager = new IntegrationManager();
        #get company details here
        $companyDetails = $integrationManager->getCompanyDetails($companyId);
        $companyDetails = !empty($companyDetails[0]) ? $companyDetails[0] : '';
        $companyCode = $companyDetails->code;
        $userId = $companyDetails->created_by;
        $userData = $this->getUserByUserId($userId);
        $this->user = !empty($userData[0]) ? $userData[0] : '';
        $userEmailid = $this->user->emailid;
        $bucketId = "1";


        foreach ($empInfo as $key => $value) {
            $query = "Select * from contacts where emailid = '" . $value['work_email'] . "' AND company_id ='" . $companyId . "'";
            $existEailidAndCompanyId = DB::select($query);

            if (count($existEailidAndCompanyId) == 0) {
                $status = !empty($value['status']) ? $value['status'] : 'Inactive';
                $first_name = !empty($value['first_name']) ? $value['first_name'] : '';
                $last_name = !empty($value['last_name']) ? $value['last_name'] : '';
                $work_email = !empty($value['work_email']) ? $value['work_email'] : '';
                $work_phone = !empty($value['work_phone']) ? $value['work_phone'] : '';
                $id = !empty($value['id']) ? $value['id'] : '';
                $query = "INSERT INTO contacts (firstname, lastname, emailid, phone, company_id, import_file_id, employeeid, status, updated_by,created_by, ip_address)
                            VALUES ('" . $first_name . "', '" . $last_name . "', '" . $work_email . "','" . $work_phone . "', '" . $companyId . "', '0', '" . $id . "', '" . $status . "', '0', '0', '0')";
                DB::Statement($query);
                #Assign Bucket
                $lastInsertId = DB::getPdo()->lastInsertId();

                $bucket_insert = "INSERT INTO buckets_contacts (bucket_id, contact_id, company_id) VALUES ('" . $bucketId . "','" . $lastInsertId . "','" . $companyId . "')";
                DB::Statement($bucket_insert);
                #create contact  
                $pushData = array();
                $pushData['firstname'] = $value['first_name'];
                $pushData['lastname'] = $value['last_name'];
                $pushData['emailid'] = $this->appEncodeDecode->filterString(strtolower($value['work_email']));
                $pushData['contact_number'] = !empty($value['work_phone']) ? $value['work_phone'] : '';
                $pushData['other_id'] = !empty($value['id']) ? $value['id'] : '';
                $pushData['status'] = !empty($value['status']) ? $value['status'] : 'unknown';
                $pushData['bucket_id'] = $bucketId;
                $pushData['company_code'] = $companyCode;
                $pushData['loggedin_emailid'] = $userEmailid;
                Queue::push('Mintmesh\Services\Queues\CreateEnterpriseContactsQueue', $pushData, 'IMPORT');
            } else if (count($existEailidAndCompanyId) > 0) {
                $status = !empty($value['status']) ? $value['status'] : 'Inactive';
                $first_name = !empty($value['first_name']) ? $value['first_name'] : '';
                $last_name = !empty($value['last_name']) ? $value['last_name'] : '';
                $work_phone = !empty($value['work_phone']) ? $value['work_phone'] : '';
                $query = "UPDATE contacts SET firstname = '" . $first_name . "', lastname = '" . $last_name . "', phone = '" . $work_phone . "', status= '" . $status . "' WHERE id ='" . $existEailidAndCompanyId[0]->id . "'";
                DB::Statement($query);
            }

            \Log::info("Successfully");
        }
        return true;
    }

   public function getUserByUserId($userId = 0) {
        return DB::table('users')
                        ->select('firstname', 'emailid', 'status')
                        ->where('id', '=', $userId)->get();
    }

}
