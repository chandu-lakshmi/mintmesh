<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class Exam extends Eloquent {

        //the mysql database table used by user model
	protected $table  = 'exam';

	protected $fillable = array(
                                'company_id', 
                                'idexam_type', 
                                'name', 
                                'description', 
                                'exam_url', 
                                'description_url', 
                                'work_experience', 
                                'start_date_time',
                                'end_date_time',
                                'max_duration', 
                                'is_active',
                                'is_auto_screening', 
                                'password_protected', 
                                'password', 
                                'max_marks', 
                                'min_marks', 
                                'enable_full_screen', 
                                'shuffle_questions', 
                                'reminder_emails', 
                                'confirmation_email', 
                                'disclaimer_text', 
                                'min_marks', 
                                'created_at',  
                                'created_by',
                                'updated_by'
                                );
        // Definig mysql connection
	protected $connection = 'mysql';
        
}
