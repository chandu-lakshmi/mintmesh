<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Question_Bank extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'question_bank';

	protected $fillable = array('company_id', 'idquestion_library', 'idquestion', 'status', 'created_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
