<?php namespace Mintmesh\Services\Emails;
use Mail;
use Lang;
use Config ;
abstract class EmailManager {

        protected $input;

        protected $errors;
        
        public $emailId, $name, $message, $templatePath, $subject ;
        public $dataSet = array() ;
        

        public function __construct($input = NULL)
        {
                $this->input = $input ?: \Input::all();
        }

        public function sendMail()
        {
            if (!empty($this->emailId))
            {    
                try{
                    $this->dataSet['email'] = $this->emailId ;
                    $this->dataSet['subject'] = $this->subject ;
                    $this->dataSet['public_url'] = Config::get('constants.MNT_PUBLIC_URL');
                    $emailInput = $this->dataSet ;
                    Mail::queue($this->templatePath, $emailInput, function($message) use ($emailInput)  
                    {                                                    
                    $message->to($emailInput['email'], $emailInput['name']);
                    $message->subject($emailInput['subject']);
                    
                    if(!empty($emailInput['send_company_name'])){
                     $message->from(Config::get('constants.MINTMESH_SUPPORT.EMAILID'), $emailInput['send_company_name']); 
                    }
                    //send reply to if added
                    if (!empty($emailInput['reply_to'])){
                         $message->replyTo($emailInput['reply_to']);
                    }
                    //send attachment if attached
                    if (!empty($emailInput['attachment_path'])){
                         $message->attach($emailInput['attachment_path']);
                    }
                        //send attachment if attached
                        if (!empty($emailInput['calendar_event'])){

                            $mime_boundary = "----Meeting Booking----".MD5(TIME());
                            $message->getHeaders()->addTextHeader('MIME-Version', '1.0\n');
                            $message->getHeaders()->addTextHeader('Content-Type', 'multipart/alternative; boundary=\"'.$mime_boundary.'\"\n');
                            $message->getHeaders()->addTextHeader('Content-class', 'urn:content-classes:calendarmessage\n');
                        }
                    });
                   
                    if( count(Mail::failures()) > 0 ) {
                       return false ;

                    } else {
                        return true ;
                    }
                }
                 catch(\RuntimeException $e)
                {
                    return false ;
                }
                
            }
            else
            {
                return false ;
            }
        }

        public function getErrors()
        {
                return $this->errors;
        }


}
