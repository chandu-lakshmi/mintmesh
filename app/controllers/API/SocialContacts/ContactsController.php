<?php
namespace API\SocialContacts;
use Mintmesh\Gateways\API\SocialContacts\ContactsGateway;
use Illuminate\Support\Facades\Redirect;
use Auth;
use OAuth;
use Lang;
use Config ;
class ContactsController extends \BaseController {

        
	public function __construct(ContactsGateway $contactsGateway)
	{
		$this->contactsGateway = $contactsGateway;
        }

        /**
	 * Import all contacts 
         * 
         * POST/import_contacts
         * @param string $access_token The access token received for mintmesh
         * @param array $contacts The list of contacts
         * @param string $autoconnect 0/1 to autoconnect enabled or not
         * 
	 * @return Response
	 */
        public function importContacts()
        {
            $input = \Input::all();
            if (!empty($input))
            {
                return $this->contactsGateway->processContactsImport($input);
            }
        }
        
         /**
	 * Import all contacts 
         * 
         * POST/invite_people
         * @param string $access_token The access token received for mintmesh
         * @param string $emails The list of emails
         * 
	 * @return Response
         */
        public function sendInvitation()
        {
            $input = \Input::all();
            if (!empty($input))
            {
                return $this->contactsGateway->processInvitations($input);
            }
        }
        
        /**
	 * Get mintmesh users
         * 
         * POST/contacts
         * @param string $access_token The access token received for mintmesh
         * 
	 * @return Response
         */
        public function getMintmeshUsers()
        {
            $input = \Input::all();
            if (!empty($input))
            {
                return $this->contactsGateway->getMintmeshUsers($input);
            }
        }
        
        public function getParsedResumeDocInfo() {
             $input = \Input::all();
            if (!empty($input))
            {
            return $this->contactsGateway->getParsedResumeDocInfo($input);
            }
        }

}
?>
