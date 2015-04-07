<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Movie extends NeoEloquent
{
	protected $label  = 'Movie';

	protected $fillable = array('title','released','tagline');
        
        // Definig neo4j connection
	protected $connection = 'neo4j';
        
        /*public function find($id) {
            // TODO: Yet to implement
            return "in find by ID function";
        }*/
}