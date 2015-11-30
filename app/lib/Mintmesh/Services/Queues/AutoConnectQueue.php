<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\User\NeoUserRepository;
class AutoConnectQueue {

    protected $neoUserRepository ;
    public function __construct(NeoUserRepository $neoUserRepository)
    {
        $this->neoUserRepository = $neoUserRepository;
    }
    public function fire($job, $jobData)
    {
        $relationAttrs = $jobData['relationAttrs'] ;
        $this->neoUserRepository->AutoConnectUsers($jobData['user_email'], $jobData['to_connect_email'], $relationAttrs) ;
        $job->delete();
    }

}