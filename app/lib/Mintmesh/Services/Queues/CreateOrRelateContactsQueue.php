<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Gateways\API\SocialContacts\ContactsGateway;
class CreateOrRelateContactsQueue {

    protected $contactsGateway ;
    public function __construct(ContactsGateway $contactsGateway)
    {
        $this->contactsGateway = $contactsGateway;
    }
    public function fire($job, $jobData)
    {
        //foreach($jobData['contacts'] as $jd) {
            $this->contactsGateway->checkToCreateOrRelateContacts($jobData['emails'], $jobData['phones'], $jobData['contact'], $jobData['fromUser']) ;
        //}
        $job->delete();
    }

}