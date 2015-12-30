<?php namespace Mintmesh\Gateways\API\Referrals;

/**
 * This is the Seek Referrals Gateway. If you need to access more than one
 * model, you can do this here. This also handles all your validations.
 * Pretty neat, controller doesnt have to know how this gateway will
 * create the resource and do the validation. Also model just saves the
 * data and is not concerned with the validation.
 */

use Mintmesh\Repositories\API\Referrals\ReferralsRepository;
use Mintmesh\Services\Validators\API\Referrals\ReferralsValidator ;
use Mintmesh\Repositories\API\User\NeoUserRepository;
use Mintmesh\Repositories\API\User\UserRepository;
use Mintmesh\Gateways\API\User\UserGateway;
use Mintmesh\Repositories\API\Payment\PaymentRepository;
use Mintmesh\Gateways\API\Payment\PaymentGateway;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mintmesh\Services\ResponseFormatter\API\CommonFormatter ;
use Mintmesh\Services\APPEncode\APPEncode ;
use Lang;
use Config;
use Log;
class ReferralsGateway {
    const SUCCESS_RESPONSE_CODE = 200;
    const SUCCESS_RESPONSE_MESSAGE = 'success';
    const ERROR_RESPONSE_CODE = 403;
    const ERROR_RESPONSE_MESSAGE = 'error';
    protected $referralsRepository, $referralsValidator, $neoUserRepository, $userRepository;  
    protected $authorizer, $appEncodeDecode,$paymentRepository,$paymentGateway;
    protected $commonFormatter, $loggedinUserDetails,$neoLoggedInUserDetails, $userGateway;
	public function __construct(referralsRepository $referralsRepository, 
                                    referralsValidator $referralsValidator, 
                                    NeoUserRepository $neoUserRepository,
                                    UserRepository $userRepository,
                                    Authorizer $authorizer,
                                    CommonFormatter $commonFormatter,
                                    UserGateway $userGateway,
                                    PaymentRepository $paymentRepository,
                                    PaymentGateway $paymentGateway,
                                    APPEncode $appEncodeDecode) {
                //ini_set('max_execution_time', 500);
		$this->referralsRepository = $referralsRepository;
                $this->referralsValidator = $referralsValidator;
                $this->neoUserRepository = $neoUserRepository;
                $this->userRepository = $userRepository;
                $this->authorizer = $authorizer;
                $this->commonFormatter = $commonFormatter ;
                $this->userGateway = $userGateway ;
                $this->appEncodeDecode = $appEncodeDecode ;
                $this->paymentRepository = $paymentRepository ;
                $this->paymentGateway = $paymentGateway ;
                
	}
        
