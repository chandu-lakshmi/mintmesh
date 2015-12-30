<?php 
namespace Mintmesh\Services\ResponseFormatter\API;
use Mintmesh\Services\ResponseFormatter\Formatter;
use Mintmesh\Services\APPEncode\APPEncode ;
use Cache;
class CommonFormatter extends Formatter {
    
    protected $appEncodeDecode;
    
    public function __construct(APPEncode $appEncodeDecode)
    {
        $this->appEncodeDecode = $appEncodeDecode ;
    }
            
    public function formatResponse($responseCode="",$status="success", $messages=array(),$data=array())
    {
        if (!empty($responseCode))
        {
            //decode each string
            foreach ($data as $key=>$val)
            {
                if (is_string($val))
                {
                    $data[$key] = $this->appEncodeDecode->filterStringDecode($val);
                }
                else if (is_array($val))
                {
                    foreach ($val as $key1=>$val1)
                    {
                        if (is_string($val1))
                        {
                            $val[$key1] = $this->appEncodeDecode->filterStringDecode($val1);
                            $val[$key1] = $this->appEncodeDecode->cleanBadWords($val1);
                        }
                        else if (is_array($val1))
                        {
                            foreach ($val1 as $key2=>$val2)
                            {
                                if (is_string($val2))
                                {
                                   // echo $val2; exit;
                                    $val1[$key2] = $this->appEncodeDecode->filterStringDecode($val2);
                                    $val1[$key2] = $this->appEncodeDecode->cleanBadWords($val2);
                                }
                                else if (is_array($val2))
                                {
                                    foreach ($val2 as $key3=>$val3)
                                    {
                                        if (is_string($val3))
                                        {
                                            $val2[$key3] = $this->appEncodeDecode->filterStringDecode($val3);
                                            $val2[$key3] = $this->appEncodeDecode->cleanBadWords($val3);
                                        }
                                    }
                                    $val1[$key2] = $val2 ;
                                }
                            }
                            $val[$key1] = $val1 ;
                        }
                    }
                    $data[$key] = $val ;
                }
            }
            return array('status_code' => $responseCode, 'status' => $status, 'message' => $messages, 'data' => $data);
        }
        else
        {
            return array('status_code' => 404, 'status' => 'error', 'message' => array('msg'=>'Something Wrong Happened!'), 'data' =>array()); 
        }

    }
   
}
?>
