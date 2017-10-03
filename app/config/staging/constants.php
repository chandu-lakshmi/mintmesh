<?php
//file : app/config/staging/constants.php

return [
    'MINTMESH'      =>"MINTMESH"   ,
    'FB_PASSWORD'   =>"mintmesh@123" ,
    'GRANT_TYPE'    =>"password",
    'MNT_LOGIN_SOURCE'  =>1,
    'FB_LOGIN_SOURCE'   =>2,
    'MNT_USER_EXPIRY_HR'=>1440,//336,
    'MNT_USER_EXPIRY_HR_FOR_RESEND_ACTIVATION'=>120,
    'USER_EXPIRY_HR'=>48,
    'MNT_VERSION'   =>"v1",
    'MNT_FROM_NAME' =>'Mintmesh',
    'MM_ENTERPRISE_URL'     =>'https://enterprisestaging.mintmesh.com',
    'MNT_DEEP_LINK_IOS'     =>'mintmeshstg://',
    'MNT_ENT_DEEP_LINK_IOS' =>'mintmeshenterprise://',
    'MNT_DEEP_LINK_ANDROID' =>'http://mintmeshstg/',
    'DP_PATH' => '/uploads/ProfilePics',
    'CV_PATH' => '/uploads/Resumes',
    'ADD_BATTLE_CARDS_COUNT' => 3,
    'GOOGLE_CONTACTS_URL'=>'https://www.google.com/m8/feeds/contacts/default/full?alt=json&max-results=10000',
    'ANDROID'   =>'android',
    'IOS'=>'ios',
    'INVITE_SINGLE'     =>false,
    'INVITE_EMAIL'      =>'mintmeshapp@gmail.com',
    'MNT_PUBLIC_URL'    =>'https://staging.mintmesh.com/public/',//mintmesh public folder url
    'PUSH_APP_ID'       =>'Y18TqXXDLlu83I0qxO6UG63jmILsXAtK0TYq5QCa',//'MTPajI5Vj2EzNUvKnvvynrZHh320Nk2pu9iW3x60',
    'PUSH_REST_KEY'     =>'LE46B3l2jFlo8kILLFOmpinM3a6vbIppZ3idUzB3',//32zUISSPq5aAdkdWdrGYTQpad4JsRhoQsD4Exro8',
    'PUSH_MASTER_KEY'   =>'LT9PWSe0A7E0fiX5Y9GGucXTR6o425uS2v0Pgd96',//'LJIrWD0drrfZC55wvKwpWpnSyeq9UhMl5Ybuern6',
    'TWILIO'=>[
        'SID'           =>'AC968eede40c3bdcc35a2a5d5012521f41',
        'AUTH_TOKEN'    =>'897a3b5034602f5a20a242d4bf0045e7',
        'FROM_NUMBER'   =>'+18156760124',
        'AUTHY_API_KEY' =>'ywKZqdMKOXtyKp844aLyksoK3IiCSB1y',//ElOrhsTdJuNPgbbVNnhO9oYilExs9XLB
        'AUTHY_URL'     =>'http://api.authy.com'
    ],
    'CITRUS'=>[
        'ACCESS_KEY'    =>'QZVFK264G28QZ6NJ43KB',
        'SECRET_KEY'    =>'cd9cbbaefab824e2f9672b998188b87468e5f9e6'
    ],
    'PAYPAL'=>[
        'CLIENT_ID'     =>'AYEcKQwz-8kQcg9Lu3ZwLHS_rYwyYRbwg8Zt4vBY29pPrPu_MYK3o9dEyoQB0OnoCxiioEQTplb_96Vw',//'AQ6EVfzfI95uH_8JAyPCkpcBc4k_GDMU9W4KbI_sMsGIyLtkDDKX1CN24ojGsXrbtEhtvNCscxRlWgNZ',
        'CLIENT_SECRET' =>'EOI1RV1C5ZUoHaYs5UabDaqBYFjH_t00kgtdx3P_OThTF0Wthlpn9qvDrrz0SB5CijEW2wIYeYX4BV0P',//'EDuYEHHqzWEX7Uisehy8GTROOAsYZfjswrBRY-xikFWt_BHUwxa_qr-_N16AsGxXplgDS_eFNOpLAj9J',
        'STATUS'=>[
            'ERROR'=>'ERROR'
        ],
        'MODE'=>'sandbox',
        'VALIDATIONLEVEL'=>'strict'
    ],
    'MANUAL'=>[
        'STATUS'=>[
            'ERROR' =>'ERROR',
            'SUCESS'=>'SUCCESS'
        ]
    ],
    'REFERENCE_STATUS'=>[
        'PENDING'   =>'PENDING',
        'INTRO_COMPLETE'=>'INTRO_COMPLETED',
        'SUCCESS'   =>'SUCCESS',
        'DECLINED'  =>'DECLINED',
        'PARTIAL_ACCEPTED'=>'PARTIAL_ACCEPTED'
    ],
    'RELATIONS_TYPES'=>[
        'REQUEST_REFERENCE'     =>'REQUEST_REFERENCE',
        'INTRODUCE_CONNECTION'  =>'INTRODUCE_CONNECTION',
        'REQUESTED_CONNECTION'  =>'REQUESTED_CONNECTION',
        'ACCEPTED_CONNECTION'   =>'ACCEPTED_CONNECTION',
        'MAPPED_TO' =>'MAPPED_TO',
        'IMPORTED'  =>'IMPORTED',
        'INVITED'   =>'INVITED',
        'DECLINED'  =>'DECLINED',
        'MORE_INFO' =>'MORE_INFO',
        'KNOWS' =>'KNOWS',
        'POSSES_JOB_FUNCTION'   =>'POSSES_JOB_FUNCTION',
        'HOLDS_INDUSTRY_EXPERIENCE' =>'HOLDS_INDUSTRY_EXPERIENCE',
        'HAS_REFERRED'  =>'HAS_REFERRED',
        'DELETED_CONTACT'   =>'DELETED_CONTACT',
        'PROVIDES'  =>'PROVIDES',
        'LOOKING_FOR'   =>'LOOKING_FOR',
        'WORKS_AS'  =>'WORKS_AS',
        'CREATED'   =>'CREATED',
        'BUCKET_IMPORTED'   => 'BUCKET_IMPORTED',
        'COMPANY_CONTACT_IMPORTED'  => 'COMPANY_CONTACT_IMPORTED',
        'CONNECTED_TO_COMPANY'      => 'CONNECTED_TO_COMPANY',
        'POST_REWARDS'      => 'POST_REWARDS',
        'COMPANY_CREATED_CAMPAIGN' => 'COMPANY_CREATED_CAMPAIGN',
        'CAMPAIGN_SCHEDULE' => 'CAMPAIGN_SCHEDULE',
        'CAMPAIGN_POST'     => 'CAMPAIGN_POST',
        'CAMPAIGN_CONTACT'  => 'CAMPAIGN_CONTACT'

    ],
    'MAPPED_RELATION_TYPES'=>[
        '3'=>'REQUEST_REFERENCE',
        '4'=>'INTRODUCE_CONNECTION',
        '1'=>'REQUESTED_CONNECTION'
    ],
    'USER_CATEGORIES'   =>[
        'EXPERIENCE'    =>'Experience',
        'EDUCATION'     =>'Education',
        'CERTIFICATION' =>'Certification'
    ],
    'REFERRALS' =>[
        'POSTED'    =>'POSTED',
        'EXCLUDED'  =>'EXCLUDED',
        'INCLUDED'  =>'INCLUDED',
        'READ'  =>'READ',
        'GOT_REFERRED'  =>'GOT_REFERRED',
        'MAX_REFERRALS' =>3,
        'STATUSES'=>[
                'ACCEPTED'  =>'ACCEPTED',
                'PENDING'   =>'PENDING',
                'DECLINED'  =>'DECLINED',
                'ACTIVE'    =>'ACTIVE',
                'CLOSED'    =>'CLOSED',
                'COMPLETED' =>'COMPLETED',
                'INTERVIEWED'   => 'INTERVIEWED',
                'OFFERED'   => 'OFFERED',
                'HIRED'     => 'HIRED'
        ],
        'ASSIGNED_INDUSTRY'         =>'ASSIGNED_INDUSTRY',
        'ASSIGNED_JOB_FUNCTION'     =>'ASSIGNED_JOB_FUNCTION',
        'ASSIGNED_EMPLOYMENT_TYPE'  =>'ASSIGNED_EMPLOYMENT_TYPE',
        'ASSIGNED_EXPERIENCE_RANGE' =>'ASSIGNED_EXPERIENCE_RANGE'
    ],
    'POINTS'=>[
        'REFER_REQUEST'     => 50,
        'COMPLETE_PROFILE'  => 100,
        'SEEK_REFERRAL'     => 50,
        'ACCEPT_REFERRAL'   => 50,
        'ACCEPT_CONNECTION_REFERRAL' => 50,
        'SIGNUP' => 50
    ],
    'PAYMENTS'  =>[
        'STATUSES'  =>[
            'PENDING'   =>'PENDING',
            'SUCCESS'   =>'SUCCESS',
            'FAILED'    =>'FAILED',
            'CANCELLED' =>'CANCELLED'
        ],
        'CURRENCY'=>[
            'USD'=>'USD',
            'INR'=>'INR'
        ],
        'CONVERSION_RATES'=>[
            'INR_TO_USD'=>70,
            'USD_TO_INR'=>60
        ]
    ],
    'MINTMESH_SUPPORT'=>[
        //'EMAILID'=>'support@mintmesh.com'
        'EMAILID'   =>'no-reply@mintmesh.com',
        'REFERRER_NAME' => 'mintmesh.aws',
        'REFERRER_HOST' => '@gmail.com'
    ],
    'S3BUCKET' => 'mintmesh/stg/profilepic',
    'S3BUCKET_RESUME'   => 'mintmesh/stg/resume/',
    'RESUME_MAX_SIZE'   => 1000000,//750kb
    'EXCEL_MAX_SIZE'    => 1000000,//1MB
    'PROFILE_COMPLETION_VALUES'=>[
        'CERTIFICATION' =>10,
        'CONTACT'   =>30,
        'SKILLS'    =>20,
        'EDUCATION' =>20,
        'EXPERIENCE'    =>20
    ],
    'PROFILE_COMPLETION_SECTIONS'=>[
        'CERTIFICATION' =>'certification',
        'CONTACT'   =>'contact',
        'SKILLS'    =>'skills',
        'EDUCATION' =>'education',
        'EXPERIENCE'=>'experience'
    ],
    'USER_ABSTRACTION_LEVELS'=>[
        'BASIC'     =>'basic',
        'MEDIUM'    =>'medium',
        'FULL'      =>'full'
    ],
    'S3BUCKET_MM_REFER_RESUME'      => 'mintmesh/stg/MintmeshReferredResumes',
    'S3BUCKET_NON_MM_REFER_RESUME'  => 'mintmesh/stg/NonMintmeshReferredResumes',
    'SEND_RESUME_ATTACHMENT'        =>false,
    'S3BUCKET_COMPANY_LOGO'         => 'mintmesh/stg/companyLogo',
    'S3BUCKET_COMPANY_IMAGES'       => 'mintmesh/stg/companyImages',
    'S3BUCKET_USER_IMAGE'           => 'mintmesh/stg/userImage',
    'S3BUCKET_FILE'         =>  'mintmesh/stg/files',
    'UPLOAD_RESUME'         => '/uploads/awss3_resumes/',
    'S3UPLOAD_RESUME'       => 'mintmeshresumestg/',
    'AIusername'    => 'admin',
    'AIpassword'    => 'Aev54I0Av13bhCxM',
    'AI_AUTHENTICATION_KEY'     => '107857d5d4be08e5e2dc51ef141e0924',
    'S3_DOWNLOAD_PATH'          => 'https://s3-us-west-2.amazonaws.com/mintmeshresumestg/',
    'BITLY_URL'                 => 'https://api-ssl.bitly.com/v3/user/link_save?access_token=',
    'BITLY_ACCESS_TOKEN'        => 'b65a3bf8c767c8931eeaa067da88c2e2356f192e',
    'AI_GET_PARSED_RESUME'      => 'http://52.40.164.101/resumematcher/get_parsed_resume',//'http://54.68.58.181/resumematcher/get_parsed_resume',
    'ZENEFITS_OAUTH2_TOKEN'     => 'https://secure.zenefits.com/oauth2/token/',
    'ZENEFITS_GETAPP_URL'           =>'http://202.63.105.85/mmenterprise/getApp/zenefits',
    'ZENEFITS_GETACCESCODE_URL'     =>'http://202.63.105.85/mintmesh/getAccesCode',
    'ZENEFITS_COMPANY_INSTALLS'     =>'https://api.zenefits.com/platform/company_installs',
    'ZENEFITS_PERSON_SUBSCRIPTIONS' =>'https://api.zenefits.com/platform/person_subscriptions'
];