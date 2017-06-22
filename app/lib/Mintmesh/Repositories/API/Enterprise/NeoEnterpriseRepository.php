<?php namespace Mintmesh\Repositories\API\Enterprise;


interface NeoEnterpriseRepository {

     /*
      * create a new user node in neo4j
      */
     public function createEnterpriseUser($neoInput);
     
     /*
      * check if a node already exist with email id
      */
     public function getNodeByEmailId($emailId);
     
     /*
      * update an existing user node
      */
//     public function completeEnterpriseUserProfile($neoInput);
     
     
}
?>
