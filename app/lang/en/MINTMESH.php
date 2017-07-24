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


    "user" => array(
        "create_success" => "Successfully Created",
        "create_failure" => "Failed To Create",
        "valid" => "Successfully Validated",
        "failure" => "Failed To Process",
        "success" => "Nicely Done",
        "edit_success" => "Your details have been saved",
        "edit_failure" => "Failed To Update",
        "edit_no_changes" => "No Changes Applied",
        "invalid_request" => "Invalid Request",
        "not_mintmesh" => "No MintMesh Users Found",
        "refer_not_mintmesh" => "The person which you want to request to is not a mintmesh user",
        "profile_success" => "Success",
        "invalid_input" => "Invalid Input",
        "logged_out" => "User Successfully Logged Out",
        "user_found" => "User is already existing",
        "user_not_found" => "User Not Found",
        "user_disconnect_success" => "Successfully Disconnected",
        "user_disconnect_error" => "Users Not Connected",
        "wrong_password" => "Invalid password",
        "correct_password" => "Password successfully validated",
        "non_mintmesh_user_name" => "the contact",
        "create_success_login_fail" => "User Created Successfully but Login Fail Please login manually",
        "uploaded_large_file" => "Uploaded file size is too large",
        "invalid_file_format" => "Uploaded file format not allowed",
        "no_resume" => "Please upload a resume",
        'invalid_acess' => 'Please enter valid License Key.',
        'server_access_denied' => 'Access denied to user!'
    ),
    "referrals" => array(
        "valid" => "Successfully Validated",
        "success" => "Successfully Done",
        "error" => "Failed To Process",
        "post_closed" => "Successfully Closed",
        "post_not_closed" => "Please close all pending referrals received for this service request",
        "no_posts" => "No Posts Found",
        "no_referrals_found" => "No Referrals Found",
        "already_referred" => "This contact has already been referred",
        "limit_crossed" => "You have reached maximum number of referrals for this request",
        "no_post" => "No Post Found",
        "closed_post" => "This request has been closed",
        "no_referrals" => "You have not referred any of your contacts for this post",
        "no_result" => "No Result Found",
        "invalid_input" => "Invalid Input",
        "no_import" => "You have not imported this contact, please try importing first",
        "invalid_post" => "Invalid post id"
    ),
    "login" => array(
        "login_valid" => "Successfully Validated",
        "login_success" => "Successfully Logged In",
        "login_failure" => "Invalid Credentials",
        "email_inactive" => "You are inactive please activate via email",
        "login_credentials" => "Please login with your enterprise credentials",
        "inactive_group" => "You are an Inactive Group User",
        "inactive_user" => "You are an Inactive User",
        "contact_admin" => "Something went wrong. Please contact your administrator."
    ),
    "resendActivationLink" => array(
        "success" => "Mail Sent Successfully",
        "already_activated" => "You are already activated",
        "failure" => "Failed to send mail"
    ),
    "fb_login" => array(
        "valid" => "Successfully validated",
    ),
    "user_email_subjects" => array(
        "welcome" => "Welcome To MintMesh!",
        "introduction" => "Thank you for downloading MintMesh!",
        "forgot_password" => "MintMesh - Reset Password",
        "join_invitaion" => "MintMesh Invitation",
        "paymentSuccess_braintree" => "MintMesh Invoice",
        "paymentSuccess_citrus" => "MintMesh Summary",
        "payout_success_user" => "MintMesh Payout confirmation",
        "payout_success_admin" => "MintMesh Payout Request",
        "reset_password_success" => "MintMesh - Your password changed",
        "post_success" => "Request Posted Successfully! Next Steps",
        "resume_attachment" => "MintMesh has sent you a resume of ",
        "set_password" => "MintMesh enterprise-User Account Activation"
    ),
    "email_template_paths" => array(
        "user_welcome" => "emails.Api.User.welcome",
        "enterprise_welcome" => "emails.Api.User.enterprise_welcome",
        "user_introduction" => "emails.Api.User.introduction",
        "forgot_password" => "emails.Api.User.forgot_password",
        "enterprise_forgot_password" => "emails.Api.User.enterprise_forgot_password",
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
        "enterprise_contacts_invitation" => "emails.Api.User.enterprise_contacts_invitation",
        "contacts_job_invitation" => "emails.Api.User.job_invitation",
        "contacts_job_active_invitation" => "emails.Api.User.job_Active",
        "contacts_campaign_invitation" => "emails.Api.User.campaign_invitation",
        "user_activation" => "emails.Api.User.user_activation"
    ),
    "activate_user" => array(
        "success" => "Successfully activated",
        "invalid" => "Activation period expired",
        "error" => "Invalid activation code",
        "already_activated" => "User is already activated",
    ),
    "forgot_password" => array(
        "success" => "Email Successfully Sent",
        "invalid" => "Reset Password Period Expired",
        "error" => "Invalid Email Id",
        "valid" => "Validated",
        "activate_user" => "Please activate your account"
    ),
    "reset_password" => array(
        "success" => "New Password Updated successfully. Please login to your Enterprise Mobile Application.",
        "invalid" => "Reset Password Period Expired",
        "failed" => "Some Error Occured",
        "error" => "Invalid Token",
        "valid" => "Validated",
        "same" => "New password cannot be same as old password",
        "codeexpired" => "Reset Password link has expired"
    ),
    "check_reset_password" => array(
        "success" => "Sucess",
        "failed" => "Reset password link has expired"
    ),
    "import_contacts" => array(
        "success" => "Contacts Successfully Imported",
        "invalid" => "Invalid Input",
        "error" => "Some Error Occurred"
    ),
    "country_codes" => array(
        "success" => "Successfully Listed",
        "error" => "Some Error Occured"
    ),
    "skills" => array(
        "success" => "Successfully Listed",
        "error" => "Some Error Occured",
        "no_data_found" => "No Data Found"
    ),
    "industries" => array(
        "success" => "Successfully Listed",
        "error" => "Some Error Occured"
    ),
    "job_functions" => array(
        "success" => "Successfully Listed",
        "error" => "Some Error Occured"
    ),
    "join_invitation" => array(
        "success" => "Email Successfully Sent",
        "invalid" => "Invalid Input"
    ),
    "get_contacts" => array(
        "success" => "Successfully Listed"
    ),
    "connections" => array(
        "request_success" => "Success"
    ),
    "notifications" => array(
        "success" => "Success",
        "no_notifications" => "No Notifications",
        "messages" => array(
            '1' => 'would like to connect with you',
            '2' => 'has accepted your connection request',
            '3' => 'requests you for an introduction to',
            '4' => 'wants to refer you to',
            '5' => 'has referred you to ',
            '6' => 'has accepted to be connected to',
            '7' => 'has accepted your connection',
            '8' => 'has parked this request in the "No" Zone',
            '9' => 'has parked this request in the "No" Zone',
            '10' => 'has referred', '11' => 'would like to refer you to',
            '12' => 'has accepted your referral for',
            '13' => 'has accepted a service of you referred by',
            '14' => 'has accepted a service of',
            '15' => 'has turned down your referral of',
            '16' => 'has turned down your service request',
            '17' => 'wants to introduce you to',
            '18' => 'accepted your reference for',
            '19' => 'accepted to connect with you, referred by',
            '20' => 'has shared details of',
            '22' => 'does not want to be referred to',
            '23' => 'has referred ownself to your request',
            '24' => 'accepted your self referral of',
            '25' => 'has turned down your self referral of',
            '27' => 'is looking for',
            '28' => 'has launched a',
            '29' => 'has Interviewed your referral',
            '30' => 'has offered your referral',
            '31' => 'has hired your referral'),
        "extra_texts" => array(
            '10' => 'for your request',
            '11' => 'for a service',
            '12' => ' the position of',
            '15' => '',
            '22' => 'for this service',
            '23' => 'for your request',
            '24' => '',
            '25' => '',
            '27' => ' Please check out the Refer page.',
            '28' => ' event for jobs. Check out the refer page.',
            '29' => ' for the position of',
            '30' => ' for the position of',
            '31' => ' for the position of')
    ),
    "get_requests" => array(
        "success" => "Successfully Listed"
    ),
    "get_connections" => array(
        "success" => "Successfully Listed"
    ),
    "get_levels" => array(
        "success" => "Successfully Listed",
        "valid" => "Successfully Validated",
        "error" => "No Info Found"
    ),
    "payment" => array(
        "valid" => "Valid",
        "success" => "Success",
        "failed" => "Transaction Failed",
        "error" => "Invalid Input",
        "invalid_amount" => "Invalid Amount"
    ),
    "sms" => array(
        "valid" => "valid",
        "invalid_input" => "Invalid Input",
        "success" => "Successfully Processed",
        "failed" => "Failed to Process",
        "invalid_length" => "Invalid Token. Unexpected Length",
        "otp_sent" => "Code sent to your Inbox",
        "otp_validated" => "Code Validated Successfully",
        "max_reached" => "Maximum number of Codes reached",
        "user_exist" => " A user is already existing with this phone number, kindly change your phone number."
    ),
    "reference_flow" => array(
        "success" => "Successfully Listed",
        "invalid_input" => "Invalid Input"
    ),
    "influencers" => array(
        "success" => "Successfully Listed",
        "not_found" => "No Influencer Found"
    ),
    "recruiters" => array(
        "success" => "Successfully Listed",
        "not_found" => "No Recruiter Found"
    ),
    "change_password" => array(
        "success" => "Password Changed Successfully",
        "failed" => "Some Error Occured",
        "confirmPasswordMismatch" => "Confirm Password Mismatch",
        "oldPasswordMismatch" => "Old Password Mismatch",
        "user_not_found" => "User not Found"
    ),
    "save_user_bank" => array(
        "success" => "Bank details added successfully",
        "failed" => "Some error occured",
        "user_not_found" => "User not Found",
        "details_already_exist" => "Bank details already exist"
    ),
    "edit_user_bank" => array(
        "success" => "Bank details edited successfully",
        "failed" => "Some error occured",
        "user_not_found" => "User not found",
        "details_already_exist" => "Bank details already exist"
    ),
    "delete_user_bank" => array(
        "success" => "Bank details removed successfully",
        "failed" => "Some error occured",
        "user_not_found" => "User not found"
    ),
    "payout" => array(
        "success" => "Payout done successfully",
        "failed" => "Payout failed",
        "error" => "Some error occured",
        "email_subject" => "You have a Payout!",
        "email_note" => "Thanks for your patronage!",
        "receipient_type" => "Email",
        "invalid_amount" => "Invalid Amount",
        "success_list" => "Successfully listed",
        "no_result" => "No payout done"
    ),
    "list_user_banks" => array(
        "success" => "Banks listed successfully",
        "failed" => "Some error occured",
        "nobanksadded" => "User has no banks added",
        "user_not_found" => "User not found"
    ),
    "manualpayout" => array(
        "success" => "Payout done successfully",
        "error" => "Some error occured",
        "invalid_amount" => "Your cash limit excised",
        "wrong_password" => "User password mismatch"
    ),
    "bad_words" => array(
        "success" => "Successfully cached"
    ),
    "services" => array(
        'valid' => 'Successfully Validated',
        'success' => 'Listed',
        'error' => 'Invalid Input',
        'not_found' => 'No Result Found'
    ),
    "you_are" => array(
        'success' => 'Listed'
    ),
    "professions" => array(
        'success' => 'Listed'
    ),
    "clear_memcache" => array(
        'success' => 'Memcache Cleared Successfully',
        'dont_exist' => 'Cache for perticular key does not exist'
    ),
    "experience" => array(
        'success' => 'Successfully Retrieved',
        'failure' => 'Failed to Retrieved'
    ),
    "employment_types" => array(
        'success' => 'Successfully Retrieved',
        'failure' => 'Failed to Retrieved'
    ),
    'user_profiles_abstractions' => array('basic' => array('firstname', 'lastname', 'fullname', 'company', 'position', 'dp_path', 'emailid', 'phone', 'location'),
        'medium' => array('job_function', 'position', 'phone', 'location', 'lastname', 'emailid', 'industry', 'fullname', 'firstname', 'dp_path', 'company',
            'phoneverified', 'you_are', 'job_function_name', 'industry_name', 'you_are_name', 'profession_name')),
    "post" => array(
        "valid" => "Successfully Validated",
        "success" => "Successfully Done",
        "error" => "Failed To Process",
        "no_posts" => "No Posts Found"
    ),
    "campaign" => array(
        "success" => "Successfully Done",
        "error" => "Failed To Process",
    ),
    "referral_details" => array(
        "valid" => "Successfully Validated",
        "success" => "Successfully Done",
        "error" => "Failed To Process",
        "no_referrals" => "No Referrals Found",
    ),
