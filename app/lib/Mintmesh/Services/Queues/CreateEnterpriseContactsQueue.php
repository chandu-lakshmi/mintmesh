<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
class CreateEnterpriseContactsQueue {

    protected $enterpriseGateway ;
    public function __construct(EnterpriseGateway $enterpriseGateway)
    {
        $this->enterpriseGateway = $enterpriseGateway;
    }
    public function fire($job, $jobData)
    {
        $this->enterpriseGateway->createContactNodes($jobData) ;
        $job->delete();
    }

}