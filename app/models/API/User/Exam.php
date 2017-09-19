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
                                'idexam_type', 
                                'name', 
                                'description', 
                                'start_date_time',
                                'end_date_time',
                                'max_duration', 
                                'is_active',
                                'is_auto_screening', 
                                'max_marks', 
                                'min_marks', 
                                'created_at', 
                                'update_at', 
                                'created_by'
                                );
        // Definig mysql connection
	protected $connection = 'mysql';
        
}
