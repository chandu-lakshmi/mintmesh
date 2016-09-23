<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\Enterprise\EnterpriseGateway;
class CreateEnterpriseContactsQueue {

    protected $enterpriseGateway ;
    public function __construct(EnterpriseGateway $enterpriseGateway)
    {
        $this->enterpriseGateway = $enterpriseGateway;
    }
    public function fire($job, $jobData)
    {
        //$this->enterpriseGateway->checkToCreateEnterpriseContactsQueue($jobData['firstname'],$jobData['lastname'],$jobData['emailid'],$jobData['contact_number'],$jobData['other_id'],$jobData['status'],$jobData['bucket_id'],$jobData['company_code'],$jobData['loggedin_emailid']) ;
        $this->enterpriseGateway->createContactNodes($jobData) ;
        //print_r($jobData);
        $job->delete();
    }

}