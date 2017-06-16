<?php namespace Mintmesh\Services\Queues;


use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Mintmesh\Repositories\API\User\UserRepository;
class NonMintmeshPhoneCheckQueue {

    protected $contactsRepository, $userRepository ;
    public function __construct(ContactsRepository $contactsRepository,
                                UserRepository $userRepository)
    {
        $this->contactsRepository = $contactsRepository;
        $this->userRepository = $userRepository ;
    }
    public function fire($job, $jobData)
    {
        $contacts = $this->contactsRepository->getNonMintmeshImportedContact($jobData['user_phone']);
        if (!empty($contacts)){
            foreach ($contacts as $contact){
                $a = $this->contactsRepository->copyImportRelationsToMintmeshLabel($jobData['user_email'], $jobData['user_phone']);
            }
        }
        $referredContacts = $this->contactsRepository->getNonMintmeshContact($jobData['user_phone']);
        $getReferredRecords = array();
        if (!empty($referredContacts)){
            foreach ($referredContacts as $rContact){
                //map the got referred relation to the new node created from non mintmesh to mintmesh label
                $b=$this->contactsRepository->copyGotReferredRelationsToMintmeshLabel($jobData['user_email'], $jobData['user_phone']);
                //update the notifications_logs table to change the emailid os phone number
                $c=$this->userRepository->updateNotificationsFromPhoneToEmailId($jobData['user_email'], $jobData['user_phone']);
            }
        }
        $job->delete();
    }

}