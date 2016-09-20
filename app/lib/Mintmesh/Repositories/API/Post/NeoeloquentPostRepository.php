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
        $queryString = "Match (b:Contact_bucket)-[r:COMPANY_CONTACT_IMPORTED]->(u:User)
        where r.company_code='" . $company_code . "' and b.mysql_id='" . $relationAttrs['bucket_id'] . "' ";
        if ($queryString) {

            $queryString .= "Match (p:Post)
                                    where ID(p)=" . $postId . "
                                    create unique (p)-[:" . Config::get('constants.REFERRALS.INCLUDED');
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

    public function jobsList($email = "", $company_code = "", $type = "", $page = 0, $search = "") {
        if (!empty($email) && !empty($company_code)) {
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            if (!empty($search)) {
                $search = $this->appEncodeDecode->filterString($search);
                $queryString = "start p = node(*) where p.service_name =~ '(?i).*". $search .".*' ";

                $queryString .= "match (u:User {emailid:'" . $email . "'})-[r:POSTED]-(p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $company_code . "'}) ";
            } else {
                $queryString = "match (u:User {emailid:'" . $email . "'})-[r:POSTED]-(p:Post)-[:POSTED_FOR]-(:Company{companyCode:'" . $company_code . "'}) ";
            }
            if (isset($type) && $type != '2') {
                $queryString .= "where p.free_service='" . $type . "' ";
            }

            $queryString .= "return p,count(p) as listCount,count(distinct(u)) ORDER BY p.created_at DESC";

            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            }  
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }

    public function jobsDetails($jobid = "", $company_code = "") {
        if (!empty($jobid)) {

            $queryString = "match (p:Post),(n:Company) where ID(p)=" . $jobid . " AND n.companyCode='" . $company_code . "' return p,n";
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
}

?>
