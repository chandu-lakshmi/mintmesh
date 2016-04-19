<?php namespace Mintmesh\Repositories\API\Referrals;

use NeoUser;
use DB;
use Config;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode ;



class NeoeloquentReferralsRepository extends BaseRepository implements ReferralsRepository {

        protected $neoUser, $db_user, $db_pwd, $client,$appEncodeDecode, $db_host, $db_port;
        const LIMIT=10;
        public function __construct(NeoUser $neoUser,APPEncode $appEncodeDecode)
        {
                parent::__construct($neoUser);
                $this->neoUser = $neoUser;
                $this->db_user=Config::get('database.connections.neo4j.username') ;
                $this->db_pwd=Config::get('database.connections.neo4j.password') ;
                $this->db_host=Config::get('database.connections.neo4j.host') ;
                $this->db_port=Config::get('database.connections.neo4j.port') ;
                $this->client = new NeoClient($this->db_host, $this->db_port);
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
        }
        
        public function createService($input)
        {
            
        }
        public function createPostAndRelation($fromId, $neoInput=array(),$relationAttrs = array())
        {
            $queryString = "MATCH (u:User:Mintmesh)
                            WHERE ID(u) = ".$fromId."
                            CREATE (m:Post ";
            if (!empty($neoInput))
            {
                $queryString.="{";
                foreach ($neoInput as $k=>$v)
                {
                    if ($k == 'created_by')
                        $v= strtolower ($v);
                    $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                }
                $queryString = rtrim($queryString, ",") ;
                $queryString.="}";
            }
            $queryString.=")<-[:".Config::get('constants.REFERRALS.POSTED');
            if (!empty($relationAttrs))
            {
                $queryString.="{";
                foreach ($relationAttrs as $k=>$v)
                {
                    $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                }
                $queryString = rtrim($queryString, ",") ;
                $queryString.="}";
            }
            $queryString.="]-(u) set m.created_at='".date("Y-m-d H:i:s")."' " ;
            $queryString.="return m" ;
            //echo $queryString; exit;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            //$result = NeoUser::whereIn('emailid', $emails)->get();
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
        }
        
        public function excludeOrIncludeContact($serviceId=0, $userEmail="", $relationAttrs=array(), $state)
        {
            $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
            $queryString = "Match (u:User:Mintmesh) , (p:Post)
                            where ID(p)=".$serviceId."  and u.emailid='".$userEmail."'
                            create unique (p)-[r:".(($state=='exclude')?Config::get('constants.REFERRALS.EXCLUDED'):Config::get('constants.REFERRALS.INCLUDED'));
                        if (!empty($relationAttrs))
                        {
                            $queryString.="{";
                            foreach ($relationAttrs as $k=>$v)
                            {
                                $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                            }
                            $queryString = rtrim($queryString, ",") ;
                            $queryString.="}";
                        }
                        $queryString.="]->(u) set r.created_at='".date("Y-m-d H:i:s")."'";
           //echo $queryString ; exit;
                        $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            //$result = NeoUser::whereIn('emailid', $emails)->get();
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
                        
        }
        
        public function closePost($userEmail = "", $postId=0)
        {
            if (!empty($userEmail) && !empty ($postId))
            {
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                $queryString = "match (u:User:Mintmesh), (p:Post)
                                where ID(p)=".$postId." and u.emailid='".$userEmail."'
                                create unique (u)-[r:".Config::get('constants.REFERRALS.READ')."
                                ]->(p) set r.created_at='".date("Y-m-d H:i:s")."'" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function deactivatePost($userEmail = "", $postId=0)
        {
            if (!empty($userEmail) && !empty ($postId))
            {
                $result1 = $this->checkActivePost($postId);
                if (count($result1))
                {
                  return false ;   
                }
                else
                {
                    $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                    $queryString = "MATCH (p:Post)
                                    WHERE ID(p)=".$postId."
                                    set p.status='".Config::get('constants.REFERRALS.STATUSES.CLOSED')."' RETURN p" ;
                    $query = new CypherQuery($this->client, $queryString);
                    return $result = $query->getResultSet();
                }
                
            }
        }
        
        public function checkActivePost($postId=0)
        {
            if (!empty($postId))
            {
                //check if the post is in pending state
                $queryString1 = "match (p:Post)-[r:GOT_REFERRED]-() where r.one_way_status ='PENDING' and ID(p)=".$postId." return r";
                $query1 = new CypherQuery($this->client, $queryString1);
                return $result1 = $query1->getResultSet();
            }
        }
        public function getLatestPosts($email="")
        {
            if (!empty($email))
            {
               $email = $this->appEncodeDecode->filterString(strtolower($email));
               $queryString = "match (n:User:Mintmesh), (m:User:Mintmesh), (p:Post)
                                where n.emailid='".$email."' and m.emailid=p.created_by
                                and (n-[:ACCEPTED_CONNECTION]-m)
                                and not(n-[:EXCLUDED]-p) and not(n-[:READ]-p)
                                and  case p.service_type when 'in_location' then  n.location =~ ('.*' + p.service_location) else 1=1 end
                                 and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u) 
                                return p,count(r) ORDER BY p.created_at DESC limit 2" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return false ;
            }
        }
        
        public function getAllPosts($email="", $type="", $page=0)
        {
            $type_array = json_decode($type);
//            if (!empty($email) && !empty($type))
            if (!empty($email))
            {
                $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $skip = $limit - 10 ;
                }
                $filter_query = "";
                if($type_array) {
                    if(!(in_array('all', $type_array))) {
                        if((in_array('free', $type_array) || in_array('paid', $type_array)) && !(in_array('free', $type_array) && in_array('paid', $type_array)) ) {
                            $filter_query .= (in_array('free', $type_array))?' and p.free_service = "1" ':' and p.free_service = "0" ';
                        }
                    }
                    $type_array = array_flip($type_array);
                    unset($type_array['free']);
                    unset($type_array['paid']);
                    unset($type_array['all']);
                    $type_array = array_flip($type_array);
                    if(count($type_array) > 0) {
                        $filter_query .= " and p.service_scope IN ['".implode("','",$type_array)."'] ";
                    }                
                }
                //and p.service_scope='".$type."'
                //and r1.created_at <= p.created_at
                $email = $this->appEncodeDecode->filterString(strtolower($email));
                $queryString = "match (n:User:Mintmesh)-[r1:ACCEPTED_CONNECTION]-(m:User:Mintmesh)-[r2:POSTED]->(p:Post)
                                where n.emailid='".$email."' and m.emailid=p.created_by
                                and case p.included_set when '1' then  (n-[:INCLUDED]-p) else 1=1 end
                                and not(n-[:EXCLUDED]-p) 
                                ".$filter_query."
                                and  case p.service_type 
                                when 'in_location' then  lower(n.location) =~ ('.*' + lower(p.service_location)) else 1=1 end
                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u)
                                return p, count(distinct(u)) ORDER BY p.created_at DESC " ;
                if (!empty($limit) && !($limit < 0))
                {
                    $queryString.=" skip ".$skip." limit ".self::LIMIT ;
                }
                //echo $queryString ; exit;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return false ;
            }
        }
        
        public function getReferralsCount($relation='', $postId='', $referredBy='', $referredFor='')
        {
            if (!empty($postId) && !empty($referredBy) && !empty($referredFor) && !empty($relation))
            {
                $relationString = $relation ; //Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') ;
                $referredBy = $this->appEncodeDecode->filterString(strtolower($referredBy));
                $referredFor = $this->appEncodeDecode->filterString(strtolower($referredFor));
                //$statusList = "'".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'";
                $queryString = "Match (n)-[r:".$relationString."]->(m:Post) 
                                where ID(m)=".$postId." and r.referred_for='".$referredFor."' 
                                 and ('Mintmesh' IN labels(n) OR  'NonMintmesh' IN labels(n) OR 'User' IN labels(n)) 
                                and r.referred_by='".$referredBy."' ";
                $queryString.=" RETURN count(r)" ;
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if (isset($result[0]) && isset($result[0][0]))
                {
                    return $result[0][0];
                }
                else
                {
                    return 0;
                }
            }
        }
        
        public function getOldRelationsCount($postId=0, $userEmail="")
        {
            if (!empty($postId) && !empty($userEmail))
            {
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                $relationString = Config::get('constants.REFERRALS.GOT_REFERRED') ; //Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') ;
                $queryString = "Match (n:User)-[r:".$relationString."]->(m:Post) 
                                where ID(m)=".$postId." and n.emailid='".$userEmail."'";
                $queryString.=" RETURN count(r)" ;
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if (isset($result[0]) && isset($result[0][0]))
                {
                    return $result[0][0];
                }
                else
                {
                    return 0;
                }
            }
        }
        
        public function referContact($referred_by, $referred_for, $referredUser, $postId, $relationAttrs=array())
        {
            $referredUser = $this->appEncodeDecode->filterString(strtolower($referredUser));
            $queryString = "MATCH (u:User),(p:Post),(u1:User:Mintmesh{emailid:'".$referred_by."'})
                            WHERE u.emailid = '".$referredUser."' and ID(p)=".$postId."
                             and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
                            CREATE (u)-[r:".Config::get('constants.REFERRALS.GOT_REFERRED')." ";
            if (!empty($relationAttrs))
            {
                $queryString.="{";
                foreach ($relationAttrs as $k=>$v)
                {
                    $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                }
                $queryString = rtrim($queryString, ",") ;
                $queryString.="}";
            }
            $queryString.="]->(p) return count(p)" ;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (isset($result[0]) && isset($result[0][0]))
            {
                return $result[0][0];
            }
            else
            {
                return 0;
            }
        }
        
        public function getPostDetails($post_id=0)
        {
            if (!empty($post_id))
            {
                //p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
                $queryString = "match (p:Post) where ID(p)=".$post_id." 
                    return p" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return 0 ;
            }
        }
        
        public function getPostReferences($post_id=0, $limit=0, $page=0)
        {
            if (!empty($post_id))
            {
                $skip = 0 ;
                $queryLimit = self::LIMIT ;
                if (!empty($page))
                {
                    $skip = $limit = 0;
                    $limit = $page*10 ;
                    $skip = $limit - 10 ;
                }
                else if (!empty($limit))
                {
                    $queryLimit = $limit ;
                }
                //p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
                $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) 
                                where ID(p)=".$post_id." and
                                    ('Mintmesh' IN labels(u) OR  'NonMintmesh' IN labels(u) OR 'User' IN labels(u))
                                return u, r,labels(u) order by r.created_at desc" ;
                if (!empty($limit) && !($limit < 0))
                {
                    $queryString.=" skip ".$skip." limit ".$queryLimit ;
                }
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return 0 ;
            }
        }
        
        public function getMyReferrals($post_id=0, $email="")
        {
            if (!empty($post_id) && !empty($email))
            {
                $email = $this->appEncodeDecode->filterString(strtolower($email));
                $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) 
                                where r.referred_by='".$email."' and ID(p)=".$post_id."   
                                and ('Mintmesh' IN labels(u) OR  'NonMintmesh' IN labels(u) OR 'User' IN labels(u))
                                return u,r,labels(u)" ;
                //echo $queryString ;exit;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();   
            }
            else
            {
                return 0 ;
            }
        }
        public function editPost($input=array(), $id=0)
        {
            if (!empty($id))
            {
                $queryString = "match (p:Post) where ID(p)=".$id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' " ;
                if (!empty($input))
                {
                    $queryString.=" set " ;
                    foreach ($input as $k=>$v)
                    {
                        $queryString.="p.".$k."='".$v."'," ;
                    }
                    $queryString = rtrim($queryString,',');
                }
                $queryString.=" return count(p)";
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if (isset($result[0]) && isset($result[0][0]))
                {
                    return $result[0][0];
                }
                else
                {
                    return 0;
                }
            }
         }
         
