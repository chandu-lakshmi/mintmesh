<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Candidate_Exam_Result extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'candidate_exam_result';

	protected $fillable = array('idexam_instance', 'result', 'created_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
