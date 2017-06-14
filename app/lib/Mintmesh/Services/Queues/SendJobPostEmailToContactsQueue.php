<?php namespace Mintmesh\Services\Queues;
use Mintmesh\Gateways\API\Post\PostGateway;
class SendJobPostEmailToContactsQueue {

    protected $postGateway ;
    public function __construct(PostGateway $postGateway)
    {
        $this->postGateway = $postGateway;
    }
    public function fire($job, $jobData)
    {
        $this->postGateway->sendJobPostEmailToContacts($jobData) ;
        $job->delete();
    }

}