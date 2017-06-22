<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Company_Resumes extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'company_resumes';

	protected $fillable = array('company_id', 'file_original_name', 'status', 'file_from', 'got_referred_id', 'created_by', 'created_at', 'updated_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
