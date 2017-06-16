<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Eloquent implements UserInterface, RemindableInterface {

	use UserTrait, RemindableTrait;
        
        //the mysql database table used by user model
	protected $table  = 'users';

	protected $fillable = array('emailid','password','firstname','middlename','lastname','login_source','emailactivationcode','is_enterprise','primary_phone','status','activate_exp_date','group_id','group_status');
        // Definig mysql connection
	protected $connection = 'mysql';
        
        
}
