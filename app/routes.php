<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
/* Route related to API docs */

Route::get('test','ZenefitsController@test');
Route::get("getApp", "ZenefitsController@installMintmeshApp");
Route::get('getApp', function(){
    return Redirect::to('http://202.63.105.85/mmenterprise/getApp/zenefits');
});
Route::get("getAccesCode", "ZenefitsController@getAccesCode");
Route::any("getAccessTokenRefreshToken", "ZenefitsController@getAccessTokenRefreshToken");

Route::get('getMails','Email2Controller@getMails');
Route::any('getOauthBasedOnClientId','HomeController@getOauthBasedOnClientId');

//Download Resumes getIntegrationStatus
Route::any('getZipDownload','API\Referrals\ReferralsController@getDownloadZipSelectedResumes');

Route::any('getResumeDownload','API\Referrals\ReferralsController@getFileDownload');

//Integration Status API
Route::any('getIntegrationStatus','API\SuccessFactors\successFactorController@getIntegrationStatus');

Route::post('getParsedResumeDocInfo','API\SocialContacts\ContactsController@getParsedResumeDocInfo');
Route::get('docs', function() {
	return View::make('docs.v1.index');
});
Route::get('docs/v2', function() {
	return View::make('docs.v2.index');
});

/* Home page of Mintmesh webservice */
Route::get('/', function()
{
	return View::make('hello');
});

Route::get('/terms', function()
{
	return View::make('landings/terms');
});
Route::get('/privacy_policy', function()
{
	return View::make('landings/privacy');
});
// seek service view
 Route::get("/{serviceCode}", "API\Referrals\ReferralsController@showService");

