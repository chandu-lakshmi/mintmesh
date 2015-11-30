<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Pagination Language Lines
	|--------------------------------------------------------------------------
	|
	| The following language lines are used by the paginator library to build
	| the simple pagination links. You are free to change them to anything
	| you want to customize your views to better match your application.
	|
	*/

	
        "user"=> array(
                "create_success" => "Successfully Created",
                "create_failure" => "Failed To Create",
                "valid"   => "Successfully Validated",
                "failure" => "Failed To Process",
                "success" => "Nicely Done",
                "edit_success" => "Successfully Updated",
                "edit_failure" =>"Failed To Update",
                "edit_no_changes" => "No Changes Applied",
                "invalid_request" => "Invalid Request",
                "not_mintmesh" => "No Mintmesh Users Found",
                "refer_not_mintmesh" => "The person which you want to request to is not a mintmesh user",
                "profile_success"=>"Success",
                "invalid_input"=>"Invalid Input",
                "logged_out"=>"User Successfully Logged Out",
                "user_found"=>"User is already existing",
                "user_not_found"=>"User Not Found",
                "user_disconnect_success" => "Successfully Disconnected",
                "user_disconnect_error" => "Users Not Connected"
            
                
            ),
         "referrals"=>array(
             "valid"   => "Successfully Validated",
             "success" => "Successfully Done",
             "error" => "Failed To Process",
             "post_closed" => "Successfully Closed",
             "post_not_closed" => "Please close all pending referrals received for this service request",
             "no_posts" => "No Posts Found",
             "already_referred"=>"This contact has already been referred",
             "limit_crossed" => "You have reached maximum number of referrals for this request",
             "no_post" => "No Post Found",
             "closed_post"=>"This request has been closed",
             "no_referrals"=>"You have not referred any of your contacts for this post",
             "no_result"=>"No Result Found",
             "invalid_input"=>"Invalid Input"
         ),
         "login"=>array(
                "login_valid"   => "Successfully Validated",
                "login_success" => "Successfully Logged In",
                "login_failure" => "Invalid Credentials"
         ),
         "fb_login"=>array(
             "valid" => "Successfully validated",
         ),
         "user_email_subjects"=>array(
                "welcome"      => "Welcome To Mintmesh!"  ,
                "forgot_password" => "Reset Password - Mintmesh",
                "join_invitaion" => "Mintmesh Invitation"
         ),
         "email_template_paths"=>array(
                "user_welcome" => "emails.Api.User.welcome",
                "forgot_password" => "emails.Api.User.forgot_password",
                "join_invitation" => "emails.Api.User.join_invitation"
         ),
         "activate_user"=>array(
             "success"=>"Successfully activated",
             "invalid"=>"Activation period expired",
             "error"=>"Invalid activation code",
             "already_activated"=>"User is already activated",
         ),
        "forgot_password"=>array(
             "success"=>"Email Successfully Sent",
             "invalid"=>"Reset Password Period Expired",
             "error"=>"Invalid Email Id",
                "valid"=>"Validated"
         ),
         "reset_password"=>array(
             "success"=>"New Password Set Successfully",
             "invalid"=>"Reset Password Period Expired",
             "failed"=>"Some Error Occured",
             "error"=>"Invalid Token",
             "valid"=>"Validated"
         ),
         "import_contacts"=>array(
             "success"=>"Contacts Successfully Imported",
             "invalid"=>"Invalid Input",
             "error"=>"Some Error Occurred"
         ),
         "country_codes"=>array(
             "success"=>"Successfully Listed",
             "error"=>"Some Error Occured"
         ),
        "skills"=>array(
             "success"=>"Successfully Listed",
             "error"=>"Some Error Occured"
         ),
         "industries"=>array(
             "success"=>"Successfully Listed",
             "error"=>"Some Error Occured"
         ),
        "job_functions"=>array(
             "success"=>"Successfully Listed",
             "error"=>"Some Error Occured"
         ),
         "join_invitation"=>array(
             "success"=>"Email Successfully Sent",
             "invalid"=>"Invalid Input"
         ),
         "get_contacts"=>array(
             "success"=>"Successfully Listed"
         ),
         "connections"=>array(
             "request_success"=>"Success"
         ),
         "notifications"=>array(
             "success" => "Success",
             "no_notifications" => "No Notifications",
             "messages"=>array('1'=>'would like to connect with you', '2'=>'has accepted your connection request', '3' => 'requests you for an introduction to', '4' => 'wants to refer you to',
                                '5'=>'has referred you to ', '6'=>'has accepted to be connected to', '7'=>'has accepted your connection','8'=>'has parked this request in the "No" Zone', '9'=>'has parked this request in the "No" Zone'
                                ,'10'=>'has referred','11'=>'would like to refer you to ','12'=>'accepted your referral of','13'=>'has accepted a service of you referred by','14'=>'has accepted a service of','15'=>'has turned down your referral of',
                 '16'=>'has turned down your service request','17'=>'wants to introduce you to','18'=>'accepted your reference for','19'=>'accepted to connect with you, referred by','20'=>'has shared details of','22'=>'does not want to be referred to'),
             "extra_texts"=>array('10'=>'for your request','11'=>'for a service','12'=>'for this request','22'=>'for this service')
         ),
         "get_requests"=>array(
             "success"=>"Successfully Listed"
         ),
        "get_connections"=>array(
             "success"=>"Successfully Listed"
         ),
        "get_levels"=>array(
             "success"=>"Successfully Listed",
            "valid" => "Successfully Validated",
            "error"=>"No Info Found"
         ),
         "payment"=>array(
             "valid"=>"valid",
             "success"=>"Success",
             "failed"=>"Transaction Failed",
             "error"=>"Invalid Input"
         ),
         "sms"=>array(
             "valid"=>"valid",
             "invalid_input"=>"Invalid Input",
             "success"=>"Successfully Processed",
             "failed"=>"Failed to process",
             "invalid_length"=>"Invalid Token. Unexpected length",
             "otp_sent"=>"OTP sent to your inbox",
             "otp_validated"=>"OTP validated successfully",
             "max_reached"=>"Maximum number of OTPs reached",
             "user_exist"=>" A user is already existing with this phone number,kindly change your phone number."
         ),
    "reference_flow"=>array(
        "success"=>"Successfully listed",
        "invalid_input"=>"Invalid input"
    ),
    "influencers"=>array(
        "success"=>"Successfully listed",
        "not_found"=>"No Influencer Found"
    )
    
    

);
