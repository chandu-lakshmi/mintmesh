<?php namespace Mintmesh\Services\FileUploader;
use Lang;
use Config ;
abstract class FileUploader {
        public $source, $destination ;
        

        public function __construct()
        {
        }
        public function moveFile()
        {
            if ($this->source->isValid())
            {
                //rename the name
                $ext = $this->source->getClientOriginalExtension();
                $fileName = time().".".$ext;
                $this->source->move($this->destination, $fileName);
                return $fileName ;
            }
            else
            {
                return false ;
            }

            
        }
        
        public function uploadToS3() {
                        
            if ($this->source->isValid()) {

                $sourceFile = $this->source->getpathName();
                $sourceMimeType = $this->source->getmimeType();            
                $ext = $this->source->getClientOriginalExtension();
                $fileName = time().".".$ext;

                $s3 = \AWS::get('s3');
                try {
                        // Upload data.
                        $result = $s3->putObject(array(
                            'Bucket'        => $this->destination,
                            'Key'           => $fileName,
                            'Body'          => fopen($sourceFile,'r'),
                            'ContentType'   => $sourceMimeType,
                            'ACL'           => 'public-read',
                        ));
                                // Print the URL to the object.
                        return $result['ObjectURL'];
                } catch (S3Exception $e) {
                        return $e->getMessage();
                }
            } else {
                return false;
            }
            
        }

}
