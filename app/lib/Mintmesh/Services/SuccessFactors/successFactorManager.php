<?php
namespace Mintmesh\Services\SuccessFactors;
use DB;
/**
 * Description of Admin
 *
 * @author enterpi
 */
class successFactorManager {

//put your code here
  
   public function getSFJobs($input) {

        $sfApiData = $this->getSuccessFactorApiById($input['company_id']); 
        $sfApiData = $sfApiData[0];
        $username  = $sfApiData->username;
        $password  = $sfApiData->password;        
        $url = $sfApiData->url.'('.$input['req_id'].')?$format=json';
        
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $output = curl_exec($ch);
        curl_close($ch);
        $array  = json_decode($output,TRUE);
        $return = array();
        if(isset($array['d'])){
            $return = $array['d'];
        }	
    return $return;
}

public function getSuccessFactorApiById($id=0){    
        return DB::table('success_factor_apis')
                ->select('username','password','url')
                ->where('id', '=', $id)->get();
        }
  
	
    
}