//for user login & registration with out oAuth
Route::group(array('prefix' => 'v1'), function() {


    //get country codes list
    Route::get("country_codes", "API\User\UserController@countryCodes");
    
    //get industries list
    Route::get("get_industries", "API\User\UserController@getIndustries");
    
    //get job functions list
    Route::get("get_job_functions", "API\User\UserController@getJobFunctions");
    
    //get skills
    Route::post("get_skills", "API\User\UserController@getSkills");
    
    //get Countries list by name search
    Route::post("get_countries", "API\User\UserController@getCountries");
        
    //check email existance
    Route::post("checkEmailExistance", "API\User\UserController@checkEmailExistance");
    
    //check phone existance
    Route::post("checkPhoneExistance", "API\User\UserController@checkPhoneExistance");

    // Mintmesh user login
    Route::post("user/login", "API\User\UserController@login");
    Route::post("user/special_login", "API\User\UserController@special_login");

    // Mintmesh user creation
    Route::post("user/create", "API\User\UserController@create");

    // Mintmesh user email validation
    Route::get("user/activate/{code}", "API\User\UserController@activateAccount");

    // Mintmesh user forgot password
    Route::post("user/forgot_password", "API\User\UserController@forgotPassword");

    // Mintmesh user reset password
    Route::get("redirect_to_app/{url}", "API\User\UserController@redirectToApp");
    Route::post("user/check_reset_password", "API\User\UserController@checkResetPassword");
    Route::post("user/reset_password", "API\User\UserController@resetPassword");
    Route::post("testIndex", "API\Referrals\ReferralsController@testIndex");
    
    //citrus payment\
    Route::any("payment/generate_bill", "API\Payment\PaymentController@generateCitrusBill");
    Route::post("payment/citrus_transaction", "API\Payment\PaymentController@citrusTransaction");
    
    //cache badwords
    Route::post("cache_badwords", "API\User\UserController@cacheBadWords");
    
    //experience_ranges
       Route::get("get_experiences", "API\User\UserController@getExperiences");
    //employment_types
       Route::get("get_employment_types", "API\User\UserController@getEmploymentTypes");
       
    // Mintmesh enterprise user creation
       Route::post("enterprise/create_user", "API\Enterprise\EnterpriseController@createEnterpriseUser");
    // Mintmesh enterprise user email verification
       Route::post("enterprise/verify_email", "API\Enterprise\EnterpriseController@emailVerification");
    // Mintmesh enterprise login
       Route::post("enterprise/login", "API\Enterprise\EnterpriseController@enterpriseLogin");
       
       Route::post("enterprise/special_login", "API\Enterprise\EnterpriseController@enterpriseSpecialLogin");
       //Mintmesh enterprise with emailid 
       Route::post("enterprise/special_grant_login", "API\Enterprise\EnterpriseController@enterpriseSpecialGrantLogin");
    // Mintmesh enterprise forgot password
       Route::post("enterprise/forgot_password", "API\Enterprise\EnterpriseController@forgotPassword");
    // Mintmesh enterprise reset password   
       Route::post("enterprise/reset_password", "API\Enterprise\EnterpriseController@resetPassword");
    //set password
      Route::post("enterprise/set_password", "API\Enterprise\EnterpriseController@setPassword");
      Route::get("enterprise/update_new_permission", "API\Enterprise\EnterpriseController@updateNewPermission");
      //refer candidate
      Route::post("enterprise/refer_candidate", "API\Post\PostController@referCandidate");
      //applying job 
      Route::post("enterprise/apply_job", "API\Post\PostController@applyJob");
      //applying job 
      Route::post("enterprise/apply_job_ref", "API\Post\PostController@applyJobRef");
      //decrpyting ref
      Route::post("enterprise/decrypt_ref", "API\Post\PostController@decryptRef");
      //decrpyting ref
      Route::post("enterprise/decrypt_mobile_ref", "API\Post\PostController@decryptRequestCandidateResume");
      //get applying job list
      Route::post("enterprise/apply_jobs_list", "API\Post\PostController@applyJobsList");
      //get applying job details
      Route::post("enterprise/apply_job_details", "API\Post\PostController@applyJobDetails");
      //get success Factor jobs
      Route::get('create_sfjob/{reqid}','API\SuccessFactors\successFactorController@createSFJob');
      //decrpyting campaign ref
      Route::post("enterprise/decrypt_campaign_ref", "API\Post\PostController@decryptCampaignRef");
      //get campaign job list
      Route::post("enterprise/campaign_jobs_list", "API\Post\PostController@campaignJobsList");
      //get campaign job list
      Route::post("enterprise/company_integration", "API\Enterprise\EnterpriseController@companyIntegration");
      //get 
      Route::post("enterprise/get_talentcommunity_buckets", "API\Enterprise\EnterpriseController@getTalentCommunityBuckets");
      Route::post("enterprise/add_to_talentcommunity", "API\Enterprise\EnterpriseController@addToTalentCommunity");
      
      
      Route::post("enterprise/test_lic", "API\Enterprise\EnterpriseController@testLic");
      //unsolicited node for old companies
      Route::post("enterprise/unsolicited_old_companies", "API\Enterprise\EnterpriseController@unsolicitedForOldCompanies");
      Route::post("enterprise/not_parsed_resumes", "API\Post\PostController@notParsedResumes");
      Route::post("enterprise/rename_company", "API\Enterprise\EnterpriseController@renameCompany");
      
});

