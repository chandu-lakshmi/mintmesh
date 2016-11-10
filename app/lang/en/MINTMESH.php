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
                "edit_success" => "Your details have been saved",
                "edit_failure" =>"Failed To Update",
                "edit_no_changes" => "No Changes Applied",
                "invalid_request" => "Invalid Request",
                "not_mintmesh" => "No MintMesh Users Found",
                "refer_not_mintmesh" => "The person which you want to request to is not a mintmesh user",
                "profile_success"=>"Success",
                "invalid_input"=>"Invalid Input",
                "logged_out"=>"User Successfully Logged Out",
                "user_found"=>"User is already existing",
                "user_not_found"=>"User Not Found",
                "user_disconnect_success" => "Successfully Disconnected",
                "user_disconnect_error" => "Users Not Connected",
                "wrong_password"=>"Invalid password",
                "correct_password"=>"Password successfully validated",
		"non_mintmesh_user_name"=>"the contact",
                "create_success_login_fail"=>"User Created Successfully but Login Fail Please login manually",
                "uploaded_large_file"=>"Uploaded file size is too large",
                "invalid_file_format"=>"Uploaded file format not allowed",
                "no_resume"=>"Please upload a resume"
                
            ),
         "referrals"=>array(
             "valid"   => "Successfully Validated",
             "success" => "Successfully Done",
             "error" => "Failed To Process",
             "post_closed" => "Successfully Closed",
             "post_not_closed" => "Please close all pending referrals received for this service request",
             "no_posts" => "No Posts Found",
             "no_referrals_found"=>"No referrals found",
             "already_referred"=>"This contact has already been referred",
             "limit_crossed" => "You have reached maximum number of referrals for this request",
             "no_post" => "No Post Found",
             "closed_post"=>"This request has been closed",
             "no_referrals"=>"You have not referred any of your contacts for this post",
             "no_result"=>"No Result Found",
             "invalid_input"=>"Invalid Input",
             "no_import"=>"You have not imported this contact, please try importing first"
         ),
         "login"=>array(
                "login_valid"   => "Successfully Validated",
                "login_success" => "Successfully Logged In",
                "login_failure" => "Invalid Credentials",
                "email_inactive" => "You are inactive please activate via email",
                 "login_credentials" => "Please login with your enterprise credentials",
                 "inactive_group" => "You are an inactive group user"
         ),
         "resendActivationLink" => array(
                "success" => "Mail Sent Successfully",
                "already_activated" => "You are already activated"
        ),
         "fb_login"=>array(
             "valid" => "Successfully validated",
         ),
         "user_email_subjects"=>array(
                "welcome"      => "Welcome To MintMesh!"  ,
                "introduction"  =>"Thank you for downloading MintMesh!",
                "forgot_password" => "MintMesh - Reset Password",
                "join_invitaion" => "MintMesh Invitation",
                "paymentSuccess_braintree" => "MintMesh Invoice",
                "paymentSuccess_citrus" => "MintMesh Summary",
                "payout_success_user" => "MintMesh Payout confirmation",
                "payout_success_admin" => "MintMesh Payout Request",
                "reset_password_success" => "MintMesh - Your password changed",
                "post_success" => "Request Posted Successfully! Next Steps",
                "resume_attachment" => "MintMesh has sent you a resume of ",
                "set_password" => "Employee Referral Management System-User Account Activation"
         ),
         "email_template_paths"=>array(
                "user_welcome" => "emails.Api.User.welcome",
                "enterprise_welcome" => "emails.Api.User.enterprise_welcome",
                "user_introduction" => "emails.Api.User.introduction",
                "forgot_password" => "emails.Api.User.forgot_password",
                "set_password" => "emails.Api.User.set_password",
                "join_invitation" => "emails.Api.User.join_invitation",
                "payout_success_admin" => "emails.Api.Payment.payout_success_admin",
                "payout_failure_admin" => "emails.Api.Payment.payout_failure_admin",
                "payout_success_user" => "emails.Api.Payment.payout_success_user",
                "payout_failure_user" => "emails.Api.Payment.payout_failure_user",
                "manual_payout_success_admin" => "emails.Api.Payment.manualPayout_success_admin",
                "manual_payout_failure_admin" => "emails.Api.Payment.manualPayout_failure_admin",
                "manual_payout_success_user" => "emails.Api.Payment.manualPayout_success_user",
                "manual_payout_failure_user" => "emails.Api.Payment.manualPayout_failure_user",
                "payment_success_user" => "emails.Api.Payment.payment_success_user",
                "payment_servicfee_success_user" => "emails.Api.Payment.payment_servicfee_success_user",
                "post_success" => "emails.Api.User.post_success",
                "reset_password_success" => "emails.Api.User.reset_password_success",
                "enterprise_reset_password_success" => "emails.Api.User.enterprise_reset_password_success",
                "resume_attachment" => "emails.Api.User.resume_attachment",
                "enterprise_contacts_invitation" => "emails.Api.User.enterprise_contacts_invitation"
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
                "valid"=>"Validated",
            "activate_user"=>"Please activate your account"
         ),
         "reset_password"=>array(
             "success"=>"New Password Set Successfully",
             "invalid"=>"Reset Password Period Expired",
             "failed"=>"Some Error Occured",
             "error"=>"Invalid Token",
             "valid"=>"Validated",
             "same"=>"New password cannot be same as old password",
             "codeexpired"=>"code has been expired"
         ),
         "check_reset_password"=>array(
             "success"=>"sucess",
             "failed"=>"Reset password link has expired"
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
             "error"=>"Some Error Occured",
             "no_data_found"=>"No Data Found"
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
                                '5'=>'has referred you to ', '6'=>'has accepted to be connected to', '7'=>'has accepted your connection','8'=>'has parked this request in the "No" Zone', '9'=>'has parked this request in the "No" Zone',
                                '10'=>'has referred','11'=>'would like to refer you to','12'=>'accepted your referral of','13'=>'has accepted a service of you referred by','14'=>'has accepted a service of','15'=>'has turned down your referral of',
                                '16'=>'has turned down your service request','17'=>'wants to introduce you to','18'=>'accepted your reference for','19'=>'accepted to connect with you, referred by','20'=>'has shared details of',
                                '22'=>'does not want to be referred to','23'=>'has referred ownself to your request','24'=>'accepted your self referral', '25'=>'has turned down your self referral','27'=>'is looking for'),
             "extra_texts"=>array('10'=>'for your request','11'=>'for a service','12'=>'for this request','22'=>'for this service','23'=>'for your request', '27'=>'. Checkout the Refer page.')
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
             "error"=>"Invalid Input",
             "invalid_amount"=>"Invalid amount"
         ),
         "sms"=>array(
             "valid"=>"valid",
             "invalid_input"=>"Invalid Input",
             "success"=>"Successfully Processed",
             "failed"=>"Failed to process",
             "invalid_length"=>"Invalid Token. Unexpected length",
             "otp_sent"=>"Code sent to your inbox",
             "otp_validated"=>"Code validated successfully",
             "max_reached"=>"Maximum number of codes reached",
             "user_exist"=>" A user is already existing with this phone number, kindly change your phone number."
         ),

        "reference_flow"=>array(
            "success"=>"Successfully listed",
            "invalid_input"=>"Invalid input"
        ),
        "influencers"=>array(
            "success"=>"Successfully listed",
            "not_found"=>"No Influencer Found"
        ),
        "recruiters"=>array(
            "success"=>"Successfully listed",
            "not_found"=>"No Recruiter Found"
        ),
        "change_password"=>array(
            "success"=>"Password changed successfully",
            "failed"=>"Some error occured",
            "confirmPasswordMismatch"=>"Confirm password mismatch",
            "oldPasswordMismatch"=>"Old password mismatch",
            "user_not_found"=>"User not found"
        ),
        "save_user_bank"=>array(
            "success"=>"Bank details added successfully",
            "failed"=>"Some error occured",
            "user_not_found"=>"User not Found",
            "details_already_exist"=>"Bank details already exist"
        ),
        "edit_user_bank"=>array(
            "success"=>"Bank details edited successfully",
            "failed"=>"Some error occured",
            "user_not_found"=>"User not found",
            "details_already_exist"=>"Bank details already exist"
        ),
        "delete_user_bank"=>array(
            "success"=>"Bank details removed successfully",
            "failed"=>"Some error occured",
            "user_not_found"=>"User not found"
        ),
        "payout"=>array(
            "success"=>"Payout done successfully",
            "failed"=>"Payout failed",
            "error"=>"Some error occured",
            "email_subject"=>"You have a Payout!",
            "email_note"=>"Thanks for your patronage!",
            "receipient_type"=>"Email",
            "invalid_amount"=>"Invalid Amount",
            "success_list"=>"Successfully listed",
            "no_result"=>"No payout done"
        ),
        "list_user_banks"=>array(
            "success"=>"Banks listed successfully",
            "failed"=>"Some error occured",
            "nobanksadded"=>"User has no banks added",
            "user_not_found"=>"User not found"
        ),
        "manualpayout"=>array(
            "success"=>"Payout done successfully",
            "error"=>"Some error occured",
            "invalid_amount"=>"Your cash limit excised",
            "wrong_password"=>"User password mismatch"
        ),
        "bad_words"=>array(
            "success"=>"Successfully cached"
        ),
        "services"=>array(
            'valid'=>'Successfully validated',
            'success'=>'Listed',
            'error'=>'Invalid input',
            'not_found'=>'No Result Found'
        ),
        "you_are"=>array(
            'success'=>'Listed'
        ),
        "professions"=>array(
            'success'=>'Listed'
        ),
        "clear_memcache"=>array(
            'success'=>'Memcache Cleared successfully',
            'dont_exist'=>'Cache for perticular key does not exist'
        ),
        "experience"=>array(
            'success'=>'Successfully retrieved',
            'failure'=>'Failed to retrieved'
        ),
        "employment_types"=>array(
            'success'=>'Successfully retrieved',
            'failure'=>'Failed to retrieved'
        ),
        'user_profiles_abstractions'=>array('basic'=>array('firstname','lastname','fullname','company','position','dp_path','emailid','phone','location'),
            'medium'=>array('job_function','position','phone','location','lastname','emailid','industry','fullname','firstname','dp_path','company',

                    'phoneverified','you_are','job_function_name','industry_name','you_are_name','profession_name')),
        "post"=>array(
            "valid"   => "Successfully Validated",
            "success" => "Successfully Done",
            "error" => "Failed To Process",
            "no_posts" => "No Posts Found",
       ) ,
        "campaign"=>array(
            "success" => "Successfully Done",
            "error" => "Failed To Process",
       ) ,
    
     "referral_details"=>array(
            "valid"   => "Successfully Validated",
            "success" => "Successfully Done",
            "error" => "Failed To Process",
            "no_referrals" => "No Referrals Found",
       ) ,
