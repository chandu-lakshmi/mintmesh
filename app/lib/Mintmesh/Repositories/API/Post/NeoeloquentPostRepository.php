<?php

namespace Mintmesh\Repositories\API\Post;

use NeoEnterpriseUser;
use DB;
use Config;
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
        $queryString.="]-(u) set p.created_at='" . date("Y-m-d H:i:s") . "' ";
        $queryString.=" , p.invited_count=0, p.total_referral_count=0, p.referral_accepted_count=0, p.referral_declined_count=0, p.referral_hired_count=0, p.referral_interviewed_count=0 return p";
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

    public function createPostContactsRelation($relationAttrs = array(), $postId = '', $company_code = '') {
        $queryString = "Match (b:Contact_bucket)-[r:COMPANY_CONTACT_IMPORTED]->(u:User),(p:Post)
        where r.company_code='" . $company_code . "' and b.mysql_id='" . $relationAttrs['bucket_id'] . "' and ID(p)=" . $postId . "";
        if ($queryString) {

            $queryString .= " create unique (p)-[:" . Config::get('constants.REFERRALS.INCLUDED') ;
                                   ;
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
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    public function jobsList($email = "", $company_code = "", $type = "", $page = 0, $search = "",$permission="",$postby="") {
        if (!empty($email) && !empty($company_code)) {
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            $queryString ='';
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            if (!empty($search)) {
                $search = $this->appEncodeDecode->filterString($search);
                //$queryString = "start p = node(*) where p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*'";
                if($permission == '1' && $postby == '0'){
                    $queryString .= "match (u:User)";
                }else{
                    $queryString .= "match (u:User {emailid:'" . $email . "'})";
                }
                $queryString .= "-[r:POSTED]-(p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $company_code . "'})
                         where p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*' ";
            } 
            else {
                if($permission == '1' && $postby == '0'){    
                 $queryString = "match (u:User)";
                }else{
                $queryString = "match (u:User {emailid:'" . $email . "'})";
                }           
             $queryString .= "-[r:POSTED]-(p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $company_code . "'}) ";

            }
            if (isset($type) && $type != '2') {
                $queryString .= "where p.free_service='" . $type . "' ";
            }
            $queryString .= "return p,count(p) as listCount,count(distinct(u)) ORDER BY p.created_at DESC";

            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            } 
            //echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
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

            $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) where ID(p)=" . $input['post_id'] . " ";
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
                      $queryString .=" set r.one_way_status='".Config::get('constants.REFERRALS.STATUSES.'.$status)."', r.p1_updated_at='".date("Y-m-d H:i:s")."'" ;
                      if($status == 'DECLINED'){
                         $queryString .= " ,p.referral_declined_count = p.referral_declined_count + 1";
                      }
                     
                  }
                  
                  else if ($post_way == 'round')
                  {
                     $queryString .=" set r.completed_status='".Config::get('constants.REFERRALS.STATUSES.'.$status)."' , r.status='".Config::get('constants.REFERRALS.STATUSES.COMPLETED')."', r.p3_updated_at='".date("Y-m-d H:i:s")."'" ;
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
                $queryString = "MATCH (u:".$label.")-[r:GOT_REFERRED]->(p:Post) where ID(p)=".$postId." 
                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
                                and r.referred_by='".$referredBy."' and r.relation_count='".$relationCount."'
				and u.".$where." ='".$referral."' and  r.awaiting_action_status = 'INTERVIEWED'
                                return p,r " ;
                $query  = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                $response  = $result->count();
                
                $queryString = "MATCH (u:".$label.")-[r:GOT_REFERRED]->(p:Post) where ID(p)=".$postId." 
                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
                                and r.referred_by='".$referredBy."' and r.relation_count='".$relationCount."'
				and u.".$where." ='".$referral."' 
                                set r.awaiting_action_status = '".$status."', r.awaiting_action_by = '".$userEmailId."',
                                r.awaiting_action_updated_at= '".date("Y-m-d H:i:s")."' " ;
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
        $queryString = "match (p:Post) where ID(p)=".$postId." return p";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result[0][0];
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
        $queryString.=" set r.created_at='".date("Y-m-d H:i:s")."', n.created_at='".date("Y-m-d H:i:s")."', n.created_by = '".$userEmailId."' ";
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
        $queryString.=" set r.created_at='".date("Y-m-d H:i:s")."', n.created_at='".date("Y-m-d H:i:s")."', n.created_by = '".$userEmailId."' ";
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
        $queryString.="]->(p) return c";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }
    
    public function campaignsList($email='',$input='',$page = 0,$permission='') {
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
            if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true' || !empty($input['location']) || $input['open'] == 'true' || $input['close'] == 'true'){
                $queryString .= " where"; 
            }
            if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true'){
                $queryString .= " (";
            }
            if(isset($input['mass_recruitment']) && !empty($input['mass_recruitment']) && $input['mass_recruitment'] == 'true'){
                $queryString .= "c.campaign_type='Mass Recruitment'";
            }
            if(isset($input['militery_veterans']) && !empty($input['militery_veterans']) && $input['militery_veterans'] == 'true'){
                if($input['mass_recruitment'] == 'true'){
                 $queryString .= " or";
                }
                $queryString .= " c.campaign_type='Military Veterans'";
            }
            if(isset($input['campus_hires']) && !empty($input['campus_hires']) && $input['campus_hires'] == 'true'){
                if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true'){
                    $queryString .= " or";
                }
                $queryString .= " c.campaign_type='Campus Hires'";
            }
            if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true'){
                $queryString .= ")";
            }
            if((isset($input['open']) && $input['open'] == 'true') || (isset($input['close']) && $input['close'] == 'true')){
                if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true'){
                    $queryString .= " and";
                }
                                    $queryString .= " (";
                if($input['open'] == 'true'){
                    $queryString .= "c.status='ACTIVE'";
                }if($input['close'] == 'true'){
                    if($input['open'] == 'true'){
                        $queryString .= "or ";
                }
                    $queryString .= "c.status='closed'";
                 }
                $queryString .= ")";

            }
            if(isset($input['location']) && !empty($input['location'])){
                foreach($input['location'] as $key=>$value){
                    if(isset($value) && !empty($value)){  
                        if($input['mass_recruitment'] == 'true' || $input['militery_veterans'] == 'true' || $input['campus_hires'] == 'true' || $input['open'] == 'true' || $input['close'] == 'true'){
                            if($key == '0'){
                                $queryString .= " and (";
                                }else{
                                    $queryString .= " or ";
                            }
                     }
                     else if($key != '0'){
                        $queryString .= " or ";
                     }
                    $queryString .= "c.city='".$value."'";
                    }
                 }
                 $queryString .= ")";
            }
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
                            create unique (c)-[:" . Config::get('constants.RELATIONS_TYPES.CAMPAIGN_CONTACT');
        if (!empty($relationAttrs)) {
            $queryString.="{";
            foreach ($relationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(u) return c";
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
        
        $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_SCHEDULE]-(s:CampaignSchedule) where ID(c)=".$campaignId." return distinct(s) ORDER BY s.start_date ASC";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    public function getCampaignPosts($campaignId='') {
        
        $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_POST]-(p:Post) where ID(c)=".$campaignId." return distinct(p)";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        return $result;
    }
    
    public function getCampaignBuckets($campaignId='') {
        
        $queryString = "MATCH (c:Campaign)-[r:CAMPAIGN_CONTACT]-(u) where ID(c)=".$campaignId." return distinct(r.bucket_id)";
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
            $queryString.=" n.updated_at='".date("Y-m-d H:i:s")."', n.updated_by = '".$userEmailId."' ";
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
        $queryString.=" n.updated_at='".date("Y-m-d H:i:s")."', n.updated_by = '".$userEmailId."' ";
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
    
}

?>
