<?php namespace Mintmesh\Repositories\API\SocialContacts;

use NeoUser;
use DB,Log;
use Config;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode ;
class NeoeloquentContactsRepository extends BaseRepository implements ContactsRepository {

        protected $neoUser, $db_user, $db_pwd, $client,$appEncodeDecode, $db_host, $db_port;

        public function __construct(NeoUser $neoUser,APPEncode $appEncodeDecode)
        {
                parent::__construct($neoUser);
                $this->neoUser = $neoUser;
                $this->db_user=Config::get('database.connections.neo4j.username') ;
                $this->db_pwd=Config::get('database.connections.neo4j.password') ;
                $this->db_host=Config::get('database.connections.neo4j.host') ;
                $this->db_port=Config::get('database.connections.neo4j.port') ;
                //\Log::info("db password used".$this->db_pwd);
                 //\Log::info("db username used".$this->db_user);
                //$this->db_pwd = "m!ntm35h";
                $this->client = new NeoClient($this->db_host, $this->db_port);
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
        }
        public function createContactAndRelation($fromId, $neoInput=array(),$relationAttrs = array())
        {
            try{
                $queryString = "MATCH (u:User:Mintmesh)
                            WHERE ID(u) = ".$fromId."
                            CREATE (m:User ";
                if (!empty($neoInput))
                {
                    $queryString.="{";
                    foreach ($neoInput as $k=>$v)
                    {
                        if ($k == 'emailid')
                            $v= strtolower ($v);
                        $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                    }
                    $queryString = rtrim($queryString, ",") ;
                    $queryString.="}";
                }
                $queryString.=")<-[:".Config::get('constants.RELATIONS_TYPES.IMPORTED');
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
                $queryString.="]-(u)" ;
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
            } catch (\Everyman\Neo4j\Exception $ex) {
                    return false ;
            }
            
        }
        /*
         * get existing contacts count
         */
        public function getExistingContacts($emails = array(), $phones = array())
        {
            if (!empty($emails) || !empty($phones))
            {
                $emailids = array();
                foreach ($emails as $e)
                {
                    $emailids[] = $this->appEncodeDecode->filterString(strtolower($e));
                }
                $emailsIds = implode("','", $emailids);
                $emailsIds = !empty($emailsIds)?"'".$emailsIds."'":'' ;
                $phoneString = !empty($phones)?implode("','", $phones):'';
                $phoneString = !empty($phoneString)?"'".$phoneString."'":'' ;
                //\Log::info("<<<<<<<<<<<<<<<<<<<<<<  In getExisting contacts before >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                /*$queryString = "START node=node:node_auto_index('emailid:*') 
                                where node.emailid in[".$emailsIds."]
                                RETURN node" ;  */          
                $queryString = "Match (u) where u.emailid IN [".$emailsIds."] or replace(u.phone, '-', '') IN[".$phoneString."] "
                        . "and ('Mintmesh' IN labels(u) OR  'Imported' IN labels(u) OR 'User' IN labels(u)) return distinct(u)" ;
                //echo $queryString ;exit;
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
               // \Log::info("<<<<<<<<<<<<<<<<<<<<<<  In getExisting contacts before >>>>>>>>>>>>>>>>>>>>> ".$queryString.date('H:i:s'));
                 
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
            else
            {
                return false ;
            }
        }
        /*
         * get node details
         */
        public function getNodeDetails($email = '', $phone = '')
        {
            $email = $this->appEncodeDecode->filterString(strtolower($email));
            $result = NeoUser::whereIn('emailid', $email)->where('phone', $phone)->first();
            return $result ;
        }
        /*
         * create relationships among users
        */
        public function relateContacts($fromUser , $toUser , $relationAttrs = array(),$nonMintmesh=0)
        {
            $toUserId = $toUser['id']->getId() ;
            $toUserEmailId =  !empty($toUser->emailid)?$toUser->emailid:'';
            $fromUserEmailId =  !empty($fromUser->emailid)?$fromUser->emailid:'';
            if (!empty($fromUser) && !empty($toUser) && $toUserEmailId != $fromUserEmailId )//ignore if same user
            {
                $label = "User";
                if (!empty($nonMintmesh)){
                    $label = "NonMintmesh";
                }
                $queryString = "Match (m:".$label."), (n:User:Mintmesh)
                                where ID(m)=".$toUserId."  and n.emailid='".$fromUserEmailId."'
                                create unique (n)-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED');

                $queryString.="]->(m)  set r.created_at='".date("m-d-Y H:i:s A")."'";
                //set other relations
                if (!empty($relationAttrs))
                {
                    $queryString.=" , " ;
                    foreach ($relationAttrs as $atrName=>$atrVal)
                    {
                        $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                    }
                    $queryString = rtrim($queryString,',');
                }
                //echo $queryString ; exit;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();

            }

        }
       /*  public function relateContacts($fromUser , $toUser , $relationAttrs = array())
        {
            $toUserId = $toUser['id']->getId() ;
            if (!empty($fromUser) && !empty($toUser) && $toUserId != $fromUser->id )//ignore if same user
            {
                //check if relation is already existing
                $importRel = $this->checkImportRelation($fromUser->id, $toUserId);
                if (count($importRel)) //update
                {
                     $queryString = "Match (n:User)-[r]->(m:User)
                                where ID(m)=".$toUserId."  and ID(n)=".$fromUser->id;
                    $queryString.= " and n-[:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]->m" ;
                    //$queryString = "Match (n:User {id:".$fromUser->id."})-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]
                    //                ->(m:User {id:".$toUserId."}) " ;
                    
                }
                else //create
                {
                    $queryString = "Match (m:User), (n:User)
                                where ID(m)=".$toUserId."  and ID(n)=".$fromUser->id."
                                create (n)-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]->(m)";
                }
               // Match (n:User)-[r]->(m:User) where ID(m)=6478  and ID(n)=6472 and n-[:IMPORTED]->m
               
                $queryString.=" set r.created_at='".date("m-d-Y H:i:s A")."'";
                //set other relations
                if (!empty($relationAttrs))
                {
                    $queryString.=" , " ;
                    foreach ($relationAttrs as $atrName=>$atrVal)
                    {
                        $queryString.="r.".$atrName."='".$this->appEncodeDecode->filterString($atrVal)."',";
                    }
                    $queryString = rtrim($queryString,',');
                }
                echo $queryString ; exit;
                $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();

            }

        }
        */
        public function checkImportRelation($fromId="", $toId="")
        {
            $queryString = "Match (n:User:Mintmesh)-[r]->(m:User)
                                where ID(n)=".$fromId."  and ID(m)=".$toId." and n-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]->m return r";
            $query = new CypherQuery($this->client, $queryString);
            return $result = $query->getResultSet();
        }
        public function relateInvitees($fromUser , $toUser, $relationAttrs=array())
        {
            
            if ($toUser->id != $fromUser->id )//ignore if same user
            {
                $queryString = "Match (m:User), (n:User:Mintmesh)
                                where ID(m)=".$toUser->id."  and ID(n)=".$fromUser->id."
                                create unique (n)-[r:".Config::get('constants.RELATIONS_TYPES.INVITED');

                $queryString.="]->(m)  set r.created_at='".date("m-d-Y H:i:s A")."'";
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
        
        public function getRelatedMintmeshUsers($email='')
        {
            $email = $this->appEncodeDecode->filterString(strtolower($email)) ;
            $queryString = "MATCH (n:User:Mintmesh {emailid: '".$email."'})-[r:".Config::get('constants.RELATIONS_TYPES.IMPORTED')."]->(m:User:Mintmesh) where HAS (m.login_source) RETURN m";
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
        
        /*
         * delete imported contacts before fresh import
         */
        public function deleteImportedContacts($userEmail='')
        {
            if (!empty($userEmail))
            {
                $queryString = "match (u:User:Mintmesh)-[r:IMPORTED]->(n:User) where u.emailid='".$userEmail."' and not (u)-[:ACCEPTED_CONNECTION]-(n)  delete r";
                 $query = new CypherQuery($this->client, $queryString);
                return $result = $query->getResultSet();
            }
            else
            {
                return 0;
            }
        }
        
        public function getContactByPhoneAndName($phone='', $name=""){
            $phone = $this->appEncodeDecode->filterString(strtolower($phone));
            $name = $this->appEncodeDecode->filterString(strtolower($name));
            $queryString = "match (u:User) where fullname='".$name."' and phone='".$phone."' return u limit 1";
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
        public function getContactByEmailid($emailid=""){
            $emailid = $this->appEncodeDecode->filterString(strtolower($emailid));
            $queryString = "match (u:User) where u.emailid='".$emailid."' return u limit 1";
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
        
        public function createNodeAndRelationForPhoneContacts($from, $neoInput=array(),$relationAttrs = array(),$isImported=0)
        {
            $from = $this->appEncodeDecode->filterString(strtolower($from));
            try{
                $queryString = "MATCH (u:User:Mintmesh)
                            WHERE u.emailid = '".$from."'
                            CREATE (m:NonMintmesh";
                if (!empty($isImported)){//add imported label if created from import contacts
                    $queryString.=":Imported";
                }
                if (!empty($neoInput))
                {
                    $queryString.="{";
                    foreach ($neoInput as $k=>$v)
                    {
                        if ($k == 'emailid')
                            $v= strtolower ($v);
                        $queryString.=$k.":'".$this->appEncodeDecode->filterString($v)."'," ;
                    }
                    $queryString = rtrim($queryString, ",") ;
                    $queryString.="}";
                }
                $queryString.=")<-[:".Config::get('constants.RELATIONS_TYPES.IMPORTED');
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
                $queryString.="]-(u)" ;
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
            } catch (\Everyman\Neo4j\Exception $ex) {
                    return false ;
            }
            
        }
        
        public function getNonMintmeshContact($phone=''){
            if (!empty($phone)){
                $phone = $this->appEncodeDecode->filterString(strtolower($phone));
                $queryString = "match (u:NonMintmesh) where u.phone='".$phone."' return u limit 1";
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if ($result->count())
                {
                    return $result ;
                }
                else
                {
                    return 0 ;
                }
            }
        }
        
        public function getImportRelationDetailsByEmail($email1='', $email2=''){
            $email1 = $this->appEncodeDecode->filterString(strtolower($email1));
            $email2 = $this->appEncodeDecode->filterString(strtolower($email2));
            $queryString = "match (u:User:Mintmesh)-[r:IMPORTED]-(c:User) where u.emailid='".$email1."' and c.emailid='".$email2."' return r";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if ($result->count())
            {
                return $result[0][0] ;
            }
            else
            {
                return 0 ;
            }
            
        }
        
        public function getImportRelationDetailsByPhone($email1='', $phone=''){
            $email1 = $this->appEncodeDecode->filterString(strtolower($email1));
            $phone = $this->appEncodeDecode->filterString(strtolower($phone));
            $queryString = "match (u:User:Mintmesh)-[r:IMPORTED]-(c:NonMintmesh) where u.emailid='".$email1."' and c.phone='".$phone."' return r";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if ($result->count())
            {
                return $result[0][0] ;
            }
            else
            {
                return 0 ;
            }
            
        }
        
        public function getNonMintmeshImportedContact($phone=''){
            if (!empty($phone)){
                $phone = $this->appEncodeDecode->formatphoneNumbers($phone);
                $queryString = "match (u:NonMintmesh:Imported) where replace(u.phone, '-', '') ='".$phone."' return u limit 1";
                $query = new CypherQuery($this->client, $queryString);
                $result = $query->getResultSet();
                if ($result->count())
                {
                    return $result ;
                }
                else
                {
                    return 0 ;
                }
            }
        }
        
        public function copyImportRelationsToMintmeshLabel($emailid='', $phone=''){
            $queryString = "MATCH (n1:User:Mintmesh)-[r:IMPORTED]->(n2:NonMintmesh:Imported)
                            where replace(n2.phone, '-', '') = '".$phone."' with r,n1
                            MATCH (n3:User:Mintmesh{emailid:'".$emailid."'})
                            CREATE unique (n1)-[r1:IMPORTED]->(n3) set r1.firstname=r.firstname,r1.lastname=r.lastname,r1.fullname=r.fullname,r1.phone='phone'
                            ";
            $query = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            return true;
        }
       
        
}
?>
