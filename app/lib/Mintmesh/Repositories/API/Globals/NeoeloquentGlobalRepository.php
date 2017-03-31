<?php

namespace Mintmesh\Repositories\API\Globals;

use NeoEnterpriseUser;
use DB;
use Config;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode;

class NeoeloquentGlobalRepository extends BaseRepository implements NeoGlobalRepository {

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
    
    public function getJobsList($userEmailId='', $companyCode='',$page=0, $search = '') {
        $skip = $limit = 0;
        if (!empty($page)){
            $limit = $page*10;
            $skip  = $limit - 10;
        }
        if(!empty($userEmailId)){
             $queryString = "";
            if (!empty($search)) {
                $search = explode(' ', $search);
                $numItems = count($search);
                $i = 0;
                $queryString .= "start p=node:node_auto_index(' ";
                foreach ($search as $s){
                $search = $this->appEncodeDecode->filterString($s);
                $queryString .= "(service_name:(*".$search."*) OR service_location:(*".$search."*) OR post_type:(*".$search."*) OR name:(*".$search."*) OR campaign_type:(*".$search."*) OR campaign_name:(*".$search."*) OR address:(*".$search."*) OR city:(*".$search."*) OR state:(*".$search."*) OR country:(*".$search."*))";
                if(++$i != $numItems) {
                   $queryString .= " AND ";
                }
                }
                $queryString .= "') ";
                }
//                $queryString .= "start p=node:node_auto_index('service_name:(*".$search."*) OR service_location:(*".$search."*) OR post_type:(*".$search."*) OR name:(*".$search."*) OR campaign_type:(*".$search."*) OR campaign_name:(*".$search."*) OR address:(*".$search."*) OR city:(*".$search."*) OR state:(*".$search."*) OR country:(*".$search."*)')";
//            }
//                print_r($queryString).exit;

            $jobsList = $this->getJobsAndCampaignsList($userEmailId, $companyCode,$page,$queryString,$limit,$skip);
            return $jobsList;
        }
          
    }
    
    private function getJobsAndCampaignsList($userEmailId='', $companyCode='',$page=0,$queryString='',$limit='',$skip='') {
        $queryString .= "MATCH (u:User:Mintmesh{emailid:'".$userEmailId."'})-[r:INCLUDED]-(p:Post{status:'ACTIVE'})-[:POSTED_FOR]-(Company{companyCode:'".$companyCode."'})
                WHERE  p.post_type <>'campaign'
                WITH collect({post:p,rel:r}) as posts 
                OPTIONAL MATCH (u:User:Mintmesh{emailid:'".$userEmailId."'})-[r:CAMPAIGN_CONTACT]-(p:Campaign{status:'ACTIVE', company_code:'".$companyCode."'}) 
                WITH posts + collect({post:p,rel:r}) as rows
                UNWIND rows as row
                RETURN row ORDER BY row.post.created_at DESC";
        if (!empty($limit) && !($limit < 0))
        {
            $queryString.=" skip ".$skip." limit ".self::LIMIT ;
        }
        $query  = new CypherQuery($this->client, $queryString);
//        print_r($query).exit;
        $result = $query->getResultSet();
        if($result){
           return $result;
            
        }else{
            return false;
        }
    }
   
   }

?>
