<?php
namespace Mintmesh\Services\Parser;
use Lang;
use Config ;
use Mintmesh\Services\FileUploader\API\User\UserFileUploader;
class ParserManager {

    /**
     * @var UserFileUploader
     */
    private $userFileUploader;
    protected $s3;
    
    public function __construct() {
        $this->s3 = \AWS::get('s3');
        $this->userFileUploader = new UserFileUploader ;
    }
    
    public function initiateParser($filepath='') {
        #get the bucket name and file name here
        $key         = str_replace('%2F','/',$filepath);
        $file_path   = explode('/', $key);
        $file_name   = end($file_path);
        $file_folder = substr($key, 0, (strlen($file_name) * -1)+1);
        $file_folder = prev($file_path);
        
        #get the bucket path here
        if($file_folder == 'MintmeshReferredResumes'){
            $bucketname = Config::get('constants.S3BUCKET_MM_REFER_RESUME');
        }  else if($file_folder == 'NonMintmeshReferredResumes'){
            $bucketname = Config::get('constants.S3BUCKET_NON_MM_REFER_RESUME');
        } else {
            $bucketname ='';
        }
        $saveAsPath = Config::get('constants.PARSER_INPUT_PATH').$file_name;
        
        $result = $this->s3->getObject([
            'Bucket' => $bucketname,
            'Key'    => $file_name,
            'SaveAs' => $saveAsPath
         ]);
        
        return $result['Body']->getUri();
    }
    
    public function processParsing($filepath='') {
        
        $inputFile = $this->initiateParser($filepath);

        $rand = rand(10, 10000);
        $outputfile = Config::get('constants.PARSER_OUTPUT_PATH').$rand.".json";
        
        try {
            shell_exec("sh ".Config::get('constants.PARSER_PATH')." $inputFile $outputfile");
        } catch (Exception $ex) {
            throw 'Parser not executed...!';
        }

        $jsonFile = $this->uploadOutputJSONtoS3($outputfile);
        
        unlink($inputFile);
        return $jsonFile;
    }
    
    public function uploadOutputJSONtoS3($filepath) {
        $renamedFileName = '';
        if(file_exists($filepath)) {
            $this->userFileUploader->destination = Config::get('constants.S3BUCKET_MM_REFER_RESUME_JSON');
            $renamedFileName = $this->userFileUploader->uploadToS3BySource($filepath);
        }
        return $renamedFileName;
    }
   
}

