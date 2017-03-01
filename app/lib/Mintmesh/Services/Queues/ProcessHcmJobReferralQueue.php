<?php
namespace Mintmesh\Services\Queues;

use Mintmesh\Services\IntegrationManager\IntegrationManager;
class ProcessHcmJobReferralQueue {

    protected $integrationManager;
    public function __construct(IntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }
    public function fire($job, $jobData)
    {
        $jobDetails  =  $jobData['job_details'];
        $userDetails =  $jobData['user_details'];
        $relation    =  $jobData['rel_details'];
        $this->integrationManager->processHcmJobReferral($jobDetails, $userDetails, $relation) ;
        $job->delete();
    }

}
