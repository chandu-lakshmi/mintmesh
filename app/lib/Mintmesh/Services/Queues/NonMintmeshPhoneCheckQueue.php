<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
class NonMintmeshPhoneCheckQueue {

    protected $contactsRepository ;
    public function __construct(ContactsRepository $contactsRepository)
    {
        $this->contactsRepository = $contactsRepository;
    }
    public function fire($job, $jobData)
    {
        $contacts = $this->contactsRepository->getNonMintmeshImportedContact($jobData['user_phone']);
        if (!empty($contacts)){
            foreach ($contacts as $contact){
                $this->contactsRepository->copyImportRelationsToMintmeshLabel($jobData['user_email'], $jobData['user_phone']);
            }
        }
        $job->delete();
    }

}