<?php namespace Mintmesh\Repositories\API\Enterprise;


interface EnterpriseRepository {
    
    /*
     * Create new Enterprise user resource in storage
     */
     public function createEnterpriseUser($input);
    /*
     * Create new Company Profile resource in storage
     */
     public function createCompanyProfile($input);
     
}
?>
