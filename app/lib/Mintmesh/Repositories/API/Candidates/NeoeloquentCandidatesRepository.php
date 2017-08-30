<?php
namespace Mintmesh\Repositories\API\Candidates;

use NeoEnterpriseUser;
use Config, Lang;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\APPEncode\APPEncode;

class NeoeloquentCandidatesRepository extends BaseRepository implements NeoCandidatesRepository {

    protected $neoEnterpriseUser, $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;

    const LIMIT = 10;

    public function __construct(NeoEnterpriseUser $neoUser, APPEncode $appEncodeDecode) {
        parent::__construct($neoUser);
        $this->neoUser  = $neoUser;
        $this->db_user  = Config::get('database.connections.neo4j.username');
        $this->db_pwd   = Config::get('database.connections.neo4j.password');
        $this->db_host  = Config::get('database.connections.neo4j.host');
        $this->db_port  = Config::get('database.connections.neo4j.port');
        $this->client   = new NeoClient($this->db_host, $this->db_port);
        $this->appEncodeDecode = $appEncodeDecode;
        $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
    }
    
    public function getCandidateDetails($companyCode = '', $referredId = '') {
        
           $return = 0;
        if(!empty($companyCode) && !empty($referredId)){
            $queryString = "MATCH (p:Post)<-[r:GOT_REFERRED]-(u) where ID(r)=".$referredId."  return r,u";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0];
            }
        }
        return $return; 
    }
    
}

?>
