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
use Company_Resumes as CR;
use User as U;
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
                
                    $param = array();
                    $param['doc_id']    = $docData->id;
                    $param['tenant_id'] = $docData->company_id;
                    $param['email_id']  = !empty($result_check['email']) ? $result_check['email'] : '';
                    $param['name']      = !empty($result_check['name']) ? $result_check['name'] : '';
                    $param['phone']     = !empty($result_check['phone']) ? $result_check['phone'] : '';
                    #create Unsolicited Referrals here
                    $unsolicitedAry = $this->createUnsolicitedReferrals($param);
                }
            }
        }
        return TRUE;
    }
    
    public function createUnsolicitedReferrals($param) {
        
        $return   = $neoInput = $user = array();
        $docId    = !empty($param['doc_id']) ? $param['doc_id'] : 0;
        $emailId  = !empty($param['email_id']) ? $param['email_id'] : '';
        
        $companyResumes = $this->getCompanyResumesDetailsByDocId($docId);
        
        if(!empty($companyResumes) && !empty($emailId)){
            #get the user details with emailid
            $userNode       = $this->getUserNodeByEmailId($emailId);   
            $companyCode    = $companyResumes->code;
            $referred_for   = $companyResumes->emailid;
            $statusPending  = Config::get('constants.REFERRALS.STATUSES.PENDING');
            #check if the user node already exists or not
            if(empty($userNode)){
                #form the user data
                $name = !empty($param['name']) ? $param['name'] : '';
                $user['firstname']  = $name;
                $user['fullname']   = $name;
                $user['emailid']    = $emailId;
                $user['phone']      = !empty($param['phone']) ? $param['phone'] : ''; 
                #creating User Node in db
                $this->createUserNode($user);
            }
            #form the referral details input here
            $neoInput['referral']               = $emailId;
            $neoInput['referred_by']            = $referred_for;
            $neoInput['resume_path']            = $companyResumes->file_source;                
            $neoInput['resume_original_name']   = $companyResumes->file_original_name;
            $neoInput['created_at']             = gmdate('Y-m-d H:i:s'); 
            $neoInput['relation_count']         = '1';
            $neoInput['status']                 = $statusPending;
            $neoInput['completed_status']       = $statusPending;
            $neoInput['awaiting_action_status'] = $statusPending;
            $neoInput['awaiting_action_by']     = $referred_for;
            $neoInput['one_way_status']         = Config::get('constants.REFERRALS.STATUSES.UNSOLICITED');
            #create got referred relation here
            $gotReferredId = $this->createUnsolicitedReferralsRelation($companyCode, $emailId, $neoInput);
            $this->updateCompanyResumes($docId, $gotReferredId);
        }
        return TRUE;
    }
    
    public function getUserNodeByEmailId($emailID='') {
        
        $return = FALSE;
        if($emailID){
            $queryString = "MATCH (u:User) WHERE u.emailid='".$emailID."' RETURN u";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
        }
        return $return;
    }
    
    public function createUserNode($input) {
        
        $return = FALSE;
        if(!empty($input['emailid'])){
            $queryString = "CREATE (n:User {";
            foreach ($input as $k => $v) {
                if ($k == 'emailid')
                    $v = strtolower($v);
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.=" }) return n";            
            $query = new CypherQuery($this->client, $queryString);
            $return = $query->getResultSet();
        }
        return $return;
    }
    
    public function createUnsolicitedReferralsRelation($companyCode='', $emailid='', $relationAttrs=array()) {
        
        $return = FALSE;
        if(!empty($companyCode) && !empty($emailid)){
            $relation = Config::get('constants.REFERRALS.GOT_REFERRED');
            $queryString = "MATCH (c:Company{companyCode:'".$companyCode."'})<-[:COMPANY_UNSOLICITED]-(n:Unsolicited),(u:User{emailid:'".$emailid."'}) ";
            $queryString.=" create (u)-[r:" . $relation;
            if (!empty($relationAttrs)) {
                $queryString.="{";
                foreach ($relationAttrs as $k => $v) {
                    $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                }
                $queryString = rtrim($queryString, ",");
                $queryString.="}";
            }
            $queryString.="]->(n) return r";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0]->getID();
            }
        }
        return $return;
    }
    
    public function getCompanyResumesDetailsByDocId($documentId=0){
        $return = FALSE;
        if(!empty($documentId)){
            $sql = "select r.id, r.file_source, r.file_original_name, c.code, u.emailid from company_resumes r
                    left join company c on c.id=r.company_id
                    left join users u on u.id=r.created_by
                    where r.id='".$documentId."' " ;
            $result = DB::Select($sql);
            if(!empty($result[0]))
                $return = $result[0];
        }
        return $return;
    }
    
    public function getCompanyResumesByDocId($documentId=0) {
        $return = FALSE;
        if($documentId){
            $result = CR::where ('id',$documentId)->get();
            if(isset($result[0]))
                $return = $result[0];
        }
        return $return;
    }
    
    public function updateCompanyResumes($documentId=0, $gotReferredId=0)
    {   
        $results   = FALSE;
        $updatedAt = gmdate("Y-m-d H:i:s");
        $companyResumes = array(
                        "status"            => 2,
                        "got_referred_id"   => $gotReferredId,
                        "updated_at"        => $updatedAt
                    );
        if($documentId){
            $results = CR::where ('id',$documentId)->update($companyResumes); 
        }
       return $results;
    }
     
}
