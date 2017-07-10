<?php 
namespace Mintmesh\Services\Validators\Api\SuccessFactors;
use Mintmesh\Services\Validators\Validator;
class IntegrationValidator extends Validator {
    public static $rules = array(
        'integrationStatus' => array(
                'company_id'            => 'required',
                'authentication_key'  => 'required'),
          );
   
}
?>
