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
    
    public function getCandidateDetails($companyCode = '', $candidateId = '', $referenceId = '') {
        
        $return = 0;
        if(($candidateId || $referenceId)){
            
            if($referenceId) { 
                $queryString = "MATCH (p:Post)<-[r:GOT_REFERRED]-(u) where ID(r)=".$referenceId."  return u,r,p";
            } elseif ($candidateId) {
                $queryString = "MATCH (u:User) where ID(u)=".$candidateId."  return u";
            }
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if(isset($result[0]) && isset($result[0][0])){
                $return = $result[0];
            }
        }
        return $return; 
    }
    
    public function getCandidateTagJobsList($companyCode = '', $search = "") {
        
        $return = FALSE;  
        if ($companyCode) {
            #required query string parameters form here
            $searchQuery = $limitQuery = '';
            if (!empty($search)) {
                $search = $this->appEncodeDecode->filterString($search);
                $searchQuery =  "and (p.service_name =~ '(?i).*". $search .".*' or p.service_location =~ '(?i).*". $search .".*') ";
            }    
            $baseQuery = "MATCH (p:Post{status:'ACTIVE'})-[:POSTED_FOR]-(:Company{companyCode:'" . $companyCode . "'}) where p.post_type <> 'internal'  ";        
            #query string formation here
            $queryString  = $baseQuery.$searchQuery;
            $queryString .= " return p ORDER BY p.created_at DESC";
            $query = new CypherQuery($this->client, $queryString);
            $return = $query->getResultSet();
        } 
        return $return;
    }
    
}

?>