//Route::group(array('prefix' => 'v1'), function() {
Route::group(array('prefix' => 'v1', 'before' => 'oauth'), function() {

    
        //complete user profile
        Route::post("user/complete_profile", "API\User\UserController@completeUserProfile");
        //edit user profile
        Route::post("user/edit_profile", "API\User\UserController@editProfile");
        //change user password
        Route::post("user/change_password", "API\User\UserController@changePassword");
        // import contacts
        Route::post("user/import_contacts", "API\SocialContacts\ContactsController@importContacts");
        // sending invitation
        Route::post("user/invite_people", "API\SocialContacts\ContactsController@sendInvitation");
        // get list of mintmesh users
        Route::post("user/contacts/", "API\SocialContacts\ContactsController@getMintmeshUsers");
        //set connection request between two users
        Route::post("user/request_connect", "API\User\UserController@connectUserRequest");
        //get profile details
        Route::post("user/get_profile", "API\User\UserController@getUserProfile");
        // get single notification details
        Route::post("user/get_single_notification_details", "API\User\UserController@getSingleNotificationDetails");
       //get reference flow
       Route::post("user/get_reference_flow", "API\User\UserController@getReferenceFlow");
       // get user details by emailid
       Route::post("user/get_profile_by_emailid", "API\User\UserController@getUserDetailsByEmail");
       //get notifications
       Route::post("user/get_notifications", "API\User\UserController@getAllNotifications");
       //accept connection
       Route::post("user/accept_connection", "API\User\UserController@acceptConnection");
       // close notification
       Route::post("user/close_notification", "API\User\UserController@closeNotification");
       // get connected users
       Route::post("user/get_connected_users", "API\User\UserController@getConnectedAndMMUsers");
       // request reference
       Route::post("user/request_reference", "API\User\UserController@referContact");
       // get mutual requests
       Route::post("user/get_mutual_requests", "API\User\UserController@getMutualRequests");
       // get user connections
       Route::post("user/get_user_connections", "API\User\UserController@getUserConnections");
       
       // get users by location
       Route::post("user/get_users_by_location", "API\User\UserController@getUsersByLocation");
       
       // refer a connection
       Route::post("user/refer_my_connection", "API\User\UserController@referMyConnection");
       
       // get my requests
       Route::post("user/get_my_requests", "API\User\UserController@getMyRequests");
       // get levels info
       Route::post("user/get_levels_info", "API\User\UserController@getLevelsInfo");
      // get specific level info
       Route::post("user/get_specific_level_info", "API\User\UserController@getSpecificLevelInfo");
       // disconnect a user
       Route::post("user/disconnect_user", "API\User\UserController@disConnectUsers");
       
       // get influencers list
       Route::post("user/get_influencers_list", "API\User\UserController@getInfluencersList");
       
       // get recruiters list
       Route::post("user/get_recruiters_list", "API\User\UserController@getRecruitersList");
       
       //send otp
       Route::post("user/send_otp", "API\SMS\SmsController@sendOTP");
       //verify otp
       Route::post("user/verify_otp", "API\SMS\SmsController@verifyOTP");
       // check user password
       Route::post("user/check_user_password", "API\User\UserController@checkUserPassword");
       // logout
       Route::post("user/logout", "API\User\UserController@logout");
   
       //seeks
        // seek a referral
       Route::post("referral/seek_service_referral", "API\Referrals\ReferralsController@seekServiceReferral");
       Route::post("referral/edit_post", "API\Referrals\ReferralsController@editPost");
       Route::post("referral/close_post", "API\Referrals\ReferralsController@closePost");
       Route::post("referral/deactivate_post", "API\Referrals\ReferralsController@deactivatePost");
       
       Route::post("referral/get_latest_posts", "API\Referrals\ReferralsController@getLatestPosts");
       Route::post("referral/get_posts", "API\Referrals\ReferralsController@getPosts");
       Route::post("referral/refer_contact", "API\Referrals\ReferralsController@referContact");
       Route::post("referral/get_post_details", "API\Referrals\ReferralsController@getPostDetails");
       Route::post("referral/get_post_references", "API\Referrals\ReferralsController@getPostReferences");
       Route::post("referral/get_my_referrals", "API\Referrals\ReferralsController@getMyReferrals");
       //get all referrals done by me
       Route::post("referral/get_all_referrals", "API\Referrals\ReferralsController@getAllReferrals");
       Route::post("referral/process_post", "API\Referrals\ReferralsController@processPost");
       Route::post("referral/get_post_status_details", "API\Referrals\ReferralsController@getPostStatusDetails");
       Route::post("referral/get_my_referral_contacts", "API\Referrals\ReferralsController@getMyReferralContacts");
       Route::post("referral/search_people", "API\Referrals\ReferralsController@searchPeople");
       Route::post("referral/get_mutual_people", "API\Referrals\ReferralsController@getMutualPeople");
       Route::post("referral/get_referrals_cash", "API\Referrals\ReferralsController@getReferralsCash");
       
       //payment
       //payment client tokens
       Route::post("payment/generate_bt_token", "API\Payment\PaymentController@generateBTToken");
       
       //save bank details of user
       Route::post("payment/save_user_bank", "API\Payment\PaymentController@saveUserBank");
       //edit bank details of user
       Route::post("payment/edit_user_bank", "API\Payment\PaymentController@editUserBank");
       //delete bank details of user
       Route::post("payment/delete_user_bank", "API\Payment\PaymentController@deleteUserBank");
       //list bank details of user
       Route::post("payment/list_user_banks", "API\Payment\PaymentController@listUserBanks");
       //payment transactions
       Route::post("payment/bt_transaction", "API\Payment\PaymentController@braintreeTransaction");
       Route::post("payment/payout", "API\Payment\PaymentController@payout");
       Route::post("payment/manual_payout", "API\Payment\PaymentController@manualPayout");
       Route::post("get_payouts", "API\Payment\PaymentController@getPayoutTransactions");
       
       //sms
       Route::post("send_sms", "API\SMS\SmsController@sendSMS");
       
       //services
       Route::post("get_services", "API\User\UserController@getServices");
       //you are values
       Route::post("get_you_are_values", "API\User\UserController@getYouAreValues");
       
       //get professions for provider service provider
       Route::post("get_professions", "API\User\UserController@getProfessions");
        
       //resend activation link
       Route::post("resend_activation_link", "API\User\UserController@resendActivationLink");
       
       /**************
        * APIs for Mintmesh enterprise APP with oAuth
        */
       //posting job from campaigns
       Route::post("enterprise/get_jobs_list", "API\Post\PostController@getJobsList");
       //get campaigns
       Route::post("referral/get_campaign_details", "API\Referrals\ReferralsController@getCampaignDetails");
       //get job details
       Route::post("enterprise/get_job_details", "API\Post\PostController@getJobDetails");

       /**************
        * APIs for Mintmesh enterprise with oAuth
        */
       // Mintmesh enterprise Company Profile creation
       Route::post("enterprise/update_company", "API\Enterprise\EnterpriseController@updateCompanyProfile");    
       //Posting a job
       Route::post("enterprise/post_job", "API\Post\PostController@postJob");
       //enterprise contacts upload
       Route::post("enterprise/contacts_upload", "API\Enterprise\EnterpriseController@enterpriseContactsUpload");
       //enterprise create new bucket
       Route::post("enterprise/create_bucket", "API\Enterprise\EnterpriseController@createBucket");
       
       //Update Bucket By Dinesh Pitla
       Route::post("enterprise/update_bucket", "API\Enterprise\EnterpriseController@updateBucket");
       
       //enterprise contacts upload
       Route::post("enterprise/upload_contacts", "API\Enterprise\EnterpriseController@uploadContacts");
       //enterprise validate Contacts input File Headers
       Route::post("enterprise/validate_headers", "API\Enterprise\EnterpriseController@validateContactsFileHeaders");
       //enterprise add contact
       Route::post("enterprise/add_contact", "API\Enterprise\EnterpriseController@addContact");
       Route::post("enterprise/buckets_list", "API\Enterprise\EnterpriseController@enterpriseBucketsList");
       Route::post("enterprise/contacts_list", "API\Enterprise\EnterpriseController@enterpriseContactsList");
       Route::post("enterprise/email_invitation", "API\Enterprise\EnterpriseController@enterpriseContactsEmailInvitation");
      //List of jobs posted from web
       Route::post("enterprise/jobs_list", "API\Post\PostController@jobsList");
      //Job details posted from web
       Route::post("enterprise/job_details", "API\Post\PostController@jobDetails");
       Route::post("enterprise/get_user_details", "API\Enterprise\EnterpriseController@getUserDetails");
      //  Mintmesh enterprise referral details  
       Route::post("enterprise/job_referral_details", "API\Post\PostController@jobReferralDetails");
      //  Mintmesh enterprise status details
       Route::post("enterprise/process_job", "API\Post\PostController@processJob");
      //  Mintmesh enterprise awaiting action status update
       Route::post("enterprise/awaiting_action", "API\Post\PostController@awaitingAction");
      //company details for enterprise
       Route::post("enterprise/view_company_details", "API\Enterprise\EnterpriseController@viewCompanyDetails");
      //connected companies list for user
       Route::post("enterprise/connected_companies_list", "API\Enterprise\EnterpriseController@connectedCompaniesList");
      //connect to company for mobile user
       Route::post("enterprise/connect_to_company", "API\Enterprise\EnterpriseController@connectToCompany");
      //view dashboard details
       Route::post("enterprise/view_dashboard", "API\Enterprise\EnterpriseController@viewDashboard");
      //company_profile
      Route::post("enterprise/get_company_profile", "API\Enterprise\EnterpriseController@getCompanyProfile");
      //get company subscriptions log
      Route::post("enterprise/get_company_subscriptions", "API\Enterprise\EnterpriseController@getCompanySubscriptions");
      //update contacts list
      Route::post("enterprise/update_contacts_list", "API\Enterprise\EnterpriseController@updateContactsList");
      //other edits in contact list
      Route::post("enterprise/other_edits_in_contact_list", "API\Enterprise\EnterpriseController@otherEditsInContactList");
      //get permissions
      Route::post("enterprise/permissions", "API\Enterprise\EnterpriseController@getPermissions");
      //add user to company
      Route::post("enterprise/add_user", "API\Enterprise\EnterpriseController@addUser");
      //add group
      Route::post("enterprise/add_group", "API\Enterprise\EnterpriseController@addGroup");
      //get groups
      Route::post("enterprise/get_groups", "API\Enterprise\EnterpriseController@getGroups");
      //get user permissions
      Route::post("enterprise/get_user_permissions", "API\Enterprise\EnterpriseController@getUserPermissions");
      //update enterprise user details
      Route::post("enterprise/update_user", "API\Enterprise\EnterpriseController@updateUser");
      //change enterprise user password
      Route::post("enterprise/change_password", "API\User\UserController@changePassword");
      //get Job rewards
      Route::post("enterprise/job_rewards", "API\Post\PostController@jobRewards");
      //deactivate post
      Route::post("enterprise/deactivate_post", "API\Enterprise\EnterpriseController@deactivatePost");
      
      //add campaign
      Route::post("enterprise/add_campaign", "API\Post\PostController@addCampaign");
      //campaigns list
      Route::post("enterprise/campaigns_list", "API\Post\PostController@campaignsList");
      //edit campaign
      Route::post("enterprise/view_campaign", "API\Post\PostController@viewCampaign");
      //deactivate post
      Route::post("enterprise/deactivate_post", "API\Enterprise\EnterpriseController@deactivatePost");
      //resend activation link
      Route::post("enterprise/resend_activation_link", "API\Enterprise\EnterpriseController@resendActivationLink");    
      //get company all referrals link
      Route::post("enterprise/get_company_all_referrals", "API\Post\PostController@getCompanyAllReferrals");
      //mutliple awaiting action
      Route::post("enterprise/multiple_awaiting_action", "API\Post\PostController@multipleAwaitingAction");
      //posting job from campaigns
      Route::post("enterprise/job_post_from_campaigns", "API\Post\PostController@jobPostFromCampaigns");
      //add hcm
      Route::post("enterprise/add_edit_hcm", "API\Enterprise\EnterpriseController@addEditHcm");
      Route::post("enterprise/add_edit_zenefits_hcm", "API\Enterprise\EnterpriseController@addEditZenefitsHcm");
      Route::post("enterprise/add_edit_icims_hcm", "API\Enterprise\EnterpriseController@addEditIcimsHcm");
      //view hcm
      Route::post("enterprise/get_hcm_list", "API\Enterprise\EnterpriseController@getHcmList");
      Route::post("enterprise/get_zenefits_hcm_list", "API\Enterprise\EnterpriseController@getZenefitsHcmList");
      Route::post("enterprise/get_icims_hcm_list", "API\Enterprise\EnterpriseController@getIcimsHcmList");
      Route::post("enterprise/get_greenhouse_hcm_list", "API\Enterprise\EnterpriseController@getGreenhouseHcmList");
      //view hcm
      Route::post("enterprise/get_hcm_partners", "API\Enterprise\EnterpriseController@getHcmPartners");
      // company all contacts
      Route::post("enterprise/company_all_contacts", "API\Enterprise\EnterpriseController@companyAllContacts");
      // add_configuration
      Route::post("enterprise/add_configuration", "API\Enterprise\EnterpriseController@addConfiguration");
      // get_configuration
      Route::post("enterprise/get_configuration", "API\Enterprise\EnterpriseController@getConfiguration");
      // get_configuration
      Route::post("enterprise/upload_resume", "API\Post\PostController@uploadResume");
      Route::post("enterprise/get_resumes_update_status", "API\Post\PostController@getResumesUpdateStatus");
      //career settings
      Route::post("enterprise/edit_career_settings", "API\Post\PostController@editCareerSettings");
      Route::post("enterprise/get_career_settings", "API\Post\PostController@getCareerSettings");
      //candidate management
      Route::post("enterprise/get_candidate_email_templates", "API\Candidates\CandidatesController@getCandidateEmailTemplates");
      Route::post("enterprise/get_candidate_details", "API\Candidates\CandidatesController@getCandidateDetails");
      Route::post("enterprise/add_candidate_schedule", "API\Candidates\CandidatesController@addCandidateSchedule");
      Route::post("enterprise/add_candidate_email", "API\Candidates\CandidatesController@addCandidateEmail");
      Route::post("enterprise/add_candidate_comment", "API\Candidates\CandidatesController@addCandidateComment");
      Route::post("enterprise/get_candidate_activities", "API\Candidates\CandidatesController@getCandidateActivities");
      Route::post("enterprise/get_candidate_tag_jobs_list", "API\Candidates\CandidatesController@getCandidateTagJobsList");
      Route::post("enterprise/add_candidate_tag_jobs", "API\Candidates\CandidatesController@addCandidateTagJobs");
      Route::post("enterprise/get_candidate_comments", "API\Candidates\CandidatesController@getCandidateComments");
      Route::post("enterprise/get_candidate_sent_emails", "API\Candidates\CandidatesController@getCandidateSentEmails");
      Route::post("enterprise/get_candidate_referral_list", "API\Candidates\CandidatesController@getCandidateReferralList");
      
});


