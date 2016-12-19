<?php
use Mintmesh\Services\APPEncode\APPEncode;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB as D;
use Config as C;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
use Mintmesh\Services\Parser\ParserManager;

class parserJob extends Command {
     protected $neoEnterpriseUser,$neoPostRepository, $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'parserJob:run';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
                $this->db_user = Config::get('database.connections.neo4j.username');
                $this->db_pwd = Config::get('database.connections.neo4j.password');
                $this->db_host = Config::get('database.connections.neo4j.host');
                $this->db_port = Config::get('database.connections.neo4j.port');
                $this->client = new NeoClient($this->db_host, $this->db_port);
                $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
                $this->neoEnterpriseUser = $this->db_user;
                $this->userFileUploader = new UserFileUploader;
                $this->appEncodeDecode = new APPEncode();
                $this->Parser = new ParserManager;
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {   
	$dir = __DIR__;
        $dir_array  = explode('/', $dir, -2);
	$dir_str    = implode('/',$dir_array);
        $directory  = $dir_str.'/uploads/s3_resumes/';
        $result     = $this->getParseList();
        if(!empty($result)){
            foreach($result as $value){
               $relation     = $value[0];//relation details
               $status       = 1;
               $relationId   = $relation->getID();
               #update status here
               $updateStatus = $this->updateResumeParsedStatus($relationId, $status);
               #download the file from s3 bucket
               $filepath         =  !empty($relation->resume_path)?$relation->resume_path:'';
               $this->Parser     =  new ParserManager;
               $parsedRes        =  $this->Parser->processParsing($filepath);
               #save the parsed json file path here
               $updateParsedJson =  $this->updateResumeParsedJsonPath($relationId, $parsedRes);
               // adding confident score calcuation job to queue
               $this->addSolicitedConfidentScoreJobtoQueue($relationId);
            }
        }
        return TRUE;
    }
    
    public function getParseList() {
            $return = array();
            $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p:Post) where r.resume_parsed=0 return r LIMIT 1";
            $query  = new CypherQuery($this->client, $queryString);
            $result = $query->getResultSet();
            if($result->count()){
                $return = $result;
            }
        return  $return;   
    }
    
    public function updateResumeParsedStatus($relationId=0, $status=0){
        $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p:Post) where ID(r)=".$relationId." set r.resume_parsed=".$status." RETURN r";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    } 
    
    public function updateResumeParsedJsonPath($relationId=0, $jsonPath=''){
        $queryString = "MATCH (u)-[r:GOT_REFERRED]->(p:Post) where ID(r)=".$relationId." set  r.resume_parsed_Json ='".$jsonPath."' RETURN r";
        $query = new CypherQuery($this->client, $queryString);
        return $result = $query->getResultSet();
    }

    // function for initiating the job for confident score calculation
    public function addSolicitedConfidentScoreJobtoQueue($relationID) {
        $pushData['relationID'] = $relationID;
        Queue::push('Mintmesh\Services\Queues\ConfidentScoreQueue', $pushData);
    }
}