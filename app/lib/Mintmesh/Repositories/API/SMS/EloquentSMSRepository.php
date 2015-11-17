<?php namespace Mintmesh\Repositories\API\SMS;


use SMS_Logs;
use Config ;
use Mintmesh\Repositories\BaseRepository;
use Mintmesh\Services\APPEncode\APPEncode ;
class EloquentSMSRepository extends BaseRepository implements SMSRepository {

        protected $sms, $appEncodeDecode;
        
        public function __construct(APPEncode $appEncodeDecode, SMS_Logs $sms)
        {
                //parent::__construct($user);
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->sms = $sms ;
        }
        // creating new sms log in storage
        public function logSMS($input)
        {
            $user = array(
                        "from_email" => $this->appEncodeDecode->filterString(strtolower($input['from_email'])),
                        "to_number"  =>$this->appEncodeDecode->filterString($input['to_number']),
                        "message"   =>$this->appEncodeDecode->filterString($input['message']),
                        "send_status"   =>$this->appEncodeDecode->filterString($input['send_status']),
                        "sms_type_id"   =>$input['sms_type'],
                        "twilio_response"   =>$this->appEncodeDecode->filterString($input['twilio_response'])
            );
            return $this->sms->create($user);
        }
        
}
