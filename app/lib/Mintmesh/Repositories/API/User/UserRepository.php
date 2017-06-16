<?php namespace Mintmesh\Repositories\API\User;


interface UserRepository {
    /*
     * Create new user resource in storage
     */
     public function createUser($input);
     
     /*
     * get user using verification code
     */
     public function getUserByCode($code);
     
     /*
      * get user details using email id
      */
     public function getUserByEmail($email);
     /*
      * return list of country with codes
      */
     public function getCountryCodes();
     

}
?>
