<?php namespace Mintmesh\Gateways\API\SocialContacts;

/**
 * This is the Social network contacts Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */

use Mintmesh\Repositories\API\SocialContacts\ContactsRepository;
use Mintmesh\Services\Validators\API\SocialContacts\ContactsValidator ;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Services\Emails\API\User\UserEmailManager ;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader ;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;
use Mintmesh\Services\APPEncode\APPEncode ;
use Lang;
use Config;
use Log,Queue;
class ContactsGateway {
    
    protected $contactsRepository, $contactsValidator, $neoUserRepository, $userRepository;  
    protected $authorizer, $appEncodeDecode;
    protected $userFileUploader;
    protected $userEmailManager;
    protected $commonFormatter, $loggedinUserDetails;
	public function __construct(ContactsRepository $contactsRepository, 
                                    ContactsValidator $contactsValidator, 
                                    NeoUserRepository $neoUserRepository,
                                    UserRepository $userRepository,
                                    Authorizer $authorizer,
                                    CommonFormatter $commonFormatter,
                                    UserEmailManager $userEmailManager,
                                    UserFileUploader $userFileUploader,
                                    APPEncode $appEncodeDecode) {
            //ini_set('max_execution_time', 500);
		$this->contactsRepository = $contactsRepository;
                $this->contactsValidator = $contactsValidator;
                $this->neoUserRepository = $neoUserRepository;
                $this->userRepository = $userRepository;
                $this->authorizer = $authorizer;
                $this->commonFormatter = $commonFormatter ;
                $this->userFileUploader = $userFileUploader ;
                $this->userEmailManager = $userEmailManager ;
                $this->appEncodeDecode = $appEncodeDecode ;
                
	}
        
