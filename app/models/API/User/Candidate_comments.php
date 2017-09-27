<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Candidate_Comments extends Eloquent {
        public $timestamps = false;
        //the mysql database table used by user model
	protected $table  = 'candidate_comments';

	protected $fillable = array('company_id', 'candidate_id','comment',  'created_by', 'created_at', 'updated_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
