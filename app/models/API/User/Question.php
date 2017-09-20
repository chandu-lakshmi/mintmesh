<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Question extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'question';

	protected $fillable = array('company_id', 'idquestion_type', 'question', 'question_notes', 'question_value','is_answer_required', 'has_multiple_answers', 'status', 'created_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
