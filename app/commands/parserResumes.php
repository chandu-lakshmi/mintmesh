<?php 
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use API\SocialContacts\ContactsController;
use Mintmesh\Services\IntegrationManager\AIManager;


class parserResumes extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */

	protected $name = 'parserResumes:run';
	protected $description = 'Command test';
	protected $contacts;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
        {
            parent::__construct();

           $this->contacts = new AIManager();
        }

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */

    public function fire()
    {
        $res = $this->contacts->getResumesUpdateStatus();
        $this->info('Resume Parser'.$res);
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