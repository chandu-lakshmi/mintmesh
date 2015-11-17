<?php namespace Mintmesh\Services\Queues;

use Config ;
use Mintmesh\Repositories\API\SMS\SMSRepository;
class SMSQueue {

    protected $smsRepository ;
    protected $twilio,$twilio_sid,$twilio_token,$from_number  ;
    public function __construct(smsRepository $smsRepository)
    {
        $this->smsRepository = $smsRepository;
        $this->twilio_sid = Config::get('constants.TWILIO.SID');
        $this->twilio_token = Config::get('constants.TWILIO.AUTH_TOKEN');
        $this->from_number = Config::get('constants.TWILIO.FROM_NUMBER');
        $this->twilio = new \Aloha\Twilio\Twilio($this->twilio_sid, $this->twilio_token, $this->from_number);
    }
    public function fire($job, $jobData)
    {
        $message = $jobData['message'];
        $number = $jobData['number'] ;
        $smsInput=array();
        $smsInput['sms_type'] = $jobData['type_sms'] ;
        $smsInput['from_email'] = $jobData['from'] ;
        $smsInput['to_number'] = $number ;
        $smsInput['message'] = $message ;
        try{
             $result = $this->twilio->message($number,$message);
             $smsInput['send_status'] = 1 ;
             $successList[]=$number ;
        } catch (\Services_Twilio_RestException $e) {
            $smsInput['send_status'] = 0 ;
            $result = $e ;
        }
        
        $smsInput['twilio_response'] = $result ;
        $this->smsRepository->logSMS($smsInput);
        $job->delete();
    }

}