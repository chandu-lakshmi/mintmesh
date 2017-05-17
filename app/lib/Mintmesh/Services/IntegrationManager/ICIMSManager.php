<?php

namespace Mintmesh\Services\IntegrationManager;

use Mintmesh\Services\APPEncode\APPEncode;
use Mintmesh\Repositories\BaseRepository;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Client as NeoClient;
use Everyman\Neo4j\Cypher\Query as CypherQuery;
use Guzzle\Http\Client as guzzleClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Message\ResponseInterface;
use lib\Parser\MyEncrypt;
use DB,
    Config,
    Queue,
    Lang;

class ICIMSManager extends IntegrationManager {
    
    protected $db_user, $db_pwd, $client, $appEncodeDecode, $db_host, $db_port;
    protected $userRepository, $guzzleClient;
    public $requestParams = array();

    const SUCCESS_RESPONSE_CODE = 200;
    const API_END_POINT = 'API_END_POINT';
    const DCNAME = 'DCNAME';
    const USERNAME = 'USERNAME';
    const PASSWORD = 'PASSWORD';

    public function __construct() {
        $this->db_user = Config::get('database.connections.neo4j.username');
        $this->db_pwd = Config::get('database.connections.neo4j.password');
        $this->db_host = Config::get('database.connections.neo4j.host');
        $this->db_port = Config::get('database.connections.neo4j.port');
        $this->client = new NeoClient($this->db_host, $this->db_port);
        $this->client->getTransport()->setAuth($this->db_user, $this->db_pwd);
        $this->neoEnterpriseUser = $this->db_user;
        $this->guzzleClient = new guzzleClient();
        $this->appEncodeDecode = new APPEncode();
    }
    
    public function intiateRequest($company_hcm_job_id) {
        
        $integrationManager = new IntegrationManager();
        $companyJobDetail = $integrationManager->getCompanyHcmJobbyId($company_hcm_job_id);
        #scheduler enabled or disabled here
        if(!empty($companyJobDetail) && $companyJobDetail->status == '1'){
            $JobDetails = $integrationManager->getJobDetail($companyJobDetail->hcm_jobs_id);
            $companyJobConfigDetails = $integrationManager->getCompanyJobConfigs($JobDetails->hcm_id, $companyJobDetail->company_id);
            $requestParams = $integrationManager->composeRequestParams($JobDetails, $companyJobDetail, $companyJobConfigDetails);

            $this->requestParams = $requestParams;
            $return = $integrationManager->doRequest($requestParams);
            $this->processResponseData($return, $companyJobDetail->hcm_jobs_id, $companyJobDetail->company_id, $requestParams);
            $integrationManager->updateLastProcessedTime($company_hcm_job_id, $companyJobDetail);
        }
        return TRUE;
    }
    
    public function processResponseData($responseBody, $jobId, $companyId, $requestParams) {
        
       $integrationManager = new IntegrationManager();
        $array = json_decode($responseBody, TRUE);
        $jobsInfo = $return = $arrayIcicms = array(); 
        if (isset($array['searchResults'])) {
        foreach ($array['searchResults'] as $key => $value) {
            $jobsInfo = $this->doJobsRequest($value['self'],$requestParams);
             $arrayIcicms[$key] = json_decode($jobsInfo, TRUE);
                $return[$key]['jobUrl'] = $value['self'];  
                $return[$key]['jobTitle'] = $arrayIcicms[$key]['header'][0]['value'];  
                $return[$key]['jobId'] = substr($arrayIcicms[$key]['header'][1]['value'], 5);  
                $return[$key]['numberofpositions'] = $arrayIcicms[$key]['header'][2]['value'];  
                $return[$key]['joblocation'] = $arrayIcicms[$key]['header'][3]['value']; 
                $return[$key]['jobpoststart'] = $arrayIcicms[$key]['header'][4]['value']; 
                $return[$key]['positioncategory'] = $arrayIcicms[$key]['header'][5]['value']; 
                $return[$key]['overview'] = strip_tags($arrayIcicms[$key]['description'][0]['value'] . $arrayIcicms[$key]['description'][1]['value'] . $arrayIcicms[$key]['description'][2]['value']); 
//                $return[$key]['responsibilities'] = $arrayIcicms[$key]['description'][1]['value']; 
//                $return[$key]['qualifications'] = $arrayIcicms[$key]['description'][2]['value']; 
            
        }
       // print_r($return); die;
       $this->createIcimsJob($return, $companyId, $jobId);
        //$this->processJobsResponseData($return,$jobId, $companyId, $requestParams);
       }
        
    }
    
    public function processJobsResponseData($jobsInfo,$jobId, $companyId, $requestParams) {
        print_r($jobsInfo); die;
         $return = array();
        if (isset($jobsInfo)) {
            foreach ($jobsInfo as $key => $value) {
                $return[$key]['jobTitle'] = $value['header'][0]['value'];  
                $return[$key]['jobId'] = $value['header'][1]['value'];  
                $return[$key]['numberofpositions'] = $value['header'][2]['value'];  
                $return[$key]['joblocation'] = $value['header'][3]['value']; 
                $return[$key]['jobpoststart'] = $value['header'][4]['value']; 
                $return[$key]['positioncategory'] = $value['header'][5]['value']; 
                $return[$key]['overview'] = $value['description'][0]['value']; 
                $return[$key]['responsibilities'] = $value['description'][1]['value']; 
                $return[$key]['qualifications'] = $value['description'][2]['value']; 
            }
         //print_r($return); die;
            
        }

        return true;
    }
    
