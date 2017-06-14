<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Levels_Logs extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'levels_logs';

	protected $fillable = array('levels_id','points_types_id','points','user_email','from_email','other_email','ip_address');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
