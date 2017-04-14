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
        $companyCode =  $jobData['company_code'];
        $jobDetails  =  $jobData['job_details'];
        $userDetails =  $jobData['user_details'];
        $relation    =  $jobData['rel_details'];
        $this->integrationManager->processHcmJobReferral($jobDetails, $userDetails, $relation, $companyCode) ;
        $job->delete();
    }

}
