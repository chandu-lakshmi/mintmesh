<?php namespace Mintmesh\Repositories\API\User;

use NeoUser, Config;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode ;
class NeoeloquentUserRepository extends BaseRepository implements NeoUserRepository {

        protected $neoUser, $db_user, $db_pwd, $appEncodeDecode;
        const LIMIT=10;
        public function __construct(NeoUser $neoUser,APPEncode $appEncodeDecode)
        {
                parent::__construct($neoUser);
                $this->neoUser = $neoUser;
                $this->db_user=Config::get('database.connections.neo4j.username') ;
                $this->db_pwd=Config::get('database.connections.neo4j.password') ;
                $this->client = new NeoClient();
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
        }
        /*
         * Create new user node in neo
         */
        public function createUser($input) {
            $queryString = "CREATE (n:User";
            if (!empty($input))
            {
                if(!empty($input['login_source'])) {
                    $queryString = "CREATE (n:User:Mintmesh";
                }

                $queryString.="{";
                foreach ($input as $k=>$v)
                {
                    if ($k == 'emailid')
                        $v = strtolower ($v) ;
                    $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                }
                $queryString = rtrim($queryString, ",") ;
                $queryString.="}";
            }
            $queryString.=") return n";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            return $result ;
            //return $this->neoUser->create($input);
        }
        
