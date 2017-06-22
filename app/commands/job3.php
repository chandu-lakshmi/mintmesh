<?php
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mintmesh\Services\APPEncode\APPEncode;
use Models\EmailcaptureModel;
use PhpImap\Mailbox as ImapMailbox;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;
use lib\Parser\DocxConversion;
use lib\Parser\PdfParser;
class job3 extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'job3:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get inbox mails';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $dir = __DIR__;
	$dir_array = explode('/', $dir, -2);
        $dir_str = implode('/',$dir_array);
        DB::statement("insert into cron_details (type) values('job3')");
        set_time_limit(0);
        $EmailcaptureModel = new EmailcaptureModel();
	$cm_id = 1;
	$settings = $EmailcaptureModel->db_setting($cm_id);
	if($settings){
            $mail_set = $settings->host.':'.$settings->port;
            $mailbox = new ImapMailbox('{'.$mail_set.'/imap/ssl/novalidate-cert}INBOX', $settings->user, $settings->pwd, $dir_str.'/uploads/'.$settings->attachment_path);
            // Read all messaged into an array:
            $mailsIds = $mailbox->searchMailbox('UNSEEN');
            if(!$mailsIds) {
                die('Mailbox is empty');
            }
	    /*print_r($mailsIds);//exit;
	    echo "<pre>";*/
	    foreach($mailsIds as $mailId){
	    // Get the first message and save its attachment(s) to disk:
                $mail = $mailbox->getMail($mailId);
		$mail_att = $mail->getAttachments();
		/*print_r($mail);
		echo "\n\n\n\n\n";
		var_dump($mail->getAttachments());*/
		$EmailcaptureModel->saveEmailData($mail,$mail_att);
	    }
           
	    /*$Parse_manager = new Parse_manager();
	    $data = $Parse_manager::getInstance(array());*/
	    /*$EmailcaptureModel = new EmailcaptureModel();
	    $mail_id =$EmailcaptureModel->getEmailInfo($cm_id='1');//array(1,85);
	    print_r($mail_id);*/
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