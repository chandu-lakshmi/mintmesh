<?php namespace Mintmesh\Services\ResponseFormatter;

abstract class Formatter {

    
        abstract protected function formatResponse($responseCode="",$status="success", $messages=array(),$data=array());
        


}