     protected function doJobsRequest($api_url,$requestParams) {
        // do request to hcm endpoints
       $endPoint = $api_url;
        $request = $this->guzzleClient->get($endPoint);
        if (array_key_exists(self::USERNAME, $requestParams)) {
            
            $username = $requestParams[self::USERNAME];
            $password = $requestParams[self::PASSWORD];
            \Log::info("ICICMS Endpoint hit : $endPoint");
            $request->setAuth($username, $password);
        } else if (array_key_exists(self::AuthorizationHeader, $requestParams)) {
            $accesToken = $requestParams[self::AuthorizationHeader];
            \Log::info("ICICMS Endpoint hit : $endPoint");
            $request->setHeader(self::AuthorizationHeader, $accesToken);
        }

        try {
            $response = $request->send();
            
            if ($response->isSuccessful() && $response->getStatusCode() == self::SUCCESS_RESPONSE_CODE) {
                return $response->getBody();
            } else {
                \Log::info("Error while getting response : $response->getInfo()");
            }
        } catch (ClientErrorResponseException $exception) {
         
            $responseBody = $exception->getResponse()->getBody(true);
        }

    }
    
    public function createIcimsJob($dataAry, $companyId, $jobId) {
        $SFManager = new SFManager();
        $integrationManager = new IntegrationManager();
        $objCompany = new \stdClass();
        $hcmJobId = $jobId;
        $companyId = $companyId;
        $bucketId = 1;
        $fromId = $postId = 0;
        $dfText = 'See Job Description';
        $companyDetails = $integrationManager->getCompanyDetails($companyId);
        $companyDetails = $companyDetails[0];
        $companyName = $companyDetails->name; //'company68';
        $companyCode = $companyDetails->code; //510632;
        $companyLogo = $companyDetails->logo; //510632;
        $userDetails = $integrationManager->getUserDetails($companyDetails->created_by);
        $userDetails = $userDetails[0];
        $userEmailId = !empty($userDetails->emailid) ? $userDetails->emailid : ''; //'gopi68@mintmesh.com';
        $userFirstname = !empty($userDetails->firstname) ? $userDetails->firstname : ''; //'gopi68@mintmesh.com';
        $objCompany->fullname = $companyName;

        $notificationMsg = Lang::get('MINTMESH.notifications.messages.27');
        $params['company_code'] = $companyCode;
        $params['bucket_id'] = $bucketId;
        $params['company_id'] = $companyId;

        if (!empty($userEmailId)) {
            $neoUser = $this->getNeoUserByEmailId($userEmailId);
            $fromId = !empty($neoUser->getID()) ? $neoUser->getID() : ''; //292819;
            $userData = $SFManager->getUserByEmail($userEmailId);
            $this->user = !empty($userData[0])?$userData[0]:'';
        }
        if (!empty($dataAry)) {
            foreach ($dataAry as $row) {
                $inviteCount = 0;
                $neoInput = $relationAttrs = $postCompanyrelationAttrs = $neoCompanyBucketContacts = array();
                $JobMappingFields = $integrationManager->getJobMappingFields($hcmJobId, $companyId);
                foreach ($JobMappingFields as $field) {

                    $neoInput[$field->destination_key] = !empty($row[$field->source_key]) ? $row[$field->source_key] : '';
                }
                $neoInput['service_scope'] = "find_candidate";
                $neoInput['service_from_web'] = 1;
               
                    $neoInput['employment_type'] = $dfText;
               
                $neoInput['service_period'] = 'immediate';
                $neoInput['service_type'] = 'global';
                $neoInput['free_service'] = 1;
                if (empty($neoInput['service_currency'])) {
                    $neoInput['service_currency'] = 'USD';
                }
                $neoInput['service_cost'] = '';
                $neoInput['company'] = $companyName;
                $neoInput['job_description'] = ''; //job_description
                $neoInput['skills'] = '';
                $neoInput['status'] = Config::get('constants.POST.STATUSES.ACTIVE');
                $neoInput['created_by'] = $userEmailId;
                $neoInput['bucket_id'] = $bucketId;
                $neoInput['post_type'] = 'external';
                $neoInput['hcm_type'] = 'ICIMS';

                $neoInput['job_function'] = (!empty($neoInput['job_function']) && !filter_var($neoInput['job_function'], FILTER_VALIDATE_INT)) ? $neoInput['job_function'] : $dfText;
                $neoInput['experience_range'] = $dfText;
                $neoInput['industry'] = $dfText;

                $relationAttrs['created_at'] = date("Y-m-d H:i:s");
                $relationAttrs['company_name'] = $companyName;
                $relationAttrs['company_code'] = $companyCode;
                #get the extra information
              //  $reqId = $neoInput['jobid'];
               
//                $isNotExisted = $SFManager->checkJobExistedWithReqIdOrNot($reqId);
//                echo $isNotExisted; die;
//                if ($isNotExisted) {
                   
                    //print_r($neoInput).exit;
                    $createdPost = $SFManager->createPostAndUserRelation($fromId, $neoInput, $relationAttrs);
                    print_r($createdPost); die;
                    if (isset($createdPost[0]) && isset($createdPost[0][0])) {
                        $postId = $createdPost[0][0]->getID();
                    } else {
                        $postId = 0;
                    }
                    if (!empty($postId)) {
                        #map post and Rewards
                        $rewardsAttrs = $excludedList = array();
                        $rewardsAttrs['post_id'] = $postId;
                        $rewardsAttrs['rewards_type'] = 'free';
                        $rewardsAttrs['type'] = 'discovery';
                        $rewardsAttrs['currency_type'] = 1;
                        $rewardsAttrs['rewards_value'] = 0;
                        $rewardsAttrs['created_at'] = gmdate("Y-m-d H:i:s");
                        $rewardsAttrs['created_by'] = $userEmailId;
                        $createdRewards = $SFManager->createRewardsAndPostRelation($postId, $rewardsAttrs);
                        #map post and company
                        $postCompanyrelationAttrs['created_at'] = gmdate("Y-m-d H:i:s");
                        $postCompanyrelationAttrs['user_emailid'] = $userEmailId;
                        if (!empty($relationAttrs['company_code'])) {
                            $createdrelation = $SFManager->createPostAndCompanyRelation($postId, $relationAttrs['company_code'], $postCompanyrelationAttrs);
                        }
                        #for reply emailid 
                        $replyToName = Config::get('constants.MINTMESH_SUPPORT.REFERRER_NAME');
                        $replyToHost = Config::get('constants.MINTMESH_SUPPORT.REFERRER_HOST');

                        $neoCompanyBucketContacts = $SFManager->getImportContactsList($params);
                        //$inviteCount = !empty($neoCompanyBucketContacts['total_records'][0]->total_count)?$neoCompanyBucketContacts['total_records'][0]->total_count:0;
                        foreach ($neoCompanyBucketContacts['Contacts_list'] as $contact => $contacts) {
                            $pushData = array();
                            if ($contacts->status != 'Separated') {

                                #creating included Relation between Post and Contacts 
                                $pushData['postId'] = $postId;
                                $pushData['bucket_id'] = $params['bucket_id'];
                                $pushData['contact_emailid'] = $contacts->emailid;
                                $pushData['company_code'] = $params['company_code'];
                                $pushData['user_emailid'] = $userEmailId;
                                $pushData['notification_msg'] = $notificationMsg;
                                $pushData['notification_log'] = 1; //for log the notification or not
                                Queue::push('Mintmesh\Services\Queues\CreateEnterprisePostContactsRelation', $pushData, 'default');
                                
                                #send push notifications to all the contacts
                                $notifyData   = array();
                                $notifyData['serviceId']            = $postId;
                                $notifyData['loggedinUserDetails']  = $this->user;
                                $notifyData['neoLoggedInUserDetails'] = $objCompany;//obj
                                $notifyData['includedList']     = array($contacts->emailid);
                                $notifyData['excludedList']     = $excludedList;
                                $notifyData['service_type']     = '';
                                $notifyData['service_location'] = '';
                                $notifyData['notification_type']  = 27;
                                $notifyData['service_name']       = $neoInput['service_name'];
                                Queue::push('Mintmesh\Services\Queues\NewPostReferralQueue', $notifyData, 'Notification');

                                #send email notifications to all the contacts
                                $refId = $refCode = 0;
                                $emailData = array();
                                $refId = $SFManager->getUserNodeIdByEmailId($contacts->emailid);
                                $refCode = MyEncrypt::encrypt_blowfish($postId . '_' . $refId, Config::get('constants.MINTMESH_ENCCODE'));
                                $replyToData = '+ref=' . $refCode;
                                $emailData['company_name'] = $companyName;
                                $emailData['company_code'] = $companyCode;
                                $emailData['post_id'] = $postId;
                                $emailData['post_type'] = $neoInput['post_type'];
                                $emailData['company_logo'] = $companyLogo;
                                $emailData['to_firstname'] = $contacts->firstname;
                                $emailData['to_lastname'] = $contacts->lastname;
                                $emailData['to_emailid'] = $contacts->emailid;
                                $emailData['from_userid'] = $fromId;
                                $emailData['from_emailid'] = $userEmailId;
                                $emailData['from_firstname'] = $userFirstname;
                                $emailData['ip_address'] = '192.168.1.1';
                                $emailData['ref_code'] = $refCode;
                                $emailData['reply_to'] = $replyToName . $replyToData . $replyToHost;
                                Queue::push('Mintmesh\Services\Queues\SendJobPostEmailToContactsQueue', $emailData, 'Notification');
                                $inviteCount+=1;
                            }
                        }
                        $SFManager->updatePostInviteCount($postId, $inviteCount);
                    }
               // }
            }
        }
        return true;
    }
}
