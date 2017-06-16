<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NeoEnterpriseUser extends NeoEloquent{

	protected $label  = 'User';

	protected $fillable = array('fullname','emailid','is_enterprise');
        
        // Definig neo4j connection
	protected $connection = 'neo4j';
        
        public function imported()
        {
            return $this->belongsToMany('NeoEnterpiseUser', 'IMPORTED');
        }
        public function invited()
        {
            return $this->belongsToMany('NeoEnterpriseUser', 'INVITED');
        }
        
        
}