        public function validateServiceSeekReferralInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('seek_service_referral')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function validateGetPostsInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('get_posts')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function validatereferContact($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('refer_contact')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function validateClosePostInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('close_post')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function verifyGetPostDetails($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('get_post_details')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        public function verifyProcessPostDetails($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('process_post')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function verifyPostStatusDetails($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('post_status_details')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function verifyreferralContacts($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('referral_contacts')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        public function verifyMutualPeople($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('mutual_people')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
            
        }
        
        //validation on get referrals cash
        public function validateReferralsCashInput($input)
        {
            //validator passes method accepts validator filter key as param
            if($this->referralsValidator->passes('get_referrals_cash')) {
                /* validation passes successfully */
                $message = array('msg'=>array(Lang::get('MINTMESH.payment.valid')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }

            /* Return validation errors to the controller */
            return $this->commonFormatter->formatResponse(406, "error", $this->referralsValidator->getErrors(), array()) ;
        }
        
        public function seekServiceReferral($input)
        {
           
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
             $fromId = $this->neoLoggedInUserDetails->id ;
            if ($this->loggedinUserDetails = $this->getLoggedInUser())
            {
                //$this->loggedinUserDetails
                $neoInput = array();
                //form insert array
                $neoInput['service'] = $input['service'] ;
                $neoInput['service_location'] = !empty($input['service_location'])?$input['service_location']:"" ;
                $neoInput['service_period'] = $input['service_period'];
                $neoInput['service_scope'] = $input['service_scope'];
                $neoInput['service_type'] = $input['service_type'];
                $neoInput['service_currency'] = !empty($input['service_currency'])?$input['service_currency']:"" ;
                $neoInput['service_cost'] = !empty($input['service_cost'])?$input['service_cost']:'';
                if (!empty($input['web_link']))
                {
                    $neoInput['web_link'] = $input['web_link'] ;
                }
                if (!empty($input['free_service']) && empty($input['service_cost']))
                {
                    $neoInput['free_service'] = $input['free_service'];
                }
                else
                {
                    $neoInput['free_service'] = 0 ;
                }
                $neoInput['created_by'] = $this->loggedinUserDetails->emailid;
                $neoInput['status'] = Config::get('constants.REFERRALS.STATUSES.ACTIVE');
                
                //relation attributes
                $relationAttrs = array();
                $relationAttrs['created_at'] = date("Y-m-d H:i:s") ;
                $createdService = $this->referralsRepository->createPostAndRelation($fromId, $neoInput, $relationAttrs);
                if (isset($createdService[0]) && isset($createdService[0][0]))
                {
                    $serviceId = $createdService[0][0]->getID() ;
                }
                else
                {
                    $serviceId = 0;
                }
                //exclude contacts
                if (!empty($input['excluded_list']))
                {
                    $relationAttrs = array();
                    $relationAttrs['service_scope'] = $input['service_scope'] ;
                    $excludedList = json_decode($input['excluded_list']) ;
                    if(is_array($excludedList) && !empty($serviceId))
                    {
                        foreach($excludedList as $user)
                        {
                            $excluded = $this->referralsRepository->excludeContact($serviceId, $user, $relationAttrs) ;
                        }
                    }
                }
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.error')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            
        }
        
        public function closePost($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $postId = $input['post_id'] ;
            $closed = $this->referralsRepository->closePost($userEmail, $postId);
            if (!empty($closed))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.post_closed')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.error')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            
        }
        
        public function deactivatePost($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $postId = $input['post_id'] ;
            $closed = $this->referralsRepository->deactivatePost($userEmail, $postId);
            if (!empty($closed))
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.post_closed')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.post_not_closed')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }
            
        }
        public function getLatestPosts()
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $posts = $this->referralsRepository->getLatestPosts($userEmail);
            if (!empty(count($posts)))
            {
                $returnPosts = array();
                foreach ($posts as $post)
                {
                    $postDetails = $this->formPostDetailsArray($post[0]) ;
                    $postDetails['no_of_referrals'] = !empty($post[1])?$post[1]:0 ;
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']) ;
                    $postDetails['UserDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                    $returnPosts[] = $postDetails ;
                }
                $data = array("posts"=>$returnPosts);
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_posts')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        //get all posts of a type
        public function getPosts($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $page = !empty($input['page_no'])?$input['page_no']:0;
            $posts = $this->referralsRepository->getAllPosts($userEmail, $input['request_type'],$page);
            if (!empty(count($posts)))
            {
                $returnPosts = array();
                foreach ($posts as $post)
                {
                    $postDetails = $this->formPostDetailsArray($post[0]) ;
                    $postDetails['no_of_referrals'] = !empty($post[1])?$post[1]:0 ;
                    $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']) ;
                    $postDetails['UserDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                    $returnPosts[] = $postDetails ;
                }
                $data = array("posts"=>$returnPosts);
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_posts')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        
        
        
        public function referContact($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $relationCount = 1 ;
           //count for succss relations
           $existingRelationCount = $this->referralsRepository->getReferralsCount(Config::get('constants.REFERRALS.GOT_REFERRED'), $input['post_id'], $userEmail, $input['refer_to']);
           if ($existingRelationCount < Config::get('constants.REFERRALS.MAX_REFERRALS'))
           {
               //continue only when the request count is in limit
               //create a relation between the post and user
               $oldRelationCount = $this->referralsRepository->getOldRelationsCount($input['post_id'], $input['referring']);
               if (!empty($oldRelationCount))
               {
                   $relationCount = $oldRelationCount + 1 ;
                   //$message = array('msg'=>array(Lang::get('MINTMESH.referrals.already_referred')));
                   //return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
               }
               else
               {
                   $relationCount = 1 ;
               }
               $relationAttrs = array();
               $relationAttrs['referred_by'] = strtolower($userEmail) ;
               $relationAttrs['referred_for'] = strtolower($input['refer_to']) ;
               $relationAttrs['created_at'] = date("Y-m-d H:i:s") ;
               $relationAttrs['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING') ;
               $relationAttrs['one_way_status']=Config::get('constants.REFERRALS.STATUSES.PENDING') ;
               if (!empty($input['message']))
               {
                   $relationAttrs['message'] = $input['message'] ;
               }
               if (!empty($input['bestfit_message']))
               {
                   $relationAttrs['bestfit_message'] = $input['bestfit_message'] ;
               }
               $relationAttrs['relation_count'] = $relationCount ;
               $result = $this->referralsRepository->referContact($userEmail, $input['refer_to'], $input['referring'], $input['post_id'], $relationAttrs);
               if (!empty($result))
               {
                   //send notification to the person who created post
                   $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['refer_to'], 10, array('extra_info'=>$input['post_id']), array('other_user'=>$input['referring'])) ;
                   $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
               }
               else
               {
                   $message = array('msg'=>array(Lang::get('MINTMESH.referrals.closed_post')));
                   return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
               }
           }
           else
           {
               //return limit crossed message
               $message = array('msg'=>array(Lang::get('MINTMESH.referrals.limit_crossed')));
               return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
           }
           $userEmail = $this->neoLoggedInUserDetails->emailid ;
        }
		
        public function getPostDetails($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $postDetails = array();
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            if (!empty($userEmail))
            {
                $postResult = $this->referralsRepository->getPostDetails($input['post_id']);
                if (count($postResult))
                {
                    $postDetails = $this->formPostDetailsArray($postResult[0][0]);
                    if (!empty($postDetails['created_by']))
                    {
                        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']) ;
                        $postDetails['userDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute') ;
                    }
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $postDetails) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_post')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                }
            }
        }
        
        public function getPostReferences($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $postDetails = array();
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            if (!empty($userEmail))
            {
                $limit = $page_no = 0 ;
                if (!empty($input['limit']))
                {
                    $limit = $input['limit'] ;
                }
                if (!empty($input['page_no']))
                {
                    $page_no = $input['page_no'] ;
                }
                $result = $this->referralsRepository->getPostReferences($input['post_id'], $limit, $page_no);
                if (count($result))
                {
                    $returnArray = array();
                    $postResult = $this->referralsRepository->getPostDetails($input['post_id']);
                    if (count($postResult))//continue when post exist and is active
                    {
                        $returnArray['postDetails'] = $postDetails = $this->formPostDetailsArray($postResult[0][0]);
                        $returnArray['referrals_count'] = $this->referralsRepository->getPostReferralsCount($input['post_id']);
                        if (!empty($postDetails['created_by']))
                        {
                            $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($postDetails['created_by']) ;
                            $returnArray['userDetails'] = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute') ;
                        }
                    }
                    else //else return 
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_post')));
                        return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                    }
                    
                    foreach ($result as $k=>$v)
                    {
                        if (isset($v[0]) && isset($v[1]))
                        {
                            $postDetails = array();
                            $p1Status = '';
                            $reference_details = $this->userGateway->formUserDetailsArray($v[0], 'property') ;
                            foreach ($reference_details as $k1=>$v1)
                            {
                                $postDetails['to_user_'.$k1] = $v1 ;
                            }
                            $viaUserDetails = $this->neoUserRepository->getNodeByEmailId($v[1]->referred_by) ;
                            $referred_by_details = $this->userGateway->formUserDetailsArray($viaUserDetails, 'attribute');
                            foreach ($referred_by_details as $k2=>$v2)
                            {
                                $postDetails['from_user_'.$k2] = $v2 ;
                            }
                            $relationDetails = $v[1]->getProperties();
                            if (!empty($relationDetails['one_way_status']))
                            {
                                if ($relationDetails['one_way_status'] == Config::get('constants.REFERRALS.STATUSES.ACCEPTED'))
                                {
                                    $p1Status = strtolower(Config::get('constants.REFERRALS.STATUSES.ACCEPTED')) ;
                                    $postDetails['relation_status']=!empty($relationDetails['completed_status'])?strtolower($relationDetails['completed_status']):strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                                }
                                else
                                {
                                    $postDetails['relation_status'] = $p1Status = strtolower($relationDetails['one_way_status']) ;
                                }
                            }
                            else
                            {
                                $p1Status = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING')) ;
                                $postDetails['relation_status'] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING')) ;
                            }
                            $postDetails['relation_count'] = $relationDetails['relation_count'];
                            //categorize references
                            if (!empty($p1Status) && $p1Status ==  strtolower(Config::get('constants.REFERRALS.STATUSES.ACCEPTED')))
                            {
                                $returnArray['references']['accepted'][] = $postDetails ;
                            }
                            else if (!empty($p1Status) && $p1Status ==  strtolower(Config::get('constants.REFERRALS.STATUSES.DECLINED')))
                            {
                                $returnArray['references']['declined'][] = $postDetails ;
                            }
                            else
                            {
                                $returnArray['references']['pending'][] = $postDetails ;
                            }
                            //$returnArray['references'][] = $postDetails ;
                        }
                    }
                    $data =  $returnArray ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_referrals_found')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                }
            }
        }
        public function formPostDetailsArray($postResult = array())
        {
            if (!empty($postResult))
            {
                $postId = $postResult->getId();
                $return = $postResult->getProperties();
                $return['post_id'] = $postId ;
                return $return ;
            }
            else
            {
                return array();
            }
        }
        
        public function getMyReferrals($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $users = $this->referralsRepository->getMyReferrals($input['post_id'], $userEmail);
            $postCreatedBy = '' ;
                        
            if (count($users))
            {
                $userDetails = array();
                foreach ($users as $k=>$v)
                {
                    $u = $this->userGateway->formUserDetailsArray($v[0], 'property') ;
                    $u['relation_id']=$v[1]->getId();
                    $relation_details = $v[1]->getProperties();
                    if (!empty($relation_details['one_way_status']))
                    {
                        if ($relation_details['one_way_status'] == Config::get('constants.REFERRALS.STATUSES.ACCEPTED'))
                        {
                            $u['relation_status']=!empty($relation_details['completed_status'])?strtolower($relation_details['completed_status']):strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING'));
                        }
                        else
                        {
                            $u['relation_status'] = strtolower($relation_details['one_way_status']) ;
                        }
                    }
                    else
                    {
                        $u['relation_status'] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING')) ;
                    }
                    $u['relation_count']=$relation_details['relation_count'];
                    $input['post_created_by'] = $u['post_created_by']=$relation_details['referred_for'];
                    $userDetails[] = $u ;
                    
                }
                
                $suggestions = $this->getPostSuggestions($input);
                $data=array("users"=>$userDetails) ;
                $data['suggestions'] = !empty($suggestions['data']['users'])?$suggestions['data']['users']:array() ;
                $data['referrals_count'] = $this->referralsRepository->getPostReferralsCount($input['post_id']);
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $data=array("users"=>array()) ;
                //get post details
                $postDetails = $this->referralsRepository->getPostDetails($input['post_id']);
                if (count($postDetails) && isset($postDetails[0][0]))
                {
                    $input['post_created_by'] = !empty($postDetails[0][0]->created_by)?$postDetails[0][0]->created_by:'';
                    $suggestions = $this->getPostSuggestions($input);
                    $data['suggestions'] = !empty($suggestions['data']['users'])?$suggestions['data']['users']:array() ;
                }
                $data['referrals_count'] = $this->referralsRepository->getPostReferralsCount($input['post_id']);
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_referrals')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
        }
        
        public function getPostSuggestions($input)
        {
            //get suggestions for the post
            $suggestionInput['other_email'] = $input['post_created_by'] ;
            $suggestionInput['limit'] = 5 ;
            $suggestionInput['suggestion'] = 1 ;
            $suggestionInput['post_id'] = $input['post_id'] ;
            $suggestions = $this->getMyReferralContacts($suggestionInput) ;
            return $suggestions ;
        }
        public function getLoggedInUser()
        {
            $resourceOwnerId = $this->authorizer->getResourceOwnerId();
            return $this->userRepository->getUserById($resourceOwnerId);
        }
        
        public function editPost($input)
        {
            if (!empty($input['post_id']))
            {
                $id = $input['post_id'] ;
                unset($input['post_id']);
                //remove free servce
                if (!empty($input['free_service']))
                {
                    unset($input['free_service']);
                }
                if (!empty($input['access_token']))
                unset($input['access_token']);
                if (!empty($input))
                {
                    $result = $this->referralsRepository->editPost($input, $id);
                    if (!empty($result))
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                        return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                    }
                    else
                    {
                        $message = array('msg'=>array(Lang::get('MINTMESH.referrals.closed_post')));
                        return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
                    }
                    
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_post')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        
        public function processPost($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $parse = 1 ;
            $payment_transaction_id = 0;
            $data = array();
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            if ($input['post_way'] == 'one')
            {
                $referral = $input['from_user'];
            }
            else if ($input['post_way'] == 'round')
            {
                $referral = $userEmail ;
            }
            /*if ($input['status'] == 'declined')
            {
                $parse = 0;
            }*/
            $result = $this->referralsRepository->processPost($input['post_id'], $input['referred_by'], $referral, $input['status'], $input['post_way'], $input['relation_count']);
            if (count($result))
            {
                if ($input['post_way'] == 'one')
                {
                    $relationId = !empty($result[0][1])?$result[0][1]->getID():0 ;
                    $postId = $input['post_id'] ;
                   
                    if ($input['status'] != 'declined')
                    {
                       
                        //enter into payment transactions if not free service
                        if (!empty($result[0][0]) && empty($result[0][0]->free_service))
                        {
                            //update existing transactions if any
                            $tranRes =  $this->paymentRepository->cancelOtherTransactions($postId, $relationId);
                            //get comission percentage
                            $payment_per_res = $this->paymentRepository->getComissionPercentage(1);
                            $transactionInput = array();
                            $transactionInput['from_user'] = $this->appEncodeDecode->filterString(strtolower($userEmail));
                            $transactionInput['to_user'] = $this->appEncodeDecode->filterString(strtolower($input['referred_by'])); 
                            $transactionInput['for_user'] = $this->appEncodeDecode->filterString(strtolower($referral)); 
                            $transactionInput['amount'] = !empty($result[0][0]->service_cost)?$result[0][0]->service_cost:0;
                           if (!empty($result[0][0]->service_currency))
                           {
                               $transactionInput['payment_type'] = (strtolower($result[0][0]->service_currency)=='dlr')?1:2 ;
                           }
                           else
                           {
                               $transactionInput['payment_type'] = 1 ;
                           }
                           $transactionInput['payment_reason'] = 1 ;
                            $transactionInput['mm_transaction_id'] = $t_id = $this->paymentGateway->generateTansactionId($input['referred_by']) ;
                            $transactionInput['comission_percentage']=!empty($payment_per_res->comission_percentage)?$payment_per_res->comission_percentage:0;
                            $transactionInput['payed_for_id']=$input['post_id'];
                            $transactionInput['relation_id']=!empty($result[0][1])?$result[0][1]->getID():0;
                            $transactionInput['status']=Config::get('constants.PAYMENTS.STATUSES.PENDING');
                            $payment_transaction = $this->paymentRepository->insertTransaction($transactionInput); 
                            $data['transaction_id'] = $t_id ;
                            $data['comission_percentage'] = $percentage = !empty($payment_per_res->comission_percentage)?$payment_per_res->comission_percentage:0;
                            $data['amount'] = $amount = !empty($result[0][0]->service_cost)?$result[0][0]->service_cost:0;
                            if ($transactionInput['payment_type'] == 1)
                            {
                                $data['client_token'] = $this->paymentGateway->getBTClientToken();
                                $data['total_amount'] = $this->paymentGateway->calculateTotalAmount($percentage, $amount);
                                //insert into payment gateways inputs table
                                $paymentInput = array();
                                $paymentInput['token'] = !empty($data['client_token'])?$data['client_token']:'';
                                $paymentInput['bill'] = !empty($data['bill_details'])?json_encode($data['bill_details']):'';
                                $paymentInput['mm_transaction_id'] = !empty($transactionInput['mm_transaction_id'] )?$transactionInput['mm_transaction_id'] :'';
                                $paymentInputRes = $this->paymentRepository->insertGatewayInput($paymentInput);
                                
                            }
                            else
                            {
                                $data['bill_details'] = 'citrus' ;
                               // $data['bill_details'] = $this->paymentGateway->generateCitrusBill($data['amount'], $data['comission_percentage']);
                            }
                            
                            /*
                            
                            // update post payment status
                            $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus(!empty($result[0][1])?$result[0][1]->getID():0,Config::get('constants.PAYMENTS.STATUSES.PENDING'));
                            */
                        }
                        else if (!empty($result[0][0]) && !empty($result[0][0]->free_service))//free service
                        {
                            $postUpdateStatus = $this->referralsRepository->updatePostPaymentStatus(!empty($result[0][1])?$result[0][1]->getID():0,'');
                            //send notifications
                            //send notification to the person who get referred to the post
                            $sqlUser = $this->userRepository->getUserByEmail($input['referred_by']);
                            $mysqlId = $sqlUser->id ;
                            $referred_by_details = $this->userRepository->getUserById($mysqlId);
                            $referred_by_neo_user = $this->neoUserRepository->getNodeByEmailId($input['referred_by']) ;
                            //add credits
                            $this->userRepository->logLevel(3, $input['referred_by'], $userEmail, $referral,Config::get('constants.POINTS.SEEK_REFERRAL'));
                            $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $referral, 11, array('extra_info'=>$input['post_id']), array('other_user'=>$userEmail),1) ;
                            //send notification to via person
                            $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 12, array('extra_info'=>$input['post_id']), array('other_user'=>$referral),1) ;
                            //send battle card to u1 containing u3 details
                            $this->userGateway->sendNotification($referred_by_details, $referred_by_neo_user, $this->loggedinUserDetails->emailid, 20, array('extra_info'=>$input['post_id']), array('other_user'=>$referral),1) ;
                
                        }
                    }
                    else
                    {
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 15, array('extra_info'=>$input['post_id']), array('other_user'=>$referral),$parse) ;
                    }
                    
                }
                else if ($input['post_way'] == 'round')
                {
                    if ($input['status'] != 'declined')
                    {
                        $this->userRepository->logLevel(4,$userEmail, $input['from_user'], $input['referred_by'],Config::get('constants.POINTS.ACCEPT_REFERRAL'));
                        //connect u3 and u1
                        $relationAttrs=array() ;
                        $relationAttrs['created_at'] = date("Y-m-d H:i:s") ;
                        $relationAttrs['refered_by_email'] = $input['referred_by'] ;
                        $responce = $this->neoUserRepository->acceptConnection($userEmail, $input['from_user'], $relationAttrs);
                        //send notification to the person who posted the service
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['from_user'], 13, array('extra_info'=>$input['post_id']), array('other_user'=>$input['referred_by']),$parse) ;
                        //send notification to the via person 
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 14, array('extra_info'=>$input['post_id']), array('other_user'=>$input['from_user']),$parse) ;
                    }
                    else
                    {
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['from_user'], 16, array('extra_info'=>$input['post_id']), array('other_user'=>$input['referred_by']),$parse) ;
                        //send notification to the via person 
                        $this->userGateway->sendNotification($this->loggedinUserDetails, $this->neoLoggedInUserDetails, $input['referred_by'], 22, array('extra_info'=>$input['post_id']), array('other_user'=>$input['from_user']),$parse) ;
                    }
                    
                }
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_post')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        
        public function getPostStatusDetails($input)
        {
            $isReferredUser = false;
            $result = $this->referralsRepository->getPostStatusDetails($input);
            if (count($result))
            {
                $returnArray = array();
                if (!empty($result[0][0]) && !empty($result[0][1]))
                {
                    $returnArray['relation_updated_at'] = !empty($result[0][0]->updated_at)?$result[0][0]->updated_at:$result[0][0]->created_at ;
                    if (!empty($result[0][0]->referred_for))
                    {
                        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($result[0][0]->referred_for) ;
                        $UserDetails = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                        if (!empty($UserDetails))
                        {
                            foreach ($UserDetails as $k=>$v)
                            {
                                $returnArray[$k] = $v ;
                            }
                        }
                        
                    }
                    if (!empty($result[0][0]->referred_by))
                    {
                        $neoUserDetails = $this->neoUserRepository->getNodeByEmailId($result[0][0]->referred_by) ;
                        $fromUserDetails = $this->userGateway->formUserDetailsArray($neoUserDetails, 'attribute');
                        if (!empty($fromUserDetails))
                        {
                            foreach ($fromUserDetails as $k=>$v)
                            {
                                $returnArray["from_".$k] = $v ;
                            }
                        }
                        
                    }
                    if (!empty($result[0][1]))
                    {
                        
                        $toUserDetails = $this->userGateway->formUserDetailsArray($result[0][1], 'property');
                        if (!empty($toUserDetails))
                        {
                            foreach ($toUserDetails as $k=>$v)
                            {
                                $returnArray["to_".$k] = $v ;
                            }
                        }
                        //check if phone is verified if p3 is loggedin
                        $this->loggedinUserDetails = $this->getLoggedInUser();
                        $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
                        if ($returnArray['to_emailid'] == $this->loggedinUserDetails->emailid)
                        {
                            $phone_verified = !empty($this->neoLoggedInUserDetails->phoneverified)?$this->neoLoggedInUserDetails->phoneverified:0;
                            $returnArray['phone_verified'] = $phone_verified ;
                            $isReferredUser = true ;
                        }
                    }
                    
                    $returnArray["relation_id"] = !empty($result[0][0])?$result[0][0]->getID():0 ;
                    //get transaction details
                    $getTranDetsInp = array();
                    $getTranDetsInp['from_user'] = $input['from_user'] ;
                    $getTranDetsInp['to_user'] = $input['referred_by'] ;
                    $getTranDetsInp['for_user'] = $input['referral'] ;
                    $getTranDetsInp['service_id'] = $input['post_id'] ;
                    $getTranDetsInp['relation_id'] = $returnArray["relation_id"] ;
                    $getTranDetsInp['status'] = Config::get('constants.REFERRALS.STATUSES.PENDING') ;
                    $returnArray["payment_status"] = !empty($result[0][0]->payment_status)?$result[0][0]->payment_status:"" ;
                    if ($returnArray["payment_status"] == Config::get('constants.REFERRALS.STATUSES.PENDING'))
                    {
                       $transactionDetails = $this->paymentRepository->getTransactionDetails($getTranDetsInp);
                       if (!empty($transactionDetails))
                       {
                           $returnArray['mm_transaction_id'] = !empty($transactionDetails->mm_transaction_id)?$transactionDetails->mm_transaction_id:0 ;
                            if ($transactionDetails->payment_type == 1)
                            {
                                $returnArray['bt_client_token'] = $this->paymentGateway->getBTClientToken();
                                 //insert into payment gateways inputs table
                                 $paymentInput = array();
                                 $paymentInput['token'] = !empty($returnArray['bt_client_token'])?$returnArray['bt_client_token']:'';
                                 $paymentInput['bill'] = '';
                                 $paymentInput['mm_transaction_id'] = !empty($transactionDetails->mm_transaction_id )?$transactionDetails->mm_transaction_id :'';
                                 $paymentInputRes = $this->paymentRepository->insertGatewayInput($paymentInput);
                            }
                            else
                            {
                                $returnArray['bill_details'] = 'citrus' ;
                            }
                            if (!empty($transactionDetails->amount) && !empty($transactionDetails->comission_percentage))
                             {
                                $perAmount = ($transactionDetails->comission_percentage/100)*$transactionDetails->amount;
                                $totalAmount = $transactionDetails->amount+$perAmount ;
                                $returnArray['amount'] = $totalAmount ;
                             }
                       }
                       
                    }
                    
                    if (!empty($result[0][0]->one_way_status))
                    {
                        $returnArray["one_way_status"] = strtolower($result[0][0]->one_way_status) ;
                        $returnArray["p1_updated_at"] = !empty($result[0][0]->p1_updated_at)?$result[0][0]->p1_updated_at:"";
                    }
                    else
                    {
                        $returnArray["one_way_status"] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING')) ;
                    }
                    if (!empty($result[0][0]->completed_status))
                    {
                        $returnArray["complete_status"] = strtolower($result[0][0]->completed_status) ;
                        $returnArray["p3_updated_at"] = !empty($result[0][0]->p3_updated_at)?$result[0][0]->p3_updated_at:"";
                    }
                    else
                    {
                        $returnArray["complete_status"] = strtolower(Config::get('constants.REFERRALS.STATUSES.PENDING')) ;
                    }
                    
                    $returnArray["optional_message"] = !empty($result[0][0]->message)?$result[0][0]->message:"";
                    $returnArray["bestfit_message"] = !empty($result[0][0]->bestfit_message)?$result[0][0]->bestfit_message:"";
                    $returnArray["p2_updated_at"] = !empty($result[0][0]->created_at)?$result[0][0]->created_at:"";
                    $returnArray["service_name"] = !empty($result[0][2]->service)?$result[0][2]->service:"";
                    $returnArray["service_created_at"] = !empty($result[0][2]->created_at)?$result[0][2]->created_at:"";
                    $returnArray['points_awarded'] = Config::get('constants.POINTS.SEEK_REFERRAL') ;
                    if ($isReferredUser)//p3 is veiwing it
                    {
                        if (isset($returnArray['bestfit_message']))
                        {
                            unset($returnArray['bestfit_message']);
                        }
                    }
                    $data=array("status_details"=>$returnArray) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_post')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        
        
        public function getMyReferralContacts($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $input['email'] = $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $postReferralsResult = $this->referralsRepository->getPostReferrals($input['post_id'], $userEmail);
            $referrals = $users = array();
            if (count($postReferralsResult))
            {
                foreach ($postReferralsResult as $k=>$v)
                {
                    $referrals[]=$v[0]->emailid ;
                }
                
            }
            $result = $this->referralsRepository->getMyReferralContacts($input);
            if (count($result))
            {
                foreach ($result as $k1=>$v1)
                {
                    if (!in_array($v1[0]->emailid, $referrals))
                    {
                        if ($v1[0]->emailid != $input['other_email'])
                        {
                            $users[]=$this->userGateway->formUserDetailsArray($v1[0],'property') ;
                        }
                        
                    }
                }
                $data = array("users"=>$users) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_result')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            
        }
         
        public function searchPeople($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            if (!empty($input['access_token']))
                unset($input['access_token']);
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            /*if (!empty($input))
            {*/
                $result = $this->referralsRepository->searchPeople($userEmail,$input);
                if (count($result))
                {
                    $users = array();
                    foreach ($result as $k=>$v)
                    {
                        if ($v[0]->emailid != $userEmail)//ignore if logged in user
                        {
                            $a=$this->userGateway->formUserDetailsArray($v[0],'property') ;
                            //check staus
                            $statusRes = $this->neoUserRepository->getRequestStatus($userEmail,'', $v[0]->emailid, Config::get('constants.RELATIONS_TYPES.REQUEST_REFERENCE'));
                            if (!empty($statusRes))// if pending
                            {
                                if ($statusRes['status'] == Config::get('constants.REFERENCE_STATUS.PENDING') || $statusRes['status'] == Config::get('constants.REFERENCE_STATUS.INTRO_COMPLETE'))
                                {
                                    if ($statusRes['status'] != Config::get('constants.REFERENCE_STATUS.PENDING'))
                                    {
                                        //check if declined at other side
                                        $otherStatusRes = $this->neoUserRepository->getRequestStatus('', $v[0]->emailid,$userEmail, Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'));
                                        if (!empty($otherStatusRes))
                                        {
                                            if ($otherStatusRes['status'] == Config::get('constants.REFERENCE_STATUS.DECLINED'))
                                            {
                                                $a['request_sent_at'] = 0 ;
                                                $a['connected'] = 0 ;
                                            }
                                            else
                                            {
                                                $a['request_sent_at'] = $statusRes['created_at'] ;
                                                $a['connected'] = 2 ;
                                            }
                                        }
                                        else
                                        {
                                            $a['request_sent_at'] = $statusRes['created_at'] ;
                                            $a['connected'] = 2 ;
                                        }
                                    }
                                    else
                                    {
                                        $a['request_sent_at'] = $statusRes['created_at'] ;
                                        $a['connected'] = 2 ;
                                    }

                                }
                                else
                                {
                                    $a['connected'] = 0 ;
                                    $a['request_sent_at'] = 0;
                                }
                            }else
                            {
                                $a['connected'] = 0 ;
                                $a['request_sent_at'] = 0;
                            }
                            //get known list
                            $knownPeopleInput = $knownPeopleListResult = $knownPeopleList = array();
                            $knownPeopleInput['other_email'] = !empty($a['emailid'])?$a['emailid']:'' ;
                            if (!empty($knownPeopleInput['other_email']))
                            {
                                $knownPeopleListResult = $this->getMutualPeople($knownPeopleInput) ;
                                if (!empty($knownPeopleListResult['data']['users']))
                                foreach ($knownPeopleListResult['data']['users'] as $r=>$v)
                                {
                                    $knownPeopleList[] = $v['fullname'] ;
                                }
                                $a['known_people'] = $knownPeopleList ;
                            }

                            $users[] = $a ;
                        }
                    }
                    $data=array("users"=>$users) ;
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
                }
                else
                {
                    $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_result')));
                    return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
                }
            /*}
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.invalid_input')));
                return $this->commonFormatter->formatResponse(406, "error", $message, array()) ;
            }*/
            
        }
        
        public function getMutualPeople($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $userEmail = $this->neoLoggedInUserDetails->emailid ;
            $result = $this->referralsRepository->getMutualPeople($userEmail,$input['other_email']);
            if (count($result))
            {
                $users = array();
                foreach ($result as $k=>$v)
                {
                    $users[]=$this->userGateway->formUserDetailsArray($v[0],'property') ;
                }
                $data=array("users"=>$users) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_result')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
        }
        public function getAllReferrals($input)
        {
            $return = array();
            $loggedinUserDetails = $this->getLoggedInUser();
            if ($loggedinUserDetails)
            {
                $neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($loggedinUserDetails->emailid) ;
                $return['userDetails'] = $this->userGateway->formUserDetailsArray($neoLoggedInUserDetails, 'attribute') ;
                $userEmail = !empty($loggedinUserDetails->emailid)?$loggedinUserDetails->emailid:'';
                $page = !empty($input['page'])?$input['page']:0;
                $relationDetails = $this->referralsRepository->getAllReferrals($userEmail,$page);
                $return['referrals'] = array();
                if (count($relationDetails))
                {
                    
                    foreach ($relationDetails as $relation)
                    {
                        $p2Status = Config::get('constants.REFERENCE_STATUS.PENDING') ;
                        $to_emailid = "" ;
                        $a = $relation[0]->getProperties();
                        if (!empty($relation[1]) && !empty($a))
                        {
                            $a['relation_type'] = strtolower($relation[1]) ;
                        }
                        if (!empty($relation[2]) && !empty($a) && !empty($relation[1]))
                        {
                            if ($relation[1] == Config::get('constants.RELATIONS_TYPES.INTRODUCE_CONNECTION'))
                            {
                                $toUserDetails = $this->userGateway->formUserDetailsArray($relation[2], 'property') ;
                                //details of third user
                                foreach ($toUserDetails as $k=>$v)
                                {
                                    $a['other_user_'.$k] = $v ;
                                }
                                $to_emailid = $toUserDetails['emailid'] ;
                                $a['other_status'] = !empty($relation[0]->status)?$relation[0]->status:Config::get('constants.REFERENCE_STATUS.PENDING');
                                //get details of the person who requested for reference (p1)
                                if (!empty($a['request_for_emailid']))
                                {
                                    //get relation id of the request reference relation
                                    
                                    $requestR = $this->referralsRepository->getRequestReferenceRelationId($a['request_for_emailid'],$loggedinUserDetails->emailid, $toUserDetails['emailid'],$a['request_count']);
                                    if (count($requestR))
                                    {
                                        $a['referral_relation'] = !empty($requestR[0][0])?$requestR[0][0]:0;
                                    }
                                    $otherUserResult = $this->neoUserRepository->getNodeByEmailId($a['request_for_emailid']) ;
                                    $otherUserDetails = $this->userGateway->formUserDetailsArray($otherUserResult, 'attribute') ;
                                    foreach ($otherUserDetails as $k=>$v)
                                    {
                                        $a['to_user_'.$k] = $v ;
                                    }
                                }
                            }
                            else if ($relation[1] == Config::get('constants.REFERRALS.GOT_REFERRED'))
                            {
                                //get details of the person who got referred(p3)
                                $toUserDetails = $this->userGateway->formUserDetailsArray($relation[3], 'property') ;
                                foreach ($toUserDetails as $k=>$v)
                                {
                                    $a['to_user_'.$k] = $v ;
                                }
                                $postDetails = $relation[2]->getProperties() ;
                                $postId = $relation[2]->getId() ;
                                foreach ($postDetails as $k=>$v)
                                {
                                    $a['post_details_'.$k] = $v ;
                                }
                                //get details of the person who created the post(p1)
                                if (!empty($a['post_details_created_by']))
                                {
                                    $otherUserResult = $this->neoUserRepository->getNodeByEmailId($a['post_details_created_by']) ;
                                    $otherUserDetails = $this->userGateway->formUserDetailsArray($otherUserResult, 'attribute') ;
                                    foreach ($otherUserDetails as $k=>$v)
                                    {
                                        $a['other_user_'.$k] = $v ;
                                    }
                                }
                                $a['post_id'] = $postId ;
                                $a['post_status'] = !empty($postDetails['status'])?strtolower($postDetails['status']):'' ;
                                $a['referrals_count'] = $this->referralsRepository->getPostReferralsCount($postId);
                                $a['other_status'] = !empty($relation[0]->one_way_status)?$relation[0]->one_way_status:Config::get('constants.REFERENCE_STATUS.PENDING');
                            }
                        }
                        $return['referrals'][] = $a ;
                    }
                }
                $data = array("my_referrals"=>$return) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.get_requests.success')));
                return $this->commonFormatter->formatResponse(self::SUCCESS_RESPONSE_CODE, self::SUCCESS_RESPONSE_MESSAGE, $message, $data) ;
            }
            else
            {
                $responseMessage = Lang::get('MINTMESH.user.user_not_found');
                $responseCode = self::ERROR_RESPONSE_CODE;
                $responseStatus = self::ERROR_RESPONSE_MESSAGE;
                $responseData = array();
            }
            $message = array('msg'=>array($responseMessage));
            return $this->commonFormatter->formatResponse($responseCode, $responseStatus, $message, $responseData) ;
        }
        public function getReferralsCash($input)
        {
            $this->loggedinUserDetails = $this->getLoggedInUser();
            $this->neoLoggedInUserDetails = $this->neoUserRepository->getNodeByEmailId($this->loggedinUserDetails->emailid) ;
            $page=0;
            if (!empty($input['page']))
            {
                $page = $input['page'];
            }
            $result = $this->paymentRepository->getPaymentTransactions($this->loggedinUserDetails->emailid,$input['payment_reason'], $page);
            $total_cash = 0;
            if (count($result))
            {
                $returnArray = array();
                $total_cash_res = $this->paymentRepository->getPaymentTotalCash($this->loggedinUserDetails->emailid,$input['payment_reason']);
                if (!empty($total_cash_res))
                {
                    $total_cash = $total_cash_res[0]->total_cash ;
                }
                foreach ($result as $res)
                {
                    $r['post_id'] = $res->service_id;
                    $postDetailsR = $this->referralsRepository->getPostAndReferralDetails($res->service_id,$this->loggedinUserDetails->emailid,$res->for_user);
                    if (count($postDetailsR))
                    {
                        $r['relation_count'] = isset($postDetailsR[0][0])?$postDetailsR[0][0]->relation_count:array();
                    }
                    $fromUser = $this->neoUserRepository->getNodeByEmailId($res->from_user);
                    $r['from_email'] = $res->from_user;
                    $r['for_email'] = $res->for_user ;
                    $r['my_email'] = $res->to_user ;
                    $r['created_at'] = $res->last_modified_at ;
                    $r['amount'] = $res->amount ;
                    $fromUserDetails = $this->userGateway->formUserDetailsArray($fromUser);
                    if (!empty($fromUserDetails))
                    {
                        foreach ($fromUserDetails as $k=>$v)
                        {
                            $r['from_user_'.$k]=$v ;
                        }
                    }
                    $forUser = $this->neoUserRepository->getNodeByEmailId($res->for_user);
                    $forUserDetails = $this->userGateway->formUserDetailsArray($forUser) ;
                    if (!empty($forUserDetails))
                    {
                        foreach ($forUserDetails as $k=>$v)
                        {
                            $r['for_user_'.$k]=$v ;
                        }
                    }
                    $returnArray[] = $r ;
                }
                
                $data=array("referrals"=>$returnArray,"total_cash"=>$total_cash) ;
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.success')));
                return $this->commonFormatter->formatResponse(200, "success", $message, $data) ;
            
            }
            else
            {
                $message = array('msg'=>array(Lang::get('MINTMESH.referrals.no_result')));
                return $this->commonFormatter->formatResponse(200, "success", $message, array()) ;
            }
            
        }
        
        public function testIndex()
        {
            $this->referralsRepository->testIndex();
        }
    
}
?>
