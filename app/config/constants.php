<?php
//file : app/config/constants.php


return [
    'MINTMESH'=>"MINTMESH"   ,
    'FB_PASSWORD'=>"mintmesh@123" ,
    'GRANT_TYPE'=>"password",
    'MNT_LOGIN_SOURCE'=>1,
    'FB_LOGIN_SOURCE'=>2,
    'MNT_USER_EXPIRY_HR'=>48,
    'MNT_VERSION'=>"v1",
    'MNT_FROM_NAME'=>'Mintmesh',
    'MNT_DEEP_LINK_IOS'=>'mintmeshstg://',
    'MNT_DEEP_LINK_ANDROID'=>'http://mintmeshstg/',
    'DP_PATH' => '/uploads/ProfilePics',
    'CV_PATH' => '/uploads/Resumes',
    'GOOGLE_CONTACTS_URL'=>'https://www.google.com/m8/feeds/contacts/default/full?alt=json&max-results=10000',
    'ANDROID'=>'android',
    'IOS'=>'ios',
    'INVITE_SINGLE'=>TRUE,
    'INVITE_EMAIL'=>'mintmeshapp@gmail.com',
    'PUSH_APP_ID' =>'0YORwFGpy2BsSW4g6ify3FxeBraHHiMDuzFUTJX0',// 'MTPajI5Vj2EzNUvKnvvynrZHh320Nk2pu9iW3x60',//'0YORwFGpy2BsSW4g6ify3FxeBraHHiMDuzFUTJX0',
    'PUSH_REST_KEY' =>'dVGfvLnENYwyTXVKoUPJKvOW4J9Ww9UoRbQQfJg7',// '32zUISSPq5aAdkdWdrGYTQpad4JsRhoQsD4Exro8',//'dVGfvLnENYwyTXVKoUPJKvOW4J9Ww9UoRbQQfJg7',
    'PUSH_MASTER_KEY' =>'noK7rjixKT90K6GICpVwzgoMIb8v8JOhq2U1Z6BA',// 'LJIrWD0drrfZC55wvKwpWpnSyeq9UhMl5Ybuern6',//'noK7rjixKT90K6GICpVwzgoMIb8v8JOhq2U1Z6BA'
    'TWILIO'=>[
        'SID'=>'AC61f4636c861c92b39112a5898195777d',
        'AUTH_TOKEN'=>'5bf17a942a0a2781e3eaf8f9abbb4730',
        'FROM_NUMBER'=>'(334) 610-3965',
        'AUTHY_API_KEY'=>'ywKZqdMKOXtyKp844aLyksoK3IiCSB1y',//ElOrhsTdJuNPgbbVNnhO9oYilExs9XLB
        'AUTHY_URL'=>'http://api.authy.com'
    ],
    'CITRUS'=>[
        'ACCESS_KEY'=>'QZVFK264G28QZ6NJ43KB',
        'SECRET_KEY'=>'cd9cbbaefab824e2f9672b998188b87468e5f9e6'
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
        'DELETED_CONTACT'=>'DELETED_CONTACT'
        
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
        ]
    ],
    'POINTS'=>[
        'REFER_REQUEST' => 50,
        'COMPLETE_PROFILE' => 50,
        'SEEK_REFERRAL' => 50,
        'ACCEPT_REFERRAL' => 50,
        'ACCEPT_CONNECTION_REFERRAL' => 50
    ],
    'PAYMENTS'=>[
        'STATUSES'=>[
            'PENDING'=>'PENDING',
            'SUCCESS'=>'SUCCESS',
            'FAILED'=>'FAILED',
            'CANCELLED'=>'CANCELLED'
        ]
    ]
        
];