//                    'phoneverified','you_are','job_function_name','industry_name','you_are_name','profession_name')),
        "enterprise"=>array(
            'import_contacts_success' =>'Contacts imported Successfully',
            'import_contacts_failure' =>'Records already exists',
            'upload_contacts_inserted' =>'Contacts successfully imported ',
            'upload_contacts_updated' =>'Contacts successfully updated',
            'upload_contacts_failure' =>'No rows affected',
            'invalid_headers'         =>'Invalid headers or Worksheet,Please download the template and import',
            'invalid_file_format'     =>'Invalid file format or file size more than 1 MB',
            'retrieve_success'        =>'Successfully retrieved',
            'retrieve_failure'        =>'Failed to retrieved',
            'success'                 => "Successfully Done",
            'error'                   => "Failed To Process"
        ),
    "enterprise_user_email_subjects"=>array(
                "welcome"      => "Welcome To MintMesh Enterprise"
        ),
     "companyDetails"=>array(
               "success"                    => "successfully retrieved",
               "no_details"                 => "no details found",
               "bucket_success"             => "bucket successfully created",
               "bucket_failure"             => "failed to create bucket",
               "bucket_exsisted"            => "bucket name already exsisted",
               "company_not_exists"         => "Invalid Company code",
               "company_already_connected"  => "Company already connected"
     ),
    'editContactList' => array(
            "success"   =>  "successfully updated",
            "failure"   =>  "failed to update",
            "file_format"       =>  "Invalid input file format.File extension should be:",
            "invalid_headers"   =>  "Invalid input file Headers",
            "invalidempid"      =>  "empid already exists"          
    ),
    'deleteContact'   => array(
            "success"  =>   "successfully deleted",
            "failure"   =>   "Failed to delete"
    ),
    'editStatus'      => array(
            "success"   =>   "status changed successfully",
            "failure"   =>   "failed to update status"
    ),
    'addContact'      => array(
               "success"  =>  "contact added successfully",
               "failure"  =>   "failed to add contact",
               "contactExists"   => "contact already exists",
               "contactUpdated"  =>  "contact updated successfully"
    ),
    'getPermissions'   => array(
                "success"  => "permissions successfully retrieved",
                "failure"  => "no data found"
    ),
    'addPermissions'   => array(
                "success"  => "permissions added successfully",
                "failure"  =>  "failed to add permissions",
                "invalidUserId"  => "please provide valid user id"
    ),
    'getGroupPermissions' => array(
                "success"  => "permissions retrieved successfully",
                "failure"  =>  "failed to retrieve permissions",
                "invalidUserId"  => "please provide valid user id"
    ),
    'addUser'    =>   array(
                "success"  =>  "user added successfully",
                "failue"   =>   "failed to add user",
                "userexists" =>  "user already exists",
                "edit"     => "user details updated successfully",
                "emailidexists"  => "emailid already exists"
    ),
    'editUser'   =>   array(
                "success"  =>  "user updated successfully",
                "failure"   =>   "failed to update user",
                "emailexists" => "emailid already exists"
    ),
    'getGroups'   =>   array(
                "success"  =>  "successfully retrieved groups",
                "failure"  =>  "no groups found"
    ),
    'addGroup'   =>   array(
                "success"  =>  "group added successfully",
                "failure"  =>   "failed to add group",
                "groupExists"=>  "group name has already been taken"
    ),
    'editGroup'   =>   array(
                "success"  =>  "group updated successfully",
                "failure"   =>   "failed to update group",
                "groupExists"=>  "group name has already been taken",
                "permissionserror" => "permissions cannot be edited"
    ),
    'getUserPermissions' => array(
                "success"  =>  "user permissions retrieved successfully",
                "failure"   =>   "failed to retrieve user permissions",
    ),
    'updateUser' => array(
                "success"  =>   "updated user details successfully",
                "failure"  =>   "failed to update user details"
    ),
    'set_password'=>array(
             "success"=>"Password Created Successfully",
             "invalid"=>"Password Period Expired",
             "failed"=>"Some Error Occured",
             "error"=>"Invalid Token",
             "codeexpired"=>"code has been expired"
    ),
    'campaigns'=>array(
            "success"=>"successfully retrieved campaigns",
            "no_campaigns"=>"no campaigns found",
            "failure"=>"failed to retrieve campaigns"
    ),
    'campaignDetails'=>array(
            "success"=>"successfully retrieved campaign details",
            "no_campaigns"=>"no campaigns found",
            "failure"=>"failed to retrieve campaign details"
    ),
    'updateNewPermission'=>array(
            "success"=>"updated permission successfully",
            "failure"=>"failed to update permission",
          ),
    'rewards' => array(
            "success"=>"Successfully Done",
            "no_rewards"=>"No Rewards found"
          ),
    'deactivatepost' => array(
            "success" => "Successfully Closed",
            "failure" => "No posts found",
            "no_permissions" => "permission not allowed to close the job"
            
            )
    
);