        public function updatePhoneVerified($emailid="",$phoneverified=0)
        {
            if (!empty($emailid))
            {
                $phone_verified=!empty($phoneverified)?$phoneverified:0;
                $queryString="match (u:User:Mintmesh) where u.emailid='".$emailid."' set u.phoneverified='".$phone_verified."' return u" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return 0;
            }
        }
        public function getNodeByEmailId($email='')
        {
            return $this->neoUser->whereEmailid($this->appEncodeDecode->filterString(strtolower($email)))->first();
        }
        //get user details by email id and check if it is a mintmesh user
        public function getNodeByEmailIdMM($email='')
        {
            $queryString = "MATCH (n:User:Mintmesh {emailid: '".$this->appEncodeDecode->filterString(strtolower($email))."'}) where HAS (n.login_source) RETURN n";
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
        public function updateUser($input)
        {
            //get user object
            $user = $this->getNodeByEmailId($this->appEncodeDecode->filterString($input['emailid']));
            if (count($user))
            {
                //set params
                //unset email id as we do not update it
                unset($input['emailid']);
                foreach ($input as $key=>$val)
                {
                    $user->$key = $this->appEncodeDecode->filterString($val);
                }
                $user->save();
                return $user ;
            }
            else
            {
                return 0 ;
            }
            
        }
        
        // complete a user profile
        public function completeUserProfile($input)
        {
            
        }
        
        //set request connection
        public function setConnectionRequest($fromUserId, $toUserId, $relationAttrs=array())
        {
            if ($toUserId != $fromUserId && (!empty($fromUserId) && !empty($toUserId)))//ignore if same user
            {
                $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh)
                                where ID(m)=".$toUserId."  and ID(n)=".$fromUserId."
                                create unique (n)-[r:".Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION');

                $queryString.="]->(m) set r.created_at='".date("Y-m-d H:i:s")."'";
                if (!empty($relationAttrs))
                {
                    $queryString.=" , " ;
                    foreach ($relationAttrs as $atrName=>$atrVal)
                    {
                        $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                    }
                    $queryString = rtrim($queryString,',');
                }
                
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        //accept connection
        public function acceptConnection($from_email='', $to_email='', $relationAttrs=array())
        {
            $to_email = $this->appEncodeDecode->filterString(strtolower($to_email));
            $from_email = $this->appEncodeDecode->filterString(strtolower($from_email));
            $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh)
                            where m.emailid='".$to_email."'  and n.emailid='".$from_email."'
                            create unique (n)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION');

            $queryString.="]->(m) set r.created_at='".date("Y-m-d H:i:s")."'";
            //set other relation properties
            if (!empty($relationAttrs))
            {
                $queryString.=" , " ;
                foreach ($relationAttrs as $atrName=>$atrVal)
                {
                    $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                }
                $queryString = rtrim($queryString,',');
            }
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        
        public function getReferralAcceptConnection($from_email='', $to_email='', $other_email='')
        {
            $to_email = $this->appEncodeDecode->filterString(strtolower($to_email));
            $from_email = $this->appEncodeDecode->filterString(strtolower($from_email));
            $other_email = $this->appEncodeDecode->filterString(strtolower($other_email));
            $queryString = "Match (m:User:Mintmesh)-[r:ACCEPTED_CONNECTION]-(n:User:Mintmesh)
                            where m.emailid='".$to_email."'  and n.emailid='".$from_email."' and r.refered_by_email='".$other_email."'
                             return r";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        //map to device
        public function mapToDevice($deviceToken, $emailId)
        {
            if (!empty($deviceToken) && !empty($emailId))
            {
                $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
                //unmap the user if assigned to any other device
                //$query1 = "MATCH (u:User:Mintmesh {emailid: '".$emailId."' })-[r:LOGGED_IN]-(d:Device) delete r" ;
                //$query = new CypherQuery($this->client, $query1);
                //$deleteResult1 = $query->getResultSet();
                //unmap the device if assigned to any other user
                $query2 = "MATCH (d:Device { deviceToken: '".$deviceToken."' })-[r]-() set r.status=0 return r" ;
                $query = new CypherQuery($this->client, $query2);
                $deleteResult2 = $query->getResultSet();
                $queryString = "MATCH (u:User:Mintmesh) WHERE u.emailid='".$emailId."' MERGE (d:Device{deviceToken:'".$deviceToken."' }) CREATE unique (u)-[r:".Config::get('constants.RELATIONS_TYPES.MAPPED_TO')."]->(d)  set r.created_at='".date("Y-m-d H:i:s")."',r.status=1";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function getDeviceToken($emailId)
        {
            if (!empty($emailId))
            {
                $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
                $queryString = "MATCH (n:User:Mintmesh {emailid: '".$emailId."'})-[r:".Config::get('constants.RELATIONS_TYPES.MAPPED_TO')."]->(d:Device) where r.status=1 RETURN n,d" ;
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if (count($result))
                {
                    return $result ;
                }
                else
                {
                    $queryString = "MATCH (n:User:Mintmesh {emailid: '".$emailId."'}) RETURN n" ;
                    $query = new CypherQuery($this->client, $queryString);
                    return $result = $query->getResultSet();
                }
            }
        }
        
        public function getConnectedAndMMUsers($emailId)
        {
            if (!empty($emailId))
            {
                $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
                $queryString = "MATCH (n:User:Mintmesh {emailid: '".$emailId."'})-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]->(m) where HAS (m.login_source) RETURN DISTINCT m order by m.firstname
                                UNION
                                MATCH (n:User:Mintmesh {emailid: '".$emailId."'})-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(m:User) where has(m.login_source) RETURN DISTINCT m order by m.firstname asc" ;
                
                /*$queryString = "MATCH (n:User {emailid: '".$emailId."'})-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."|".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."|".Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION')."]-(m:User) where has(m.login_source) RETURN m order by m.firstname asc" ;
                */
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        public function getConnectedUsers($emailId)
        {
            if (!empty($emailId))
            {
                $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
                $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(n:User:Mintmesh)
                                where m.emailid='".$emailId."'  
                                RETURN DISTINCT n order by n.firstname asc" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function getConnectionsByLocation($emailId, $location="")
        {
            if (!empty($emailId) && !empty($location))
            {
                $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
                $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(n:User:Mintmesh)
                                where m.emailid='".$emailId."' and n.location =~ '.*".$location.".*' 
                                RETURN DISTINCT n" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        
        public function getConnectedUsersCount($emailId)
        {
            if (!empty($emailId))
            {
                $emailId = $this->appEncodeDecode->filterString(strtolower($emailId));
                $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(n:User:Mintmesh)
                                where m.emailid='".$emailId."'  
                                RETURN count(DISTINCT n)" ;
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
        
        public function getMutualRequests($fromEmail, $toEmail)
        {
            if (!empty($fromEmail) && !empty($toEmail))
            {
                //Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION'),Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION')
                $relations = array(Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'));
                $relationString = implode("|",$relations) ;
                $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
                $toEmail = $this->appEncodeDecode->filterString(strtolower($toEmail));
                $queryString = "Match (m:User:Mintmesh)-[r:".$relationString."]->(n:User:Mintmesh) 
                                where m.emailid='".$fromEmail."' and n.emailid='".$toEmail."' 
                                RETURN r, type(r) as relationName" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function getMutualRequestsCount($fromEmail, $toEmail)
        {
            if (!empty($fromEmail) && !empty($toEmail))
            {
                //Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION')
                $relations = array(Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'));
                $relationString = implode("|",$relations) ;
                $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
                $toEmail = $this->appEncodeDecode->filterString(strtolower($toEmail));
                $queryString = "Match (m:User:Mintmesh)-[r:".$relationString."]->(n:User:Mintmesh) 
                                where m.emailid='".$fromEmail."' and n.emailid='".$toEmail."' 
                                RETURN count(r)" ;
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
        
        public function getMyRequests($fromEmail, $page=0)
        {
            if (!empty($fromEmail))
            {
                $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $skip = $limit - 10 ;
                }
                $relations = array(Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'), Config::get('constants.REFERRALS.POSTED'));
                $relationString = implode("|",$relations) ;
                $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
                $queryString = "Match (m:User:Mintmesh)-[r:".$relationString."]->(n)
                                where m.emailid='".$fromEmail."'  
                                RETURN r, type(r) as relationName, n order by r.created_at desc" ;
                if (!empty($limit) && !($limit < 0))
                {
                    $queryString.=" skip ".$skip." limit ".self::LIMIT ;
                }
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        public function getMyRequestsCount($fromEmail)
        {
            if (!empty($fromEmail))
            {
                $relations = array(Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'), Config::get('constants.REFERRALS.POSTED'));
                $relationString = implode("|",$relations) ;
                $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
                $queryString = "Match (m:User:Mintmesh)-[r:".$relationString."]->(n) 
                                where m.emailid='".$fromEmail."'  
                                RETURN count(r)" ;
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
        
        
        public function checkSelfReferenceStatus($referred_by='',$relationId=0)
        {
            if (!empty($referred_by) && !empty($relationId))
            {
                $queryString = "match (m:User)-[r:".Config::get('constants.RELATIONS_TYPES.HAS_REFERRED')."]->(n:User)"
                        . " where m.emailid='".$referred_by."'  and ID(r)=".$relationId.""
                        . "  return r ";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else {
                return false ;
            }
        }
        public function getIntroduceConnection($fromEmail="", $toEmail="",$forEmail="", $relationCount=0)
        {
            if (!empty($fromEmail) && !empty($toEmail))
            {
                $relations = array(Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'));
                $relationString = implode("|",$relations) ;
                $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
                $toEmail = $this->appEncodeDecode->filterString(strtolower($toEmail));
                $forEmail = $this->appEncodeDecode->filterString(strtolower($forEmail));
                
                /*$queryString = "Match (m:User)-[r:".$relationString."]->(n:User)
                                where m.emailid='".$fromEmail."' and n.emailid='".$toEmail."' 
                                 and r.request_for_emailid='".$forEmail."'
                                RETURN r" ;*/
                 $queryString = "Match (m:User:Mintmesh)-[r:".$relationString."]->(n:User:Mintmesh)
                                where m.emailid='".$fromEmail."' and n.emailid='".$toEmail."' 
                                 and r.request_for_emailid='".$forEmail."' and r.request_count='".$relationCount."' 
                                RETURN r order by r.relation_count desc limit 1" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function checkConnection($email1='', $email2='')
        {
              $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION')."]-(n:User:Mintmesh)
                                where m.emailid='".$email1."' and n.emailid='".$email2."' 
                                RETURN SIGN(COUNT(r)) as con_count" ;
              $query = new CypherQuery($this->client, $queryString);
              $count = $query->getResultSet();  
              if (!empty($count[0]) && !empty($count[0][0]))
              {
                  return $count[0][0] ;
              }
              else
              {
                  return false ;
              }
        }
        public function checkPendingConnection($email1='', $email2='')
        {
              $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.REQUESTED_CONNECTION')."]-(n:User:Mintmesh)
                                where m.emailid='".$email1."' and n.emailid='".$email2."' and r.status='".Config::get('constants.REFERENCE_STATUS.PENDING')."'
                                RETURN SIGN(COUNT(r)) as con_count, r.created_at, r.status" ;
              $query = new CypherQuery($this->client, $queryString);
              $count = $query->getResultSet();  
              if (!empty($count[0]) && !empty($count[0][0]))
              {
                  return $count[0][1] ;
              }
              else
              {
                  return false ;
              }
        }
        public function logout($deviceToken = '', $userDetails=array())
        {
            if (!empty($deviceToken) && !empty($userDetails))
            {
                $queryString = "MATCH (d:Device { deviceToken: '".$deviceToken."' })-[r]-(n:User:Mintmesh { emailid: '".$userDetails->emailid."' }) set r.status=0" ;
                $query = new CypherQuery($this->client, $queryString);
                $deleteResult = $query->getResultSet();
            }
        }
        
        public function requestReference($from="", $to="", $relationAttrs=array(), $relationType="")
        {
            if (!empty($from) && !empty($to) && !empty($relationType))
            {
                if (isset($relationAttrs['request_for_emailid']) && !empty($relationAttrs['request_for_emailid']))
                {
                    //get request count to set unique relation
                    //if request type is introduce connection then get count from request reference
                    if ($relationType == Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'))
                    {
                        $count = $this->getRequestCount(Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'), $relationAttrs['request_for_emailid'], $from, $to) ;
                        $relationAttrs['request_count'] = $count ;
                    }
                    else
                    {
                        $count = $this->getRequestCount($relationType, $from, $to, $relationAttrs['request_for_emailid']) ;
                        $relationAttrs['request_count'] = $count+1 ;
                    }
                    
                    
                }
                $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh)
                                where m.emailid='".$to."'  and n.emailid='".$from."'
                                create unique (n)-[r:".$relationType."";

                //set other relation properties
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
                
                $queryString.="]->(m)  set r.created_at='".date("Y-m-d H:i:s")."' return ID(r)";
                
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function changeRelationStatus($from="", $to="", $for="", $relationType="", $status="", $points=0)
        {
            if (!empty($from) && !empty($to) && !empty($status))
            {
                $count = 0;
                if (isset($for) && !empty($for))
                {
                     //if request type is introduce connection then get count from request reference
                    if ($relationType == Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'))
                    {
                        $count = $this->getRequestCount(Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'), $for, $from, $to) ;
                    }
                    else
                    {
                        //get request count to update latest relation
                        $count = $this->getRequestCount($relationType, $from, $to, $for) ;
                    }
                }
                
                $queryString = "MATCH (n:User:Mintmesh)-[r:".$relationType."]->(m:User:Mintmesh) 
                                where n.emailid='".$from."' AND m.emailid='".$to."'";
                if (!empty($for))
                {
                    $queryString.=" AND r.request_for_emailid='".$for."'" ;
                }
                if (!empty($count))
                {
                    $queryString.=" AND r.request_count='".$count."'";
                }
                
                $queryString.=" SET r.status='".$status."' ";
                if (!empty($points))
                {
                    $queryString.=" , r.points_earned='".$points."'";
                }
                $queryString.= " RETURN r" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            
        }
        
        
        public function createDeclinedRelation($from="", $to="", $relationAttrs=array())
        {
            if (!empty($from) && !empty($to))
            {
                $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh)
                                where m.emailid='".$to."'  and n.emailid='".$from."'
                                create unique (n)-[r:".Config::get('constants.RELATIONS_TYPES.DECLINED')."";

                //set other relation properties
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
                $queryString.="]->(m)  set r.created_at='".date("Y-m-d H:i:s")."'";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function getSectionInfo($id=0, $sectionName='')
        {
            if (!empty($id))
            {
                //$email = $this->appEncodeDecode->filterString(strtolower($input['emailid'])) ;
                $queryString = "MATCH (n:User:Mintmesh:".$sectionName.") where ID(n)=".$id." RETURN n";
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if (!empty($result) && isset($result[0]) && isset($result[0][0]))
                {
                    return $result[0][0]->getProperties();
                }
                else
                {
                    return false;
                }
                
            }
        }
        public function removeCategoryNodeRelation($input, $sectionName='', $relationName='')
        {
            $queryString = "Match (m:User:Mintmesh)-[r:".$relationName."]->(n:User:Mintmesh:".$sectionName.")
                            where m.emailid='".$input['emailid']."' and ID(n)= ".$input['id']." 
                             delete r return 1";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        public function getCategoryNodeRelationCount($input, $sectionName='', $relationName='')
        {
            $queryString = "Match (m:User:Mintmesh)-[r:".$relationName."]->(n:User:Mintmesh:".$sectionName.")
                            where m.emailid='".$input['emailid']."' 
                            return count(n)";
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
        public function updateCategoryNodeNRelation($input=array(), $relationAttrs=array(), $sectionName='', $relationName='')
        {
            
            $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh:".$sectionName.")
                            where m.emailid='".$input['emailid']."'  and ID(n)=".$input['id']."
                            create unique (m)-[r:".$relationName;

            $queryString.="]->(n)  set r.created_at='".date("Y-m-d H:i:s")."'";
            if (!empty($input))
            {
                if (isset($input['id']))
                    unset($input['id']);
                if (isset($input['emailid']))
                    unset($input['emailid']);
                $queryString.=" , " ;
                foreach ($input as $atrName=>$atrVal)
                {
                    $queryString.="n.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                }
                $queryString = rtrim($queryString,',');
            }
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
           
        }
        
        public function createCategoryNodeNRelation($input=array(), $relationAttrs=array(), $sectionName='', $relationName='')
        {
            $queryString = "MATCH (u:User:Mintmesh)
                            WHERE u.emailid = '".$input['emailid']."'
                            CREATE (m:User:Mintmesh:".$sectionName." ";
            if (!empty($input))
            {
                if (isset($input['emailid']))
                        unset($input['emailid']);
                $queryString.="{";
                foreach ($input as $k=>$v)
                {
                    $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                }
                $queryString = rtrim($queryString, ",") ;
                $queryString.="}";
            }
            $queryString.=")<-[r:".$relationName;
            $queryString.="]-(u)   set r.created_at='".date("Y-m-d H:i:s")."'" ;
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
        
        public function getMoreDetails($emailid="")
        {
            $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            $queryString = "MATCH (n:User:Mintmesh)-[r:MORE_INFO]->(m) where n.emailid='".$emailid."' RETURN m as row, labels(m) as labelName";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            //echo "<pre>";
            //print_r($result);exit;
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
        }
        
        public function getUserSkills($emailid="")
        {
             $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            $queryString = "MATCH (n:User:Mintmesh)-[r:KNOWS]->(m:Skills) where n.emailid='".$emailid."' RETURN m as row";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            //echo "<pre>";
            //print_r($result);exit;
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
        }
        public function getUserJobFunction($emailid="")
        {
             $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            $queryString = "MATCH (n:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.POSSES_JOB_FUNCTION')."]->(m:Job_Functions) where n.emailid='".$emailid."' RETURN m";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            //echo "<pre>";
            //print_r($result);exit;
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
        }
        public function getUserIndustry($emailid="")
        {
             $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            $queryString = "MATCH (n:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.HOLDS_INDUSTRY_EXPERIENCE')."]->(m:Industries) where n.emailid='".$emailid."' RETURN m";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            //echo "<pre>";
            //print_r($result);exit;
            if ($result->count())
            {
                return $result ;
            }
            else
            {
                return false ;
            }
        }
        
        //get refer request count
        public function getRequestCount($relation, $fromEmail, $toEmail, $forEmail)
        {
            if (!empty($fromEmail) && !empty($toEmail) && !empty($forEmail) && !empty($relation))
            {
                $relationString = $relation ; //Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE') ;
                $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
                $toEmail = $this->appEncodeDecode->filterString(strtolower($toEmail));
                $queryString = "Match (m:User:Mintmesh)-[r:".$relationString."]->(n:User:Mintmesh) 
                                where m.emailid='".$fromEmail."' and n.emailid='".$toEmail."' 
                                and r.request_for_emailid='".$forEmail."' 
                                RETURN count(r)" ;
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
        
        public function getRequestStatus($email1='', $email2='', $forEmail='', $relationType = "")
        {
              $queryString = "Match (m:User:Mintmesh)-[r:".$relationType."]-(n:User:Mintmesh)
                                where ";
              
              if (!empty($email1))
              {
                  $queryString.=" m.emailid='".$email1."'";
                  if (!empty($email2))
                  {
                      $queryString.=" and " ;
                  }
              }
              if (!empty($email2))
              {
                  $queryString.=" n.emailid='".$email2."'";
              }
              if (!empty($forEmail))
              {
                  $count = $this->getRequestCount($relationType, $email1, $email2, $forEmail) ;
                  $queryString.=" AND r.request_for_emailid='".$forEmail."'" ;
                  if (!empty($count))
                  {
                      $queryString.=" AND r.request_count='".$count."'" ;
                  }
              }
             $queryString.=" RETURN  r.status, r.created_at" ;
             if (empty($email1) || empty($email2))
             {
                $queryString.=" order by r.updated_at desc limit 1";
             }
             $query = new CypherQuery($this->client, $queryString);
              $res = $query->getResultSet();  
              if (!empty($res[0]) && !empty($res[0][0]))
              {
                  return array('status'=>$res[0][0], 'created_at'=>$res[0][1]) ;
              }
              else
              {
                  return false ;
              }
        }
        
        public function getReferenceFlow($relationId=0)
        {
            if (!empty($relationId))
            {
                $queryString = "match (u:User:Mintmesh)-[r]->(u1:User:Mintmesh) where ID(r)=".$relationId." return r,u,u1 limit 1";
                $query = new CypherQuery($this->client, $queryString);
                return $res = $query->getResultSet();  
            }
            else
            {
                return 0;
            }
            
        }
        public function removeContact($fromEmail="", $toEmail="")
        {
            $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
            $toEmail = $this->appEncodeDecode->filterString(strtolower($toEmail));
            $queryString = "Match (n:User:Mintmesh)-[r:ACCEPTED_CONNECTION|REQUESTED_CONNECTION]-(m:User:Mintmesh) 
                            where n.emailid='".$fromEmail."' and m.emailid='".$toEmail."' 
                            and (n-[:ACCEPTED_CONNECTION]-m) delete r";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        
        public function createDeleteContactRelation($fromEmail="", $toEmail="")
        {
            
            $fromEmail = $this->appEncodeDecode->filterString(strtolower($fromEmail));
            $toEmail = $this->appEncodeDecode->filterString(strtolower($toEmail));
            $queryString = "Match (m:User:Mintmesh),(n:User:Mintmesh)
                                    where m.emailid='".$fromEmail."' and n.emailid='".$toEmail."'
                                    create unique (m)-[r:".Config::get('constants.RELATIONS_TYPES.DELETED_CONTACT')."";

                    $queryString.="]->(n)  set r.created_at='".date("Y-m-d H:i:s")."'";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        
       public function mapSkills($skills=array(),$emailid='')
       {
           if (!empty($skills) && !empty($emailid))
           {
               foreach ($skills as $skill)
               {
                   $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
                    $queryString = "Match (m:User:Mintmesh),(s:Skills)
                                    where m.emailid='".$emailid."' and s.mysql_id=".$skill."
                                    create unique (m)-[r:".Config::get('constants.RELATIONS_TYPES.KNOWS')."";

                    $queryString.="]->(s)  set r.created_at='".date("Y-m-d H:i:s")."'";

                    $query = new CypherQuery($this->client, $queryString);
                    $result = $query->getResultSet();
               }
               return true ;
           }
           else
           {
               return 0;
           }
       }
       public function mapJobFunction($jobFunction=0,$emailid='')
       {
           if (!empty($jobFunction) && !empty($emailid))
           {
               
                $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
                 $queryString = "Match (m:User:Mintmesh),(j:Job_Functions)
                                 where m.emailid='".$emailid."' and j.mysql_id=".$jobFunction."
                                 create unique (m)-[r:".Config::get('constants.RELATIONS_TYPES.POSSES_JOB_FUNCTION')."";

                 $queryString.="]->(j)  set r.created_at='".date("Y-m-d H:i:s")."'";

                 $query = new CypherQuery($this->client, $queryString);
                 $result = $query->getResultSet();
               return true ;
           }
           else
           {
               return 0;
           }
       }
       public function mapIndustry($industry=0,$emailid='')
       {
           if (!empty($industry) && !empty($emailid))
           {
               
                $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
                 $queryString = "Match (m:User:Mintmesh),(i:Industries)
                                 where m.emailid='".$emailid."' and i.mysql_id=".$industry."
                                 create unique (m)-[r:".Config::get('constants.RELATIONS_TYPES.HOLDS_INDUSTRY_EXPERIENCE')."";

                 $queryString.="]->(i)  set r.created_at='".date("Y-m-d H:i:s")."'";

                 $query = new CypherQuery($this->client, $queryString);
                 $result = $query->getResultSet();
               return true ;
           }
           else
           {
               return 0;
           }
       }
       
       public function unMapSkills($emailid='')
       {
           if (!empty($emailid))
           {
               $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
               $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.KNOWS')."]-(s:Skills)
                                where m.emailid='".$emailid."'
                                delete r";

                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
           }
           else
           {
               return 0;
           }
       }
       
       public function unMapJobFunction($emailid='')
       {
           if (!empty($emailid))
           {
               $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
               $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.POSSES_JOB_FUNCTION')."]-(j:Job_Functions)
                                where m.emailid='".$emailid."'
                                delete r";

                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
           }
           else
           {
               return 0;
           }
       }
       public function unMapIndustry($emailid='')
       {
           if (!empty($emailid))
           {
               $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
               $queryString = "Match (m:User:Mintmesh)-[r:".Config::get('constants.RELATIONS_TYPES.HOLDS_INDUSTRY_EXPERIENCE')."]-(i:Industries)
                                where m.emailid='".$emailid."'
                                delete r";

                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
           }
           else
           {
               return 0;
           }
       }
       
       /*
        * refer connection by own
        */
       public function referMyConnection($fromUser, $toUser, $relationAttrs=array())
        {
            if (!empty($fromUser) && !empty($toUser))
            {
                $fromUser = $this->appEncodeDecode->filterString(strtolower($fromUser));
                $toUser = $this->appEncodeDecode->filterString(strtolower($toUser));
                $queryString = "Match (m:User), (n:User)
                                where m.emailid='".$toUser."'  and n.emailid='".$fromUser."'
                                create (n)-[r:".Config::get('constants.RELATIONS_TYPES.HAS_REFERRED');

                $queryString.="]->(m) set r.created_at='".date("Y-m-d H:i:s")."'";
                if (!empty($relationAttrs))
                {
                    $queryString.=" , " ;
                    foreach ($relationAttrs as $atrName=>$atrVal)
                    {
                        $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                    }
                    $queryString = rtrim($queryString,',');
                }
                $queryString.=" return r" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        /*
         * check status of connection refered by self
         */
        public function changeSelfReferContactStatus($relationId=0,$status='')
        {
            if (!empty($relationId) && !empty($status))
            {
                $queryString = "match (m:User)-[r:HAS_REFERRED]-(n:User) where "
                    . "ID(r)=".$relationId." set r.status='".$status."'";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return 0 ;
            }
            
        }
        
        public function getUserByPhone($phone="",$email="")
        {
            if (!empty($phone))
            {
                $queryString = "match (u:User:Mintmesh) where u.phone='".$phone."' and has(u.login_source) and u.phoneverified='1'";
                if (!empty($email))//exclude email
                {
                    $queryString.=" and u.emailid<>'".$email."'";
                }
                $queryString.=" return count(u)" ;
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
                return 0;
            }
        }
        
        public function ConnectToInvitee($user, $relationAttrs=array())
        {
            if (!empty($user))
            {
                $user = $this->appEncodeDecode->filterString(strtolower($user));
                $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh)
                                where m.emailid='".$user."' 
                                and (n)-[:".Config::get('constants.RELATIONS_TYPES.INVITED')."]->(m)
                                create unique (m)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION');

                $queryString.="]->(n) set r.created_at='".date("Y-m-d H:i:s")."'";
                if (!empty($relationAttrs))
                {
                    $queryString.=" , " ;
                    foreach ($relationAttrs as $atrName=>$atrVal)
                    {
                        $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                    }
                    $queryString = rtrim($queryString,',');
                }
                $queryString.=" return r" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function AutoConnectUsers($user, $toConnectEmail, $relationAttrs=array())
        {
            if (!empty($user))
            {
                $user = $this->appEncodeDecode->filterString(strtolower($user));
                $toConnectEmail = $this->appEncodeDecode->filterString(strtolower($toConnectEmail));
                $queryString = "Match (m:User:Mintmesh), (n:User:Mintmesh)
                                where m.emailid='".$user."' 
                                and n.emailid='".$toConnectEmail."' 
                                create unique (m)-[r:".Config::get('constants.RELATIONS_TYPES.ACCEPTED_CONNECTION');

                $queryString.="]->(n) set r.created_at='".date("Y-m-d H:i:s")."'";
                if (!empty($relationAttrs))
                {
                    $queryString.=" , " ;
                    foreach ($relationAttrs as $atrName=>$atrVal)
                    {
                        $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                    }
                    $queryString = rtrim($queryString,',');
                }
                $queryString.=" return r" ;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
        }
        
        public function getInfluencersList($userEmail = '')
        {
            if (!empty($userEmail))
            {
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                $queryString = "match (u:User:Mintmesh)-[r:ACCEPTED_CONNECTION]-(u1:User:Mintmesh)-[r1:ACCEPTED_CONNECTION]-(u2:User:Mintmesh) where u.emailid='".$userEmail."' and u2.emailid<>'".$userEmail."' and not (u)-[:ACCEPTED_CONNECTION]-(u2) with u2
                                match (u2)-[r2:ACCEPTED_CONNECTION]-(u3) return u2,count(distinct(u3))
                                order by count(distinct(u3)) desc  limit 20";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return false;
            }
        }
        
        public function getRecruitersList($userEmail = '', $page)
        {
            if (!empty($userEmail))
            {
                 $skip = $limit = 0;
                if (!empty($page))
                {
                    $limit = $page*10 ;
                    $skip = $limit - 10 ;
                }
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail));
                $queryString = "MATCH (u:User:Mintmesh),(u1:User:Mintmesh) where lower(u1.you_are)='recruiter' and u1.location=~('.*' + u.location) and u.emailid='".$userEmail."' and u1.emailid<>'".$userEmail."' and not (u)-[:ACCEPTED_CONNECTION]-(u1) RETURN u1 ";
                //echo $queryString ; exit;
                if (!empty($limit) && !($limit < 0))
                {
                    $queryString.=" skip ".$skip." limit ".self::LIMIT ;
                }
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return false;
            }
        }
        
        public function getAutoconnectUsers($userEmail='', $email='', $phones=array())
        {
            if (!empty($email))
            {
                $email = $this->appEncodeDecode->filterString(strtolower($email)) ;
                $userEmail = $this->appEncodeDecode->filterString(strtolower($userEmail)) ;
                if (!empty($phones))
                {
                    foreach ($phones as $k=>$v)
                    {
                        $temp = preg_replace('/[^0-9.]+/', '', $v);
                        $phones[$k] = "+".$temp ;
                    }
                }
                $phoneString = !empty($phones)?implode("','", $phones):'';
                $phoneString = "'".$phoneString."'" ;
                $queryString = "match (v:User:Mintmesh{emailid:'".$userEmail."'}),(u:User:Mintmesh)"
                                . " where (u.emailid='".$email."' and (u)-[:IMPORTED]->(v) and not (u)-[:DELETED_CONTACT]-(v) and not (u)-[:ACCEPTED_CONNECTION]-(v)) or (replace(u.phone, '-', '') IN[".$phoneString."] and u.phoneverified='1' and (u)-[:IMPORTED]->(v) and not (u)-[:DELETED_CONTACT]-(v) and not (u)-[:ACCEPTED_CONNECTION]-(v)) return u" ;
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
            else
            {
                return false ;
            }
             
        }
        
        public function checkImport($user1='', $user2='')
        {
            if (!empty($user1) && !empty($user2))
            {
                $queryString = "match (u1:User:Mintmesh)-[r:IMPORTED]->(u2:User) where u1.emailid='".$user1."' and u2.emailid='".$user2."' return r";
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return 0;
            }
        }
        
        
         
}
?>
