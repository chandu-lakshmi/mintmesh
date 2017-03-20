<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Company_Profile extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'company';

	protected $fillable = array('name','code','employees_no','website','logo','status','created_by','ip_address');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
