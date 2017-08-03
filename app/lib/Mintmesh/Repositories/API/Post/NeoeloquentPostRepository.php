<?php

namespace Mintmesh\Repositories\API\Post;

use NeoEnterpriseUser;
use DB,Queue;
use Config, Lang;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode;

class NeoeloquentPostRepository extends BaseRepository implements NeoPostRepository {

    protected $neoEnterpriseUser, $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;

    const LIMIT = 10;

    public function __construct(NeoEnterpriseUser $neoUser, APPEncode $appEncodeDecode) {
        parent::__construct($neoUser);
        $this->neoUser = $neoUser;
        $this->db_user = Config::get('database.connections.neo4j.username');
        $this->db_pwd = Config::get('database.connections.neo4j.password');
        $this->db_host = Config::get('database.connections.neo4j.host');
        $this->db_port = Config::get('database.connections.neo4j.port');
        $this->client = new NeoClient($this->db_host, $this->db_port);
        $this->appEncodeDecode = $appEncodeDecode;
        $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
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
        $queryString.="]-(u) set p.created_at='" . gmdate("Y-m-d H:i:s") . "' ";
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

    public function createPostContactsRelation($relationAttrs = array(), $postId = '', $company_code = '', $contactEmailid = '', $bucketId = '') {
        
        if (!empty($postId) && !empty($company_code) && !empty($contactEmailid)) {
            
            $queryString = "Match (b:Contact_bucket)-[r:COMPANY_CONTACT_IMPORTED]->(u:User),(p:Post)
            where r.company_code='" . $company_code . "' and b.mysql_id='" . $bucketId . "' and ID(p)=" . $postId . " and u.emailid='".$contactEmailid."' ";
            if ($queryString) {

                $queryString .= " create unique (p)-[:" . Config::get('constants.REFERRALS.INCLUDED') ;
                                       
                if (!empty($relationAttrs)) {
                    $queryString.="{";
                    foreach ($relationAttrs as $k => $v) {
                        $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                    }
                    $queryString = rtrim($queryString, ",");
                    $queryString.="}";
                }
                $queryString.="]->(u)";
            }
            $queryString .= " return u";
            //echo $queryString;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
        }
        return TRUE;
    }
    
      public function jobsList_old($input=array(), $page = 0, $search = "",$permission="",$postby="") {
        if (!empty($input['userEmail']) && !empty($input['company_code'])) {
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            $email = $this->appEncodeDecode->filterString(strtolower($input['userEmail']));
            if($permission == '1' && $postby == '0'){
                $queryString = "match (u:User)";
                }else{
                    $queryString = "match (u:User {emailid:'" . $email . "'})";
                }
            $queryString .= "-[r:POSTED]-(p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $input['company_code'] . "'}) where p.status <> 'PENDING' ";
                
            if (!empty($search)) {
                $search = $this->appEncodeDecode->filterString($search);               
                $queryString .= "and (p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*') ";
            } 
            if (isset($input['request_type']) && $input['request_type'] != '2') {
////                if(!empty($search)){
////                    $queryString .= "and ";
////                }else{
//                    $queryString .= "and ";
//                }
                $queryString .= "and p.free_service='" . $input['request_type'] . "' ";
            }
            $queryString .= "return p,count(p) as listCount,count(distinct(u)) ORDER BY p.created_at DESC";

            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            } 
//            echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }
    
      public function jobsList($input=array(), $page = 0, $search = "", $permission="", $postby="") {
        
        $return = $createdBy = $requestType = FALSE;  
        if (!empty($input['userEmail']) && !empty($input['company_code'])) {
            $email = $this->appEncodeDecode->filterString(strtolower($input['userEmail']));
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            #required query string parameters form here
            $createdBy = $requestType = $searchQuery = $limitQuery = '';
            if (!empty($search)) {
                $search = $this->appEncodeDecode->filterString($search);
                $searchQuery =  "and (p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*') ";
            }    
            if(($permission == '1' && $postby != '0') || $permission == '0'){
                $createdBy = " and p.created_by = '" . $email . "' ";
            }
            if (isset($input['request_type']) && $input['request_type'] != '2') {
                $requestType = " and p.free_service='" . $input['request_type'] . "' ";
            }
            if (!empty($limit) && !($limit < 0)) {
                $limitQuery = " skip " . $skip . " limit " . self::LIMIT;
            }
            $baseQuery = "MATCH (p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $input['company_code'] . "'}) where p.status <> 'PENDING' ";        
            #query string formation here
            $queryString = $baseQuery.$searchQuery.$createdBy.$requestType;
            $queryString .= " WITH count(p) AS cnt ";
            $queryString .= $baseQuery.$searchQuery.$createdBy.$requestType;
            $queryString .= " WITH p, cnt ORDER BY p.created_at DESC ".$limitQuery;
            $queryString .= " return p,cnt ";
            $query = new CypherQuery($this->client, $queryString);
            $return = $query->getResultSet();
        } 
        return $return;
    }

    public function jobsDetails($jobid = "", $company_code = "") {
        if (!empty($jobid)) {

            $queryString = "match (p:Post),(n:Company) where ID(p)=" . $jobid . " AND n.companyCode='" . $company_code . "' return p,n";
            //echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }

    public function jobReferralDetails($input,$page = 0) {
        if (!empty($input['post_id'])) {
          $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }

            $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) where ID(p)=" . $input['post_id'] . " and r.one_way_status <> 'UNSOLICITED'";
            if(!empty($input['status'])){
                $queryString .= "and r.one_way_status='".$input['status']."' ";
            }
            $queryString .= "return u,r,p ORDER BY r.created_at DESC";
             if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            }  
            $query = new CypherQuery($this->client, $queryString);
            return $result[0][0] = $query->getResultSet();
        } else {
            return false;
        }
    }

      public function statusDetails($post_id=0, $referred_by="", $referral="", $status="", $post_way="", $relation_count=0,$nonMintmesh=0)
         {
             if (!empty($post_id) && !empty($referred_by) && !empty($referral) && !empty($status) && !empty($post_way) && !empty($relation_count))
             {
                 $referred_by = $this->appEncodeDecode->filterString(strtolower($referred_by));
//                 $gmDate = gmdate("Y-m-d H:i:s");
                 $referral = $this->appEncodeDecode->filterString(strtolower($referral));;
                 $status = strtoupper($status) ;
				 if (!empty($nonMintmesh)){//if for phone number referred
					 $queryString = "match (u:NonMintmesh)-[r:GOT_REFERRED]->(p:Post)
                                  where ID(p)=".$post_id ;
				 }else{
					 $queryString = "match (u:User)-[r:GOT_REFERRED]->(p:Post)
                                  where ID(p)=".$post_id ;
				 }
                  if ($post_way == 'one')//ignore the state for p3
                  {
                    $queryString .=" and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'";
                  }

                  $queryString.=" and r.referred_by='".$referred_by."' and r.relation_count='".$relation_count."'";
				  if (!empty($nonMintmesh)){//if for phone number referred
					 $queryString.= " and u.phone='".$referral."' " ;
				 }else{
					 $queryString.= " and u.emailid='".$referral."' " ;
				 }

                  if ($post_way == 'one')
                  {
                      $queryString .=" set r.one_way_status='".Config::get('constants.REFERRALS.STATUSES.'.$status)."', r.p1_updated_at='".gmdate("Y-m-d H:i:s")."'" ;
                      if($status == 'DECLINED'){
                         $queryString .= " ,p.referral_declined_count = p.referral_declined_count + 1";
                      }
                     
                  }
                  
                  else if ($post_way == 'round')
                  {
                     $queryString .=" set r.completed_status='".Config::get('constants.REFERRALS.STATUSES.'.$status)."' , r.status='".Config::get('constants.REFERRALS.STATUSES.COMPLETED')."', r.p3_updated_at='".gmdate("Y-m-d H:i:s")."'" ;
                  }
                  $queryString.=" return p,r,u" ;
                  $query = new CypherQuery($this->client, $queryString);
                  return $result = $query->getResultSet();
                                 
             }
            
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
       
      public function getPostInviteCount($jobid = "") {
          $return = 0;
           if (!empty($jobid)) {
               $queryString = "match (p:Post)-[r:INCLUDED]-(u:User) where ID(p)=" . $jobid . " return count(distinct(u))";
               $query = new CypherQuery($this->client, $queryString);
               $result = $query->getResultSet();
               if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
               }
           } 
           return $return;
       }
      //Update Active or Decline Status count
      public function updatePostStatusCount($data = array()) {
          if (!empty($data)) {
              $queryString = "match (p:Post),(n:Company) where ID(p)=" . $data['post_id'] . " AND n.companyCode='" . $data['company_code'] . "' set p.accept_count = ". $data['acceptCount'] ." , p.decline_count = ".$data['declineCount']." return p,n";
              $query = new CypherQuery($this->client, $queryString);
              return $result = $query->getResultSet();
          } else {
              return false;
          }
      }
      
      public function getJobTitle($emailid = ''){
          if(!empty($emailid)){
              $queryString = "match (u:User)-[r:WORKS_AS]->(j:Job) where u.emailid='".$emailid."' return j";
          $query = new CypherQuery($this->client, $queryString);
              return $result = $query->getResultSet();
          } else {
              return false;
          }
      }
      
      public function updateAwaitingActionDetails($userEmailId, $postId=0, $referredBy="", $referral="", $status="", $relationCount=0, $nonMintmesh=0)
         {
            $result= $response = FALSE;
            if (!empty($userEmailId) && !empty($postId) && !empty($referredBy) && !empty($referral) && !empty($status) && !empty($relationCount))
             {
                $status   = strtoupper($status) ; 
                $label    = 'User';
                $where    = 'emailid';
                $referral    = $this->appEncodeDecode->filterString(strtolower($referral));
                $referredBy  = $this->appEncodeDecode->filterString(strtolower($referredBy));  
                if(!empty($nonMintmesh)){//if for phone number referred
                    $label = 'NonMintmesh';
                    $where = 'phone';
                }  
//                                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
                $queryString = "MATCH (u:".$label.")-[r:GOT_REFERRED]->(p:Post) where ID(p)=".$postId." 
                                and r.referred_by='".$referredBy."' and r.relation_count='".$relationCount."'
				and u.".$where." ='".$referral."' and  r.awaiting_action_status = 'INTERVIEWED'
                                return p,r " ;
                $query  = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                $response  = $result->count();
//                                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'

                $queryString = "MATCH (u:".$label.")-[r:GOT_REFERRED]->(p:Post) where ID(p)=".$postId." 
                                and r.referred_by='".$referredBy."' and r.relation_count='".$relationCount."'
				and u.".$where." ='".$referral."' 
                                set r.awaiting_action_status = '".$status."', r.awaiting_action_by = '".$userEmailId."',
                                r.awaiting_action_updated_at= '".gmdate("Y-m-d H:i:s")."' " ;
                $queryString.= ($response)?", p.referral_interviewed_count = p.referral_interviewed_count - 1 ":"";
                $queryString.= ($status == 'INTERVIEWED')?", p.referral_interviewed_count = p.referral_interviewed_count + 1 ":"";
                $queryString.= ($status == 'HIRED')?", p.referral_hired_count = p.referral_hired_count + 1 ":"";
                $queryString.=" return p,r" ;
                //echo $queryString;exit;
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();                       
             } 
            return $result;
         }
         
         public function bucket($id='') {
             $queryString = "MATCH (c:Contact_bucket) where c.mysql_id='".$id."' return c.name";
             $query = new CypherQuery($this->client, $queryString);
             $result = $query->getResultSet();   
             return $result[0][0];
             
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
    
    public function getPostRewards($postId=0) {
        
        $queryString = "MATCH (p:Post)-[r:POST_REWARDS]-(w:Rewards) where ID(p)=".$postId." return distinct(p),r,w";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    public function getJobReferrals($postId=0) {
        
        $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) where ID(p)=".$postId." return u,r,p ORDER BY r.created_at DESC";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    
    public function getPosts($postId) {
        $return = array();
        $queryString = "match (p:Post) where ID(p)=".$postId." return p";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();         
        if (isset($result[0]) && isset($result[0][0])){
            $return = $result[0][0];
        }
       return $return;
    }
    
    public function createCampaignAndCompanyRelation($companyCode='', $campaign = array(), $userEmailId='') {
        $userEmailId = $this->appEncodeDecode->filterString($userEmailId);
        $queryString = "MATCH (c:Company) WHERE c.companyCode = '". $companyCode ."'
                            CREATE (n:Campaign ";
        if (!empty($campaign)) {
            $queryString.="{";
            foreach ($campaign as $k => $v) { 
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=")<-[r:" . Config::get('constants.RELATIONS_TYPES.COMPANY_CREATED_CAMPAIGN') ." ]-(c) ";
        $queryString.=" set r.created_at='".gmdate("Y-m-d H:i:s")."', n.created_at='".gmdate("Y-m-d H:i:s")."', n.created_by = '".$userEmailId."' ";
        $queryString.=" return n";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }
    
    public function createCampaignScheduleRelation($campaignId='', $campaignSchedule = array(), $userEmailId='') {
        $userEmailId = $this->appEncodeDecode->filterString($userEmailId);
        $queryString = "MATCH (c:Campaign) WHERE ID(c)=".$campaignId."
                            CREATE (n:CampaignSchedule ";
        if (!empty($campaignSchedule)) {
            $queryString.="{";
            foreach ($campaignSchedule as $k => $v) { 
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.=")<-[r:" . Config::get('constants.RELATIONS_TYPES.CAMPAIGN_SCHEDULE') ." ]-(c) ";
        $queryString.=" set r.created_at='".gmdate("Y-m-d H:i:s")."', n.created_at='".gmdate("Y-m-d H:i:s")."', n.created_by = '".$userEmailId."' ";
        $queryString.=" return n";
        //echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }
    
    public function createPostAndCampaignRelation($postId = '', $campaignId = '', $postCampaignRelationAttrs = array()) {
        $queryString = "Match (p:Post),(c:Campaign)
                                    where ID(p)=".$postId." and ID(c)=".$campaignId."
                                    create unique (c)-[:" . Config::get('constants.RELATIONS_TYPES.CAMPAIGN_POST');
        if (!empty($postCampaignRelationAttrs)) {
            $queryString.="{";
            foreach ($postCampaignRelationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(p) return p";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
     public function campaignsList($email='',$input='',$page = 0,$permission='',$filters = '') {
        if(!empty($email)){
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            if (!empty($input['search'])) {
                $search = $this->appEncodeDecode->filterString($input['search']);
                $queryString = "start c = node(*) where c.campaign_name =~ '(?i).*". $search .".*'";
                $queryString .= " MATCH (c:Campaign{company_code:'" . $input['company_code'] . "'";
            }else{
                $queryString = "MATCH (c:Campaign{company_code:'" . $input['company_code'] . "'";
            }         
            if($input['all_campaigns'] == '0' || $permission == '0'){
                $queryString .= ",created_by:'".$email."'";
            }
            $queryString .= "})";
            if(isset($filters) && !empty($filters)){
               $queryString .= " where "; 
               if(in_array("Mass Recruitment",$filters) || in_array("Military Veterans",$filters) || in_array("Campus Hires",$filters)){
                   $queryString .= "("; 
               }
                if(in_array("Mass Recruitment",$filters)){
               $queryString .= "c.campaign_type='Mass Recruitment' "; 
               }
                if(in_array("Military Veterans",$filters)){
                if(in_array("Mass Recruitment",$filters)){
                    $queryString .= "or ";}
                    $queryString .= "c.campaign_type='Military Veterans' "; 
                }
                if(in_array("Campus Hires",$filters)){
                if(in_array("Mass Recruitment",$filters) || in_array("Military Veterans",$filters)){
                    $queryString .= "or ";}
                    $queryString .= "c.campaign_type='Campus Hires' "; 
                }
                if(in_array("Mass Recruitment",$filters) || in_array("Military Veterans",$filters) || in_array("Campus Hires",$filters)){
                $queryString .= ") ";
                }
                if(in_array("Open",$filters) || in_array("Close",$filters)){
                    
                if(in_array("Open",$filters)){
                if(in_array("Mass Recruitment",$filters) || in_array("Military Veterans",$filters) || in_array("Campus Hires",$filters)){
                    $queryString .= "and ";}
                    $queryString .= "(c.status='ACTIVE' "; 
                }
                if(in_array("Close",$filters)){
                if(in_array("Mass Recruitment",$filters) || in_array("Military Veterans",$filters) || in_array("Campus Hires",$filters)){
                    if(in_array("Open",$filters)){
                        $queryString .= "or ";
                    }else{
                    $queryString .= "and (";
                    }
                    }
                    else if(in_array("Open",$filters)){
                    $queryString .= "or "; 
                    }else{
                        $queryString .= "( "; 
                    }
                    $queryString .= "c.status='CLOSED'"; 
                }
                $queryString .= ")";
                }
            }             

//            if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true' || !empty($input['location']) || $input['open'] == 'true' || $input['close'] == 'true'){
//                $queryString .= " where"; 
//            }
//            if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true'){
//                $queryString .= " (";
//            }
//            if(isset($input['mass_recruitment']) && !empty($input['mass_recruitment']) && $input['mass_recruitment'] == 'true'){
//                $queryString .= "c.campaign_type='Mass Recruitment'";
//            }
//            if(isset($input['militery_veterans']) && !empty($input['militery_veterans']) && $input['militery_veterans'] == 'true'){
//                if($input['mass_recruitment'] == 'true'){
//                 $queryString .= " or";
//                }
//                $queryString .= " c.campaign_type='Military Veterans'";
//            }
//            if(isset($input['campus_hires']) && !empty($input['campus_hires']) && $input['campus_hires'] == 'true'){
//                if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true'){
//                    $queryString .= " or";
//                }
//                $queryString .= " c.campaign_type='Campus Hires'";
//            }
//            if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true'){
//                $queryString .= ")";
//            }
//            if((isset($input['open']) && $input['open'] == 'true') || (isset($input['close']) && $input['close'] == 'true')){
//                if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true'){
//                    $queryString .= " and";
//                }
//                                    $queryString .= " (";
//                if($input['open'] == 'true'){
//                    $queryString .= "c.status='ACTIVE'";
//                }if($input['close'] == 'true'){
//                    if($input['open'] == 'true'){
//                        $queryString .= "or ";
//                }
//                    $queryString .= "c.status='CLOSED'";
//                 }
//                $queryString .= ")";
//
//            }
//            if(isset($input['location']) && !empty($input['location'])){
//                $open = 0;//and condition open or not
//                foreach($input['location'] as $key=>$value){
//                    if(isset($value) && !empty($value)){  
//                        
//                        if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true' || $input['open'] == 'true' || $input['close'] == 'true')
//                        {
//                            if($key == '0'){
//                                $queryString .= " and (";
//                                $open = 1;
//                                }else{
//                                    $queryString .= " or ";
//                            }
//                        }
//                        else if($key != '0')
//                        {
//                            $queryString .= " or ";
//                        }
//                    $queryString .= " c.country='".$value."'";
//                    }
//                 }
//                 if($open){
//                    $queryString .= ")";
//                 }
//            }
            $queryString .= " return c, count(distinct(c)) as total_count ORDER BY c.created_at DESC";
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            } 
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }  
        else{
            return false;
        }
    } 
    
    public function createCampaignContactsRelation($relationAttrs = array(), $campaignId='', $contactId='') {
        $queryString = "Match (u:User),(c:Campaign)
                            where ID(c)=".$campaignId." and u.emailid = '".$contactId."'
                            create unique (c)-[r:" . Config::get('constants.RELATIONS_TYPES.CAMPAIGN_CONTACT');
        if (!empty($relationAttrs)) {
            $queryString.="{";
            foreach ($relationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(u) set r.post_read_status =0 return c";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
    public function getCampaignById($campaignId='') {
        
        $return = FALSE;
        $queryString = "MATCH (c:Campaign) where ID(c)=".$campaignId." return c";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet(); 
        if (isset($result[0]) && isset($result[0][0])){
            $return = $result[0][0];
        } 
        return  $return;   
    }
    public function getCampaignSchedule($campaignId='') {
        $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_SCHEDULE]-(s:CampaignSchedule) where ID(c)=".$campaignId." return distinct(s) ORDER BY s.gmt_end_date DESC";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    public function getCampaignPosts($campaignId='', $page=0, $search = '', $status = '') {
        $skip  = $limit = 0;
        if (!empty($page)){
            $limit = $page*10 ;
            $skip  = $limit - 10 ;
        }
        $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_POST]-(p:Post) where ID(c)=".$campaignId." ";
          if(!empty($search)){
                $queryString .= "and (p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*') ";
            }
          if(!empty($status)){
                $queryString .= "and p.status='".$status."' ";
            }
        $queryString .= "return distinct(p) ORDER BY p.created_at DESC ";
        if (!empty($limit) && !($limit < 0))
        {
            $queryString.=" skip ".$skip." limit ".self::LIMIT ;
        }
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    
    public function getCampaignActivePosts($campaignId='') {
        
        $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_POST]-(p:Post{status:'ACTIVE'}) where ID(c)=".$campaignId."  return distinct(p) ORDER BY p.created_at DESC ";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    
    public function editCampaignAndCompanyRelation($companyCode, $campaignId='', $campaign = array(), $userEmailId='') {
        $userEmailId = $this->appEncodeDecode->filterString($userEmailId);
        $queryString = "Match (n:Campaign)<-[r:COMPANY_CREATED_CAMPAIGN]-(c:Company) where ID(n)=".$campaignId." set ";
        if (!empty($campaign)) {
            foreach ($campaign as $k => $v) { 
                $queryString.="n.".$k . "='" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString.=" n.updated_at='".gmdate("Y-m-d H:i:s")."', n.updated_by = '".$userEmailId."' ";
        }
        $queryString.=" return n";
        //echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }
    
    public function updateCampaignScheduleRelation($scheduleId='', $campaignId='', $campaignSchedule = array(), $userEmailId='') {
        $userEmailId = $this->appEncodeDecode->filterString($userEmailId);
        $queryString = "MATCH (n:CampaignSchedule)<-[r:" . Config::get('constants.RELATIONS_TYPES.CAMPAIGN_SCHEDULE') ." ]-(c:Campaign) 
                WHERE ID(n)=".$scheduleId." and ID(c)=".$campaignId." set ";                 
        if (!empty($campaignSchedule)) {
            foreach ($campaignSchedule as $k => $v) { 
                $queryString.="n.".$k . "='" . $this->appEncodeDecode->filterString($v) . "',";
            }
        }
        $queryString.=" n.updated_at='".gmdate("Y-m-d H:i:s")."', n.updated_by = '".$userEmailId."' ";
        $queryString.=" return n";
        //echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
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
       
    public function ptest() {
            $return = array();
            $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p:Post) where  r.resume_parsed=0 return r,u,p limit 1";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if($result->count()){
                $return = $result;
            }
        return  $return;   
       }
       
    public function checkCandidateReferred($postId=0, $emailid='') {
        $queryString = "MATCH (u:User)-[r:GOT_REFERRED]->(p:Post) where u.emailid='".$emailid."' and ID(p)=".$postId." and r.status<>'DECLINED' return r";
        $query = new CypherQuery($this->client, $queryString);
         $result = $query->getResultSet();
         //print_r($result).exit;
        if($result->count())
            return false;
        else {      
            return TRUE;    
        }
    }
    
     public function checkCandidate($emailid='',$postId=0) {
        $queryString = "MATCH (u:User)-[r:GOT_REFERRED]->(p:Post) where u.emailid='".$emailid."' and ID(p)=".$postId." return r";
        //echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
         $result = $query->getResultSet();
         return $result;
    }
    
    public function checkUserNodeExist($emailid){
        $queryString = "MATCH (u:User) where u.emailid='".$emailid."' return u.emailid";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if(count($result)>0)
            return false;
        else {      
            return true;    
        }
    }
    
    public function createUserNode($emailid){
        $queryString = "CREATE (u:User) SET u.emailid='".$emailid."' return u.emailid";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
    
    public function createRelationBtUserAndPost($postId=0, $emailId='', $relAttrs = array())
        {
            $return = FALSE;
            $relAttrs['status']         = Config::get('constants.REFERRALS.STATUSES.PENDING') ;
            $relAttrs['one_way_status'] = Config::get('constants.REFERRALS.STATUSES.PENDING') ;
            $queryString = "Match (u:User),(p:Post)
                            where ID(p)=".$postId." and u.emailid = '".$emailId."'
                            create unique (u)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED');
            if (!empty($relAttrs)) {
                $queryString.="{";
                foreach ($relAttrs as $k => $v) {
                    $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
                }
                $queryString = rtrim($queryString, ",");
                $queryString.="}";
            }
            $queryString.="]->(p) set r.created_at='".date("Y-m-d H:i:s")."' return r";
            //echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        
    public function updateResumeParsedStatus($relationId=0, $status=0){
        $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p) where ID(r)=".$relationId." set r.resume_parsed=".$status." RETURN r";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    } 
    public function updateResumeParsedJsonPath($relationId=0, $jsonPath=''){
        $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p) where ID(r)=".$relationId." set  r.resume_parsed_Json ='".$jsonPath."' RETURN r";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
    public function getCompanyAllReferrals_old($emailId='', $companyCode='', $search='', $page=0,$filters = '')
    {
       $return = FALSE;
       if (!empty($emailId) && !empty($companyCode))
       {
           $userEmail = $this->appEncodeDecode->filterString(strtolower($emailId));
           $skip = $limit = 0;
           if (!empty($page)){
               $limit = $page*10 ;
               $skip = $limit - 10 ;
           }

           $queryString = "MATCH (c:Company)<-[:POSTED_FOR]-(p:Post)<-[r:GOT_REFERRED]-(u) where c.companyCode='".$companyCode."' ";
           if (!empty($search)){
              $queryString.=" and (p.service_name =~ '(?i).*". $search .".*' ";

              if($search[0] == 'd' || $search[0] == 'u'){
                  $queryString .= "or r.one_way_status =~ '(?i).*". $search .".*'";    
              }else{
                  $queryString .= "or r.awaiting_action_status =~ '(?i).*". $search .".*'";
              }
              $queryString .= ") ";
           }
           if(!empty($filters)){
               $queryString .= "and (";
           if(in_array("Accepted",$filters)){
               $queryString .= "r.awaiting_action_status='ACCEPTED' "; 
           }
           if(in_array("Declined",$filters)){
               if(in_array("Accepted",$filters)){
               $queryString .= "or ";}
               $queryString .= "r.one_way_status='DECLINED' "; 
           }
           if(in_array("Unsolicited",$filters)){
               if(in_array("Accepted",$filters) || in_array("Declined",$filters)){
               $queryString .= "or ";}
               $queryString .= "r.one_way_status='UNSOLICITED' "; 
           }
           if(in_array("Interviewed",$filters)){
               if(in_array("Accepted",$filters) || in_array("Declined",$filters) || in_array("Unsolicited",$filters)){
               $queryString .= "or ";}
               $queryString .= "r.awaiting_action_status='INTERVIEWED' "; 
           }
           if(in_array("Offered",$filters)){
               if(in_array("Accepted",$filters) || in_array("Declined",$filters) || in_array("Unsolicited",$filters) || in_array("Interviewed",$filters)){
               $queryString .= "or ";}
               $queryString .= "(r.awaiting_action_status='OFFERMADE' or r.awaiting_action_status='OFFERED') "; 
           }
           if(in_array("Hired",$filters)){
               if(in_array("Accepted",$filters) || in_array("Declined",$filters) || in_array("Unsolicited",$filters) || in_array("Interviewed",$filters) || in_array("Offered",$filters)){
               $queryString .= "or ";}
               $queryString .= "r.awaiting_action_status='HIRED' "; 
           }
           $queryString .= ")";
           }
           $queryString.=" return p,u,r order by r.created_at desc";
           if (!empty($limit) && !($limit < 0))
           {
               $queryString.=" skip ".$skip." limit ".self::LIMIT ;
           }
           $query = new CypherQuery($this->client, $queryString);
           $result = $query->getResultSet(); 
            if($result->count())
               $return = $result;  
        } 
       return $return; 
    }
    
    public function getCompanyAllReferrals($emailId='', $companyCode='', $search='', $page=0, $filters = '')
    {
       $return = FALSE;
       if (!empty($emailId) && !empty($companyCode))
       {
            $searchQuery = $filterQuery = $limitQuery ='';
            $userEmail = $this->appEncodeDecode->filterString(strtolower($emailId));
            $skip = $limit = 0;
            if (!empty($page)){
               $limit = $page*10 ;
               $skip = $limit - 10 ;
            }
            #form filters logic here
            if(!empty($filters)){
                $filterQuery = " and (";
                if(in_array("Accepted",$filters)){
                    $filterQuery .= "r.awaiting_action_status='".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."' or "; 
                }
                if(in_array("Declined",$filters)){
                    $filterQuery .= "r.one_way_status='".Config::get('constants.REFERRALS.STATUSES.DECLINED')."' or "; 
                }
                if(in_array("Unsolicited",$filters)){
                    $filterQuery .= "r.one_way_status='".Config::get('constants.REFERRALS.STATUSES.UNSOLICITED')."' or "; 
                }
                if(in_array("Interviewed",$filters)){
                    $filterQuery .= "r.awaiting_action_status='".Config::get('constants.REFERRALS.STATUSES.INTERVIEWED')."' or "; 
                }
                if(in_array("Offered",$filters)){
                    $filterQuery .= " r.awaiting_action_status='".Config::get('constants.REFERRALS.STATUSES.OFFERED')."' or "; 
                }
                if(in_array("Hired",$filters)){
                    $filterQuery .= "r.awaiting_action_status='".Config::get('constants.REFERRALS.STATUSES.HIRED')."' "; 
                }
                $filterQuery = rtrim($filterQuery, " or ");
                $filterQuery .= ")";
            }    
            #form the search logic here
            if (!empty($search)){
                $searchQuery =" and (p.service_name =~ '(?i).*". $search .".*' ";
                if($search[0] == 'd' || $search[0] == 'u'){
                    $searchQuery .= "or r.one_way_status =~ '(?i).*". $search .".*')";    
                }else{
                    $searchQuery .= "or r.awaiting_action_status =~ '(?i).*". $search .".*')";
                }
            }
            #form the limit logic here
            if (!empty($limit) && !($limit < 0)) {
                $limitQuery = " skip " . $skip . " limit " . self::LIMIT;
            }
            $baseQuery = "MATCH (c:Company{companyCode:'".$companyCode."'})<-[:POSTED_FOR|COMPANY_UNSOLICITED]-(p)<-[r:GOT_REFERRED]-(u) where c.companyCode='".$companyCode."' ";        
            #query string formation here
            $queryString = $baseQuery.$searchQuery.$filterQuery;
            $queryString .= " WITH count(p) AS cnt ";
            $queryString .= $baseQuery.$searchQuery.$filterQuery;
            $queryString .= " WITH p,u,r,cnt ORDER BY r.created_at DESC ".$limitQuery;
            $queryString .= " return p,u,r,cnt ";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet(); 
            if($result->count())
               $return = $result;  
        } 
       return $return; 
    }
    
    public function referCandidate($neoInput='',$input='') {
        $queryString = "Match (p:Post),(u:User)
                                    where ID(p)=". $input['post_id'] ." and u.emailid='" . $neoInput['referral'] . "'
                                    create unique (u)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED');
         if (!empty($neoInput)) {
                    $queryString.="{";
                    foreach ($neoInput as $k => $v) {
                        $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                    }
                    $queryString = rtrim($queryString, ",");
                    $queryString.="}";
                    }
                    $queryString .= "]->(p) set ";
                    if($neoInput['one_way_status'] != 'UNSOLICITED'){
                    $queryString.="p.total_referral_count = p.total_referral_count + 1,";
                    }
                    $queryString .= "r.resume_parsed=0";
                    if($neoInput['one_way_status'] == 'UNSOLICITED'){
                        $queryString .= ",p.unsolicited_count = p.unsolicited_count + 1";
                    }
                    $queryString .=  " return count(p),u,ID(r)";
                    $query = new CypherQuery($this->client, $queryString);
                    $result = $query->getResultSet();
                    return $result;
        
    }
    
    public function getReferralDetails($id='') {
        $queryString = "Match (u:User)-[r:GOT_REFERRED]->(p:Post) where ID(r)=".$id." return u,r,p";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result;
        
    }
    
    public function checkIncludedRelation($postId=0, $refById=0) {
        $return = FALSE;
        $queryString = "MATCH (p:Post)-[r:INCLUDED]->(u:User) where ID(p)=".$postId." and ID(u)=".$refById." return r";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if (isset($result[0]) && isset($result[0][0])){
            $return = $result[0][0];
        }
       return $return;
    }
    
    public function getApplyJobsList($companyCode='',$refById='', $page=0,$search = '',$input='') {
        $return = array();
        $skip   = $limit = 0;
        if (!empty($page)){
            $limit = $page*10 ;
            $skip  = $limit - 10 ;
        }
        $search = $this->appEncodeDecode->filterString($search);
        $queryString = "MATCH (c:Company)<-[:POSTED_FOR]-(p:Post{status:'ACTIVE'})-[:INCLUDED]->(u:User) where c.companyCode = '".$companyCode."' and ID(u)=".$refById." and p.post_type <> 'campaign' ";
        if(!empty($input['share']) && $input['share'] == 1){
            $queryString .= "and p.post_type <> 'internal' ";
        }
            if(!empty($search)){
                $queryString .= "and (p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*') ";
            }
            $queryString .= "WITH count(p) AS cnt
                        MATCH (c:Company)<-[:POSTED_FOR]-(p:Post{status:'ACTIVE'})-[:INCLUDED]->(u:User) where c.companyCode = '".$companyCode."' and ID(u)=".$refById." and p.post_type <> 'campaign' ";
            if(!empty($input['share']) && $input['share'] == 1){
            $queryString .= "and p.post_type <> 'internal' ";
            }
            if(!empty($search)){
                $queryString .= "and (p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*') ";
            }
          $queryString    .=  "RETURN p,c, cnt order by p.created_at desc ";
        if (!empty($limit) && !($limit < 0))
        {
            $queryString.=" skip ".$skip." limit ".self::LIMIT ;
        }
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if($result->count())
            $return = $result;  
         
       return $return;
    }
    
    public function getPostCompany($postId=''){
        $return = FALSE;
        $queryString = "MATCH (p:Post)-[:POSTED_FOR]->(c:Company) where ID(p)=".$postId." return c";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if (isset($result[0]) && isset($result[0][0])){
            $return = $result[0][0];
        }
       return $return;
    }
    
    public function getBucketForPost($postId=''){
        $queryString = "MATCH (p:Post)<-[r:CAMPAIGN_POST]-(c:Campaign)-[r1:CAMPAIGN_CONTACT]->(u:User) where ID(p)=".$postId." return distinct(r1.bucket_id)";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if($result){
            return $result;
        }else{
            return false;
        }
    }
    
     public function getCampaignCompany($campaignId=''){
        $queryString = "MATCH (c:Company)-[:COMPANY_CREATED_CAMPAIGN]->(n:Campaign) where ID(n)=".$campaignId." return c";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if($result){
            return $result[0][0];
        }else{
            return false;
        }
    }
    
    public function checkCampaignUserRelation($input) {
        $queryString = "MATCH (c:Campaign)-[:CAMPAIGN_CONTACT]->(n:User) where ID(c)=".$input['campaign_id']." and ID(n)=".$input['reference_id']." return c";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if($result->count() != 0){
            return $result[0][0];
        }else{
            $queryString = "MATCH (u:User)-[:CREATED]->(c:Company)-[:COMPANY_CREATED_CAMPAIGN]->(n:Campaign) where ID(n)=".$input['campaign_id']." and ID(u)=".$input['reference_id']." return n";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if($result->count() != 0){
                return $result[0][0];
            }else{
            return false;
        }
        }
    }
      
    public function getJobsList($userEmailId='', $companyCode='',$page=0, $search = '') {
        $skip = $limit = 0;
        if (!empty($page)){
            $limit = $page*10;
            $skip  = $limit - 10;
        }
        if (!empty($search)) {
            $search = $this->appEncodeDecode->filterString($search);
        }
        
        if(!empty($userEmailId)){

            $queryString = "MATCH (u:User:Mintmesh{emailid:'".$userEmailId."'})-[r:INCLUDED]-(p:Post{status:'ACTIVE'})-[:POSTED_FOR]-(Company{companyCode:'".$companyCode."'}) 
                            WHERE  p.post_type <>'campaign' ";
                        if (!empty($search)) {
                                 $queryString .= " OPTIONAL MATCH (p)-[:ASSIGNED_EMPLOYMENT_TYPE]->(e:EmploymentType)
                                                    WITH p,r,e 
                                                   OPTIONAL MATCH (p)-[:ASSIGNED_JOB_FUNCTION]->(j:Job_Functions)
                                                    WITH p,r,e,j
                                                   OPTIONAL MATCH (p)-[:ASSIGNED_INDUSTRY]->(i:Industries)
                                                    WITH p,r,e,j,i
                                                   OPTIONAL MATCH (p)-[:ASSIGNED_EXPERIENCE_RANGE]->(x:ExperienceRange)
                                                    WITH p,r,e,j,i,x 
                                                    WHERE  
                                                    (p.service_name =~ '(?i).*". $search .".*' or
                                                    p.service_location =~ '(?i).*". $search .".*' or
                                                    p.post_type =~ '(?i).*". $search .".*' or 
                                                    e.name=~'(?i).*". $search .".*' or 
                                                    j.name=~'(?i).*". $search .".*' or 
                                                    i.name=~'(?i).*". $search .".*' or 
                                                    x.name=~'(?i).*". $search .".*')";
                            }
                $queryString .=  "WITH collect({post:p,rel:r}) as posts 
                OPTIONAL MATCH (u:User:Mintmesh{emailid:'".$userEmailId."'})-[r:CAMPAIGN_CONTACT]-(p:Campaign{status:'ACTIVE', company_code:'".$companyCode."'}) ";
                        if (!empty($search)) {
                            $search = $this->appEncodeDecode->filterString($search);
                            $queryString .= "where (p.campaign_type =~ '(?i).*". $search .".*' or p.campaign_name =~ '(?i).*". $search .".*' or p.address =~ '(?i).*". $search .".*' or p.city =~ '(?i).*". $search .".*' or p.state =~ '(?i).*". $search .".*' or p.country =~ '(?i).*". $search .".*') ";
                        }
               $queryString .=  "WITH posts + collect({post:p,rel:r}) as rows
                UNWIND rows as row
                RETURN distinct(row) ORDER BY row.post.created_at DESC";
        if (!empty($limit) && !($limit < 0))
        {
            $queryString.=" skip ".$skip." limit ".self::LIMIT ;
        }
      //print_r($queryString).exit;
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
            if($result)
                return $result;
            
        }else{
            return false;
        }
    }
    
    public function checkCampaignContactRelation($campaignId='',$emailid='') {
        $queryString = "MATCH (c:Campaign)-[:CAMPAIGN_CONTACT]->(n:User) where ID(c)=".$campaignId." and n.emailid='".$emailid."' return n";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if(count($result)>0){
        return false;
        }else{
            return true;
        }
    }
    
    public function getPostCampaign($postId='') {
        $queryString = "MATCH (c:Campaign)-[:CAMPAIGN_POST]->(p:Post) where ID(p)=".$postId." return c";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result; 
    }
    
    public function getCompanyJobsCount($emailId='',$companyCode='') {
        $return = 0;
        if(!empty($emailId) && !empty($companyCode)){
            $queryString = "MATCH (u:User:Mintmesh{emailid:'".$emailId."'})-[r:INCLUDED]-(p:Post{status:'ACTIVE'})-[:POSTED_FOR]-(Company{companyCode:'".$companyCode."'}) return count(p)";
            //echo $queryString;exit;
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
        }
        return $return; 
    }
    
     public function mapJobFunctionToUser($jobFunctionId='', $userId='', $relationType=''){
            $queryString = "Match (p:User),(j:Job_Functions)
                                    where ID(p)=".$userId." and j.mysql_id=".$jobFunctionId."
                                    create unique (p)-[r:".$relationType."";

            $queryString.="]->(j)  set r.created_at='".date("Y-m-d H:i:s")."' return j";

            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
    }
    
    public function getCampaignJobIds($campaignId=''){
        $result = false;
        if(!empty($campaignId)){
            $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_POST]-(p:Post) where ID(c)=".$campaignId." return distinct(ID(p))";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
        }
        return $result;
    }
    
    public function checkCampaignContactsRelation($campaignId='', $emailId='') {
        $return = 0;
        if(!empty($emailId) && !empty($campaignId)){
            $queryString = "MATCH (u:User)-[r:CAMPAIGN_CONTACT]-(c:Campaign) where u.emailid='".$emailId."' and ID(c)=".$campaignId." return count(r)";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
        }
        return $return; 
    }
    public function checkPostAndCampaignRelation($postId='', $campaignId='') {
        $return = 0;
        if(!empty($postId) && !empty($campaignId)){
            $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_POST]-(p:Post) where ID(c)=".$campaignId." and ID(p)=".$postId." return p";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result;
            }
        }
        return $return; 
    }
    
    public function checkPostContactsRelation($postId='', $emailId='') {
        $return = 0;
        if(!empty($emailId) && !empty($postId)){
            $queryString = "match (u:User)-[r:INCLUDED]-(p:Post) where u.emailid='".$emailId."' and ID(p)=".$postId." return count(r)";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
        }
        return $return; 
    }
    
    public function getCompanyJobsList($emailId='',$companyCode='') {
        $return = 0;
        if(!empty($emailId) && !empty($companyCode)){
            $queryString = "MATCH (u:User:Mintmesh{emailid:'".$emailId."'})-[r:INCLUDED]-(p:Post{status:'ACTIVE'})-[:POSTED_FOR]-(Company{companyCode:'".$companyCode."'})
                WHERE  p.post_type <>'campaign' 
                WITH collect({post:p,rel:r}) as posts 
                OPTIONAL MATCH (u:User:Mintmesh{emailid:'".$emailId."'})-[r:CAMPAIGN_CONTACT]-(p:Campaign{status:'ACTIVE', company_code:'".$companyCode."'}) 
                WITH posts + collect({post:p,rel:r}) as rows
                UNWIND rows as row
                RETURN distinct(row)";            
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result;
            }
        }
        return $return; 
    }
    
    public function getCampaigns($companyCode='') {
        $result = false;
        if(!empty($companyCode)){
            $queryString = "match (c:Company)-[COMPANY_CREATED_CAMPAIGN]-(p:Campaign) where c.companyCode='".$companyCode."'  return ID(p)";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
        }
        return $result;
    }
    
    public function changePostStatus($postId=''){
        $queryString = "match (p:Post) where ID(p)=".$postId." set p.status ='ACTIVE' ";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        return $result;
    }
    
     public function companyPostsAutoConnectWithContact($pushData) {
        #form the details here
        $postId  = !empty($pushData['postId'])?$pushData['postId']:'';
        $contactEmailId = !empty($pushData['contact_emailid'])?$pushData['contact_emailid']:'';
        if(!empty($contactEmailId) && !empty($postId)){
            $inviteCount = $this->getPostInviteCount($postId);
            $relation    = $this->checkPostContactsRelation($postId, $contactEmailId);
            #check the condition for duplicat job post here
            if(empty($relation)){
                #creating relation with each job
                Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                $inviteCount+=1;
                $this->updatePostInviteCount($postId, $inviteCount);
            } 
        }
    }
    
    public function getUserByNeoID($neoId=0) {
        
        $return = 0;
        if(!empty($neoId)){
            $queryString = "MATCH (u:User) where ID(u)=".$neoId."  return u";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result;
            }
        }
        return $return; 
    }
    
    public function getGotReferredRelationDetailsById($gotReferredId = 0) {
        
        $return = 0;
        if(!empty($gotReferredId)){
            $queryString = "match (p:Post)-[r:GOT_REFERRED]-(u:User) where ID(r)=".$gotReferredId."  return distinct(r),u,p limit 1";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0];
            }
        }
        return $return; 
    }
    
    public function updateMobileReferCandidateResume($neoInput = array(), $gotReferredId = 0) {
        
        $return = 0;
        if(!empty($gotReferredId)){
            $queryString = "match (p:Post)-[r:GOT_REFERRED]-(u:User) where ID(r)=".$gotReferredId." ";
            $queryString.= "set r.document_id='".$neoInput['document_id']."' ,r.resume_path='".$neoInput['resume_path']."',r.resume_original_name='".$neoInput['resume_original_name']."' ";
            $queryString.= " return ID(r)";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0][0];
            }
        }
        return $return; 
    }
    
     public function campaignJobsList($campaignId = '', $page = 0, $searchName = '', $searchLocation = '', $searchExperience = '') {
        
        $return = array();
        if(!empty($campaignId)){
            $skip  = $limit = 0;
            if (!empty($page)){
                $limit = $page*10 ;
                $skip  = $limit - 10 ;
            }
            $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_POST]-(p:Post) where ID(c)=".$campaignId." ";
            if(!empty($searchName) || !empty($searchLocation) || !empty($searchExperience)){
                  $queryString .= " OPTIONAL MATCH (p)-[:ASSIGNED_EXPERIENCE_RANGE]->(x:ExperienceRange)
                                  WITH p,x
                                  WHERE
                                  (p.service_name =~ '(?i).*".$searchName.".*' and p.service_location =~ '(?i).*".$searchLocation.".*' and x.name=~'(?i).*". $searchExperience .".*') ";
              }
            $queryString .= " return distinct(p) ORDER BY p.created_at DESC ";
            if (!empty($limit) && !($limit < 0))
            {
                $queryString.=" skip ".$skip." limit ".self::LIMIT ;
            }
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            $return = $result;
        }
        return $return;
    }
}

?>
