<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\User\NeoUserRepository;
class ConnectToInviteesQueue {

    protected $neoUserRepository ;
    public function __construct(NeoUserRepository $neoUserRepository)
    {
        $this->neoUserRepository = $neoUserRepository;
    }
    public function fire($job, $jobData)
    {
        $relationAttrs = $jobData['relationAttrs'] ;
        $this->neoUserRepository->ConnectToInvitee($jobData['user_email'], $relationAttrs) ;
        $job->delete();
    }

}