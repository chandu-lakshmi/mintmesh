<?php 
namespace Mintmesh\Services\Validators\Admin;
use Mintmesh\Services\Validators\Validator;
class AdminValidator extends Validator {
    public static $rules = array(
        'clear_memcache'=>array(
            'key_name'=>'required'
        )
        );
    
}
?>
