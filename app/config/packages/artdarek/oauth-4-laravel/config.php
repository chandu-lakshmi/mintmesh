<?php 

return array( 
	
	/*
	|--------------------------------------------------------------------------
	| oAuth Config
	| Ref : https://github.com/artdarek/oauth-4-laravel
	|--------------------------------------------------------------------------
	*/

	/**
	 * Storage
	 */
	'storage' => 'Session', 

	/**
	 * Consumers
	 */
	'consumers' => array(

		/**
		 * Facebook
		 */
        'Facebook' => array(
            'client_id'     => '787708747966771',//'990509094300158',
            'client_secret' => '1f36833a7f994c0be8dbfb4130b8e9cd',//'0a01b54e31002ccddaaa3281c092729c',
            'scope'         => array('email','read_friendlists'),
        ),
        'Linkedin' => array(
		    'client_id'     => '75w22owh5ugrxz',
		    'client_secret' => 'sd22NSIag2tuxwce',
             'scope' => array('r_basicprofile','r_contactinfo','r_emailaddress','r_network','rw_nus')
		),  		
       'Google' => array(
        'client_id'     => '611937606188-clendfdk2mhdg04o5o2addbt138vm17b.apps.googleusercontent.com',
        'client_secret' => 'CweKvG-ImfmMHpPs3ukkKapn',
        'scope'         => array('email','profile', 'https://www.google.com/m8/feeds/')
         ),
            'Twitter' => array(
    'client_id'     => 'g7DlcCn4Of1mGJpX6F9qWQKA7',
    'client_secret' => 'Y9oroo1uGVkLs3F3zFXFoWaa6M8fMCgXv67HpBCkwgGrFYd8gj',
    // No scope - oauth1 doesn't need scope
),  
	),
        

);