//                    'phoneverified','you_are','job_function_name','industry_name','you_are_name','profession_name')),
    "enterprise" => array(
        'import_contacts_success' => 'Contacts imported Successfully',
        'import_contacts_failure' => 'Records already exists',
        'upload_contacts_inserted' => 'Contacts successfully imported ',
        'upload_contacts_updated' => 'Contacts successfully updated',
        'upload_contacts_failure' => 'No rows affected',
        'invalid_headers' => 'Invalid Input File Headers or Worksheet,Please download the template and import',
        'invalid_file_format' => 'Invalid Input File Headers or file size more than 1 MB',
        'retrieve_success' => 'Successfully retrieved',
        'retrieve_failure' => 'Failed to retrieved',
        'success' => "Successfully Done",
        'error' => "Failed To Process"
    ),
    "enterprise_user_email_subjects" => array(
        "welcome" => "Welcome To MintMesh Enterprise"
    ),
    "companyDetails" => array(
        "success" => "Successfully retrieved",
        "no_details" => "No details found",
        "bucket_success" => "Bucket successfully created",
        "bucket_failure" => "Failed to create bucket",
        "bucket_exsisted" => "Bucket name already exsisted",
        "bucket_deleted" => "Community deleted successfully.",
        "bucket_delete_fail" => "Comminity Not Deleted",
        "company_not_exists" => "Invalid Company Code",
        "company_already_connected" => "Company already connected"
    ),
    'editContactList' => array(
        "success" => "Successfully updated",
        "failure" => "Failed to update",
        "file_format" => "Invalid input file format.File extension should be:",
        "invalid_headers" => "Invalid input file Headers",
        "invalidempid" => "Empid already exists",
        "contactsLimitExceeded" => "Your contacts limit is exceeded. Please contact support@mintmesh.com"
    ),
    'deleteContact' => array(
        "success" => "Successfully Deleted",
        "failure" => "Failed to Delete"
    ),
    'editStatus' => array(
        "success" => "Status changed Successfully",
        "failure" => "Failed to update status"
    ),
    'addContact' => array(
        "success" => "Contact added successfully",
        "failure" => "Failed to add contact",
        "contactExists" => "Contact already exists",
        "contactUpdated" => "Contact updated successfully"
    ),
    'getPermissions' => array(
        "success" => "Permissions successfully retrieved",
        "failure" => "No Data Found"
    ),
    'addPermissions' => array(
        "success" => "Permissions added successfully",
        "failure" => "Failed to add permissions",
        "invalidUserId" => "Please provide valid user id"
    ),
    'getGroupPermissions' => array(
        "success" => "Permissions retrieved successfully",
        "failure" => "Failed to retrieve permissions",
        "invalidUserId" => "Please provide valid user id"
    ),
    'addUser' => array(
        "success" => "User added successfully",
        "failure" => "Failed to add user",
        "userexists" => "User already exists",
        "edit" => "User details updated successfully",
        "emailidexists" => "Emailid already exists"
    ),
    'editUser' => array(
        "success" => "User updated successfully",
        "failure" => "Failed to update user",
        "emailexists" => "Emailid already exists"
    ),
    'getGroups' => array(
        "success" => "Successfully retrieved Groups",
        "failure" => "No Groups Found"
    ),
    'addGroup' => array(
        "success" => "Group added successfully",
        "failure" => "Failed to add group",
        "groupExists" => "Group name has already been taken"
    ),
    'editGroup' => array(
        "success" => "Group updated successfully",
        "failure" => "Failed to update group",
        "groupExists" => "Group name has already been taken",
        "permissionserror" => "Permissions cannot be edited"
    ),
    'getUserPermissions' => array(
        "success" => "User permissions retrieved successfully",
        "failure" => "Failed to retrieve user permissions",
    ),
    'updateUser' => array(
        "success" => "Updated user details successfully",
        "failure" => "Failed to update user details"
    ),
    'set_password' => array(
        "success" => "Password Created Successfully",
        "invalid" => "Password Period Expired",
        "failed" => "Some Error Occured",
        "error" => "Invalid Token",
        "codeexpired" => "Code has been expired"
    ),
    'campaigns' => array(
        "success" => "Successfully retrieved campaigns",
        "no_campaigns" => "No campaigns found",
        "failure" => "Failed to retrieve campaigns"
    ),
    'campaignDetails' => array(
        "success" => "Successfully retrieved campaign details",
        "no_campaigns" => "No campaigns found",
        "failure" => "Failed to retrieve campaign details"
    ),
    'updateNewPermission' => array(
        "success" => "Updated permission successfully",
        "failure" => "Failed to update permission",
        "not_admin" => "Not an admin user"
    ),
    'rewards' => array(
        "success" => "Successfully Done",
        "no_rewards" => "No Rewards found"
    ),
    'deactivatepost' => array(
        "success" => "Successfully Closed",
        "failure" => "No posts found",
        "no_permissions" => "Permission not allowed to close the job"
    ),
    'candidates' => array(
        "success" => "Successfully retrieved candidates List",
        "no_candidates" => "No candidate records found",
        "failure" => "Failed to retrieve candidates",
        "awaiting_resume" => "Awaiting Application from Candidate"
    ),
    'apply_job' => array(
        "success" => "Successfully Applied",
        "ref_success" => "Successfully Referred",
        "failure" => "Failed to refer",
        "post_closed" => "Job is closed",
        "referred" => "Candidate is already referred for the post.",
        "referrer_invalid" => "Invalid referrer",
        "invalid" => "Invalid reference id and post id",
        "user_separated" => "This job is no longer available."
    ),
    'apply_jobs_list' => array(
        "success" => "Successfully retrieved Jobs List",
        "no_jobs" => "No Jobs found",
        "failure" => "Failed to retrieve Jobs List",
        "user_separated" => "These jobs are no longer available."
    ),
    'apply_job_details' => array(
        "success" => "Successfully retrieved Job details",
        "no_jobs" => "No Jobs found",
        "failure" => "Failed to retrieve Job details"
    ),
    'company_integration' => array(
        "success" => "Successfully integrated",
        "failure" => "Failed to integrated"
    ),
    'hcm_details' => array(
        "insert_success" => "HCM details added successfully",
        "update_success" => "HCM Details Successfully Updated",
        "retrieve_success" => "successfully retrieved HCM details",
        "hcm_name_isexist" => "HCM name already exist",
        "retrieve_failure" => "Failed to retrieve HCM details"
    ),
    'add_configuration' => array(
        "success" => "Successfully added",
        "failure" => "Failed to add configuration details"
    ),
    'edit_configuration' => array(
        "success" => "Successfully updated",
        "failure" => "Failed to update configuration details"
    ),
    'configuration_details' => array(
        "success" => "Successfully retrieved configuration details",
        "failure" => "No details found"
    ),
    "upload_resume" => array(
        "success" => "Successfully uploaded",
        "failure" => "No details found",
        "file_not_found" => "File not found"
    ),
    "not_parsed_resumes" => array(
        "success" => "Successfully listed",
        "failure" => "No records found",
        "auth_key_failure" => "Invalid authentication key"
    )
);
