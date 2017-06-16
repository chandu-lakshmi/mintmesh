<?php 
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mintmesh\Services\IntegrationManager\IntegrationManager;
use Mintmesh\Services\IntegrationManager\SFManager;

class hcmJob extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */

	protected $name = 'hcmJob:run';

	protected $description = 'Command test';
	protected $integrationManager;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
        {
            parent::__construct();

            $this->SFManager = new SFManager();
        }

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */

    public function fire()
    {
        $jobId = $this->option('job_id');
        
        $res = $this->SFManager->intiateRequest($jobId);
        //$res = $this->integrationManager->createJob($jobId);
        //print_r($jobId).exit;
        

        $this->info('This a command test'.$res);
    }
    
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::OPTIONAL, 'An example argument.'],
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
            ['job_id', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];

    }


}