        /*
         * Process the contacts
         */
        public function processContacts($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails)
            {
                $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
                if (!empty($this->neoLoggedInUserDetails) && count($this->neoLoggedInUserDetails))
                {
                    //declare returnArray
                    $returnArray = $autoconnectedUsers = array();
                    $fromUser = $this->neoLoggedInUserDetails ;
                    if (!empty($input['contacts']))
                    {
                        $contacts = json_decode($input['contacts']); //$input['contacts'] ;
                        if (!empty($contacts) && is_array($contacts))
                        {
                            //delete all imported contacts
                            $deletedResult = $this->contactsRepository->deleteImportedContacts($this->loggedinUserDetails->emailid);
                            $mintmeshEmails = array();
                            foreach($contacts as $contact)
                            {
                                $connectResult = array();
                                //process only when only email exists
                                if (!empty($contact->emails) && is_array($contact->emails) && !in_array($fromUser->emailid,$contact->emails))
                                {
                                    $relationAttrs = array();
                                    $emails = !empty($contact->emails)?$contact->emails:array();
                                    $phones = !empty($contact->phones)?$contact->phones:array();
                                    //autoconnect people
                                    if (!empty($input['autoconnect']))
                                    $connectResult = $this->checkAutoconnect($this->loggedinUserDetails->emailid, $emails, $phones);
                                     //\Log::info("<<<<<<<<<<<<<<<<<<<<<<  In getExisting contacts before >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                    $result = $this->contactsRepository->getExistingContacts($emails, $phones);
                                    //\Log::info("<<<<<<<<<<<<<<<<<<<<<<  In getExisting contacts after >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                    if (empty($result))
                                    {
                                        //create nodes for each email id
                                       // \Log::info("<<<<<<<<<<<<<<<<<<<<<<  In create contacts before >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                        $createResult = $this->createNonMembersNodes($emails, $contact);
                                        //\Log::info("<<<<<<<<<<<<<<<<<<<<<<  In create contacts after >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                        if (!$createResult)//return if some error occurs
                                        {
                                            $message = array('msg'=>array(Lang::get('MINTMESH.import_contacts.error')));
                                            return $this->commonFormatter->formatResponse(406, "error", $message, array('mintmesh_users'=>$returnArray)) ;
                                        }
                                    }
                                    else
                                    {
                                        $existingEmails = array();
                                        foreach ($result as $res)//process each contacts 
                                        {
                                            if (!empty($res[0]->login_source) && !in_array($res[0]->emailid,$mintmeshEmails))//says that it is mintmesh user
                                            {
                                                $r = $res[0]->getProperties();
                                                if (!empty($res[0]->location))//user has completed profile
                                                {
                                                    if (!empty($res[0]->from_linkedin))//if  linked in
                                                    {
                                                        $r['dp_path'] = $res[0]->linkedinImage ;
                                                    }
                                                    else if (!empty($res[0]->dp_renamed_name))
                                                    {
                                                        $r['dp_path'] = $res[0]->dp_path."/".$res[0]->dp_renamed_name ;
                                                    }
                                                    else
                                                    {
                                                        $r['dp_path'] = "";
                                                    }
                                                }
                                                if (isset($r['id']))
                                                    unset($r['id']);
                                                $connected = $this->neoUserRepository->checkConnection($fromUser->emailid,$res[0]->emailid);
                                                if (!empty($connected))
                                                {
                                                    if (!empty($connected['connected'])){
                                                        $r['connected'] = 1 ;
                                                    }else{
                                                        $r['connected'] = 0 ;
                                                    }
                                                    $r['request_sent_at'] = 0 ;
                                                }
                                                else
                                                {
                                                    //check if in pending state
                                                    $pending = $this->neoUserRepository->checkPendingConnection($fromUser->emailid,$res[0]->emailid);
                                                    if (!empty($pending))// if pending
                                                    {
                                                        $r['request_sent_at'] = $pending ;
                                                        $r['connected'] = 2 ;
                                                    }else
                                                    {
                                                        $r['connected'] = 0 ;
                                                        $r['request_sent_at'] = 0;
                                                    }
                                                }
                                                if (!empty($contact->recordID))
                                                $r['recordID'] = $contact->recordID ;
                                                unset($r['services']);//unset it as it causes chrash
                                                if (!empty($r['emailid']) && in_array($r['emailid'],$connectResult))
                                                {
                                                    $r['connected']=1;
                                                    $autoconnectedUsers[] = $r ;
                                                }
                                                else
                                                {
                                                   $returnArray[]=$r; 
                                                }
                                                
                                                
                                            }
                                            $toUser = $res ;
                                            $relationAttrs = $this->formRelationAttributes($contact);
                                            $r1 = $res[0]->getProperties();
                                            //create relation for users
                                            try{
                                                //\Log::info("<<<<<<<<<<<<<<<<<<<<<<  In relate contacts before >>>>>>>>>>>>>>>>>>>>> ".$r1['emailid'].date('H:i:s'));
                                                $relation = $this->contactsRepository->relateContacts($fromUser, $toUser, $relationAttrs);   
                                               // \Log::info("<<<<<<<<<<<<<<<<<<<<<<  In relate contacts after >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                            }
                                            catch(\RuntimeException $e)
                                            {
                                                $message = array('msg'=>array(Lang::get('MINTMESH.import_contacts.error')));
                                                return $this->commonFormatter->formatResponse(406, "error", $message, array('mintmesh_users'=>$returnArray)) ;
                                            }
                                            $existingEmails[] = $res[0]->emailid ;
                                            $mintmeshEmails[] = $res[0]->emailid ;
                                        }
                                        //create nodes for remaining users
                                        $stringedEmails = array();
                                        foreach ($emails as $e)
                                        {
                                            $stringedEmails[] = $this->appEncodeDecode->filterString(strtolower($e)) ;
                                        }
                                        $remainingContacts = array_diff($stringedEmails, $existingEmails) ;
                                        $createResult = $this->createNonMembersNodes($remainingContacts, $contact);
                                    }
                                    
                                }    
                            }
                            $message = array('msg'=>array(Lang::get('MINTMESH.import_contacts.success')));
                            return $this->commonFormatter->formatResponse(200, "success", $message, array('mintmesh_users'=>$returnArray,'autoconnected_users'=>$autoconnectedUsers)) ;
                        }
                        else
                        {
                            $message = array('msg'=>array(Lang::get('MINTMESH.import_contacts.invalid')));
                            return $this->commonFormatter->formatResponse(201, "error", $message, array()) ;
                        }

                    }
                    else
                    {
                         $message = array('msg'=>array(Lang::get('MINTMESH.import_contacts.invalid')));
                         return $this->commonFormatter->formatResponse(201, "error", $message, array()) ;
                    }
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                    return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
                }
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.user.user_not_found')));
                return $this->commonFormatter->formatResponse(self::ERROR_RESPONSE_CODE, self::ERROR_RESPONSE_MESSAGE, $message, array()) ;
            }
        }
        
