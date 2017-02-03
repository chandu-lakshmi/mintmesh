<?php
use Illuminate\Console\Command;
use Mintmesh\Services\Notification\NotificationManager;

class testJob extends Command { 
    protected $name = 'testJob:run';
    protected $notification;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->notification = new NotificationManager();
    }
    
    public function fire()
    { 
        
        echo "in fire method \n";
        #IOS
        //$platformArn = "arn:aws:sns:us-west-2:548244693820:app/APNS_SANDBOX/MintMeshDev";
        #Android
        //$platformArn = "arn:aws:sns:us-west-2:548244693820:app/GCM/MintMeshEnterpriseAndroidDev";//stg
        $platformArn = "arn:aws:sns:us-west-2:548244693820:app/GCM/MintmeshAndroid202ENV";//202
        $message = 'Amazon SNS Mobile Push message for Hi Gopi.';
        //$deviceToken = '61a6357045a769455b6ca6f98687d286a4d9e1e846360bbf2372f9d6cdf0c318';
        //$deviceToken = 'fF1ZYr8H09g:APA91bFFzhzWDvQ5ulaOienV7KI66TCqZNSTH1FzU70vuzQiyo0wEE3UrBnO0iqZcLscOoSMnTI_eMvhljKorC6Dji5t8jN2pRC-wUxSdsvT8RaWFp3csuDSeRIXVsS-PZqPP7Tc15t7';
        $deviceToken = 'APA91bHfl1g2EsVZr2qMPbK-_W-pZguSffYZx1tWRHNlKZxb4nQOQYIl5V7kxMvXzQU2Lm5fD3sOprCWlUWqmKzUMVOcdndqTvH2qutRc5mxtZxUcHJ3mxS5QpCVVFSLotXwRrTKdpzxNIpNw8rD0WYUkjoNusamIg';
        
        $res = $this->notification->createPlatformEndpoint($deviceToken, $platformArn);
        //print_r($res).exit;
        //$EndpointArn = "arn:aws:sns:us-west-2:548244693820:endpoint/APNS_SANDBOX/MintMeshDev/fb4ad7dc-3336-3c76-8763-b90ecbdeaa9f";
        $this->notification->publish($message, $res['EndpointArn']);
        //$this->notification->publish($message, $EndpointArn);
    }
    
    
    /*
     * Guzzle\Service\Resource\Model Object
    (
        [structure:protected] =>
        [data:protected] => Array
            (
                [EndpointArn] => arn:aws:sns:us-west-2:548244693820:endpoint/APNS_SANDBOX/MintMeshDev/fb4ad7dc-3336-3c76-8763-b90ecbdeaa9f
                [ResponseMetadata] => Array
                    (
                        [RequestId] => b9031feb-c66a-5bf9-9be1-4f1bf4de3ed7
                    )

            )
    )
     * MintMeshDev
        Apple iOS Dev
        arn:aws:sns:us-west-2:548244693820:app/APNS_SANDBOX/MintMeshDev
        MintMeshEnterpriseAndroidDev
        Google Android
        arn:aws:sns:us-west-2:548244693820:app/GCM/MintMeshEnterpriseAndroidDev
        dev platform ARNs
     */
}