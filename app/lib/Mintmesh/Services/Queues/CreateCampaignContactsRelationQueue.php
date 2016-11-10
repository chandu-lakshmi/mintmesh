<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\Post\PostGateway;
class CreateCampaignContactsRelationQueue {

    protected $postGateway ;
    public function __construct(PostGateway $postGateway)
    {
        $this->postGateway = $postGateway;
    }
    public function fire($job, $jobData)
    {
        $this->postGateway->createCampaignContactsRelation($jobData) ;
        $job->delete();
    }

}