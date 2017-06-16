<?php namespace Mintmesh\Services\Queues;


//parse includes
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseACL;
use Parse\ParsePush;
use Parse\ParseUser;
use Parse\ParseInstallation;
use Parse\ParseException;
use Parse\ParseAnalytics;
use Parse\ParseFile;
use Parse\ParseCloud;
use Parse\ParseClient;
use Config ;
class ParseQueue {

    
    public function fire($job, $jobData)
    {
        $app_id = Config::get('constants.PUSH_APP_ID') ;//"MTPajI5Vj2EzNUvKnvvynrZHh320Nk2pu9iW3x60";
        $rest_key = Config::get('constants.PUSH_REST_KEY') ; //"32zUISSPq5aAdkdWdrGYTQpad4JsRhoQsD4Exro8" ;
        $master_key = Config::get('constants.PUSH_MASTER_KEY') ; //"LJIrWD0drrfZC55wvKwpWpnSyeq9UhMl5Ybuern6";
        $data = $jobData['parse_data'] ;
        $deviceToken = $jobData['deviceToken'] ;
        ParseClient::initialize( $app_id, $rest_key, $master_key );
        
        $query = ParseInstallation::query();
        //\Log::info('data', $data);
        $query->equalTo("deviceToken", $deviceToken);
        ParsePush::send(array(
            "where" => $query,
            "data" => $data
        ));
        $job->delete();
    }

}