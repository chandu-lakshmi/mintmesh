<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
class ContactsQueue {

    protected $contactsRepository ;
    public function __construct(ContactsRepository $contactsRepository)
    {
        $this->contactsRepository = $contactsRepository;
    }
    public function fire($job, $jobData)
    {
        $neoInput = $jobData['neoInput'] ;
        $relationAttrs = $jobData['relationAttrs'] ;
        $this->contactsRepository->createContactAndRelation($jobData['from_user_id'], $neoInput, $relationAttrs) ;
        $job->delete();
    }

}