        public function checkAutoconnect($userEmail='', $emails=array(),$phones=array())
        {
            $autoConnectedUsers = array();
            if (!empty($emails) && !empty($userEmail))
            {
                foreach ($emails as $email)
                {
                    $autoConnectPeople = $this->neoUserRepository->getAutoconnectUsers($userEmail, $email, $phones);
                    if (!empty($autoConnectPeople))
                    {
                        foreach ($autoConnectPeople as $person)
                        {
                            try{
                                $pushData = array();
                                $pushData['user_email']=$userEmail;
                                $pushData['to_connect_email']=$autoConnectedUsers[]=$person[0]->emailid;
                                $pushData['relationAttrs']=array('auto_connected'=>1);
                                Queue::push('Mintmesh\Services\Queues\AutoConnectQueue', $pushData, 'IMPORT');
                            }
                            catch(\RuntimeException $e)
                            {
                                
                            }
                        }
                    }
                }
            }
            return $autoConnectedUsers ;
            
        }
        
        public function createNonMembersNodes($emails = array(), $contact = array())
        {
            if (!empty($emails) && !empty($contact))
            {
                //get user details using access token
                $this->loggedinUserDetails = $this->getLoggedInUser();
                $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
                $loginUserDetails = $this->loggedinUserDetails;
                $fromUser = $this->neoLoggedInUserDetails ;
                foreach ($emails as $email)
                {
                    //create node in neo4j
                    $neoInput = array();
                    $neoInput['firstname'] = isset($contact->firstName)?$contact->firstName:'';
                    $neoInput['lastname'] = isset($contact->lastName)?$contact->lastName:'';
                    $neoInput['fullname'] = isset($contact->fullname)?$contact->fullname:'';
                    $neoInput['emailid'] = $email;
                    //$neoInput['secondary_emails'] = isset($emails)?$emails:array('0');
                    $neoInput['phone'] = isset($phones[0])?$phones[0]:'';
                    //$neoInput['secondary_phones'] = isset($phones)?$phones:array('0');
                    try{
                        $relationAttrs = $this->formRelationAttributes($contact);
                        $pushData = array();
                        $pushData['from_user_id'] = $fromUser->id ;
                        $pushData['neoInput']=$neoInput;
                        $pushData['relationAttrs']=$relationAttrs;
                        Queue::push('Mintmesh\Services\Queues\ContactsQueue', $pushData, 'IMPORT');
                        //$toUser =  $this->contactsRepository->createContactAndRelation($fromUser->id, $neoInput, $relationAttrs) ;
                    }
                    catch(\RuntimeException $e)
                    {
                        return false ;
                    }
                }
                return true ;
            }
        }
        
