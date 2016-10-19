<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Groups extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'groups';

	protected $fillable = array('name','company_id','is_primary','created_by','ip_address','status');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
