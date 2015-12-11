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
Route::get('docs', function() {
	return View::make('docs.v1.index');
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

//for user login & registration with out oAuth
Route::group(array('prefix' => 'v1'), function() {

    
    // FB login
    //Route::get("user/fb_login", "API\User\UserController@loginWithFacebook");

    //get country codes list
    Route::get("country_codes", "API\User\UserController@countryCodes");
    
    //get industries list
    Route::get("get_industries", "API\User\UserController@getIndustries");
    
    //get job functions list
    Route::get("get_job_functions", "API\User\UserController@getJobFunctions");
    
    //get skills
    Route::post("get_skills", "API\User\UserController@getSkills");
        
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
    Route::post("user/reset_password/", "API\User\UserController@resetPassword");
    Route::post("testIndex", "API\Referrals\ReferralsController@testIndex");
    
    //citrus payment\
    Route::any("payment/generate_bill", "API\Payment\PaymentController@generateCitrusBill");
    Route::post("payment/citrus_transaction", "API\Payment\PaymentController@citrusTransaction");
});

//Route::group(array('prefix' => 'v1'), function() {
Route::group(array('prefix' => 'v1', 'before' => 'oauth'), function() {

        //complete user profile
        Route::post("user/complete_profile", "API\User\UserController@completeUserProfile");
        //edit user profile
        Route::post("user/edit_profile", "API\User\UserController@editProfile");
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
       //send otp
       Route::post("user/send_otp", "API\SMS\SmsController@sendOTP");
       //verify otp
       Route::post("user/verify_otp", "API\SMS\SmsController@verifyOTP");
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
       
       ;
       
       //payment transactions
       Route::post("payment/bt_transaction", "API\Payment\PaymentController@braintreeTransaction");
       
       //sms
       Route::post("send_sms", "API\SMS\SmsController@sendSMS");
       

        
        
});


/* Custom route for 404 page not found */
App::missing(function($exception)
{
    return Response::json(array('status_code' => 404, 'status' => 'error', 'message' => array('msg'=>'Page not found'), 'data' =>array()));
});

/* Route for getting the access toker with end users oAuth2.0
|  Frst Time:
|  POST: username & password
|  returns accesstoken
|  Next time: (When access token expires, use the refresh token to get new access token)
|  POST : refresh token
|  return accesstoken
*/
Route::post("oauth/access_token", function() {
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
});

Route::post('setMailQueue', function()
{
	$input = [
    'name' => 'shweta',
    'email' => 'akiraa31@yahoo.com',
    'comment' =>  'Testing queues for mintmesh',
    'subject' =>  'Testing email queues for mintmesh'
	];

	Mail::queue('hello', $input, function($message) use ($input)  
	{
	$message->to($input['email'], $input['name']);
	$message->subject($input['subject']);
	});
});
Route::post('setSmsQueue', function()
{
    Queue::push('Mintmesh\Services\Queues\SMSQueue', "test");
});

