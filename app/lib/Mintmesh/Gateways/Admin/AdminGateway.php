<?php namespace Mintmesh\Gateways\Admin;

/**
 * This is the Admins Gateway.
 */

use Mintmesh\Services\Validators\Admin\AdminValidator ;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;


use Lang;
use Cache;

class AdminGateway {
    
    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    protected $adminRepository;    
    protected $adminValidator;
    protected $commonFormatter;
	public function __construct(AdminValidator $adminValidator,
                                    CommonFormatter $commonFormatter) {
                $this->adminValidator = $adminValidator;
                $this->commonFormatter = $commonFormatter ;
        }
        
        public function doValidation($validatorFilterKey, $langKey) {
             //validator passes method accepts validator filter key as param
            if($this->adminValidator->passes($validatorFilterKey)) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get($langKey)));
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseMsg = self::SUCCESS_RESPONSE_MESSAGE;
                $data = array();                
            } else {
                /* Return validation errors to the controller */
                $message = $this->adminValidator->getErrors();
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseMsg = self::ERROR_RESPONSE_MESSAGE;
                $data = array();
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseMsg, $message, $data) ;
        }
        
        // validation on clear memcache
        public function validateclearMemcache($input) {            
            return $this->doValidation('clear_memcache','MINTMESH.clear_memcache.valid');
        }
        
        // function to clear memcache data based on key
        public function clearMemcache($input)
        {
            if(Cache::has($input['key_name']))
            {
                Cache::forget($input['key_name']);
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $message = array('msg'=>array(Lang::get('MINTMESH.clear_memcache.success')));
                $data = array();
            } else {
                $message = Lang::get('MINTMESH.clear_memcache.dont_exist');
                $responseCode = self::SUCCESS_RESPONSE_CODE;
                $responseStatus = self::SUCCESS_RESPONSE_MESSAGE;
                $data = array();
            }
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $data);
        }

}
?>
