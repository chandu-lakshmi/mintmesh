<?php namespace Mintmesh\Services\Queues;

use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Services\Notification\NotificationManager;
use Config ;
class PushNotificationQueue {
    
    protected $notificationManager , $neoUserRepository;
    public function __construct(NotificationManager $notificationManager, NeoUserRepository $neoUserRepository)
    {
        $this->notificationManager  = $notificationManager;
        $this->neoUserRepository    = $neoUserRepository;
    }
    
    public function fire($job, $jobData)
    { 
       \Log::info("<<<<<<<<<<<<<<<< In kj fire >>>>>>>>>>>>>".print_r($jobData,1));
        $platformArn    = '';
        $data           = $jobData['data'];
        $emailId        = $jobData['emailid'];
        $osType         = $jobData['os_type'];
        
        if ($osType == Config::get('constants.IOS')){
            $platformArn  = Config::get('constants.PLATFORM_ARN_IOS');
            $message = '{"APNS": "{ \"aps\": { \"alert\": \"'.$data['message'].'\", \"from_user\":\"'.$data['from_user'].'\", \"id\":\"'.$data['id'].'\", \"referral\":\"'.$data['referral'].'\", \"referred_by\":\"'.$data['referred_by'].'\", \"relation_count\":\"'.$data['relation_count'].'\",\"referred_by_phone\":\"'.$data['referred_by_phone'].'\",\"note_type\":\"'.$data['note_type'].'\" } }"}';
        } else if ($osType == Config::get('constants.ANDROID')){
            $platformArn  = Config::get('constants.PLATFORM_ARN_ANDROID');
            $message = '{"GCM": "{ \"data\": { \"Message\": \"'.$data['message'].'\", \"from_user\":\"'.$data['from_user'].'\", \"id\":\"'.$data['id'].'\", \"referral\":\"'.$data['referral'].'\", \"referred_by\":\"'.$data['referred_by'].'\", \"relation_count\":\"'.$data['relation_count'].'\",\"referred_by_phone\":\"'.$data['referred_by_phone'].'\",\"note_type\":\"'.$data['note_type'].'\" } }"}';
        }
       
        #check Platform Endpoint already created or not
        if(!empty($jobData['deviceToken']) && empty($jobData['platformArn']) && !empty($platformArn)){

            #create Platform Endpoint
            $deviceToken = $jobData['deviceToken'];
            $res         = $this->notificationManager->createPlatformEndpoint($deviceToken, $platformArn);
            $endpointArn = $res['EndpointArn'];
            $this->neoUserRepository->updateDeviceEndpointArn($emailId, $deviceToken, $endpointArn);
           
        }  else {
            $endpointArn = $jobData['EndpointArn'];
        }
        
        if(!empty($platformArn)){
            #publish Push Notification here
            \Log::info("<<<<<<<<<<<<<<<< In kj Notification here >>>>>>>>>>>>>".print_r($message,1));
            \Log::info("<<<<<<<<<<<<<<<< In kj Notification here end point >>>>>>>>>>>>>".print_r($endpointArn,1));
            $this->notificationManager->publishJson($message, $endpointArn);
        }
        $job->delete();
    }

}