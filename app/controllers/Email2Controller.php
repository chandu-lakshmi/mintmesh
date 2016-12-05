<?php
//namespace Email;

//use Mintmesh\Gateways\API\Post\PostGateway;
//use lib\Email\Parse_manager;
use Mintmesh\Services\APPEncode\APPEncode;
use Models\EmailcaptureModel;
use PhpImap\Mailbox as ImapMailbox;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;
use lib\Parser\DocxConversion;
use lib\Parser\PdfParser;
class Email2Controller extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/
public function getMails3()
	{
		$target_file='mail_attchments/wmt_go_100/Gantt - Store #5658.pdf';
		$imageFileType='pdf';
			if ($imageFileType == 'pdf') {
				$pdfObj = new PdfParser();
				
				$resumeText = $pdfObj->parseFile($target_file);
				// $resumeText = $pdfObj->getText();
			} else {
				$docObj = new DocxConversion($target_file);
				$resumeText = $docObj->convertToText();
			}
			 
               $records = APPEncode::getParserValues($resumeText);
					echo "<pre>";
				print_r($resumeText);exit;
	}
	public function getMails()
	{
		set_time_limit(0);
		$EmailcaptureModel = new EmailcaptureModel();
		$cm_id = 1;
		$settings = $EmailcaptureModel->db_setting($cm_id);
		if($settings){
			$mail_set = $settings->host.':'.$settings->port;
	$mailbox = new ImapMailbox('{'.$mail_set.'/imap/ssl/novalidate-cert}INBOX', $settings->user, $settings->pwd, 'uploads/'.$settings->attachment_path);
//print_r($mailbox);exit;
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
		echo 123;
		/*$Parse_manager = new Parse_manager();
		$data = $Parse_manager::getInstance(array());*/
		/*$EmailcaptureModel = new EmailcaptureModel();
		$mail_id =$EmailcaptureModel->getEmailInfo($cm_id='1');//array(1,85);
		print_r($mail_id);*/
		}
	}

}
