<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB as D;
use Config as C;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
class job1 extends Command {
     protected $neoEnterpriseUser,$neoPostRepository, $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'job1:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close campaigns';

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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        DB::statement("insert into cron_details (type) values('job1')");
        // your update function here
        $queryString = "MATCH (c:Campaign) return c";
        $query  = new CypherQuery($this->client, $queryString);
        $result = $query->getResultSet();   
        foreach($result as $key=>$value){
            $campaignId = $value[0]->getID();
            $queryString1 = "MATCH (c:Campaign)-[r:CAMPAIGN_SCHEDULE]-(s:CampaignSchedule) where ID(c)=".$campaignId." return distinct(s) ORDER BY s.start_date ASC";
            $query  = new CypherQuery($this->client, $queryString1);
            $result1 = $query->getResultSet();    
            $currentDate = gmdate("Y-m-d H:i:s");
            if(isset($result1[0])){
                $queryString3 = "MATCH (c:Campaign) where ID(c) = $campaignId ";
                $updated_at = gmdate("Y-m-d H:i:s");
                if($result1[0][0]->gmt_end_date < $currentDate  && $value[0]->status == 'ACTIVE'){
                    $queryString3 .= "set c.status = 'CLOSED',c.updated_at = '".$updated_at."'";
                }                 
                else if($result1[0][0]->gmt_end_date > $currentDate  && $value[0]->status == 'CLOSED'){
                    $queryString3 .= "set c.status = 'ACTIVE',c.updated_at = '".$updated_at."'";
                }
                $queryString3 .= "return c ";
            }
            $query  = new CypherQuery($this->client, $queryString3);
            $result = $query->getResultSet(); 
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [

        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [

        ];
    }

}