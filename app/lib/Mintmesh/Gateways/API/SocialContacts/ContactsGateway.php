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
    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    protected $contactsRepository, $contactsValidator, $neoUserRepository, $userRepository;  
    protected $authorizer, $appEncodeDecode;
    protected $userFileUploader;
    protected $userEmailManager;
    protected $commonFormatter, $loggedinUserDetails;
    protected $processedContacts=array();
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

        public function processContactsImport($input) {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            if ($this->loggedinUserDetails)
            {
                # initializing array
                $emailId = array();
                $phonenumber = array();
                $userPropertyArray = array();
                $existingUsernodeIds = array();
                $mintmeshUserArray = array();
                $autoconnectedUsers = array();
                $emailMasterArray = array();
                $phonenumerMasterArray = array();
                $pushData = array();
                $mintUsers = array();
                // getting the loggedin user details
                $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid);
                // logged in user/from user data
                $fromUser_email = $this->loggedinUserDetails->emailid;
                $fromUser_phone = $this->neoLoggedInUserDetails->phone;
                // decoding imported contacs to array
                $contacts = $this->decodeInputContacts($input['contacts']);
                # creating from user object
                $fromUserObject = new \stdClass ;
                $fromUserObject->emailid = $fromUser_email ;
                $fromUserObject->phone = $fromUser_phone ;
                if(!empty($contacts) && is_array($contacts)) {
                    
                    #1 Deleting all the import relation with the import source node
                    $this->contactsRepository->deleteImportedContacts($fromUser_email);
                    #2 Composing email and phone number strings
                    foreach ($contacts as $contact) {                   
                        # one contact can have multiple emailids
                        foreach($contact->emails as $emailidValue) {
                            #emailid array
                            $emailId[] = $emailidValue;
                            $emailMasterArray[$this->appEncodeDecode->filterString(strtolower($emailidValue))] = !empty($contact->recordID)?$contact->recordID:'';
                        }
                        # one contact can have multiple phone numbers
                        $phonesFormated = $this->formatPhoneNumbers($contact->phones,$fromUser_phone);
                        if (!empty($phonesFormated)){
                            foreach ($phonesFormated as $phoneValue) {
                                #phonenumber array
                                $phonenumber[] = $phoneValue;
                                $phonenumerMasterArray[$phoneValue] = !empty($contact->recordID)?$contact->recordID:'';
                            }
                        }
                        /*
                        # collecting data to place in queue
                        $pushData['contacts'][] = $contact;*/
                        $pushData = array();
                        $pushData['emails']=$contact->emails;
                        $pushData['phones']=$phonesFormated;
                        $pushData['contact']=$contact;
                        $pushData['fromUser']=$fromUserObject;
                        Queue::push('Mintmesh\Services\Queues\CreateOrRelateContactsQueue', $pushData, 'IMPORT');
                    }

                    #3 Checking for existing user nodes with email or with phone
                    $result = $this->contactsRepository->getExistingContacts(array_unique($emailId), array_unique($phonenumber));
                    if(!empty($result)) {
                        #some nodes already present for the imported contacts
                        #4 foreach loop to traverse each node and its properties in $result
                        foreach ($result as $res) {
                             # getting node properties for each item
                            $userPropertyArray = $res[0]->getProperties();
                            #check if the same user is not in the list
                            if ($userPropertyArray['emailid'] != $fromUser_email){
                                # getting list of existing user node IDs for autoconnect
                                $existingUsernodeIds[] = $res[0]->getID();

                                # Custom properties for differentiating connected and pending request
                                $userPropertyArray['connected'] = 0;
                                $userPropertyArray['request_sent_at'] = 0;


                                # displaypicture check
                                $userPropertyArray['dp_path'] = !empty($userPropertyArray['dp_renamed_name'])?$userPropertyArray['dp_renamed_name']:'';

                                #5 getting all connected minmtehs user nodes
                                $connected = $this->neoUserRepository->checkConnection($fromUser_email,$userPropertyArray['emailid']);

                                if(!empty($connected)) {
                                    $userPropertyArray['connected'] = !empty($connected['connected'])?1:0;
                                } else {
                                    #5.1 Check for any pending connection request exists for the emailid
                                    $pending = $this->neoUserRepository->checkPendingConnection($fromUser_email,$userPropertyArray['emailid']);
                                    if (!empty($pending))
                                    {
                                        $userPropertyArray['request_sent_at'] = $pending ;
                                        $userPropertyArray['connected'] = 2 ;
                                    }
                                }
                                $formattedMintmeshPhoneNumber = str_replace('-', '', $userPropertyArray['phone']);

                                if (!empty($emailMasterArray[$userPropertyArray['emailid']])) {
                                    $userPropertyArray['recordID'] = $emailMasterArray[$userPropertyArray['emailid']] ;
                                } else if (!empty($phonenumerMasterArray[$formattedMintmeshPhoneNumber])) {
                                    $userPropertyArray['recordID'] = $phonenumerMasterArray[$formattedMintmeshPhoneNumber] ;
                                }
                                unset($userPropertyArray['services']);//unset it as it causes chrash
                                # composing return array to front end
                                $mintmeshUserArray[$userPropertyArray['emailid']] = $userPropertyArray;
                                # create import relation for mintmesh users
                                $relationAttrs = $this->createEmptyRelationAttributes();
                                //create relation for users
                                try{
                                    $relation = $this->contactsRepository->relateContacts($fromUserObject, $res, $relationAttrs);  
                                }
                                catch(\RuntimeException $e)
                                {
                                }
                            }
                        } # for loop ending here

                         #6 autoconnect logic, when the input value is having autoconnect paran then do autoconnect
                            if (!empty($input['autoconnect'])) {
                                # checking for possible autoconnect
                                $autoConnectResult = $this->checkAutoconnect($fromUser_email, $existingUsernodeIds);
                                if(!empty($autoConnectResult)) {

                                    foreach ($autoConnectResult as $autoConnectEmailId) {
                                        if (array_key_exists($autoConnectEmailId, $mintmeshUserArray)) {
                                            $mintmeshUserArray[$autoConnectEmailId]['connected']=1;
                                            $autoconnectedUsers[$autoConnectEmailId] = $mintmeshUserArray[$autoConnectEmailId] ;
                                            unset($mintmeshUserArray[$autoConnectEmailId]);
                                        } 
                                     }

                                }

                            }
                        foreach ($mintmeshUserArray as $mintUser) {
                            $mintUser['position'] = (isset($mintUser['position']) && !empty($mintUser['position'])?$mintUser['position']:(isset($mintUser['you_are']) && !empty($mintUser['you_are'])?$this->userRepository->getYouAreName($mintUser['you_are'],"name"):''));
                            $mintUsers[]=$mintUser;
                        }
                         #7 pushing the data to queue
                          //  Log::info("push data".print_r($pushData, true));
                         //Queue::push('Mintmesh\Services\Queues\CreateOrRelateContactsQueue', $pushData, 'IMPORT');
                        #8 response to client
                        $responseMessage = Lang::get('MINTMESH.import_contacts.success');                    
                        $responseCode    = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus  = self::SUCCESS_RESPONSE_MESSAGE;
//                        $responseData    = array('mintmesh_users'=>array_values($mintmeshUserArray),'autoconnected_users'=>array_values($autoconnectedUsers));
                        $responseData    = array('mintmesh_users'=>$mintUsers,'autoconnected_users'=>array_values($autoconnectedUsers));

                        
                    } else { #empty contact ends here 
                        #no nodes present fresh contact import
                        # all the contacts need to be imported
                        //Log::info("push data".print_r($pushData, true));
                        //Queue::push('Mintmesh\Services\Queues\CreateOrRelateContactsQueue', $pushData, 'IMPORT');
                        $responseMessage = Lang::get('MINTMESH.import_contacts.success');                    
                        $responseCode    = self::SUCCESS_RESPONSE_CODE;
                        $responseStatus  = self::SUCCESS_RESPONSE_MESSAGE;
                        $responseData    = array('mintmesh_users'=>array(),'autoconnected_users'=>array());

                    }
                } // no input params if ends here
                else {
                    $responseMessage = Lang::get('MINTMESH.import_contacts.invalid');                    
                    $responseCode    = self::ERROR_RESPONSE_CODE;
                    $responseStatus  = self::ERROR_RESPONSE_MESSAGE;
                    $responseData    = array();

                }
            }else{ //loggedin user not found
                $responseMessage = Lang::get('MINTMESH.user.user_not_found');                    
                $responseCode    = self::ERROR_RESPONSE_CODE;
                $responseStatus  = self::ERROR_RESPONSE_MESSAGE;
                $responseData    = array();
            }
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
        }
        
        public function decodeInputContacts($jsonString) {
            return json_decode($jsonString);
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
                            //Queue::push('Mintmesh\Services\Queues\DeleteContactQueue', array('emailid'=>$this->loggedinUserDetails->emailid), 'IMPORT');
                            $deletedResult = $this->contactsRepository->deleteImportedContacts($this->loggedinUserDetails->emailid);
                            $mintmeshEmails = $processedEmails = array();
                            if ($deletedResult){// process afer delete contact is done
                                foreach($contacts as $contact)
                                {
                                    $connectResult = $autoConnectIds = array();
                                    $relationAttrs = array();
                                    $emails = !empty($contact->emails)?$contact->emails:array();
                                    $phones = !empty($contact->phones)?$contact->phones:array();
                                    //format the phone numbers
                                    $userPhone = !empty($fromUser->phone)?$fromUser->phone:'';
                                    $phones = $this->formatPhoneNumbers($phones, $userPhone);
                                    // \Log::info("<<<<<<<<<<<<<<<<<<<<<<  In getExisting contacts before >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                    $result = $this->contactsRepository->getExistingContacts($emails, $phones);
                                   // \Log::info("<<<<<<<<<<<<<<<<<<<<<<  In getExisting contacts after >>>>>>>>>>>>>>>>>>>>> ".date('H:i:s'));
                                    if (!empty($result))
                                    {
                                        foreach ($result as $res)//process each contacts 
                                        {
                                            $r = $res[0]->getProperties();
                                            $r['dp_path'] = !empty($r['dp_renamed_name'])?$r['dp_renamed_name']:'';
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
                                            $returnArray[$res[0]->emailid]=$r; 
                                            $autoConnectIds[]=$res[0]->getID();
                                        }
                                        //autoconnect people
                                        if (!empty($input['autoconnect']))
                                        $connectResult = $this->checkAutoconnect($this->loggedinUserDetails->emailid, $autoConnectIds, $userPhone);
                                        //foreach sutoconnect result unset the result array
                                        foreach ($connectResult as $autoConnectEmailId){
                                            if (!empty($returnArray[$autoConnectEmailId])){
                                                $returnArray[$autoConnectEmailId]['connected']=1;
                                                $autoconnectedUsers[] = $returnArray[$autoConnectEmailId] ;
                                                unset($returnArray[$autoConnectEmailId]);
                                            }
                                        }
                                    }
                                    //check for create or relate contacts
                                    $pushData = array();
                                    $pushData['emails']=$emails;
                                    $pushData['phones']=$phones;
                                    $pushData['contact']=$contact;
                                    $pushData['fromUser']=$this->neoLoggedInUserDetails;
                                    //$jobData = $pushData ;
                                    Queue::push('Mintmesh\Services\Queues\CreateOrRelateContactsQueue', $pushData, 'IMPORT');
                                    //$this->checkToCreateOrRelateContacts($jobData['emails'], $jobData['phones'], $jobData['contact'], $jobData['fromUser']) ;
                                }
                            }
                            $tmp = Array(); 
                            foreach($returnArray as &$ma) 
                                $tmp[] = &$ma["fullname"]; 
                            array_multisort($tmp, $returnArray); 
                            
                            $tmp = Array(); 
                            foreach($autoconnectedUsers as &$ma) 
                                $tmp[] = &$ma["fullname"]; 
                            array_multisort($tmp, $autoconnectedUsers); 
                            $message = array('msg'=>array(Lang::get('MINTMESH.import_contacts.success')));
                            return $this->commonFormatter->formatResponse(200, "success", $message, array('mintmesh_users'=>array_values($returnArray),'autoconnected_users'=>$autoconnectedUsers)) ;
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
        
        public function checkAutoconnect($userEmail='', $user_ids=array(), $userPhone='')
        {
            $autoConnectedUsers = array();
            if (!empty($user_ids) && !empty($userEmail))
            {
                //foreach ($emails as $email)
                //{
                    $autoConnectPeople = $this->neoUserRepository->getAutoconnectUsers($userEmail, $user_ids, $userPhone);
                    if (!empty($autoConnectPeople))
                    {
                        foreach ($autoConnectPeople as $person)
                        {
                            try{
                                $pushData = array();
                                $pushData['user_email']=$userEmail;
                                $pushData['to_connect_email']=$autoConnectedUsers[]=$person[0]->emailid;
                                $pushData['relationAttrs']=array('auto_connected'=>1);
                                Queue::push('Mintmesh\Services\Queues\AutoConnectQueue', $pushData);
                            }
                            catch(\RuntimeException $e)
                            {
                                
                            }
                        }
                    }
                //}
            }
            return $autoConnectedUsers ;
            
        }
        
        public function createNonMembersNodes($fromEmail='', $emails = array(), $contact = array())
        {
            if (!empty($emails) && !empty($contact))
            {
                if (empty($fromEmail)){
                    //get user details using access token
                    $this->loggedinUserDetails = $this->getLoggedInUser();
                    $fromEmail = $this->loggedinUserDetails->emailid;
                }
                $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($fromEmail) ;
                $fromUser = $this->neoLoggedInUserDetails ;
                foreach ($emails as $email)
                {
                    //create node in neo4j
                    $neoInput = array();
                    $neoInput['firstname'] = '';
                    $neoInput['lastname'] = '';
                    $fullname = '';
                    $neoInput['fullname'] = '';
                    $neoInput['emailid'] = $email;
                    //$neoInput['secondary_emails'] = isset($emails)?$emails:array('0');
                    $neoInput['phone'] = isset($phones[0])?$phones[0]:'';
                    //$neoInput['secondary_phones'] = isset($phones)?$phones:array('0');
                    try{
                        $relationAttrs = $this->formRelationAttributes($contact);
                        /*$pushData = array();
                        $pushData['from_user_id'] = $fromUser->id ;
                        $pushData['neoInput']=$neoInput;
                        $pushData['relationAttrs']=$relationAttrs;
                        Queue::push('Mintmesh\Services\Queues\ContactsQueue', $pushData, 'IMPORT');
                        */
                        $this->contactsRepository->createContactAndRelation($fromUser->id, $neoInput, $relationAttrs) ;
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
            $relationAttrs['firstname'] = isset($contact->firstName)?$contact->firstName:'';
            $relationAttrs['lastname'] = isset($contact->lastName)?$contact->lastName:'';
            if (!empty($relationAttrs['firstname']) && !empty($relationAttrs['lastname'])){
                $fullname = $relationAttrs['firstname']." ".$relationAttrs['lastname'];
            }else{
                $fullname = '';
            }
            
            $relationAttrs['fullname'] = isset($contact->fullname)?$contact->fullname:$fullname;

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
        public function createEmptyRelationAttributes(){
            return array('from'=>'phone','firstname'=>'','lastname'=>'','fullname'=>'');
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
                        $dataSet['sender_name'] =!empty($this->neoLoggedInUserDetails->firstname && $this->neoLoggedInUserDetails->lastname)?$this->neoLoggedInUserDetails->firstname." ".$this->neoLoggedInUserDetails->lastname:'';
                        $dataSet['sender_email'] =!empty($this->neoLoggedInUserDetails->emailid)?$this->neoLoggedInUserDetails->emailid:'';
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
                        $dataSet['sender_name'] =!empty($fromUser->firstname)?$fromUser->firstname:'';
                        $dataSet['sender_email'] =!empty($fromUser->emailid)?$fromUser->emailid:'';
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
        
        public function sendPostReferralInvitations($input)
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
                        $firstname = '';
                        //set name empty for non mintmesh email
                        if (!empty($userDetails['login_source'])){//is a mintmesh user
                            $firstname = !empty($userDetails['firstname'])?$userDetails['firstname']:'';
                        }
                        $dataSet['name'] = $firstname;
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
                         $relationAttrs['request_for_post_id'] = $input['post_id'] ;
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
        
        public function formatPhoneNumbers($phones=array(), $userPhone=''){
            $returnPhoneArray = array();
            $countryCodeArray = explode("-",$userPhone);
            $countryCode = !empty($countryCodeArray[0])?$countryCodeArray[0]:'';
            foreach ($phones as $phone){
                
                $phone = $this->appEncodeDecode->formatphoneNumbers($phone);
                //check if the number contains country code assigned
                    if (strpos($phone, "+") === false){
                        $returnPhoneArray[] = $countryCode.$phone ;
                    }else{
                        $returnPhoneArray[] = $phone ;
                    }
            }
            return $returnPhoneArray ;
        }
        
        public function createNonMembersNodesForPhone($fromEmail='', $phones = array(), $contact = array())
        {
            if (!empty($phones) && !empty($contact) && !empty($fromEmail))
            {
                foreach ($phones as $phone)
                {
                    //create node in neo4j
                    $neoInput = array();
                    $neoInput['firstname'] = '';
                    $neoInput['lastname'] = '';
                    $fullname = '';
                    $neoInput['fullname'] = '';
                    $neoInput['emailid'] = '';
                    $neoInput['phone'] = $phone;
                    try{
                        $relationAttrs = $this->formRelationAttributes($contact);
                        /*$pushData = array();
                        $pushData['from_user_email'] = $fromEmail ;
                        $pushData['neoInput']=$neoInput;
                        $pushData['relationAttrs']=$relationAttrs;
                        Queue::push('Mintmesh\Services\Queues\NonMintmeshContactsQueue', $pushData, 'IMPORT');
                        */
                        $this->contactsRepository->createNodeAndRelationForPhoneContacts($fromEmail, $neoInput, $relationAttrs,1);
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
        
        public function checkToCreateOrRelateContacts($emails=array(), $phones=array(), $contact=array(), $fromUser){
            $fromUser = (object) $fromUser ;
            $contact = (object) $contact ;
           // $myClass = new ContactsGateway;
            if (!empty($emails) || !empty($phones)){
                //create contact and relations
                //create nodes for emailids
                if (!empty($contact->emails) && is_array($contact->emails) && !in_array($fromUser->emailid,$contact->emails))
                {
                    //create nodes for each email id
                    $createResult = $this->createNonMembersNodes($fromUser->emailid, $emails, $contact);
                }
                //create nodes for phone numbers
                if (!empty($contact->phones) && is_array($contact->phones) && !in_array($fromUser->phone,$contact->phones))
                {
                    //create nodes for each phone number
                    $createResult = $this->createNonMembersNodesForPhone($fromUser->emailid, $phones, $contact);
                }
                /*$contactsResult = $this->contactsRepository->getExistingNonMintmeshContacts($emails, $phones);
                if (!empty($contactsResult)){
                    $existingEmails = array();
                    $existingPhones = array();
                    foreach ($contactsResult as $c){
                        $toUser = $c ;
                        $relationAttrs = $this->formRelationAttributes($contact);
                        //create relation for users
                        try{
                            if (!in_array($toUser['id']->getId(), $this->processedContacts)){
                                //print_r($this->processedContacts);
                                $relation = $this->contactsRepository->relateContacts($fromUser, $toUser, $relationAttrs);  
                                $this->processedContacts[]=$toUser['id']->getId() ;
                            }
                        }
                        catch(\RuntimeException $e)
                        {
                        }
                        $existingEmails[] = $c[0]->emailid ;
                        $existingPhones[] = str_replace('-', '', $c[0]->phone) ;
                        //create nodes for remaining users
                        $stringedEmails = array();
                        foreach ($emails as $e)
                        {
                            $stringedEmails[] = $this->appEncodeDecode->filterString(strtolower($e)) ;
                        }
                        $remainingContacts = array_diff($stringedEmails, $existingEmails) ;
                        $createResult =$this->createNonMembersNodes($fromUser->emailid, $remainingContacts, $contact);
                        //create remaining phone contacts for phone
                        $remainingPhones = array_diff($phones, $existingPhones) ;
                        $createResult = $this->createNonMembersNodesForPhone($fromUser->emailid, $remainingPhones, $contact);
                    }
                }
                else{
                    //create contact and relations
                    //create nodes for emailids
                    if (!empty($contact->emails) && is_array($contact->emails) && !in_array($fromUser->emailid,$contact->emails))
                    {
                        //create nodes for each email id
                        $createResult = $this->createNonMembersNodes($fromUser->emailid, $emails, $contact);
                    }
                    //create nodes for phone numbers
                    if (!empty($contact->phones) && is_array($contact->phones) && !in_array($fromUser->phone,$contact->phones))
                    {
                        //create nodes for each phone number
                        $createResult = $this->createNonMembersNodesForPhone($fromUser->emailid, $phones, $contact);
                    }
                }*/
            }
        }
   
}
?>