         public function processPost($post_id=0, $referred_by="", $referral="", $status="", $post_way="", $relation_count=0,$nonMintmesh=0)
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
                  if ($post_way == 'one' && $status == Config::get('constants.REFERRALS.STATUSES.DECLINED'))
                  {
                      $queryString .=" set r.one_way_status='".Config::get('constants.REFERRALS.STATUSES.'.$status)."', r.p1_updated_at='".date("Y-m-d H:i:s")."'" ;
                  }
                  else if ($post_way == 'round')
                  {
                     $queryString .=" set r.completed_status='".Config::get('constants.REFERRALS.STATUSES.'.$status)."' , r.status='".Config::get('constants.REFERRALS.STATUSES.COMPLETED')."', r.p3_updated_at='".date("Y-m-d H:i:s")."'" ;
                  }
                  $queryString.=" return p,r" ;
                  $query = new CypherQuery($this->client, $queryString);
                  return $result = $query->getResultSet();
                                 
             }
            
         }
         
         public function getPostStatusDetails($input=array())
         {
            
             if (!empty($input['post_id']) && !empty($input['referred_by']) && !empty($input['referral']) && !empty($input['from_user']) && !empty($input['relation_count']))
             {
                 $input['referred_by'] = $this->appEncodeDecode->filterString(strtolower($input['referred_by']));;
                 $input['referral'] = $this->appEncodeDecode->filterString(strtolower($input['referral']));;
                 $input['from_user'] = $this->appEncodeDecode->filterString(strtolower($input['from_user']));
                 if (!empty($input['referred_by_phone'])){
                     $queryString = "Match (u:NonMintmesh)-[r:GOT_REFERRED]->(p:Post) 
                                 where ID(p)=".$input['post_id']." 
                                  and u.phone='".$input['referral']."' and 
                                  r.referred_by='".$input['referred_by']."' and r.referred_for='".$input['from_user']."'
                                  and r.relation_count='".$input['relation_count']."' return r,u,p,labels(u)" ;
                 }else{
                     $queryString = "Match (u:User)-[r:GOT_REFERRED]->(p:Post) 
                                 where ID(p)=".$input['post_id']." 
                                  and u.emailid='".$input['referral']."' and 
                                  r.referred_by='".$input['referred_by']."' and r.referred_for='".$input['from_user']."'
                                  and r.relation_count='".$input['relation_count']."' return r,u,p,labels(u)" ;
                 }
                 $query = new CypherQuery($this->client, $queryString);
                 return $result = $query->getResultSet();
             }
         }
         
        
         public function getMyReferralContacts($input=array())
         {
            if (!empty($input['other_email']) && !empty($input['email']))
             {
                if (!empty($input['limit']) && !emptY($input['suggestion']))//to retrieve sugestions
                {
                    $skip=0;
                    $limit=5 ;
                }
                 $input['other_email'] = $this->appEncodeDecode->filterString(strtolower($input['other_email']));
                 $input['email'] = $this->appEncodeDecode->filterString(strtolower($input['email']));
                 $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh), (o:User:Mintmesh)
                                    where m.emailid='".$input['email']."' and n.emailid='".$input['other_email']."'
                                     and (m)-[:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(o)    
                                    and not (n-[:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-o)
                                    RETURN DISTINCT o order by o.firstname asc " ;
                 
                 if (!empty($input['suggestion']))
                 {
                     $queryString = "Match (m:User:Mintmesh {emailid:'".$input['email']."'})-[:ACCEPTED_CONNECTION]-(o:User:Mintmesh), (n:User:Mintmesh {emailid:'".$input['other_email']."'}),(p:Post)
                                    where   not (n-[:ACCEPTED_CONNECTION]-o)
                                    and lower(o.location) =~ ('.*' + lower(p.service_location)) and ID(p)=".$input['post_id']."
                                    RETURN DISTINCT o order by o.firstname asc";
                     /*
                     $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh), (o:User:Mintmesh),(p:Post)
                                    where m.emailid='".$input['email']."' and n.emailid='".$input['other_email']."'
                                     and (m)-[:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(o)    
                                    and not (n-[:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-o)
                                    and lower(o.location) =~ ('.*' + lower(p.service_location)) and ID(p)=".$input['post_id']."
                                    RETURN DISTINCT o order by o.firstname asc " ;*/
                 }
                 if (!empty($limit) && !($limit < 0))
                 {
                    $queryString.=" skip ".$skip." limit ".$limit ;
                 }
                 $query = new CypherQuery($this->client, $queryString);
                 return $result = $query->getResultSet();
             }
             else
             {
                 return 0 ;
             }
         }
         //get post rerferrals in pendin and accepted state
         public function getPostReferrals($post_id=0,$referred_by="")
         {
             if (!empty($post_id) && !empty($referred_by))
             {
                 //and p.status='ACTIVE'
                 $queryString = "match (m1:User:Mintmesh),(p:Post) where ID(p)=".$post_id." with m1,p
                                match (m1)-[r1:GOT_REFERRED]-(p) where r1.referred_by='".$referred_by."'
                                with max(r1.relation_count) as rel_count ,m1,r1  
                                match (m1)-[r1:GOT_REFERRED]-(p) 
                                where r1.relation_count=rel_count  
                                and r1.one_way_status in ['".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'] return m1" ;
                 /*$queryString = "match (m:User),(m1:User),(p:Post) where ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' with m,m1,p
                                match (m1)-[r1:GOT_REFERRED]-(p) where m1.emailid=m.emailid 
                                with max(r1.relation_count) as rel_count ,m  
                                match (m)-[r:GOT_REFERRED]-(p) 
                                where r.relation_count=rel_count and r.referred_by='".$referred_by."' and r.one_way_status in ['".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'] return m" ;
                 */
                  //$queryString = "match (m:User)-[r:GOT_REFERRED]-(p:Post) where 
                  
                 //           ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' return m" ;
                 $query = new CypherQuery($this->client, $queryString);
                 return $result = $query->getResultSet();
             }
             else
             {
                 return 0;
             }
             
         }
         
         public function searchPeople($userEmail="",$searchInput=array())
         {
             if (!empty($userEmail))
             {
                 $skills = array();
                 $queryString = "match (u:User:Mintmesh)-[:ACCEPTED_CONNECTION]-(u1:User)-[:ACCEPTED_CONNECTION]-(u2:User)";
                 if (!empty($searchInput['skills']))
                 {
                     $skills = json_decode($searchInput['skills']);
                     if (!empty($skills) && is_array($skills) && count($skills) <=3)
                     {
                         $queryString.=",(s1:Skills{mysql_id:$skills[0]})";
                         if (!empty($skills[1]))
                         {
                             $queryString.=",(s2:Skills{mysql_id:$skills[1]})" ;
                         }
                         if (!empty($skills[2]))
                         {
                             $queryString.=",(s3:Skills{mysql_id:$skills[2]})" ;
                         }
                     }
                     if (!empty($skills) && is_array($skills) && count($skills) > 3)
                     {
                         $queryString.="-[:KNOWS]-(s:Skills) " ;
                     }
                 }
                $queryString.=" where u.emailid='".$userEmail."' and " ;
                //if (!empty($searchInput))
                //{
                    $queryString.=!empty($searchInput['fullname'])?"lower(u2.fullname)=~ '.*".strtolower($searchInput['fullname']).".*' AND ":"";
                    $queryString.=!empty($searchInput['job_function'])?"u2.job_function='".$searchInput['job_function']."' AND ":"";
                    $queryString.=!empty($searchInput['industry'])?"u2.industry='".$searchInput['industry']."' AND ":"";
                    $queryString.=!empty($searchInput['company'])?"lower(u2.company)=~ '.*".strtolower($searchInput['company']).".*' AND ":"";
                    $queryString.=!empty($searchInput['location'])?"lower(u2.location)=~ '.*".strtolower($searchInput['location'])."' AND ":"";
                    if (!empty($skills) && is_array($skills) && count($skills) <=3)
                     {
                         $queryString.="( (u2)-[:KNOWS]-(s1)";
                         if (!empty($skills[1]))
                         {
                             $queryString.=" and (u2)-[:KNOWS]-(s2)" ;
                         }
                         if (!empty($skills[2]))
                         {
                             $queryString.=" and (u2)-[:KNOWS]-(s3)" ;
                         }
                         $queryString.=") and " ;
                     }
                    if (!empty($skills) && is_array($skills) && count($skills) > 3)
                    {
                        $queryString.="s.mysql_id IN [".implode(",",$skills)."] and";
                    }
                //}
                
                $queryString.=" not (u)-[:ACCEPTED_CONNECTION]-(u2) return DISTINCT(u2) order by u2.firstname asc";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
             }
             else
             {
                 return 0 ;
             }
             
         }
         
         public function getMutualPeople($userEmail1="", $userEmail2="")
         {
             if (!empty($userEmail1) && !empty($userEmail2))
             {
                 $queryString = "match (u:User:Mintmesh)-[:ACCEPTED_CONNECTION]-(u1:User)-[:ACCEPTED_CONNECTION]-(u2:User)
                 where u.emailid='".$userEmail2."' and u2.emailid='".$userEmail1."' return distinct(u1) order by u1.firstname asc";
             $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
             }
             else
             {
                 return 0;
             }
         }
         
         public function getPostAndReferralDetails($post_id=0,$referred_by="",$userEmail="")
         {
             if (!empty($post_id) && !empty($referred_by) && !empty($userEmail))
             {
                $query1 = "match (p:Post)-[r:GOT_REFERRED]-(u:User) where ID(p)=".$post_id." 
                            and u.emailid='".$userEmail."' and r.referred_by='".$referred_by."' return max(r.relation_count) as count"  ;
                $max_count = 1 ;
                $query = new CypherQuery($this->client, $query1);
                $countResult = $query->getResultSet();  
                if (!empty($countResult[0]) && !empty($countResult[0][0]))
                {
                    $max_count =  $countResult[0][0] ;
                }
                $queryString = "match (m1)-[r1:GOT_REFERRED]-(p) where ID(p)=".$post_id." and m1.emailid='".$userEmail."'
                                    and r1.referred_by='".$referred_by."'
                                    and r1.relation_count='".$max_count."'  
                                    return r1,p" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();    
             }
             else
             {
                 return 0 ;
             }
         }
         
         public function getPostReferralsCount($postId=0)
         {
             if (!empty($postId))
             {
                 $queryString = "match  (u)-[r:GOT_REFERRED]->(p:Post)
                                 where ID(p)=".$postId." and ('Mintmesh' IN labels(u) OR  'NonMintmesh' IN labels(u) OR 'User' IN labels(u))"
                         . " return count(u)" ;
                 $query = new CypherQuery($this->client, $queryString);
                $countResult = $query->getResultSet(); 
                if (!empty($countResult[0]) && !empty($countResult[0][0]))
                {
                    return $countResult[0][0] ;
                }
                else { return 0 ;}
             }
             else
             {
                 return 0 ;
             }
         }
         
         public function updatePostPaymentStatus($relation=0,$status='', $is_self_referred=0)
         {
             if (!empty($relation))
             {
                 $queryString = "match (p:Post)-[r:GOT_REFERRED]-(u) where ID(r)=".$relation."
                                  set ";
                 if (!empty($status))
                 {
                     $queryString.= "r.payment_status='".$status."'," ;
                 }
                 if (!empty($is_self_referred)){
                     $queryString.= "r.completed_status='".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."'," ;
                 }
                 $queryString.= " r.one_way_status='".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."', r.p1_updated_at='".date("Y-m-d H:i:s")."'" ; 
                 $query = new CypherQuery($this->client, $queryString);
                 return $result = $query->getResultSet(); 
             }
             else
             {
                 return 0;
             }
         }
         
         public function getAllReferrals($userEmail='', $page=0)
         {
             if (!empty($userEmail))
             {
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $skip = $limit - 10 ;
                }
                $relations = array(Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'), Config::get('constants.REFERRALS.GOT_REFERRED'));
                $relationString = implode("|",$relations) ;
                $queryString="match (u)-[r:".$relationString."]->(p) where ('Mintmesh' IN labels(u) OR 'NonMintmesh' IN labels(u) OR 'User' IN labels(u)) and case type(r) when '".Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION')."' then u.emailid='".$userEmail."' else r.referred_by='".$userEmail."' end return r, type(r) as relationName, p, u, labels(u) order by r.created_at desc";
                if (!empty($limit) && !($limit < 0))
                {
                    $queryString.=" skip ".$skip." limit ".self::LIMIT ;
                }
                //echo $queryString ;exit;
                 $query = new CypherQuery($this->client, $queryString);
                 return $result = $query->getResultSet(); 
             }
             else
             {
                 return false ;
             }
         }
         
         public function getRequestReferenceRelationId($from='', $to='', $for='',$relation_count=0)
        {
            $from = $this->appEncodeDecode->filterString(strtolower($from)) ;
            $to = $this->appEncodeDecode->filterString(strtolower($to)) ;
            $for = $this->appEncodeDecode->filterString(strtolower($for)) ;
            $queryString = "match (u:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE')."]-(u1:User:Mintmesh) "
                            . "where  u.emailid='".$from."' and u1.emailid='".$to."' and r.request_for_emailid='".$for."'"
                    . " and r.request_count='".$relation_count."' return ID(r) as relation_id" ;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
            
        }
        
        public function getServiceDetailsByCode($serviceCode)
        {
            $queryString = "match (u:User:Mintmesh)-[r:".Config::get('constants.REFERRALS.POSTED')."]->(p:Post) where p.service_code='".$serviceCode."' return u,p limit 1";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
         
        public function referContactByPhone($referred_by, $referred_for, $referredUser, $postId, $relationAttrs=array())
        {
            $referredUser = $this->appEncodeDecode->filterString(strtolower($referredUser));
            $queryString = "MATCH (u:NonMintmesh),(p:Post),(u1:User:Mintmesh{emailid:'".$referred_by."'})
                            WHERE u.phone = '".$referredUser."' and ID(p)=".$postId."
                             and (u1)-[:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]->(u)
                             and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
                            CREATE (u)-[r:".Config::get('constants.REFERRALS.GOT_REFERRED')." ";
            if (!empty($relationAttrs))
            {
                $queryString.="{";
                foreach ($relationAttrs as $k=>$v)
                {
                    $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                }
                $queryString = rtrim($queryString, ",") ;
                $queryString.="}";
            }
            $queryString.="]->(p) return count(p)" ;
            
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (isset($result[0]) && isset($result[0][0]))
            {
                return $result[0][0];
            }
            else
            {
                return 0;
            }
        }
		
		//get post rerferrals in pendin and accepted state
         public function getMyNonMintmeshReferrals($post_id=0,$referred_by="")
         {
             if (!empty($post_id) && !empty($referred_by))
             {
                 //and p.status='ACTIVE'
                 $queryString = "match (m1),(p:Post) where ID(p)=".$post_id." with m1,p
                                match (m1)-[r1:GOT_REFERRED]-(p) where r1.referred_by='".$referred_by."' 
                                 and ('NonMintmesh' IN labels(m1) OR 'User' IN labels(m1))
								with max(r1.relation_count) as rel_count ,m1,r1  
                                match (m1)-[r1:GOT_REFERRED]-(p) 
                                where r1.relation_count=rel_count  
                                and r1.one_way_status in ['".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'] return distinct(m1), labels(m1)" ;
                 /*$queryString = "match (m:User),(m1:User),(p:Post) where ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' with m,m1,p
                                match (m1)-[r1:GOT_REFERRED]-(p) where m1.emailid=m.emailid 
                                with max(r1.relation_count) as rel_count ,m  
                                match (m)-[r:GOT_REFERRED]-(p) 
                                where r.relation_count=rel_count and r.referred_by='".$referred_by."' and r.one_way_status in ['".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'] return m" ;
                 */
                  //$queryString = "match (m:User)-[r:GOT_REFERRED]-(p:Post) where 
                  
                 //           ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' return m" ;
                 //echo $queryString ; exit;
                 $query = new CypherQuery($this->client, $queryString);
                 return $result = $query->getResultSet();
             }
             else
             {
                 return 0;
             }
             
         }
         
         public function getPostAndReferralDetailsNonMintmesh($post_id=0,$referred_by="",$userPhone="")
         {
             if (!empty($post_id) && !empty($referred_by) && !empty($userPhone))
             {
                $query1 = "match (p:Post)-[r:GOT_REFERRED]-(u:NonMintmesh) where ID(p)=".$post_id." 
                            and u.phone='".$userPhone."' and r.referred_by='".$referred_by."' return max(r.relation_count) as count"  ;
                $max_count = 1 ;
                $query = new CypherQuery($this->client, $query1);
                $countResult = $query->getResultSet();  
                if (!empty($countResult[0]) && !empty($countResult[0][0]))
                {
                    $max_count =  $countResult[0][0] ;
                }
                $queryString = "match (m1:NonMintmesh)-[r1:GOT_REFERRED]-(p) where ID(p)=".$post_id." and m1.phone='".$userPhone."'
                                    and r1.referred_by='".$referred_by."'
                                    and r1.relation_count='".$max_count."'  
                                    return r1,p" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();    
             }
             else
             {
                 return 0 ;
             }
         }
         
         public function getExcludedPostsList($userEmail='', $postsIds = array()){
             $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
             if (!empty($userEmail)){
                 $postsIds = implode(",",$postsIds);
                 $queryString = "match (u:User:Mintmesh)-[r:EXCLUDED]-(p:Post) where u.emailid='".$userEmail."' and ID(p) IN [".$postsIds."] return ID(p) as post_id";
                 $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
             }else{
                 return 0;
             }
         }
         
        public function getAllPostsV3($email="", $type="", $page=0)
        {
//            if (!empty($email) && !empty($type))
//            {
//                $skip = $limit = 0;
//                if (!empty($page))
//                {
//                    $limit = $page*10 ;
//                    $skip = $limit - 10 ;
//                }
//                //and p.service_scope='".$type."'
//                //and r1.created_at <= p.created_at
//                $email = $this->appEncodeDecode->filterString(strtolower($email));
//                $queryString = "match (n:User:Mintmesh)-[r1:ACCEPTED_CONNECTION]-(m:User:Mintmesh)-[r2:POSTED]->(p:Post)
//                                where n.emailid='".$email."' and m.emailid=p.created_by
//                                and not(n-[:EXCLUDED]-p)
//                                and  case p.service_type when 'in_location' then  lower(n.location) =~ ('.*' + lower(p.service_location)) else 1=1 end
//                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
//                                return p ORDER BY p.created_at DESC " ;
//                //OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u)
//                //echo $queryString ; exit;
//                /*$queryString = "match (n:User:Mintmesh), (m:User:Mintmesh), (p:Post)
//                                where n.emailid='".$email."' and m.emailid=p.created_by
//                                and (n-[:ACCEPTED_CONNECTION]-m)
//                                and not(n-[:EXCLUDED]-p) 
//                                and  case p.service_type when 'in_location' then  lower(n.location) =~ ('.*' + lower(p.service_location)) else 1=1 end
//                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
//                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u)
//                                return p, count(distinct(u)) ORDER BY p.created_at DESC " ;*/
//                if (!empty($limit) && !($limit < 0))
//                {
//                    $queryString.=" skip ".$skip." limit ".self::LIMIT ;
//                }
//                //echo $queryString ; exit;
//                $query = new CypherQuery($this->client, $queryString);
//                return $result = $query->getResultSet();
//            }
//            else
//            {
//                return false ;
//            }
            $type_array = json_decode($type);
//            if (!empty($email) && !empty($type))
            if (!empty($email))
            {
                $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $skip = $limit - 10 ;
                }
                $filter_query = "";
                if($type_array) {
                    if(!(in_array('all', $type_array))) {
                        if((in_array('free', $type_array) || in_array('paid', $type_array)) && !(in_array('free', $type_array) && in_array('paid', $type_array)) ) {
                            $filter_query .= (in_array('free', $type_array))?' and p.free_service = "1" ':' and p.free_service = "0" ';
                        }
                    }
                    $type_array = array_flip($type_array);
                    unset($type_array['free']);
                    unset($type_array['paid']);
                    unset($type_array['all']);
                    $type_array = array_flip($type_array);
                    if(count($type_array) > 0) {
                        $filter_query .= " and p.service_scope IN ['".implode("','",$type_array)."'] ";
                    }                
                }
                //and p.service_scope='".$type."'
                //and r1.created_at <= p.created_at
                $email = $this->appEncodeDecode->filterString(strtolower($email));
                $queryString = "match (n:User:Mintmesh)-[r1:ACCEPTED_CONNECTION]-(m:User:Mintmesh)-[r2:POSTED]->(p:Post)
                                where n.emailid='".$email."' and m.emailid=p.created_by
                                and case p.included_set when '1' then  (n-[:INCLUDED]-p) else 1=1 end
                                and not(n-[:EXCLUDED]-p) 
                                ".$filter_query."
                                and  case p.service_type 
                                when 'in_location' then  lower(n.location) =~ ('.*' + lower(p.service_location)) else 1=1 end
                                and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' 
                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u)
                                return p, count(distinct(u)) ORDER BY p.created_at DESC " ;
                if (!empty($limit) && !($limit < 0))
                {
                    $queryString.=" skip ".$skip." limit ".self::LIMIT ;
                }
                //echo $queryString ; exit;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return false ;
            }
        }
        
        public function getReferralsListCounts($userEmail='', $postsIds = array()){
             $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
             if (!empty($userEmail)){
                 $postsIds = implode(",",$postsIds);
                 $queryString = "MATCH (p:Post)-[r:GOT_REFERRED]-(u) where  ID(p) IN [".$postsIds."] return ID(p) as post_id,count(distinct(u)) ";
                 $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
             }else{
                 return 0;
             }
         }

}
?>
