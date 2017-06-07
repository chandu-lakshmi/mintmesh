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

class AIManager {

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

    public function getResumesByStatus($status) {
          $query = "SELECT * FROM company_resumes WHERE status = '" . $status . "'";
          $result = DB::select($query);
          return $result;
        }
        
    public function getResumesUpdateStatus() {
        
        //Set Time limit to execute
        set_time_limit(0);
        
        //Get Status from company_resumes
        $result = $this->getResumesByStatus($status = 1);
        
        //API call from API for Parsed Resume
        $endPoint = "http://54.68.58.181/resumematcher/get_parsed_resume";
        
        //Username and Passwords of AI server
        $username = Config::get('constants.AIusername');//"admin";
        $password = Config::get('constants.AIpassword');//"Aev54I0Av13bhCxM";
        
        $data = array();
        
        if ($result) {
            foreach ($result as $docData) {
                $pos = strpos($docData->file_original_name, ".");
                $data['tenant_id'] = $docData->company_id;
                $data['doc_id'] = $docData->id . "." . substr($docData->file_original_name, $pos + 1);

                $post = json_encode($data);
                $process = curl_init($endPoint);
                curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($process, CURLOPT_HEADER, 0);
                curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
                curl_setopt($process, CURLOPT_TIMEOUT, 30);
                curl_setopt($process, CURLOPT_POST, 1);
                curl_setopt($process, CURLOPT_POSTFIELDS, $post);
                curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
                $return = curl_exec($process);
                curl_close($process);
                $result_check = json_decode($return, TRUE);
                \Log::info("Parser Resumes" . print_r($result_check, true));
               
                if (!empty($result_check)) {
                    
                    $sql = "UPDATE company_resumes SET status = '2',updated_at = '" . NOW() . "' WHERE company_id ='" . $data['tenant_id'] . "'";
                    DB::Statement($sql);
                }
            }
        }
        return TRUE;
    }
}
