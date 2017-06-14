<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
class PostsQueue {

    protected $referralsRepository ;
    public function __construct(ReferralsRepository $referralsRepository)
    {
        $this->referralsRepository = $referralsRepository;
    }
    public function fire($job, $jobData)
    {
        $serviceId = $jobData['serviceId'] ;
        $user = $jobData['user'] ;
        $relationName = $jobData['relationName'] ;
        $relationAttrs = $jobData['relationAttrs'] ;
        $this->referralsRepository->excludeOrIncludeContact($serviceId, $user, $relationAttrs, $relationName) ;
        $job->delete();
    }

}