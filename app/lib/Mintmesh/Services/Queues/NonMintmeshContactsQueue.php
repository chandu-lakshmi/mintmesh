<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
class NonMintmeshContactsQueue {

    protected $contactsRepository ;
    public function __construct(ContactsRepository $contactsRepository)
    {
        $this->contactsRepository = $contactsRepository;
    }
    public function fire($job, $jobData)
    {
        $neoInput = $jobData['neoInput'] ;
        $relationAttrs = $jobData['relationAttrs'] ;
        $this->contactsRepository->createNodeAndRelationForPhoneContacts($jobData['from_user_email'], $neoInput, $relationAttrs,1);
        $job->delete();
    }

}