<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Notifications_Logs extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'notifications_logs';

	protected $fillable = array('notifications_types_id','from_user','from_email','to_email','other_email','message','other_message','extra_info','status','other_status','ip_address');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
