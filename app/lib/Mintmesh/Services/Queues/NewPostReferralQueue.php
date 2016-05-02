<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\Referrals\ReferralsGateway;
use Mintmesh\Gateways\API\User\UserGateway;
class NewPostReferralQueue {

    protected $referralsGateway, $userGateway ;
    public function __construct(ReferralsGateway $referralsGateway,UserGateway $userGateway)
    {
        $this->referralsGateway = $referralsGateway;
        $this->userGateway = $userGateway;
    }
    public function fire($job, $jobData)
    {
        $this->referralsGateway->sendPushNotificationsForPosts($jobData['serviceId'], $jobData['loggedinUserDetails'],$jobData['neoLoggedInUserDetails'], $jobData['includedList'], $jobData['excludedList'], $jobData['service_type'], $jobData['service_location']);
        $job->delete();
    }

}