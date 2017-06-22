<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Emails_Logs extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'emails_logs';

	protected $fillable = array('emails_types_id','from_user','from_email','to_email','related_code','sent','ip_address');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
