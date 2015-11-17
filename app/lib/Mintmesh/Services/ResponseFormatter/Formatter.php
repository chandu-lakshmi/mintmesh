<?php namespace Mintmesh\Services\ResponseFormatter;

abstract class Formatter {

        public function formatResponse($responseCode="",$status="success", $messages=array(),$data=array())
        {
            if (!empty($responseCode))
            {
                return array('status_code' => $responseCode, 'status' => $status, 'message' => $messages, 'data' => $data);
            }
            else
            {
                return array('status_code' => 404, 'status' => 'error', 'message' => array('msg'=>'Something Wrong Happened!'), 'data' =>array()); 
            }
            
        }


}
