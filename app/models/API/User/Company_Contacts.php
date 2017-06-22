<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Company_Contacts extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'contacts';

	protected $fillable = array('user_id', 'company_id', 'import_file_id', 'firstname', 'lastname', 'emailid', 'phone', 'employeeid', 'status', 'updated_by', 'created_at', 'created_by', 'ip_address');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
