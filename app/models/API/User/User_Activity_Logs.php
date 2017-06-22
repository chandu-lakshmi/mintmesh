<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class User_Activity_Logs extends Eloquent {
        public $timestamps = false;
        //the mysql database table used by user model
	protected $table  = 'user_activity_logs';

	protected $fillable = array('user_id', 'application_type', 'module_type');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
