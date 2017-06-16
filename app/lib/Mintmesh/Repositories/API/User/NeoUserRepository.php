<?php namespace Mintmesh\Repositories\API\User;


interface NeoUserRepository {

     /*
      * create a new user node in neo4j
      */
     public function createUser($neoInput);
     
     /*
      * check if a node already exist with email id
      */
     public function getNodeByEmailId($emailId);
     
     /*
      * update an existing user node
      */
     public function updateUser($neoInput);
     
     /*
      * completing a user profile setup
      */
     public function completeUserProfile($neoInput);
     
     
}
?>
