<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Database Connections
	|--------------------------------------------------------------------------
	|
	| Here are each of the database connections setup for your application.
	| Of course, examples of configuring each database platform that is
	| supported by Laravel is shown below to make development simple.
	|
	|
	| All database work in Laravel is done through the PHP PDO facilities
	| so make sure you have the driver for your particular database of
	| choice installed on your machine before you begin development.
	|
	*/

	'connections' => array(

		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => '192.168.1.103',
			'database'  => 'mintmesh2',
			'username'  => 'mintmesh',
			'password'  => 'm!ntm35h',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
		'neo4j' => array (
            'driver' => 'neo4j',
            'host'   => '192.168.1.103',//127.0.0.1
            'port'   => '7474',
            'username' => 'neo4j',
            /* 
            | Password for using sample DB "admin" 
			| Password for using realtime DB "mintmesh"
            */
            'password' => 'm!ntm35h'//mintmesh
        ),

	),

);