/* Custom route for 404 page not found */
App::missing(function($exception)
{
    return Response::json(array('status_code' => 404, 'status' => 'error', 'message' => array('msg'=>'Page not found'), 'data' =>array()));
});

/* Route for getting the access token with end users oAuth2.0
|  Frst Time:
|  POST: username & password
|  returns accesstoken
|  Next time: (When access token expires, use the refresh token to get new access token)
|  POST : refresh token
|  return accesstoken
*/
Route::post("oauth/access_token", function() {
    \Log::info('<------------------- oauth/access_token ---------------------> ');
	return Response::json(Authorizer::issueAccessToken());
});


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Below routes are only specific for Admin panel
| This uses some default credentials for testing and bypassing user authentication
| and gains some special previleges
|
*/

Route::group(array("prefix" => "admin"), function() {
	Route::get("login", 'Admin\LoginController@index');
        //app download link
        Route::get('app/download', function()
        {
                return View::make('Admin.app');
        });
        //clear memcache
        Route::post("clear_memcache", "Admin\AdminController@clearMemcache");

});


////////////////////////////******V2 apis*******///////////////////////////////////
Route::group(array('prefix' => 'v2', 'before' => 'oauth'), function() {
    
    //complete user profile,v2 only should be used
    Route::post("user/complete_profile", "API\User\UserController@completeUserProfile_v2");

    //you are values, changed for edit profile..v1 should be used
    Route::post("get_you_are_values", "API\User\UserController@getYouAreValues_v2");
    
    Route::post("referral/refer_contact", "API\Referrals\ReferralsController@referContactV2");

});