        public function formRelationAttributes($contact=array())
        {
            
            $relationAttrs = array() ;
            $relationAttrs['from'] = 'phone' ;
            $relationAttrs['display_name'] = isset($contact->fullname)?$contact->fullname:''; ;

            if (!empty($contact->hasImage))
            {
                //upload dp image
                $originalFileName = $contact->dpImage->getClientOriginalName();
                $renamedFileName = $this->uploadPicture($contact->dpImage);
                $relationAttrs['dp_path'] = url('/').Config::get('constants.DP_PATH') ;
                $relationAttrs['dp_original_name'] = $originalFileName ;
                $relationAttrs['dp_renamed_name'] = $renamedFileName ;
            }
            return $relationAttrs ;
        }
        public function uploadPicture($image)
        {
            if (!empty($image))
            {
                //upload the file
                $this->userFileUploader->source = $image ;
                $this->userFileUploader->destination = public_path().Config::get('constants.DP_PATH') ;
                return $renamedFileName = $this->userFileUploader->moveFile();
            }
            
        }
        public function processInvitations($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $fromUser = $this->neoLoggedInUserDetails ;
            if (!empty($input['emails']))
            {
                $loginUserDetails = $this->loggedinUserDetails;
                $emails = $input['emails'] ;
                $emails = json_decode($emails);
                foreach ($emails as $email)
                {
                    //call mail 
                    $userDetails = $this->neoUserRepository->getNodeByEmailId($email);
                    if (!empty($userDetails))
                    {
                        // set email required params
                        $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.join_invitation');
                        if (Config::get('constants.INVITE_SINGLE'))
                        {
                            $email = Config::get('constants.INVITE_EMAIL') ;
                        }
                        $this->userEmailManager->emailId = $email;
                        $dataSet = array();
                        $dataSet['name'] =!empty($userDetails['firstname'])?$userDetails['firstname']:'';
                        $dataSet['sender_name'] =!empty($loginUserDetails->firstname)?$loginUserDetails->firstname:'';
                        $dataSet['sender_email'] =!empty($loginUserDetails->emailid)?$loginUserDetails->emailid:'';
                        $this->userEmailManager->dataSet = $dataSet;
                        $this->userEmailManager->subject = "Invitation from ".$dataSet['sender_name'];//Lang::get('MINTMESH.user_email_subjects.join_invitaion');
                        $this->userEmailManager->name = $dataSet['name'];
                        $email_sent = $this->userEmailManager->sendMail();
                         //log email status
                         $emailStatus = 0;
                         if (!empty($email_sent))
                         {
                             $emailStatus = 1;
                         }
                         $emailLog = array(
                                'emails_types_id' => 3,
                                'from_user' => !empty($loginUserDetails->id)?$loginUserDetails->id:0,
                                'from_email' => !empty($loginUserDetails->emailid)?$loginUserDetails->emailid:'',
                                'to_email' => $this->appEncodeDecode->filterString(strtolower($email)),
                                'related_code' => '',
                                'sent' => $emailStatus,
                                'ip_address' => $_SERVER['REMOTE_ADDR']
                            ) ;
                         $this->userRepository->logEmail($emailLog);
                        $relation = $this->contactsRepository->relateInvitees($fromUser, $userDetails); 
                    }
 
                }
                $message = array('msg'=>array(Lang::get('MINTMESH.join_invitation.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.join_invitation.invalid')));
                return $this->commonFormatter->formatResponse(201, "error", $message, array()) ;
            }
        }
        
        
        public function sendReferralInvitations($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $fromUser = $this->neoLoggedInUserDetails ;
            if (!empty($input['emails']))
            {
                $loginUserDetails = $this->loggedinUserDetails;
                $emails = $input['emails'] ;
                $emails = json_decode($emails);
                foreach ($emails as $email)
                {
                    //call mail 
                    $userDetails = $this->neoUserRepository->getNodeByEmailId($email);
                    if (!empty($userDetails))
                    {
                        // set email required params
                        $this->userEmailManager->templatePath = Lang::get('MINTMESH.email_template_paths.join_invitation');
                        if (Config::get('constants.INVITE_SINGLE'))
                        {
                            $email = Config::get('constants.INVITE_EMAIL') ;
                        }
                        $this->userEmailManager->emailId = $email;
                        $dataSet = array();
                        $dataSet['name'] =!empty($userDetails['firstname'])?$userDetails['firstname']:'';
                        $dataSet['sender_name'] =!empty($loginUserDetails->firstname)?$loginUserDetails->firstname:'';
                        $dataSet['sender_email'] =!empty($loginUserDetails->emailid)?$loginUserDetails->emailid:'';
                        $this->userEmailManager->dataSet = $dataSet;
                        $this->userEmailManager->subject = "Invitation from ".$dataSet['sender_name'];//Lang::get('MINTMESH.user_email_subjects.join_invitaion');
                        $this->userEmailManager->name = $dataSet['name'];
                        $email_sent = $this->userEmailManager->sendMail();
                         //log email status
                         $emailStatus = 0;
                         if (!empty($email_sent))
                         {
                             $emailStatus = 1;
                         }
                         $emailLog = array(
                                'emails_types_id' => 3,
                                'from_user' => !empty($loginUserDetails->id)?$loginUserDetails->id:0,
                                'from_email' => !empty($loginUserDetails->emailid)?$loginUserDetails->emailid:'',
                                'to_email' => $this->appEncodeDecode->filterString(strtolower($email)),
                                'related_code' => '',
                                'sent' => $emailStatus,
                                'ip_address' => $_SERVER['REMOTE_ADDR']
                            ) ;
                         $this->userRepository->logEmail($emailLog);
                         $relationAttrs = array();
                         $relationAttrs['request_for_emailid'] = $input['for_email'] ;
                         $relation = $this->contactsRepository->relateInvitees($fromUser, $userDetails, $relationAttrs); 
                    }
 
                }
                $message = array('msg'=>array(Lang::get('MINTMESH.join_invitation.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.join_invitation.invalid')));
                return $this->commonFormatter->formatResponse(201, "error", $message, array()) ;
            }
        }
        public function getMintmeshUsers($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $user = $this->neoLoggedInUserDetails ;
            $result = $this->contactsRepository->getRelatedMintmeshUsers($user->emailid);
            $returnArray = array();
            if (!empty($result))
            {
                foreach ($result as $res)
                {
                    $returnArray[] = $res[0]->getProperties();
                }
            }
            $message = array('msg'=>array(Lang::get('MINTMESH.get_contacts.success')));
            return $this->commonFormatter->formatResponse(200, "success", $message, array('mintmesh_users'=>$returnArray)) ;
        }
        public function getLoggedInUser()
        {
            $resourceOwnerId = $this->authorizer->getResourceOwnerId();
            return $this->userRepository->getUserById($resourceOwnerId);
        }
        
        
    
}
?>
