<?php

namespace Mintmesh\Repositories\API\Enterprise;

use NeoEnterpriseUser,
    NeoCompany,
    Config;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode;

class NeoeloquentEnterpriseRepository extends BaseRepository implements NeoEnterpriseRepository {

    protected $neoEnterpriseUser, $neoCompany, $db_user, $db_pwd, $appEncodeDecode, $db_host, $db_port;

    const LIMIT = 10;

    public function __construct(NeoEnterpriseUser $neoEnterpriseUser, NeoCompany $neoCompany, APPEncode $appEncodeDecode) {
        parent::__construct($neoEnterpriseUser, $neoCompany);
        $this->neoEnterpriseUser = $neoEnterpriseUser;
        $this->neoCompany = $neoCompany;
        $this->db_user = Config::get('database.connections.neo4j.username');
        $this->db_pwd = Config::get('database.connections.neo4j.password');
        $this->db_host = Config::get('database.connections.neo4j.host');
        $this->db_port = Config::get('database.connections.neo4j.port');
        $this->client = new NeoClient($this->db_host, $this->db_port);
        $this->appEncodeDecode = $appEncodeDecode;
        $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
    }

    /*
     * Create new enterpise user node in neo4j
     */

    public function createEnterpriseUser($input) {
        $queryString = "CREATE (n:User";
        if (!empty($input)) {
            $queryString = "CREATE (n:User:Mintmesh";
            $queryString.="{";
            foreach ($input as $k => $v) {
                if ($k == 'emailid')
                    $v = strtolower($v);
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=") return n";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
        //return $this->neoUser->create($input);
    }

    public function getNodeByEmailId($email = '') {
        return $this->neoEnterpriseUser->whereEmailid($this->appEncodeDecode->filterString(strtolower($email)))->first();
    }

//        public function updateEnterpriseUser($input)
//        {
//         
//            $queryString = "MATCH (n:User) where not n:User:Mintmesh and n.emailid='".$input['emailid']."'
//                                SET n:Mintmesh, n.is_enterprise=1, n.fullname='".$input['fullname']."'
//                                RETURN n";
//            $query = new CypherQuery($this->client, $queryString);
//            return $result = $query->getResultSet();        
//        }

    public function createCompany($input) {
        $queryString = "CREATE (n:Company";
        if (!empty($input)) {
            $queryString.="{";
            foreach ($input as $k => $v) {
                if ($k == 'company')
                    $v = strtolower($v);
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=") set n.created_at='" . gmdate("Y-m-d H:i:s") . "' return n";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
        //return $this->neoUser->create($input);
    }

    public function updateCompanyLabel($companyCode = "", $name = "", $website = "", $logo = "", $images = "",$description="",$industry="",$file="",$file_org_name="") {
        if (!empty($name) || !empty($website) || !empty($size) || !empty($logo) || !empty($images) || !empty($industry)) { 
                $images = $this->appEncodeDecode->filterString($images);
                $queryString = "Match (m:User:Mintmesh), (n:Company)
                          where n.companyCode='" . $companyCode . "' set n.name='" . $name . "',n.website='" . $website . "',n.logo='" . $logo . "',n.images='" . $images . "',n.description='".$description."',n.industry='".$industry."',n.referral_bonus_file='".$file."',n.referral_bonus_org_name='".$file_org_name."'";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function mapUserCompany($emailid = '', $companyCode = '', $relationType = 'CREATED') {
        
        $relation = ($relationType === 'CONNECTED_TO_COMPANY')?Config::get('constants.RELATIONS_TYPES.CONNECTED_TO_COMPANY'):Config::get('constants.RELATIONS_TYPES.CREATED');
        
        if (!empty($emailid) && !empty($companyCode)) {
            $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            $queryString = "Match (m:User:Mintmesh),(c:Company)
                                 where m.emailid='" . $emailid . "' and c.companyCode='" . $companyCode . "'
                                 create unique (m)-[r:" . $relation . "";

            $queryString.="]->(c)  set r.created_at='" . gmdate("Y-m-d H:i:s") . "'";
            //echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            return true;
        } else {
            return 0;
        }
    }

    public function createNeoNewBucket($input = array(), $bucketId = '') {
        if (!empty($input['bucket_name']) && !empty($bucketId)) {
            $queryString = "CREATE (n:Contact_bucket) set n.name='" . $input['bucket_name'] . "', n.mysql_id='" . $bucketId . "',n.type='" . $input['default'] . "' ";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            return $result;
        }
    }

    public function createCompanyBucketRelation($company_id = '', $bucket_id = '', $relationAttrs = array()) {
        if (!empty($company_id) && !empty($bucket_id)) {
            $queryString = "Match (c:Company)-[r:" . Config::get('constants.RELATIONS_TYPES.BUCKET_IMPORTED') . "]->(b:Contact_bucket)
                                 where c.mysql_id='" . $company_id . "' and b.mysql_id='" . $bucket_id . "' return b";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            $count = $result->count();
            if ($count == 0) {
                $queryString = "Match (c:Company),(b:Contact_bucket) where c.mysql_id='" . $company_id . "' and b.mysql_id='" . $bucket_id . "' ";
                $queryString .= "create unique (c)-[r:" . Config::get('constants.RELATIONS_TYPES.BUCKET_IMPORTED') . "";
                if (!empty($relationAttrs)) {
                    $queryString.="{";
                    foreach ($relationAttrs as $k => $v) {
                        $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                    }
                    $queryString = rtrim($queryString, ",");
                    $queryString.="}";
                }

                $queryString.="]->(b)";
            }
            if ($count == 1) {
                $queryString = "Match (c:Company)-[r:" . Config::get('constants.RELATIONS_TYPES.BUCKET_IMPORTED') . "]->(b:Contact_bucket)";
                $queryString .= "  where c.mysql_id='" . $company_id . "' and b.mysql_id='" . $bucket_id . "' ";
                $queryString .= "set r.no_of_contacts = '" . $relationAttrs['no_of_contacts'] . "' return r";
            }
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            return true;
        } else {
            return 0;
        }
    }

    public function createContactNodes($bucket_id = '', $neoInput = array(), $relationAttrs = array()) {
        //check user already exists
        $queryString = "MATCH (u:User)
                            WHERE u.emailid = '" . $neoInput['emailid'] . "' return u";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        $count = $result->count();
        //if new user
        //create new user node and relation to bucket here
        if ($count == 0) {
            $queryString = " MATCH (b:Contact_bucket)
                            WHERE b.mysql_id = '" . $bucket_id . "' ";

            $queryString .= "CREATE (u:User ";
            if (!empty($neoInput)) {
                $queryString.="{";
                foreach ($neoInput as $k => $v) {
                    if ($k == 'created_by')
                        $v = strtolower($v);
                    $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                }
                $queryString = rtrim($queryString, ",");
                $queryString.="}) set u.created_at='" . gmdate("Y-m-d H:i:s") . "'";
            }
            $queryString.=" create unique (u)<-[:" . Config::get('constants.RELATIONS_TYPES.COMPANY_CONTACT_IMPORTED');
            if (!empty($relationAttrs)) {
                $queryString.="{";
                foreach ($relationAttrs as $k => $v) {
                    $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                }
                $queryString = rtrim($queryString, ",");
                $queryString.="}";
            }
            $queryString.="]-(b)";
        }
        //if exist user
        //create relation bitween user and bucket here
        if ($count == 1) {
            $queryString = " MATCH (u:User),(b:Contact_bucket)
                            WHERE b.mysql_id = '" . $bucket_id . "' and u.emailid = '" . $neoInput['emailid'] . "' ";
            $queryString.="create unique (u)<-[:" . Config::get('constants.RELATIONS_TYPES.COMPANY_CONTACT_IMPORTED');
            if (!empty($relationAttrs)) {
                $queryString.="{";
                foreach ($relationAttrs as $k => $v) {
                    $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                }
                $queryString = rtrim($queryString, ",");
                $queryString.="}";
            }
            $queryString.="]-(b)";
        }
        $queryString.=" return u";
        //fire the query here
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }
    
    public function updateContactNode($bucket_id = '', $neoInput = array(), $relationAttrs = array()) {
         $queryString = " MATCH (u:User),(b:Contact_bucket)
                            WHERE b.mysql_id = '" . $bucket_id . "' and u.emailid = '" . $neoInput['emailid'] . "' ";
           $queryString .= "set u.firstname='".$neoInput['firstname']."', u.lastname='".$neoInput['lastname']."', u.emailid='".$neoInput['emailid']."'";
           $queryString .= ",u.phone='".$neoInput['phone']."',u.status='".$neoInput['status']."' ";
            $queryString.="create unique (u)<-[:" . Config::get('constants.RELATIONS_TYPES.COMPANY_CONTACT_IMPORTED');
            if (!empty($relationAttrs)) {
                $queryString.="{";
                foreach ($relationAttrs as $k => $v) {
                    $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                }
                $queryString = rtrim($queryString, ",");
                $queryString.="}";
            }
            $queryString.="]-(b)";
            $queryString.=" return u";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if ($result->count()) {
            return $result;
            } else {
            return false;
            }
    }

    public function viewCompanyDetails($userEmailId='', $companyCode=''){
        $result = false;
        if(!empty($userEmailId) &&!empty($companyCode)){
            $queryString = "MATCH (u:User),(c:Company) where u.emailid='".$userEmailId."' and c.companyCode = '".$companyCode."'";
            $queryString.= "return c,u";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
        }
        return $result;
    }
    
    public function getCompanyProfile($email){
        if(!empty($email)){
            $queryString = "MATCH (u:User)-[r:CREATED]->(c:Company) where u.emailid='".$email."' return c,u";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
             $count = $result->count();
             if($count == 0){
                $queryString = "MATCH (u:User)-[r:CONNECTED_TO_COMPANY]-(c:Company) where u.emailid='".$email."' return c,u";
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();   
             }
        }
        return $result;
    }
    /*
     * map industry for company
    */
    public function mapIndustryToCompany($industryId='', $companyCode='', $relationType=''){
        $queryString = "Match (c:Company),(i:Industries)
                        where c.companyCode='".$companyCode."' and i.mysql_id=".$industryId."
                        create unique (c)-[r:".$relationType."";

            $queryString.="]->(i)  set r.created_at='".date("Y-m-d H:i:s")."' return i";
            //echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
    }  
    
    public function connectedCompaniesList($email=''){
        
        $result = false;
        if(!empty($email)){
            $queryString = "MATCH (u:User:Mintmesh)-[r:CREATED|CONNECTED_TO_COMPANY]-(c:Company)
                            where u.emailid='".$email."' return c order by r.created_at desc ";
//            $queryString.= " UNION MATCH (u:User:Mintmesh)-[r:COMPANY_CONTACT_IMPORTED]-(b:Contact_bucket)-[:BUCKET_IMPORTED]-(c:Company)
//                             where u.emailid='".$email."' and r.company_code=c.companyCode return c order by r.created_at desc";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
        }
        return $result;
    }
    
    public function connectedCompanyDetails($email=''){
        
        $result = false;
        if(!empty($email)){
            $queryString = "MATCH (u:User:Mintmesh)-[r:CREATED|CONNECTED_TO_COMPANY]-(c:Company)
                            where u.emailid='".$email."' return c limit 1";
            $query = new CypherQuery($this->client, $queryString);
            $resultData = $query->getResultSet();  
            if(!empty($resultData[0]) && !empty($resultData[0][0])){
                $result = $resultData[0][0];
            }
        }
        return $result;
    }
    
    public function checkCompanyUserConnected($userEmailId, $companyCode){
        
        $result = $response = FALSE;   
        if (!empty($companyCode)) {  
            $queryString = "Match (u:User:Mintmesh)-[r:CONNECTED_TO_COMPANY | CREATED]-(c:Company)
                            where u.emailid='" . $userEmailId . "' and c.companyCode='" . $companyCode . "' return r";
//            $queryString.= " UNION MATCH (u:User:Mintmesh)-[r:COMPANY_CONTACT_IMPORTED]-(b:Contact_bucket)-[:BUCKET_IMPORTED]-(c:Company) 
//                             where u.emailid='" . $userEmailId . "' and c.companyCode='" . $companyCode . "' and r.company_code=c.companyCode return r";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            $response  = $result->count(); 
        } 
        return $response;
    } 
    
    public function isCompanyExist($companyCode){
        $response = FALSE;
        if (!empty($companyCode)) {
            $queryString = "Match (c:Company) where c.companyCode='" . $companyCode . "' return c";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            $count = $result->count();
            $response = ($count !== 0)? TRUE:FALSE;      
        } 
        return $response;
    } 
    
    public function getCompanyUserPosts($userEmail='', $companyCode ='',$filterLimit=''){
        $response = array();
        if(!empty($userEmail) && !empty($companyCode)){
//            $queryString = "MATCH (u:User{emailid:'" . $userEmail . "'})-[r:POSTED]-(p:Post {status:'ACTIVE'})-[:POSTED_FOR]-(:Company{companyCode:'" . $companyCode . "'}) ";
            $queryString = "MATCH (u:User)-[r:POSTED]-(p:Post {status:'ACTIVE'})-[:POSTED_FOR]-(:Company{companyCode:'" . $companyCode . "'}) ";
            if(!empty($filterLimit)){
                $queryString.= " where p.created_at >= '" . $filterLimit . "'";
            }
            $queryString.= "return distinct(u),p order by p.created_at desc ";
//            echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet(); 
            $response['count']  = $result->count();
            $response['result'] = $result;
        }
        return $response;
    }
    
    
    public function getCompanyUserPostReferrals($companyCode='') {
        if(!empty($companyCode)){
        $queryString = "MATCH (u:User)-[r:POSTED]->(p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $companyCode . "'}) "; 
        $queryString.= "return p order by p.created_at desc ";
//        echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        }
        return $result;
    }
    
    public function getCompanyUserTopReferrals($email='',$companyCode='') {
        $result = array();
        if(!empty($email)){
            $queryString = "MATCH (:Company{companyCode:'" . $companyCode . "'})<-[:POSTED_FOR]-(p:Post)-[r:GOT_REFERRED]-() 
                            return DISTINCT(r.referred_by),count(r) as count order by count desc limit 6";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
        }
        return $result;
    }
    
    public function getReferralDetails($postId='', $filterLimit='') {
        if(!empty($postId)){
            $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p:Post) where ID(p)=".$postId." and r.one_way_status<>'UNSOLICITED' ";
            if(!empty($filterLimit)){
                $queryString.= " and r.created_at >= '".$filterLimit."' ";
            }
            $queryString.= "  return u,r,p order by r.created_at desc ";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
        }
        return $result;   
    }
    
    public function getPostInvitedCount($postId='') {
        $response = array();
        if(!empty($postId)){
            $queryString = "MATCH (p:Post)-[r:INCLUDED]-(n) where ID(p)=".$postId."  return r";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            $response['count']  = $result->count();
            $response['result'] = $result;
        }
        return $response;   
    }
    
    public function updateContactsList($email='',$input) {
           if(!empty($email)){
               $queryString = "MATCH (u:User) where u.emailid='".$email."' set u.record_id='".trim($input['record_id'])."',u.firstname='".trim($input['firstname'])."'";
               $queryString .= ",u.lastname='".trim($input['lastname'])."',u.phone='".trim($input['contact_number'])."',u.status='".trim($input['status'])."',u.employee_id='".trim(strtoupper($input['other_id']))."' return u";
               $query = new CypherQuery($this->client, $queryString);
               $result = $query->getResultSet();   
               return $result;
           }else{
               return false;
           }
    }
    
    public function deleteContact($email) {
        if(!empty($email)){
            $queryString = "MATCH ()-[r:COMPANY_CONTACT_IMPORTED]->(u:User) where u.emailid='".$email."' delete r";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
            return $result;
        }else{
            return false;
        }     
    }
    
    public function editStatus($input='',$email='') {
        if(!empty($email)){
            $queryString = "MATCH (u:User) where u.emailid='".$email."' set u.status='".trim($input['status'])."' return u ";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();   
            return $result;
        }else{
            return false;
        }
        
    }
    
    public function getDesignation($emailid = ''){
        $result=array();  
        if(!empty($emailid)){
          $queryString = "match (u:User)-[r:WORKS_AS]->(j:Job) where u.emailid='".$emailid."' 
                          return j order by r.created_at desc limit 1";
          $query = new CypherQuery($this->client, $queryString);
          $result = $query->getResultSet();
          } 
        return $result;
    }
    
    public function companyAutoConnect($emailid = '',$relationAttrs = array()) {
        
        $companyCode  = !empty($relationAttrs['company_code'])?$relationAttrs['company_code']:'';
        unset($relationAttrs['company_code']);
        
        if (!empty($emailid)&&!empty($companyCode)) {
            $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            //check user already exists
            $queryString = "MATCH (u:User)-[r:CONNECTED_TO_COMPANY]-(c:Company) where u.emailid='" . $emailid . "' and c.companyCode='" . $companyCode . "' return r";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            $count = $result->count();
            //create new user node and relation to bucket here
            if ($count == 0) {
                $queryString = "Match (u:User),(c:Company) where u.emailid='" . $emailid . "' and c.companyCode='" . $companyCode . "' ";
                $queryString.="create unique (u)<-[:" . Config::get('constants.RELATIONS_TYPES.CONNECTED_TO_COMPANY');
                if (!empty($relationAttrs)) {
                    $queryString.="{";
                    foreach ($relationAttrs as $k => $v) {
                        $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                    }
                    $queryString = rtrim($queryString, ",");
                    $queryString.="}";
                }
                $queryString.="]-(c)";
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
            }
            return true;
        } else {
            return 0;
        }
    }
    
    /*
     * Create new enterpise user node in neo4j
     */

    public function createAddUser($input) {
        $queryString = "CREATE (n:User";
        if (!empty($input)) {
            $queryString = "CREATE (n:User:Mintmesh";
            $queryString.="{";
            foreach ($input as $k => $v) {
                if ($k == 'emailid')
                    $v = strtolower($v);
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=") return n";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
        //return $this->neoUser->create($input);
    }
    
    public function getUsers($email='') {
        $queryString = "MATCH (u:User) where u.emailid='".$email."' return u";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
        
    }
    
    public function updateCompanyLogo($input='') {
        $queryString = "MATCH (c:Company) where c.mysql_id='".$input['id']."' set c.logo='".$input['photo']."',c.logo_org_name='".$input['photo_org_name']."' return c";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
    }
    
    public function updateUser($input) {
        $queryString = "MATCH (u:User) where u.mysql_id='".$input['user_id']."' set u.fullname='".$input['name']."',u.photo='".$input['photo']."',u.photo_org_name='".$input['photo_org_name']."' return u";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
    }
    
    public function getNodeById($id='') {
        $queryString = "MATCH (n:User) where ID(n)=".$id." return n";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result[0][0];
    }
    
    public function getCompanyBucketJobs($companyCode='', $bucketId=''){
        $result = false;
        if(!empty($companyCode) &&!empty($bucketId)){
            $queryString = "match (c:Company)-[POSTED_FOR]-(p:Post{status:'ACTIVE'}) where c.companyCode='".$companyCode."' and p.bucket_id =~ '.*".$bucketId.".*' return ID(p)";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
        }
        return $result;
    }
    
}

?>





