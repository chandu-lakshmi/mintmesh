<?php
namespace Mintmesh\Services\Queues;

use Mintmesh\Services\IntegrationManager\IntegrationManager;
class ProcessHcmJobReferralQueue {

    protected $integrationManager;
    public function __construct(IntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }
    public function fire($job, $pushData)
    {
        print_r($pushData).exit;
        $jobDetails  =  $pushData['job_details'];
        $userDetails =  $pushData['user_details'];
        $relation    =  $pushData['rel_details'];
        $this->integrationManager->processHcmJobReferral($jobDetails, $userDetails, $relation) ;
        $job->delete();
    }

}
