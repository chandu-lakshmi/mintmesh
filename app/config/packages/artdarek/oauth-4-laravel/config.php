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
            'client_id'     => '',
            'client_secret' => '',
            'scope'         => array(),
        ),
        'Linkedin' => array(
		    'client_id'     => 'Your Linkedin API ID',
		    'client_secret' => 'Your Linkedin API Secret',
		),  		

	)

);