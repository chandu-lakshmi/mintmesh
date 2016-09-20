<?php namespace Mintmesh\Services\Queues;

use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
class EmailInvitationEnterpriseContactsQueue {

    protected $enterpriseGateway ;
    public function __construct(EnterpriseGateway $enterpriseGateway)
    {
        $this->enterpriseGateway = $enterpriseGateway;
    }
    public function fire($job, $jobData)
    {
        $this->enterpriseGateway->enterpriseSendContactsEmailInvitation($jobData) ;
        $job->delete();
    }

}