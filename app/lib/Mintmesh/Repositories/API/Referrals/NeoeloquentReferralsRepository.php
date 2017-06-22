<?php

namespace Mintmesh\Repositories\API\Referrals;

use NeoUser;
use DB;
use Config;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode;

class NeoeloquentReferralsRepository extends BaseRepository implements ReferralsRepository {

    protected $neoUser, $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;

    const LIMIT = 10;

    public function __construct(NeoUser $neoUser, APPEncode $appEncodeDecode) {
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

    public function createPostAndRelation($fromId, $neoInput = array(), $relationAttrs = array()) {
        $queryString = "MATCH (u:User:Mintmesh)
                            WHERE ID(u) = " . $fromId . "
                            CREATE (m:Post ";
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
        $queryString.="]-(u) set m.created_at='" . date("Y-m-d H:i:s") . "' ";
        $queryString.="return m";
        //echo $queryString; exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        //$result = NeoUser::whereIn('emailid', $emails)->get();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }

    public function excludeOrIncludeContact($serviceId = 0, $userEmail = "", $relationAttrs = array(), $state) {
        $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
        $queryString = "Match (u:User:Mintmesh) , (p:Post)
                            where ID(p)=" . $serviceId . "  and u.emailid='" . $userEmail . "'
                            create unique (p)-[r:" . (($state == 'exclude') ? Config::get('constants.REFERRALS.EXCLUDED') : Config::get('constants.REFERRALS.INCLUDED'));
        if (!empty($relationAttrs)) {
            $queryString.="{";
            foreach ($relationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(u) set r.created_at='" . date("Y-m-d H:i:s") . "'";
        //echo $queryString ; exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        //$result = NeoUser::whereIn('emailid', $emails)->get();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }

    public function closePost($userEmail = "", $postId = 0) {
        if (!empty($userEmail) && !empty($postId)) {
            $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
            $queryString = "match (u:User:Mintmesh), (p:Post)
                                where ID(p)=" . $postId . " and u.emailid='" . $userEmail . "'
                                create unique (u)-[r:" . Config::get('constants.REFERRALS.READ') . "
                                ]->(p) set r.created_at='" . date("Y-m-d H:i:s") . "'";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
    }

    public function deactivatePost($userEmail = "", $postId = 0) {
        if (!empty($userEmail) && !empty($postId)) {
            $result1 = $this->checkActivePost($postId);
            if (count($result1)) {
                return false;
            } else {
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                $queryString = "MATCH (p:Post)
                                    WHERE ID(p)=" . $postId . "
                                    set p.status='" . Config::get('constants.REFERRALS.STATUSES.CLOSED') . "' RETURN p";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
    }

    public function checkActivePost($postId = 0) {
        if (!empty($postId)) {
            //check if the post is in pending state
            $queryString1 = "match (p:Post)-[r:GOT_REFERRED]-() where r.one_way_status ='PENDING' and ID(p)=" . $postId . " return r";
            $query1 = new CypherQuery($this->client, $queryString1);
            return $result1 = $query1->getResultSet();
        }
    }

    public function getLatestPosts($email = "") {
        if (!empty($email)) {
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            $queryString = "match (n:User:Mintmesh), (m:User:Mintmesh), (p:Post)
                                where n.emailid='" . $email . "' and m.emailid=p.created_by
                                and (n-[:ACCEPTED_CONNECTION]-m)
                                and not(n-[:EXCLUDED]-p) and not(n-[:READ]-p)
                                and  case p.service_type when 'in_location' then  n.location =~ ('.*' + p.service_location) else 1=1 end
                                 and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "' 
                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u) 
                                return p,count(r) ORDER BY p.created_at DESC limit 2";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }

    public function getAllPosts($email = "", $type = "", $page = 0, $search = '') {
        $type_array = json_decode($type);
//            if (!empty($email) && !empty($type))
        if (!empty($email)) {
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            $filter_query = "";
            if ($type_array) {
                if (!(in_array('all', $type_array))) {
                    if ((in_array('free', $type_array) || in_array('paid', $type_array)) && !(in_array('free', $type_array) && in_array('paid', $type_array))) {
                        $filter_query .= (in_array('free', $type_array)) ? ' and p.free_service = "1" ' : ' and p.free_service = "0" ';
                    }
                }
                $type_array = array_flip($type_array);
                unset($type_array['free']);
                unset($type_array['paid']);
                unset($type_array['all']);
                $type_array = array_flip($type_array);
                if (count($type_array) > 0) {
                    $filter_query .= " and p.service_scope IN ['" . implode("','", $type_array) . "'] ";
                }
            }
            //and p.service_scope='".$type."'
            //and r1.created_at <= p.created_at
            //match (u:User:Mintmesh {emailid:'".$email."'})-[r:INCLUDED]-(p:Post {status:'ACTIVE'})-[:POSTED_FOR]-(m:Company) 
            //".$filter_query." return p,count(distinct(u)),m ORDER BY p.created_at DESC
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            $queryString = "MATCH (u:User:Mintmesh)-[r1:CONNECTED_TO_COMPANY]-(m:Company)-[:POSTED_FOR]-(p:Post{status:'ACTIVE'}) 
                                where u.emailid='" . $email . "' and p.service_name =~ '(?i).*" . $search . ".*' " . $filter_query . " return p,count(distinct(u)),m ORDER BY p.created_at DESC
                                UNION
                                match (n:User:Mintmesh)-[r1:ACCEPTED_CONNECTION]-(m:User:Mintmesh)-[r2:POSTED]->(p:Post)
                                where n.emailid='" . $email . "' and m.emailid=p.created_by and p.created_by<>'" . $email . "'
                                and case p.included_set when '1' then  (n-[:INCLUDED]-p) else 1=1 end
                                and not(n-[:EXCLUDED]-p) 
                                and p.service_name =~ '(?i).*" . $search . ".*'
                                " . $filter_query . "
                                and  case p.service_type 
                                when 'in_location' then  lower(n.location) =~ ('.*' + lower(p.service_location)) else 1=1 end
                                and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "' 
                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u)
                                return p, count(distinct(u)),m ORDER BY p.created_at DESC ";
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            }
            //echo $queryString ; exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }

    public function getReferralsCount($relation = '', $postId = '', $referredBy = '', $referredFor = '') {
        if (!empty($postId) && !empty($referredBy) && !empty($referredFor) && !empty($relation)) {
            $relationString = $relation; //Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') ;
            $referredBy = $this->appEncodeDecode->filterString(strtolower($referredBy));
            $referredFor = $this->appEncodeDecode->filterString(strtolower($referredFor));
            //$statusList = "'".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'";
            $queryString = "Match (n)-[r:" . $relationString . "]->(m:Post) 
                                where ID(m)=" . $postId . " and r.referred_for='" . $referredFor . "' 
                                 and ('Mintmesh' IN labels(n) OR  'NonMintmesh' IN labels(n) OR 'User' IN labels(n)) 
                                and r.referred_by='" . $referredBy . "' ";
            $queryString.=" RETURN count(r)";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (isset($result[0]) && isset($result[0][0])) {
                return $result[0][0];
            } else {
                return 0;
            }
        }
    }

    public function getOldRelationsCount($postId = 0, $userEmail = "", $isNonMintmesh = 0) {
        if (!empty($postId) && !empty($userEmail)) {
            $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
            $relationString = Config::get('constants.REFERRALS.GOT_REFERRED'); //Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') ;
            $label = !empty($isNonMintmesh) ? "NonMintmesh" : "User";
            $whereLabel = !empty($isNonMintmesh) ? "replace(n.phone, '-', '')" : "n.emailid";
            $queryString = "Match (n:" . $label . ")-[r:" . $relationString . "]->(m:Post) 
                                where ID(m)=" . $postId . " and " . $whereLabel . "='" . $userEmail . "'";
            $queryString.=" RETURN count(r)";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (isset($result[0]) && isset($result[0][0])) {
                return $result[0][0];
            } else {
                return 0;
            }
        }
    }

    public function referContact($referred_by, $referred_for, $referredUser, $postId, $relationAttrs = array()) {
        $referredUser = $this->appEncodeDecode->filterString(strtolower($referredUser));
        $queryString = "MATCH (u:User),(p:Post),(u1:User:Mintmesh{emailid:'" . $referred_by . "'})
                            WHERE u.emailid = '" . $referredUser . "' and ID(p)=" . $postId . "
                             and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "' 
                            CREATE (u)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED') . " ";
        if (!empty($relationAttrs)) {
            $queryString.="{";
            foreach ($relationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(p) set p.total_referral_count = p.total_referral_count + 1 , r.resume_parsed =0 return count(p),ID(r)";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if (isset($result[0]) && isset($result[0][0])) {
            return $result;
        } else {
            return 0;
        }
    }

    public function getPostDetails($post_id = 0, $userEmailID = '') {
        if (!empty($post_id)) {
            //p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
            $queryString = "match (p:Post)-[r:INCLUDED]-(u:User:Mintmesh) where ID(p)=" . $post_id . " ";
            if (!empty($userEmailID)) {   //post read status update here
                $queryString.= " and u.emailid = '" . $userEmailID . "' set r.post_read_status =1 ";
            }
            $queryString.= " return p";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getPostReferences($post_id = 0, $limit = 0, $page = 0) {
        if (!empty($post_id)) {
            $skip = 0;
            $queryLimit = self::LIMIT;
            if (!empty($page)) {
                $skip = $limit = 0;
                $limit = $page * 10;
                $skip = $limit - 10;
            } else if (!empty($limit)) {
                $queryLimit = $limit;
            }
            //p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."'
            $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) 
                                where ID(p)=" . $post_id . " and
                                    ('Mintmesh' IN labels(u) OR  'NonMintmesh' IN labels(u) OR 'User' IN labels(u))
                                return u, r,labels(u) order by r.created_at desc";
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . $queryLimit;
            }
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getMyReferrals($post_id = 0, $email = "") {
        if (!empty($post_id) && !empty($email)) {
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post) 
                                where r.referred_by='" . $email . "' and ID(p)=" . $post_id . "   
                                and ('Mintmesh' IN labels(u) OR  'NonMintmesh' IN labels(u) OR 'User' IN labels(u))
                                return u,r,labels(u)";
            //echo $queryString ;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function editPost($input = array(), $id = 0) {
        if (!empty($id)) {
            $queryString = "match (p:Post) where ID(p)=" . $id . " and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "' ";
            if (!empty($input)) {
                $queryString.=" set ";
                foreach ($input as $k => $v) {
                    $queryString.="p." . $k . "='" . $v . "',";
                }
                $queryString = rtrim($queryString, ',');
            }
            $queryString.=" return count(p)";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (isset($result[0]) && isset($result[0][0])) {
                return $result[0][0];
            } else {
                return 0;
            }
        }
    }

    public function processPost($post_id = 0, $referred_by = "", $referral = "", $status = "", $post_way = "", $relation_count = 0, $nonMintmesh = 0) {
        if (!empty($post_id) && !empty($referred_by) && !empty($referral) && !empty($status) && !empty($post_way) && !empty($relation_count)) {
            $referred_by = $this->appEncodeDecode->filterString(strtolower($referred_by));
            $referral = $this->appEncodeDecode->filterString(strtolower($referral));
            ;
            $status = strtoupper($status);
            if (!empty($nonMintmesh)) {//if for phone number referred
                $queryString = "match (u:NonMintmesh)-[r:GOT_REFERRED]->(p:Post)
                                  where ID(p)=" . $post_id;
            } else {
                $queryString = "match (u:User)-[r:GOT_REFERRED]->(p:Post)
                                  where ID(p)=" . $post_id;
            }

            if ($post_way == 'one') {//ignore the state for p3
                $queryString .=" and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "'";
            }

            $queryString.=" and r.referred_by='" . $referred_by . "' and r.relation_count='" . $relation_count . "'";
            if (!empty($nonMintmesh)) {//if for phone number referred
                $queryString.= " and u.phone='" . $referral . "' ";
            } else {
                $queryString.= " and u.emailid='" . $referral . "' ";
            }
            if ($post_way == 'one' && $status == Config::get('constants.REFERRALS.STATUSES.DECLINED')) {
                $queryString .=" set r.one_way_status='" . Config::get('constants.REFERRALS.STATUSES.' . $status) . "',
                                           p.referral_declined_count = p.referral_declined_count + 1,
                                           r.p1_updated_at='" . date("Y-m-d H:i:s") . "'";
            } else if ($post_way == 'round') {
                $queryString .=" set r.completed_status='" . Config::get('constants.REFERRALS.STATUSES.' . $status) . "' , r.status='" . Config::get('constants.REFERRALS.STATUSES.COMPLETED') . "', r.p3_updated_at='" . date("Y-m-d H:i:s") . "'";
            }
            $queryString.=" return p,r,u";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
    }

    public function getPostStatusDetails($input = array()) {

        if (!empty($input['post_id']) && !empty($input['referred_by']) && !empty($input['referral']) && !empty($input['from_user']) && !empty($input['relation_count'])) {
            $input['referred_by'] = $this->appEncodeDecode->filterString(strtolower($input['referred_by']));
            ;
            $input['referral'] = $this->appEncodeDecode->filterString(strtolower($input['referral']));
            ;
            $input['from_user'] = $this->appEncodeDecode->filterString(strtolower($input['from_user']));
            if (!empty($input['referred_by_phone'])) {
                $queryString = "Match (u:NonMintmesh)-[r:GOT_REFERRED]->(p:Post) 
                                 where ID(p)=" . $input['post_id'] . " 
                                  and u.phone='" . $input['referral'] . "' and 
                                  r.referred_by='" . $input['referred_by'] . "' 
                                  and r.relation_count='" . $input['relation_count'] . "' return r,u,p,labels(u)";
            } else {
                $queryString = "Match (u:User)-[r:GOT_REFERRED]->(p:Post) 
                                 where ID(p)=" . $input['post_id'] . " 
                                  and u.emailid='" . $input['referral'] . "' and 
                                  r.referred_by='" . $input['referred_by'] . "' 
                                  and r.relation_count='" . $input['relation_count'] . "' return r,u,p,labels(u)";
            }
            //and r.referred_for='".$input['from_user']."'
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
    }

    public function getMyReferralContacts($input = array()) {
        if (!empty($input['other_email']) && !empty($input['email'])) {
            if (!empty($input['limit']) && !empty($input['suggestion'])) {//to retrieve sugestions
                $skip = 0;
                $limit = 10;
            }
            $input['other_email'] = $this->appEncodeDecode->filterString(strtolower($input['other_email']));
            $input['email'] = $this->appEncodeDecode->filterString(strtolower($input['email']));

            $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh), (o:User:Mintmesh)
                                    where m.emailid='" . $input['email'] . "' and n.emailid='" . $input['other_email'] . "'
                                     and (m)-[:" . Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION') . "]-(o)    
                                    and not (n-[:" . Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION') . "]-o)
                                    RETURN DISTINCT o order by lower(o.firstname) asc ";

            if (!empty($input['suggestion']) && !empty($input['post_scope'])) {
                $relation = 'PROVIDES';
                $scope = 'Service';
                if ($input['post_scope'] == "find_candidate" || $input['post_scope'] == "find_job") {
                    $relation = 'WORKS_AS';
                    $scope = 'Job';
                }

                $queryString = "Match (m:User:Mintmesh {emailid:'" . $input['email'] . "'})-[:ACCEPTED_CONNECTION]-(o:User:Mintmesh), (n:User:Mintmesh {emailid:'" . $input['other_email'] . "'}),(p:Post) 
                        where   not (n-[:ACCEPTED_CONNECTION]-o) and ID(p)=" . $input['post_id'] . "
                        with m,n,o,p
                        match (o)-[:" . $relation . "]-(s:" . $scope . ")  
                        where (lower(s.name) =~ ('.*' + lower(p.service_name)+ '.*' ) and lower(o.location) =~ ('.*' + lower(p.service_location)))
                        RETURN DISTINCT o ";

                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();

                if ($result->count() <= $limit) {
                    $queryString.= " union Match (m:User:Mintmesh {emailid:'" . $input['email'] . "'})-[:ACCEPTED_CONNECTION]-(o:User:Mintmesh), (n:User:Mintmesh {emailid:'" . $input['other_email'] . "'}),(p:Post) 
                            where   not (n-[:ACCEPTED_CONNECTION]-o) and ID(p)=" . $input['post_id'] . "
                            with m,n,o,p
                            match (o)-[:" . $relation . "]-(s:" . $scope . ")  
                            where lower(s.name) =~ ('.*' + lower(p.service_name)+ '.*' )
                            RETURN DISTINCT o ";

                    $query = new CypherQuery($this->client, $queryString);
                    $result = $query->getResultSet();

                    if ($result->count() <= $limit) {
                        $queryString.= " union Match (m:User:Mintmesh {emailid:'" . $input['email'] . "'})-[:ACCEPTED_CONNECTION]-(o:User:Mintmesh), (n:User:Mintmesh {emailid:'" . $input['other_email'] . "'}),(p:Post) 
                                where   not (n-[:ACCEPTED_CONNECTION]-o) and ID(p)=" . $input['post_id'] . "
                                and lower(o.location) =~ ('.*' + lower(p.service_location))
                                RETURN DISTINCT o";
                    }
                }
                /* $queryString = "Match (m:User:Mintmesh {emailid:'".$input['email']."'})-[:ACCEPTED_CONNECTION]-(o:User:Mintmesh), (n:User:Mintmesh {emailid:'".$input['other_email']."'}),(p:Post)
                  where   not (n-[:ACCEPTED_CONNECTION]-o)
                  and lower(o.location) =~ ('.*' + lower(p.service_location)) and ID(p)=".$input['post_id']."
                  RETURN DISTINCT o order by lower(o.firstname) asc";
                  $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh), (o:User:Mintmesh),(p:Post)
                  where m.emailid='".$input['email']."' and n.emailid='".$input['other_email']."'
                  and (m)-[:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(o)
                  and not (n-[:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-o)
                  and lower(o.location) =~ ('.*' + lower(p.service_location)) and ID(p)=".$input['post_id']."
                  RETURN DISTINCT o order by o.firstname asc " ; */
            }
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . $limit;
            }
            //echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    //get post rerferrals in pendin and accepted state
    public function getPostReferrals($post_id = 0, $referred_by = "") {
        if (!empty($post_id) && !empty($referred_by)) {
            //and p.status='ACTIVE'
            $queryString = "match (m1:User:Mintmesh),(p:Post) where ID(p)=" . $post_id . " with m1,p
                                match (m1)-[r1:GOT_REFERRED]-(p) where r1.referred_by='" . $referred_by . "'
                                with max(r1.relation_count) as rel_count ,m1,r1  
                                match (m1)-[r1:GOT_REFERRED]-(p) 
                                where r1.relation_count=rel_count  
                                and r1.one_way_status in ['" . Config::get('constants.REFERRALS.STATUSES.ACCEPTED') . "','" . Config::get('constants.REFERRALS.STATUSES.PENDING') . "'] return m1";
            /* $queryString = "match (m:User),(m1:User),(p:Post) where ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' with m,m1,p
              match (m1)-[r1:GOT_REFERRED]-(p) where m1.emailid=m.emailid
              with max(r1.relation_count) as rel_count ,m
              match (m)-[r:GOT_REFERRED]-(p)
              where r.relation_count=rel_count and r.referred_by='".$referred_by."' and r.one_way_status in ['".Config::get('constants.REFERRALS.STATUSES.ACCEPTED')."','".Config::get('constants.REFERRALS.STATUSES.PENDING')."'] return m" ;
             */
            //$queryString = "match (m:User)-[r:GOT_REFERRED]-(p:Post) where 
            //           ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' return m" ;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function searchPeople($userEmail = "", $searchInput = array()) {
        if (!empty($userEmail)) {
            $skills = array();
            $queryString = "match (u:User:Mintmesh)-[:ACCEPTED_CONNECTION]-(u1:User)-[:ACCEPTED_CONNECTION]-(u2:User)";
            if (!empty($searchInput['skills'])) {
                $skills = json_decode($searchInput['skills']);
                if (!empty($skills) && is_array($skills) && count($skills) <= 3) {
                    $queryString.=",(s1:Skills{mysql_id:$skills[0]})";
                    if (!empty($skills[1])) {
                        $queryString.=",(s2:Skills{mysql_id:$skills[1]})";
                    }
                    if (!empty($skills[2])) {
                        $queryString.=",(s3:Skills{mysql_id:$skills[2]})";
                    }
                }
                if (!empty($skills) && is_array($skills) && count($skills) > 3) {
                    $queryString.="-[:KNOWS]-(s:Skills) ";
                }
            }
            $queryString.=" where u.emailid='" . $userEmail . "' and ";
            //if (!empty($searchInput))
            //{
            $queryString.=!empty($searchInput['fullname']) ? "lower(u2.fullname)=~ '.*" . strtolower($searchInput['fullname']) . ".*' AND " : "";
            $queryString.=!empty($searchInput['job_function']) ? "u2.job_function='" . $searchInput['job_function'] . "' AND " : "";
            $queryString.=!empty($searchInput['industry']) ? "u2.industry='" . $searchInput['industry'] . "' AND " : "";
            $queryString.=!empty($searchInput['company']) ? "lower(u2.company)=~ '.*" . strtolower($searchInput['company']) . ".*' AND " : "";
            $queryString.=!empty($searchInput['location']) ? "lower(u2.location)=~ '.*" . strtolower($searchInput['location']) . "' AND " : "";
            if (!empty($skills) && is_array($skills) && count($skills) <= 3) {
                $queryString.="( (u2)-[:KNOWS]-(s1)";
                if (!empty($skills[1])) {
                    $queryString.=" and (u2)-[:KNOWS]-(s2)";
                }
                if (!empty($skills[2])) {
                    $queryString.=" and (u2)-[:KNOWS]-(s3)";
                }
                $queryString.=") and ";
            }
            if (!empty($skills) && is_array($skills) && count($skills) > 3) {
                $queryString.="s.mysql_id IN [" . implode(",", $skills) . "] and";
            }
            //}

            $queryString.=" not (u)-[:ACCEPTED_CONNECTION]-(u2) return DISTINCT(u2) order by u2.firstname asc";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getMutualPeople($userEmail1 = "", $userEmail2 = "") {
        if (!empty($userEmail1) && !empty($userEmail2)) {
            $queryString = "match (u:User:Mintmesh)-[:ACCEPTED_CONNECTION]-(u1:User)-[:ACCEPTED_CONNECTION]-(u2:User)
                 where u.emailid='" . $userEmail2 . "' and u2.emailid='" . $userEmail1 . "' return distinct(u1) order by u1.firstname asc";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getPostAndReferralDetails($post_id = 0, $referred_by = "", $userEmail = "") {
        if (!empty($post_id) && !empty($referred_by) && !empty($userEmail)) {
            $query1 = "match (p:Post)-[r:GOT_REFERRED]-(u:User) where ID(p)=" . $post_id . " 
                            and u.emailid='" . $userEmail . "' and r.referred_by='" . $referred_by . "' return max(r.relation_count) as count";
            $max_count = 1;
            $query = new CypherQuery($this->client, $query1);
            $countResult = $query->getResultSet();
            if (!empty($countResult[0]) && !empty($countResult[0][0])) {
                $max_count = $countResult[0][0];
            }
            $queryString = "match (m1)-[r1:GOT_REFERRED]-(p) where ID(p)=" . $post_id . " and m1.emailid='" . $userEmail . "'
                                    and r1.referred_by='" . $referred_by . "'
                                    and r1.relation_count='" . $max_count . "'  
                                    return r1,p";
            //echo $queryString ; exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getPostReferralsCount($postId = 0) {
        if (!empty($postId)) {
            $queryString = "match  (u)-[r:GOT_REFERRED]->(p:Post)
                                 where ID(p)=" . $postId . " and ('Mintmesh' IN labels(u) OR  'NonMintmesh' IN labels(u) OR 'User' IN labels(u))"
                    . " return count(u)";
            $query = new CypherQuery($this->client, $queryString);
            $countResult = $query->getResultSet();
            if (!empty($countResult[0]) && !empty($countResult[0][0])) {
                return $countResult[0][0];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function updatePostPaymentStatus($relation = 0, $status = '', $is_self_referred = 0, $userEmail = '') {
        if (!empty($relation)) {
            $queryString = "match (p:Post)-[r:GOT_REFERRED]-(u) where ID(r)=" . $relation . "
                                  set ";
            if (!empty($status)) {
                $queryString.= "r.payment_status='" . $status . "',";
            }
            if (!empty($is_self_referred)) {
                $queryString.= "r.completed_status='" . Config::get('constants.REFERRALS.STATUSES.ACCEPTED') . "',";
            }
            $queryString.= " r.one_way_status='" . Config::get('constants.REFERRALS.STATUSES.ACCEPTED') . "',
                                  p.referral_accepted_count = p.referral_accepted_count + 1, r.awaiting_action_status = '" . Config::get('constants.REFERRALS.STATUSES.ACCEPTED') . "',
                                  r.awaiting_action_by = '" . $userEmail . "', r.awaiting_action_updated_at= '" . date("Y-m-d H:i:s") . "',
                                  r.p1_updated_at='" . gmdate("Y-m-d H:i:s") . "' return p,r";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getAllReferrals($userEmail = '', $page = 0) {
        if (!empty($userEmail)) {
            $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            $relations = array(Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'), Config::get('constants.REFERRALS.GOT_REFERRED'));
            $relationString = implode("|", $relations);
            //$queryString="match (u)-[r:".$relationString."]->(p) where ('Mintmesh' IN labels(u) OR 'NonMintmesh' IN labels(u) OR 'User' IN labels(u)) and case type(r) when '".Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION')."' then u.emailid='".$userEmail."' else r.referred_by='".$userEmail."' end return r, type(r) as relationName, p, u, labels(u) order by r.created_at desc";
            $queryString = "match (u:User:Mintmesh)-[r:INTRODUCE_CONNECTION]->(p:User:Mintmesh) where u.emailid='" . $userEmail . "' return r, type(r) as relationName, p, u, labels(u) order by r.created_at desc
                                union
                                match (u)-[r:GOT_REFERRED]->(p:Post) where r.referred_by='" . $userEmail . "' return r, type(r) as relationName, p, u, labels(u) order by r.created_at desc";
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            }
            //echo $queryString ;exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }

    public function getRequestReferenceRelationId($from = '', $to = '', $for = '', $relation_count = 0) {
        $from = $this->appEncodeDecode->filterString(strtolower($from));
        $to = $this->appEncodeDecode->filterString(strtolower($to));
        $for = $this->appEncodeDecode->filterString(strtolower($for));
        $queryString = "match (u:User:Mintmesh)-[r:" . Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') . "]-(u1:User:Mintmesh) "
                . "where  u.emailid='" . $from . "' and u1.emailid='" . $to . "' and r.request_for_emailid='" . $for . "'"
                . " and r.request_count='" . $relation_count . "' return ID(r) as relation_id";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if ($result->count()) {
            return $result;
        } else {
            return false;
        }
    }

    public function getServiceDetailsByCode($serviceCode) {
        $queryString = "match (u:User:Mintmesh)-[r:" . Config::get('constants.REFERRALS.POSTED') . "]->(p:Post) where p.service_code='" . $serviceCode . "' return u,p limit 1";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    public function referContactByPhone($referred_by, $referred_for, $referredUser, $postId, $relationAttrs = array()) {
        $referredUser = $this->appEncodeDecode->filterString(strtolower($referredUser));
        $referredUser = $this->appEncodeDecode->formatphoneNumbers(strtolower($referredUser));
        $queryString = "MATCH (u:NonMintmesh),(p:Post),(u1:User:Mintmesh{emailid:'" . $referred_by . "'})
                            WHERE u.phone = '" . $referredUser . "' and ID(p)=" . $postId . " 
                             and (u1)-[:" . Config::get('constants.RELATIONS_TYPES.IMPORTED') . "]->(u)
                             and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "' 
                            CREATE (u)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED') . " ";
        if (!empty($relationAttrs)) {
            $queryString.="{";
            foreach ($relationAttrs as $k => $v) {
                $queryString.=$k . ":'" . $this->appEncodeDecode->filterString($v) . "',";
            }
            $queryString = rtrim($queryString, ",");
            $queryString.="}";
        }
        $queryString.="]->(p) set p.total_referral_count = p.total_referral_count + 1 , r.resume_parsed =0 return count(p),ID(r)";
        //echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        if (isset($result[0]) && isset($result[0][0])) {
            return $result;
        } else {
            return 0;
        }
    }

    //get post rerferrals in pendin and accepted state
    public function getMyNonMintmeshReferrals($post_id = 0, $referred_by = "") {
        if (!empty($post_id) && !empty($referred_by)) {
            //and p.status='ACTIVE'
            $queryString = "match (m1),(p:Post) where ID(p)=" . $post_id . " with m1,p
                                match (m1)-[r1:GOT_REFERRED]-(p) where r1.referred_by='" . $referred_by . "' 
                                 and ('NonMintmesh' IN labels(m1) OR 'User' IN labels(m1))
								with max(r1.relation_count) as rel_count ,m1,r1  
                                match (m1)-[r1:GOT_REFERRED]-(p) 
                                where r1.relation_count=rel_count  
                                and r1.one_way_status in ['" . Config::get('constants.REFERRALS.STATUSES.ACCEPTED') . "','" . Config::get('constants.REFERRALS.STATUSES.PENDING') . "'] return distinct(m1), labels(m1)";
            /* $queryString = "match (m:User),(m1:User),(p:Post) where ID(p)=".$post_id." and p.status='".Config::get('constants.REFERRALS.STATUSES.ACTIVE')."' with m,m1,p
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
        } else {
            return 0;
        }
    }

    public function getPostAndReferralDetailsNonMintmesh($post_id = 0, $referred_by = "", $userPhone = "") {
        if (!empty($post_id) && !empty($referred_by) && !empty($userPhone)) {
            $query1 = "match (p:Post)-[r:GOT_REFERRED]-(u:NonMintmesh) where ID(p)=" . $post_id . " 
                            and u.phone='" . $userPhone . "' and r.referred_by='" . $referred_by . "' return max(r.relation_count) as count";
            $max_count = 1;
            $query = new CypherQuery($this->client, $query1);
            $countResult = $query->getResultSet();
            if (!empty($countResult[0]) && !empty($countResult[0][0])) {
                $max_count = $countResult[0][0];
            }
            $queryString = "match (m1:NonMintmesh)-[r1:GOT_REFERRED]-(p) where ID(p)=" . $post_id . " and m1.phone='" . $userPhone . "'
                                    and r1.referred_by='" . $referred_by . "'
                                    and r1.relation_count='" . $max_count . "'  
                                    return r1,p";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getExcludedPostsList($userEmail = '', $postsIds = array()) {
        $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
        if (!empty($userEmail)) {
            $postsIds = implode(",", $postsIds);
            $queryString = "match (u:User:Mintmesh)-[r:EXCLUDED]-(p:Post) where u.emailid='" . $userEmail . "' and ID(p) IN [" . $postsIds . "] return ID(p) as post_id";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    public function getAllPostsV3($email = "", $type = "", $page = 0, $search = '') {
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
        if (!empty($email)) {
            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }
            $filter_query = "";
            if ($type_array) {
                if (!(in_array('all', $type_array))) {
                    if ((in_array('free', $type_array) || in_array('paid', $type_array)) && !(in_array('free', $type_array) && in_array('paid', $type_array))) {
                        $filter_query .= (in_array('free', $type_array)) ? ' and p.free_service = "1" ' : ' and p.free_service = "0" ';
                    }
                }
                $type_array = array_flip($type_array);
                unset($type_array['free']);
                unset($type_array['paid']);
                unset($type_array['all']);
                $type_array = array_flip($type_array);
                if (count($type_array) > 0) {
                    $filter_query .= " and p.service_scope IN ['" . implode("','", $type_array) . "'] ";
                }
            }
            //and p.service_scope='".$type."'
            //and r1.created_at <= p.created_at
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            $queryString = "MATCH (u:User:Mintmesh)-[r1:CONNECTED_TO_COMPANY]-(m:Company)-[:POSTED_FOR]-(p:Post{status:'ACTIVE'}) 
                                where u.emailid='" . $email . "' and p.service_name =~ '(?i).*" . $search . ".*' " . $filter_query . " return p,count(distinct(u)),m ORDER BY p.created_at DESC
                                 UNION
                                match (n:User:Mintmesh)-[r1:ACCEPTED_CONNECTION]-(m:User:Mintmesh)-[r2:POSTED]->(p:Post)
                                where n.emailid='" . $email . "' and m.emailid=p.created_by and p.created_by<>'" . $email . "'
                                and case p.included_set when '1' then  (n-[:INCLUDED]-p) else 1=1 end
                                and not(n-[:EXCLUDED]-p) 
                                and p.service_name =~ '(?i).*" . $search . ".*'
                                " . $filter_query . "
                                and  case p.service_type 
                                when 'in_location' then  lower(n.location) =~ ('.*' + lower(p.service_location)) else 1=1 end
                                and p.status='" . Config::get('constants.REFERRALS.STATUSES.ACTIVE') . "' 
                                OPTIONAL MATCH (p)-[r:GOT_REFERRED]-(u)
                                return p, count(distinct(u)),m ORDER BY p.created_at DESC ";
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            }
            //echo $queryString ; exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return false;
        }
    }

    public function getReferralsListCounts($userEmail = '', $postsIds = array()) {
        $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
        if (!empty($userEmail)) {
            $postsIds = implode(",", $postsIds);
            $queryString = "MATCH (p:Post)-[r:GOT_REFERRED]-(u) where  ID(p) IN [" . $postsIds . "] return ID(p) as post_id,count(distinct(u)) ";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        } else {
            return 0;
        }
    }

    /*
     * get post name for the new post posted to send notification
     */

    public function getPostName($postId = 0) {
        $postName = '';
        if (!empty($postId)) {
            $queryString = "match  (p:Post) where ID(p)=" . $postId . " return p.service_name";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (!empty($result[0]) && !empty($result[0][0])) {
                $postName = $result[0][0];
            }
        }
        return $postName;
    }

    /*
     * get connected users to send push notifications when a new post is created
     */

    public function getMyConnectionForNewPostPush($fromUserEmailid = '', $serviceType = '', $serviceLocation = '', $excludedList = array()) {
        if (!empty($fromUserEmailid) && !empty($serviceType)) {
            $queryString = "match (n:User:Mintmesh {emailid:'" . $fromUserEmailid . "'})-[r1:ACCEPTED_CONNECTION]-(m:User:Mintmesh)";
            if ($serviceType == 'in_location' || !empty($excludedList)) {
                $queryString.=" where ";
                if ($serviceType == 'in_location') {
                    $queryString.="lower(m.location) =~ ('.*' + lower('" . $serviceLocation . "'))";
                }
                if ($serviceType == 'in_location' && !empty($excludedList)) {

                    $queryString.=" and ";
                }
                if (!empty($excludedList)) {
                    $queryString.= "NOT m.emailid IN['" . implode("','", $excludedList) . "']";
                }
            }
            $queryString.=" return m.emailid";
            //echo $queryString ; exit;
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
    }

    /*
     * map industry for find a candidate
     */

    public function mapIndustryToPost($industryId = '', $postId = '', $relationType = '') {
        $queryString = "Match (p:Post),(i:Industries)
                                    where ID(p)=" . $postId . " and i.mysql_id=" . $industryId . "
                                    create unique (p)-[r:" . $relationType . "";

        $queryString.="]->(i)  set r.created_at='" . date("Y-m-d H:i:s") . "' return i";
        //echo $queryString;exit;
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    /*
     * map job function for find a candidate
     */

    public function mapJobFunctionToPost($jobFunctionId = '', $postId = '', $relationType = '') {
        $queryString = "Match (p:Post),(j:Job_Functions)
                                    where ID(p)=" . $postId . " and j.mysql_id=" . $jobFunctionId . "
                                    create unique (p)-[r:" . $relationType . "";

        $queryString.="]->(j)  set r.created_at='" . date("Y-m-d H:i:s") . "' return j";

        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    /*
     * map experience range for find a candidate
     */

    public function mapExperienceRangeToPost($experienceRangeId = '', $postId = '', $relationType = '') {
        $queryString = "Match (p:Post),(er:ExperienceRange)
                                    where ID(p)=" . $postId . " and er.mysql_id='" . $experienceRangeId . "'
                                    create unique (p)-[r:" . $relationType . "";

        $queryString.="]->(er)  set r.created_at='" . date("Y-m-d H:i:s") . "' return er";

        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    /*
     * map employment type for find a candidate
     */

    public function mapEmploymentTypeToPost($employmentTypeId = '', $postId = '', $relationType = '') {
        $queryString = "Match (p:Post),(et:EmploymentType)
                                    where ID(p)=" . $postId . " and et.mysql_id='" . $employmentTypeId . "'
                                    create unique (p)-[r:" . $relationType . "";

        $queryString.="]->(et)  set r.created_at='" . date("Y-m-d H:i:s") . "' return et";

        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    /*
     * get industry name for post
     */

    public function getIndustryNameForPost($postId = 0) {
        $industryName = '';
        if (!empty($postId)) {
            $queryString = "match (p:Post)-[r:ASSIGNED_INDUSTRY]->(i:Industries) where ID(p)=" . $postId . " return i.name limit 1";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (!empty($result[0])) {
                $industryName = $result[0][0];
            }
        }
        return $industryName;
    }

    /*
     * get jo function name for post
     */

    public function getJobFunctionNameForPost($postId = 0) {
        $jobFunctionName = '';
        if (!empty($postId)) {
            $queryString = "match (p:Post)-[r:ASSIGNED_JOB_FUNCTION]->(i:Job_Functions) where ID(p)=" . $postId . " return i.name limit 1";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (!empty($result[0])) {
                $jobFunctionName = $result[0][0];
            }
        }
        return $jobFunctionName;
    }

    /*
     * get experience range name for post
     */

    public function getExperienceRangeNameForPost($postId = 0) {
        $experienceRangeName = '';
        if (!empty($postId)) {
            $queryString = "match (p:Post)-[r:ASSIGNED_EXPERIENCE_RANGE]->(i:ExperienceRange) where ID(p)=" . $postId . " return i.name limit 1";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (!empty($result[0])) {
                $experienceRangeName = $result[0][0];
            }
        }
        return $experienceRangeName;
    }

    /*
     * get employment type name for post
     */

    public function getEmploymentTypeNameForPost($postId = 0) {
        $employmentTypeName = '';
        if (!empty($postId)) {
            $queryString = "match (p:Post)-[r:ASSIGNED_EMPLOYMENT_TYPE]->(i:EmploymentType) where ID(p)=" . $postId . " return i.name limit 1";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (!empty($result[0])) {
                $employmentTypeName = $result[0][0];
            }
        }
        return $employmentTypeName;
    }

    public function getPostMyReferralsCount($userEmail = '', $postId = 0) {
        $count = 0;
        $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
        if (!empty($userEmail)) {
            $queryString = "MATCH (p:Post)-[r:GOT_REFERRED{referred_by:'" . $userEmail . "'}]-(u) where ID(p) = " . $postId . " return count(distinct(u))";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if (!empty($result[0])) {
                $count = $result[0][0];
            }
        }
        return $count;
    }

    public function getCampaigns($userEmail = '', $campaignId = 0) {
        $result = array();
        if (!empty($userEmail) && !empty($campaignId)) {
            $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
            $queryString = "MATCH (u:User:Mintmesh)-[r:CAMPAIGN_CONTACT]-(c:Campaign) 
                               where ID(c)=" . $campaignId . " and u.emailid='" . $userEmail . "' 
                               set r.post_read_status =1
                               RETURN distinct(c),r";
//               echo $queryString;exit;
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
        }
        return $result;
    }

    /* function for initiating confident score data */

    public function initConfidentScoreDetails($relationID) {
        $metaData = $this->getRequiredDataforConfidentScore($relationID);

        return $metaData;
    }

    public function getRequiredDataforConfidentScore($relationID) {
        //echo "\n before neo4j query".date("H:i:s");
        $initialValues = '';
        $queryString = "MATCH (p:Post)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED') . "]-(u) WHERE Id(r)=$relationID RETURN p,r,u";
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();
        //echo "\n".$queryString;
        if ($result->count()) {
            foreach ($result as $data) {
                $postData['ID'] = $data[0]->getID();
                $postData['employment_type'] = $this->getEmploymentTypeNameForPost($data[0]->getID());
                $postData['job_function'] = $this->getJobFunctionNameForPost($data[0]->getID());
                $postData['Industry'] = $this->getIndustryNameForPost($data[0]->getID());
                $postData['experience'] = $this->getExperienceRangeNameForPost($data[0]->getID());
                $postData['description'] = $data[0]->job_description;
                $postData['title'] = $data[0]->service_name;
                $postData['location'] = $data[0]->service_location;
                $postData['skills'] = $data[0]->skills;
                //$postData = $data[0]->getProperties();
                $relationData['ID'] = $relationID;
                $relationData['RESUME_JSON'] = $data[1]->resume_parsed_Json;


                $userData = $data[2]->getProperties();
            }
            $initialValues['post'] = $postData;
            $initialValues['relation'] = $relationData;
        }
        //echo "\n after neo4j query".date("H:i:s");
        return $initialValues;
    }

    public function calculateSolicitedConfidenceScore($relationID) {
        $initialValues = $this->initConfidentScoreDetails($relationID);
        $resumeJSON = $this->getResumeString($initialValues['relation']['RESUME_JSON']);

        // calculate confidence score based on job function
        $job_function_confidentScore = round($this->confidentScore($initialValues['post']['job_function'], $resumeJSON, "jobfunction"), 2);
        // calcuate confident score based on industry
        $industry_confidentScore = round($this->confidentScore($initialValues['post']['Industry'], $resumeJSON, "industry"), 2);
        // calcuate confident score based on location
        $location_confidentScore = round($this->confidentScore_location($initialValues['post']['location'], $resumeJSON), 2);
        // calculate confident score based on skills
        $skills_confidentScore = round($this->confidentScore_skills($initialValues['post']['skills'], $resumeJSON), 2);

        $overall_score = round((($job_function_confidentScore + $industry_confidentScore + $location_confidentScore + $skills_confidentScore) / 10) * 100, 2);
        //echo "\nJF_% - ".$job_function_confidentScore;
        //echo "\nIndustry_% - ".$industry_confidentScore;
        //echo "\nLocation_% - ".$location_confidentScore;
        //echo "\nSkills_% - ".$skills_confidentScore;
        //echo "\nOverall_% - ".$overall_score;
        // updating the relation with confident score
        $queryString = "MATCH (p:Post)-[r:" . Config::get('constants.REFERRALS.GOT_REFERRED') . "]-(u) WHERE Id(r)=$relationID SET r.job_function_score = " . $job_function_confidentScore . ", r.industry_score = " . $industry_confidentScore . ", r.service_location_score = " . $location_confidentScore . ", r.skills_score = " . $skills_confidentScore . ", r.overall_score = " . $overall_score . " return r";
        //echo "\n".$queryString;
        $query = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();

        return true;
        //print_r($initialValues);
    }

    public function getResumeString($resumeJSONPath) {
        $resumeString = '';
        if (!empty($resumeJSONPath)) {
            $jsonString = file_get_contents($resumeJSONPath);
            $resumeString = str_replace(array("\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n", "\\n\\n"), " ", $jsonString);
        }
        return $resumeString;
    }

    public function confidentScore($searchKey, $resumeJSON, $type) {
        $returnPercent = 0;
        $pattern = "";
        $string = "";
        // explode searchKey for multiple values
        //echo "\n".$searchKey;
        if (empty($searchKey)) {
            return $returnPercent;
        }
        $string = explode("/", $searchKey);
        if (count($string) > 1) {
            foreach ($string as $data) {
                $str = explode(" ", $data);
                if (count($str) > 1) {
                    foreach ($str as $val) {
                        //echo "\n".$val;
                        $pattern = "/\b$val\b/i";
                        preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
                        //print_r($matches);
                        if (count($matches) > 0) {
                            $returnPercent += (100 / count($str));
                            //echo "\n1 - ".$returnPercent."\n";
                        }
                    }
                    //$returnPercent = $returnPercent / count($str);
                    //echo "\n2 - ".$returnPercent."\n";
                } else {
                    //echo "\n".$data;
                    $pattern = "/\b$data\b/i";
                    preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
                    //print_r($matches);
                    if (count($matches) > 0) {
                        $returnPercent += 100;
                    }
                    //echo "\n3 - ".$returnPercent."\n";
                }
            }
            $returnPercent = $returnPercent / count($string);
        } else {
            //echo "\n".$searchKey;
            $pattern = "/\b$searchKey\b/i";
            preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
            if (count($matches) > 0) {
                $returnPercent += 100;
                //echo "\n4 - ".$returnPercent."\n";
            }
        }

        if ($type == "jobfunction") {
            // Jobfunction confident score 10 % weightage
            $returnPercent = ($returnPercent * 1) / 100;
        } else {
            // Industry confident score 15% weightage
            $returnPercent = ($returnPercent * 1.5) / 100;
        }

        return $returnPercent;
    }

    public function confidentScore_location($searchLocation, $resumeJSON) {
        $returnPercent = 0;
        if (empty($searchLocation)) {
            return $returnPercent;
        }
        if ($searchLocation != "Anywhere") {
            $locationString = explode(",", $searchLocation);
            if (count($locationString) > 1) {
                foreach ($locationString as $value) {
                    $value = trim($value);
                    //echo "\n".$value;
                    $pattern = "/\b$value\b/i";
                    preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
                    //print_r($matches);
                    if (count($matches) > 0) {
                        $returnPercent += 100 / count($locationString);
                        //echo "\n5 - ".$returnPercent."\n";
                    }
                }
                //$returnPercent = $returnPercent / count($locationString);
                //echo "\n6 - ".$returnPercent."\n";
            } else {
                //echo "\n".$searchLocation;
                $pattern = "/\b$searchLocation\b/i";
                preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
                //print_r($matches);
                if (count($matches) > 0) {
                    $returnPercent += 100;
                    //echo "\n7 - ".$returnPercent."\n";
                }
            }
        } else {
            $returnPercent = 100;
        }
        // Location confident score 5% weightage
        $locationWeightage = ($returnPercent * 0.05) / 100;

        return $locationWeightage;
    }

    function confidentScore_skills($skills, $resumeJSON) {
        $returnPercent = 0;
        if (empty($skills)) {
            return $returnPercent;
        }
        $string = explode(",", $skills);
        if (count($string) > 1) {
            foreach ($string as $skill) {
                $skill = preg_quote(trim($skill));
                $pattern = "/\b$skill\b/i";
                preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
                if (count($matches) > 0) {
                    $returnPercent += 100;
                }
            }
            //echo "\n 1 return% -".$returnPercent;
            $returnPercent = $returnPercent / count($string);
            //echo "\n 2 return% -".$returnPercent;
        } else {
            $skills = preg_quote($skills);
            $pattern = "/\b$skills\b/i";
            preg_match($pattern, $resumeJSON, $matches, PREG_OFFSET_CAPTURE);
            //print_r($matches);
            if (count($matches) > 0) {
                $returnPercent += 100;
                //echo "\n7 - ".$returnPercent."\n";
            }
        }

        // skills confident score 70% weightage
        $skillsWeightage = ($returnPercent * 7) / 100;

        return $skillsWeightage;
    }

    public function getPostAndMyReferralDetails($postId = 0, $userEmailID = '') {
        $result = array();
        if (!empty($postId) && !empty($userEmailID)) {
            $queryString = "MATCH (u:User:Mintmesh)-[i:INCLUDED]-(p:Post)
                                WHERE  ID(p)=" . $postId . " and u.emailid='" . $userEmailID . "'
                                OPTIONAL MATCH (p)<-[r:GOT_REFERRED]-(n)
                                WHERE r.referred_by='" . $userEmailID . "' and r.one_way_status<>'UNSOLICITED'
                                and ('Mintmesh' IN labels(n) OR  'NonMintmesh' IN labels(n) OR 'User' IN labels(n))
                                set i.post_read_status =1
                                RETURN p,r,n,labels(n),i";
            $query = new CypherQuery($this->client, $queryString);
            $resultSet = $query->getResultSet();
            $result = $resultSet;
        }
        return $result;
    }

    public function getAllMyReferrals($userEmail = '', $companyCode = '', $page = 0) {
        $return = array();
        if (!empty($userEmail) && !empty($companyCode)) {

            $skip = $limit = 0;
            if (!empty($page)) {
                $limit = $page * 10;
                $skip = $limit - 10;
            }

            $queryString = "match (u)-[r:GOT_REFERRED]->(p:Post)-[r1:POSTED_FOR]->(c:Company) 
                            where r.referred_by='" . $userEmail . "' and c.companyCode='" . $companyCode . "'
                            return distinct(p),r1,c order by p.created_at desc";
            if (!empty($limit) && !($limit < 0)) {
                $queryString.=" skip " . $skip . " limit " . self::LIMIT;
            }
            $query = new CypherQuery($this->client, $queryString);
            $return = $query->getResultSet();
        }
        return $return;
    }

    public function getResumeFilePath($docId=0, $companyId=0) {
        $query = "SELECT file_source,file_original_name FROM company_resumes WHERE id = ".$docId." AND company_id = ".$companyId."";
        return DB::select($query);
    }

}

?>
