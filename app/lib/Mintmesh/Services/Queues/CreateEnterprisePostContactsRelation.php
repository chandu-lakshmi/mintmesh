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
        $this->postGateway->checkToCreateEnterprisePostContactsRelationQueue($jobData['company_code'], $jobData['user_emailid'], $jobData['bucket_id'],$jobData['postId'],$jobData['notification_msg']) ;
        $job->delete();
    }

}