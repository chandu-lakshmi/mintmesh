<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class SMS_Logs extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'sms_logs';

	protected $fillable = array('from_email','to_number','message','send_status','sms_type_id','twilio_response');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
