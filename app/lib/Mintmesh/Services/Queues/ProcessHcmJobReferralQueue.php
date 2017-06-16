<?php
namespace Mintmesh\Services\Queues;

use Mintmesh\Services\IntegrationManager\SFManager;
class ProcessHcmJobReferralQueue {

    protected $SFManager;
    public function __construct(SFManager $SFManager)
    {
        $this->SFManager = $SFManager;
    }
    public function fire($job, $jobData)
    {   
        $companyCode =  $jobData['company_code'];
        $jobDetails  =  $jobData['job_details'];
        $userDetails =  $jobData['user_details'];
        $relation    =  $jobData['rel_details'];
        $this->SFManager->processHcmJobReferral($jobDetails, $userDetails, $relation, $companyCode) ;
        $job->delete();
    }

}
