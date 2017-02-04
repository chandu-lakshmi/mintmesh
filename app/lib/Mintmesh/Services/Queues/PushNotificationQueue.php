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
        
        $message        = $jobData['data'];
        $emailId        = $jobData['emailid'];
        $osType         = $jobData['os_type'];
        #check Platform Endpoint already created or not
        if(!empty($jobData['deviceToken']) && empty($jobData['platformArn'])){
            
            if ($osType == Config::get('constants.IOS')){
                $platformArn  = Config::get('constants.PLATFORM_ARN_IOS');
            } else {
                $platformArn  = Config::get('constants.PLATFORM_ARN_ANDROID');
            }
            #create Platform Endpoint
            $deviceToken = $jobData['deviceToken'];
            $res         = $this->notificationManager->createPlatformEndpoint($deviceToken, $platformArn);
            $endpointArn = $res['EndpointArn'];
            $this->neoUserRepository->updateDeviceEndpointArn($emailId, $deviceToken, $endpointArn);
            
        }  else {
            $endpointArn = $jobData['EndpointArn'];
        }
        #publish Push Notification here
        $this->notificationManager->publish($message, $endpointArn);
        $job->delete();
    }

}