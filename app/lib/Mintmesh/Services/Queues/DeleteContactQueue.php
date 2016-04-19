<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
class DeleteContactQueue {

    protected $contactsRepository ;
    public function __construct(ContactsRepository $contactsRepository)
    {
        $this->contactsRepository = $contactsRepository;
    }
    public function fire($job, $jobData)
    {
        $this->contactsRepository->deleteImportedContacts($jobData['emailid']);
        $job->delete();
    }

}