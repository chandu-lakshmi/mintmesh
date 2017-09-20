<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Exam_Question extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'exam_question';

	protected $fillable = array('idexam', 'idquestion', 'question_value', 'status', 'created_at', 'created_by');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