Route::group(array('prefix' => 'v2'), function() {

    //get skills,v2 should be used
    Route::post("get_skills", "API\User\UserController@getSkills_v2"); 
	// Mintmesh user creation,v2 only should be used
    Route::post("user/create", "API\User\UserController@create_v2");
});

Route::group(array('prefix' => 'v3', 'before' => 'oauth'), function() {
    //get notifications
    Route::post("user/get_notifications", "API\User\UserController@getAllNotifications");
    // get connected users
    Route::post("user/get_connected_users", "API\User\UserController@getConnectedAndMMUsers");
    //refers screen
    Route::post("referral/get_posts_new", "API\Referrals\ReferralsController@getPosts");
    //refers screen
    Route::post("referral/get_posts", "API\Referrals\ReferralsController@getPostsV3");
    //get post references
    Route::post("referral/get_post_references", "API\Referrals\ReferralsController@getPostReferences");
    //get my referrals for a post
    Route::post("referral/get_my_referrals", "API\Referrals\ReferralsController@getMyReferrals");
    //get referral contacts for a post
    Route::post("referral/get_my_referral_contacts", "API\Referrals\ReferralsController@getMyReferralContacts");
    //get all referrals done by me
    Route::post("referral/get_all_referrals", "API\Referrals\ReferralsController@getAllReferrals");
    // get my requests
       Route::post("user/get_my_requests", "API\User\UserController@getMyRequests");
     // get user connections
       Route::post("user/get_user_connections", "API\User\UserController@getUserConnections");  
});

