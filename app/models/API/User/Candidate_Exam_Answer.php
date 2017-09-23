<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Candidate_Exam_Answer extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'candidate_exam_answer';

	protected $fillable = array('idexam_instance', 'idexam_question', 'idquestion', 'idquestion_option', 'answer_text', 'score', 'created_at');
        
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
