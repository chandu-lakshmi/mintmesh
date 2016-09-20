<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NeoCompany extends NeoEloquent{

	protected $label  = 'Company';

	protected $fillable = array('mysql_id','name','companyCode','size','website','logo','images');
        
        // Definig neo4j connection
	protected $connection = 'neo4j';
        
        public function imported()
        {
            return $this->belongsToMany('NeoCompany', 'IMPORTED');
        }
        public function invited()
        {
            return $this->belongsToMany('NeoCompany', 'INVITED');
        }
        
        
}
