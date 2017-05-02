<?php 
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mintmesh\Services\IntegrationManager\IntegrationManager;
use Mintmesh\Services\IntegrationManager\ZenefitsManager;

class zenefits extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */

	protected $name = 'zenefits:run';
	protected $description = 'Command test';
	protected $integrationManager;
	protected $zenefitsManager;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
        {
            parent::__construct();

           $this->zenefitsManager = new ZenefitsManager();
        }

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */

    public function fire()
    {
        $jobId = $this->option('job_id');
        
        $res = $this->zenefitsManager->insertContacts($jobId);
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