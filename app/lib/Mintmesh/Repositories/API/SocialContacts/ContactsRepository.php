<?php namespace Mintmesh\Repositories\API\SocialContacts;


interface ContactsRepository {

     /*
     * get existing contacts count from neo4j
     */
     public function getExistingContacts($emails, $phones);
     /*
      * relate users
      */
     public function relateContacts($fromUser, $toUser);
     /*
      * get node details
      */
     public function getNodeDetails($email, $phone);
     
     public function getParsedResumeInfo($input); 

}
?>
