<?php
//file : app/config/constants.php


return [
    'MINTMESH'=>"MINTMESH"   ,
    'FB_PASSWORD'=>"mintmesh@123" ,
    'GRANT_TYPE'=>"password",
    'MNT_LOGIN_SOURCE'=>1,
    'FB_LOGIN_SOURCE'=>2,
    'MNT_USER_EXPIRY_HR'=>1440,//336,
    'MNT_USER_EXPIRY_HR_FOR_RESEND_ACTIVATION'=>120,
    'MNT_VERSION'=>"v1",
    'MNT_FROM_NAME'=>'Mintmesh',
    'MNT_DEEP_LINK_IOS'=>'mintmeshstg://',
    'MNT_DEEP_LINK_ANDROID'=>'http://mintmeshstg/',
    'DP_PATH' => '/uploads/ProfilePics',
    'CV_PATH' => '/uploads/Resumes',
    'ADD_BATTLE_CARDS_COUNT' => 0,
    'GOOGLE_CONTACTS_URL'=>'https://www.google.com/m8/feeds/contacts/default/full?alt=json&max-results=10000',
    'ANDROID'=>'android',
    'IOS'=>'ios',
    'INVITE_SINGLE'=>false,
    'INVITE_EMAIL'=>'mintmeshapp@gmail.com',
    'MNT_PUBLIC_URL'=>'https://staging.mintmesh.com/public/',//mintmesh public folder url
    'PUSH_APP_ID' =>'0YORwFGpy2BsSW4g6ify3FxeBraHHiMDuzFUTJX0',//'0YORwFGpy2BsSW4g6ify3FxeBraHHiMDuzFUTJX0',// 'MTPajI5Vj2EzNUvKnvvynrZHh320Nk2pu9iW3x60',//'0YORwFGpy2BsSW4g6ify3FxeBraHHiMDuzFUTJX0',
    'PUSH_REST_KEY' =>'dVGfvLnENYwyTXVKoUPJKvOW4J9Ww9UoRbQQfJg7',//'dVGfvLnENYwyTXVKoUPJKvOW4J9Ww9UoRbQQfJg7',// '32zUISSPq5aAdkdWdrGYTQpad4JsRhoQsD4Exro8',//'dVGfvLnENYwyTXVKoUPJKvOW4J9Ww9UoRbQQfJg7',
    'PUSH_MASTER_KEY' =>'noK7rjixKT90K6GICpVwzgoMIb8v8JOhq2U1Z6BA',//'noK7rjixKT90K6GICpVwzgoMIb8v8JOhq2U1Z6BA',// 'LJIrWD0drrfZC55wvKwpWpnSyeq9UhMl5Ybuern6',//'noK7rjixKT90K6GICpVwzgoMIb8v8JOhq2U1Z6BA'
    'TWILIO'=>[
        'SID'=>'AC968eede40c3bdcc35a2a5d5012521f41',
        'AUTH_TOKEN'=>'897a3b5034602f5a20a242d4bf0045e7',
        'FROM_NUMBER'=>'+18156760124',
        'AUTHY_API_KEY'=>'ywKZqdMKOXtyKp844aLyksoK3IiCSB1y',//ElOrhsTdJuNPgbbVNnhO9oYilExs9XLB
        'AUTHY_URL'=>'http://api.authy.com'
    ],
    'CITRUS'=>[
        'ACCESS_KEY'=>'QZVFK264G28QZ6NJ43KB',
        'SECRET_KEY'=>'cd9cbbaefab824e2f9672b998188b87468e5f9e6'
    ],
    'PAYPAL'=>[
        'CLIENT_ID'=>'AYEcKQwz-8kQcg9Lu3ZwLHS_rYwyYRbwg8Zt4vBY29pPrPu_MYK3o9dEyoQB0OnoCxiioEQTplb_96Vw',//'AQ6EVfzfI95uH_8JAyPCkpcBc4k_GDMU9W4KbI_sMsGIyLtkDDKX1CN24ojGsXrbtEhtvNCscxRlWgNZ',//'AYXtuYZ9GyPCT5IoBDoSRISgXtANxqoEmTNUiBZW27hDwVzAueeMtsXIqQcbJwRLoLlHzeVvYV7fOzDL',
        'CLIENT_SECRET'=>'EOI1RV1C5ZUoHaYs5UabDaqBYFjH_t00kgtdx3P_OThTF0Wthlpn9qvDrrz0SB5CijEW2wIYeYX4BV0P',//'EDuYEHHqzWEX7Uisehy8GTROOAsYZfjswrBRY-xikFWt_BHUwxa_qr-_N16AsGxXplgDS_eFNOpLAj9J'//'ELcx1IzIF_6cjCPAXQ15MEX5M9SsCQQfbNHvbk4pOIeZRhZxpfcAJ9g60Li8bagpDLRmA2REqfzXYQ6T'
        'STATUS'=>[
            'ERROR'=>'ERROR'
        ],
        'MODE'=>'sandbox',
        'VALIDATIONLEVEL'=>'strict'
    ],
    'MANUAL'=>[
        'STATUS'=>[
            'ERROR'=>'ERROR',
            'SUCESS'=>'SUCCESS'
        ]
    ],
    'REFERENCE_STATUS'=>[
        'PENDING'=>'PENDING',
        'INTRO_COMPLETE'=>'INTRO_COMPLETED',
        'SUCCESS'=>'SUCCESS',
        'DECLINED'=>'DECLINED',
        'PARTIAL_ACCEPTED'=>'PARTIAL_ACCEPTED'
    ],
    'RELATIONS_TYPES'=>[
        'REQUEST_REFERENCE'=>'REQUEST_REFERENCE',
        'INTRODUCE_CONNECTION'=>'INTRODUCE_CONNECTION',
        'REQUESTED_CONNECTION'=>'REQUESTED_CONNECTION',
        'ACCEPTED_CONNECTION'=>'ACCEPTED_CONNECTION',
        'MAPPED_TO'=>'MAPPED_TO',
        'IMPORTED'=>'IMPORTED',
        'INVITED'=>'INVITED',
        'DECLINED'=>'DECLINED',
        'MORE_INFO'=>'MORE_INFO',
        'KNOWS'=>'KNOWS',
        'POSSES_JOB_FUNCTION'=>'POSSES_JOB_FUNCTION',
        'HOLDS_INDUSTRY_EXPERIENCE'=>'HOLDS_INDUSTRY_EXPERIENCE',
        'HAS_REFERRED'=>'HAS_REFERRED',
        'DELETED_CONTACT'=>'DELETED_CONTACT',
        'PROVIDES'=>'PROVIDES',
        'LOOKING_FOR'=>'LOOKING_FOR'
    ],
    'MAPPED_RELATION_TYPES'=>[
        '3'=>'REQUEST_REFERENCE',
        '4'=>'INTRODUCE_CONNECTION',
        '1'=>'REQUESTED_CONNECTION'
    ],
    'USER_CATEGORIES'=>[
        'EXPERIENCE'=>'Experience',
        'EDUCATION'=>'Education',
        'CERTIFICATION'=>'Certification'
    ],
    'REFERRALS'=>[
        'POSTED'=>'POSTED',
        'EXCLUDED'=>'EXCLUDED',
        'INCLUDED'=>'INCLUDED',
        'READ'=>'READ',
        'GOT_REFERRED'=>'GOT_REFERRED',
        'MAX_REFERRALS'=>3,
        'STATUSES'=>[
                'ACCEPTED'=>'ACCEPTED',
                'PENDING'=>'PENDING',
                'DECLINED'=>'DECLINED',
                'ACTIVE'=>'ACTIVE',
                'CLOSED'=>'CLOSED',
                'COMPLETED'=>'COMPLETED'
        ],
        'ASSIGNED_INDUSTRY'=>'ASSIGNED_INDUSTRY',
        'ASSIGNED_JOB_FUNCTION'=>'ASSIGNED_JOB_FUNCTION',
        'ASSIGNED_EMPLOYMENT_TYPE'=>'ASSIGNED_EMPLOYMENT_TYPE',
        'ASSIGNED_EXPERIENCE_RANGE'=>'ASSIGNED_EXPERIENCE_RANGE'
    ],
    'POINTS'=>[
        'REFER_REQUEST' => 50,
        'COMPLETE_PROFILE' => 100,
        'SEEK_REFERRAL' => 50,
        'ACCEPT_REFERRAL' => 50,
        'ACCEPT_CONNECTION_REFERRAL' => 50,
        'SIGNUP' => 50
    ],
    'PAYMENTS'=>[
        'STATUSES'=>[
            'PENDING'=>'PENDING',
            'SUCCESS'=>'SUCCESS',
            'FAILED'=>'FAILED',
            'CANCELLED'=>'CANCELLED'
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
//        'EMAILID'=>'support@mintmesh.com'
        'EMAILID'=>'v.gopi314@gmail.com'
    ],
    'S3BUCKET' => 'mintmesh/dev/profilepic',
    'S3BUCKET_RESUME' => 'mintmesh/dev/resume/',
    'PROFILE_COMPLETION_VALUES'=>[
        'CERTIFICATION'=>10,
        'CONTACT'=>30,
        'SKILLS'=>20,
        'EDUCATION'=>20,
        'EXPERIENCE'=>20
    ],
    'PROFILE_COMPLETION_SECTIONS'=>[
        'CERTIFICATION'=>'certification',
        'CONTACT'=>'contact',
        'SKILLS'=>'skills',
        'EDUCATION'=>'education',
        'EXPERIENCE'=>'experience'
    ],
    'USER_ABSTRACTION_LEVELS'=>[
        'BASIC'=>'basic',
        'MEDIUM'=>'medium',
        'FULL'=>'full'
    ],
    'S3BUCKET_MM_REFER_RESUME' => 'mintmesh/dev/MintmeshReferredResumes',
    'S3BUCKET_NON_MM_REFER_RESUME' => 'mintmesh/dev/NonMintmeshReferredResumes'
        
];