/*
|--------------------------------------------------------------------------
| Enterprise API's 
|--------------------------------------------------------------------------
|
| 
|
*/

//for user login & registration with out oAuth
Route::group(array('prefix' => 'v1/ent'), function() {
    //Enterprise Mintmesh user registration
    Route::post("user/create", "API\EnterpriseApp\EnterpriseAppController@create");
    //Enterprise Mintmesh user login
    Route::post("user/login", "API\EnterpriseApp\EnterpriseAppController@login");
    Route::post("user/special_login", "API\EnterpriseApp\EnterpriseAppController@special_login");
    //Mintmesh user forgot password
    Route::post("user/forgot_password", "API\EnterpriseApp\EnterpriseAppController@forgotPassword");
    //get skills,v2 should be used
    Route::post("get_skills", "API\EnterpriseApp\EnterpriseAppController@getSkills"); 
    //get job functions list
    Route::get("get_job_functions", "API\EnterpriseApp\EnterpriseAppController@getJobFunctions");
    //get industries list
    Route::get("get_industries", "API\EnterpriseApp\EnterpriseAppController@getIndustries");
    //get country codes list
    Route::get("country_codes", "API\EnterpriseApp\EnterpriseAppController@countryCodes");
    Route::post("user/reset_password", "API\EnterpriseApp\EnterpriseAppController@resetPassword");
    //check email existance
    Route::post("checkEmailExistance", "API\EnterpriseApp\EnterpriseAppController@checkEmailExistance");
    // Mintmesh user email validation
    Route::get("user/activate/{code}", "API\EnterpriseApp\EnterpriseAppController@activateAccount");
    //check phone existance
    Route::post("checkPhoneExistance", "API\EnterpriseApp\EnterpriseAppController@checkPhoneExistance");
    Route::post("user/check_reset_password", "API\EnterpriseApp\EnterpriseAppController@checkResetPassword");
    
    Route::get('/forgot_password', function()
    {
        return View::make('forgot-password/forgot');
    });
    
    Route::post("oauth/access_token", function() {
	return Response::json(Authorizer::issueAccessToken());
    });
});
Route::group(array('prefix' => 'v1/ent', 'before' => 'oauth'), function() {
    //logout
    Route::post("user/logout", "API\EnterpriseApp\EnterpriseAppController@logout");
    //import contacts
    Route::post("user/import_contacts", "API\EnterpriseApp\EnterpriseAppController@importContacts");
    //set connection request between two users
    Route::post("user/request_connect", "API\EnterpriseApp\EnterpriseAppController@connectUserRequest");
    //sending invitation
    Route::post("user/invite_people", "API\EnterpriseApp\EnterpriseAppController@sendInvitation");
    //sms
    Route::post("send_sms", "API\EnterpriseApp\EnterpriseAppController@sendSMS");
    //get connected users
    Route::post("user/get_connected_users", "API\EnterpriseApp\EnterpriseAppController@getConnectedAndMMUsers");
    //request reference
    Route::post("user/request_reference", "API\EnterpriseApp\EnterpriseAppController@requestReference");
    //get user details by emailid
    Route::post("user/get_profile_by_emailid", "API\EnterpriseApp\EnterpriseAppController@getUserDetailsByEmail");
    //disconnect a user
    Route::post("user/disconnect_user", "API\EnterpriseApp\EnterpriseAppController@disConnectUsers");
    //get profile details
    Route::post("user/get_profile", "API\EnterpriseApp\EnterpriseAppController@getUserProfile");
    //edit user profile
    Route::post("user/edit_profile", "API\EnterpriseApp\EnterpriseAppController@editProfile"); 
    //posting job from campaigns
    Route::post("enterprise/get_jobs_list", "API\EnterpriseApp\EnterpriseAppController@getJobsList"); 
    //get levels info
    Route::post("user/get_levels_info", "API\EnterpriseApp\EnterpriseAppController@getLevelsInfo");  
    //get specific level info
    Route::post("user/get_specific_level_info", "API\EnterpriseApp\EnterpriseAppController@getSpecificLevelInfo");
    Route::post("referral/get_referrals_cash", "API\EnterpriseApp\EnterpriseAppController@getReferralsCash");
    //close notification
    Route::post("user/close_notification", "API\EnterpriseApp\EnterpriseAppController@closeNotification");
    //get notifications
    Route::post("user/get_notifications", "API\EnterpriseApp\EnterpriseAppController@getBellNotifications");
    Route::post("referral/refer_contact", "API\EnterpriseApp\EnterpriseAppController@referContact");
    //get professions for provider service provider
    Route::post("get_professions", "API\EnterpriseApp\EnterpriseAppController@getProfessions");
    //company details for enterprise
    Route::post("enterprise/view_company_details", "API\EnterpriseApp\EnterpriseAppController@viewCompanyDetails");
    //change user password
    Route::post("user/change_password", "API\EnterpriseApp\EnterpriseAppController@changePassword");
    //hexagonal display
    Route::post("referral/get_post_status_details", "API\EnterpriseApp\EnterpriseAppController@getPostStatusDetails");
    //you are values
    Route::post("get_you_are_values", "API\EnterpriseApp\EnterpriseAppController@getYouAreValues");
    //get campaigns
    Route::post("referral/get_campaign_details", "API\EnterpriseApp\EnterpriseAppController@getCampaignDetails");
    //get job details
    Route::post("enterprise/get_job_details", "API\EnterpriseApp\EnterpriseAppController@getJobDetails");    
    //services
    Route::post("get_services", "API\EnterpriseApp\EnterpriseAppController@getServices");
    //my referral contacts
    Route::post("referral/get_my_referral_contacts", "API\EnterpriseApp\EnterpriseAppController@getMyReferralContacts");
    //get all my referrals
    Route::post("referral/get_all_my_referrals", "API\EnterpriseApp\EnterpriseAppController@getAllMyReferrals");
    
});