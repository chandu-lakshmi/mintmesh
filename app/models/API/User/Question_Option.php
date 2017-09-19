<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Question_Option extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'question_option';

	protected $fillable = array('idquestion', 'option', 'is_correct_answer', 'status', 'created_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
