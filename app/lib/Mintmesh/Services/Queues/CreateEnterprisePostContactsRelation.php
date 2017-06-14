<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\Post\PostGateway;
class CreateEnterprisePostContactsRelation {

    protected $enterpriseGateway ;
    public function __construct(PostGateway $postGateway)
    {
        $this->postGateway = $postGateway;
    }
    public function fire($job, $jobData)
    {
        $this->postGateway->createPostContactsRelation($jobData) ;
        $job->delete();
    }

}