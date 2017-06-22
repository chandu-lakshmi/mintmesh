<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NeoUser extends NeoEloquent{

	protected $label  = 'User';

	protected $fillable = array('firstname','lastname','fullname','emailid','phone','login_source','job_function','phone_country_name');
        
        // Definig neo4j connection
	protected $connection = 'neo4j';
        
        public function imported()
        {
            return $this->belongsToMany('NeoUser', 'IMPORTED');
        }
        public function invited()
        {
            return $this->belongsToMany('NeoUser', 'INVITED');
        }
        
        
}
