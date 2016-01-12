<html>    
<head>    
<script type="text/javascript">  	
var globaldata;      
function setdata(data) {          
globaldata = data;      
}      
function postResponseiOS() {          
return globaldata;      
}      
function postResponse(data) {          
CitrusResponse.pgResponse(data);      }    
</script>     
</head>     
<body>     
</body>     
</html>                  
<?php                    
  $secret_key = Config::get('constants.CITRUS.SECRET_KEY');     
  $txId = !empty($data['TxId'])?$data['TxId']:'';
  $TxStatus = !empty($data['TxStatus'])?$data['TxStatus']:'';
  $txamount = !empty($data['amount'])?$data['amount']:'';
  $pgTxnNo = !empty($data['pgTxnNo'])?$data['pgTxnNo']:'';
  $issuerRefNo = !empty($data['issuerRefNo'])?$data['issuerRefNo']:'';
  $authIdCode = !empty($data['authIdCode'])?$data['authIdCode']:'';
  $firstName = !empty($data['firstName'])?$data['firstName']:'';
  $lastName = !empty($data['lastName'])?$data['lastName']:'';
  $pgRespCode = !empty($data['pgRespCode'])?$data['pgRespCode']:'';
  $addressZip = !empty($data['addressZip'])?$data['addressZip']:'';
  $txSignature = !empty($data['signature'])?$data['signature']:'';

$verification_data =  $txId.$TxStatus.$txamount.$pgTxnNo.$issuerRefNo.$authIdCode.$firstName.$lastName.$pgRespCode.$addressZip;     
$signature = hash_hmac('sha1', $verification_data, $secret_key);    
$k = implode(",",array_keys($data));
$v = implode(",",$data);

  if ($signature == $data['signature'])  
    {	
      \Log::info("<<<<<<<< citrus in success >>>>>>>>>".$secret_key);
      $json_object = json_encode($data);										      	
      echo "<script> 
      postResponse('$json_object'); 
      </script>";										      	
      echo"<script> setdata ('$json_object');
      </script>";										      
    }										    
  else {										  	   
     $response_data = array("Error" => "Transaction Failed",
     "Reason" => "Signature Verification Failed");										  	    
 $json_object = json_encode($response_data);										  	    
 echo "
 <script> 
 postResponse('$json_object'); 
 </script>";	  	    
 echo"
 <script> 
 setdata ('$json_object'); 
 </script>";									      
 }      
?>