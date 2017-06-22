<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\Post\PostGateway;
class CompanyPostsAutoConnectWithContactQueue {

    protected $enterpriseGateway ;
    public function __construct(PostGateway $postGateway)
    {
        $this->postGateway = $postGateway;
    }
    public function fire($job, $jobData)
    {
        $this->postGateway->companyPostsAutoConnectWithContactQueue($jobData) ;
        $job->delete();
    }

}