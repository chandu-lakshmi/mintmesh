<?php
namespace Admin;
use Mintmesh\Gateways\Admin\AdminGateway;
use Response;


class AdminController extends \BaseController {

        
	public function __construct(AdminGateway $adminGateway)
	{
		$this->adminGateway = $adminGateway;
        }
	       
        /**
	 * clear memcache data 
         * 
         * POST/clear_memcache
         * 
         * @param string $key_name memcache key
	 * @return Response
	 */
        public function clearMemcache() {
            $inputAdminData = \Input::all();
            // Validating admin input data
            $validation = $this->adminGateway->validateclearMemcache($inputAdminData);
            if($validation['status'] == 'success') {
                $response = $this->adminGateway->clearMemcache($inputAdminData);
                return \Response::json($response);
            } else {
                    // returning validation failure
                return \Response::json($validation);
            }
        }
}
?>
