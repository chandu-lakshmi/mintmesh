<?php

namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
class ConfidentScoreQueue {

    protected $referralsRepository ;
    public function __construct(ReferralsRepository $referralsRepository)
    {
        $this->referralsRepository = $referralsRepository;
    }
    public function fire($job, $jobData)
    {
        $relationID = $jobData['relationID'];
        $this->referralsRepository->calculateSolicitedConfidenceScore($relationID) ;
        $job->delete();
    }

}
