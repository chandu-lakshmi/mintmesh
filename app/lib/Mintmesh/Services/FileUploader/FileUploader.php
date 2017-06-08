<?php namespace Mintmesh\Services\FileUploader;
use Lang;
use Config ;
abstract class FileUploader {
        public $source, $destination , $documentid, $tenantid;
        

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
        #tenant based resume move To S3
        public function moveResume($source='')
        {
            $fileName = false;
            if(file_exists($source)){
                $fileinfo   = pathinfo($source);
                $sourceFile = $source;
                $ext = $fileinfo['extension'];
                $fileName = $this->documentid.".".$ext;
                $destination = $this->destination;
                try {
                        #create tenant directory not exists
                        if (!file_exists($destination)) {
                            mkdir($destination, 0777);
                        }
                        #check if file exists in source
                        if(file_exists($source)){
                            copy($source, $destination.$fileName);
                            unlink($source);
                        }
                } catch (\Exception $e) {
                    \Log::info("failed to upload resume (moveResume) Document id : ".$this->documentid.'| Exception : '.$e->getMessage());
                    $fileName = false;
                }
            }
            return $fileName;
        }
      
            public function uploadToS3BySource($path) {
                        
//            if (file_exists($path)) {
                $fileinfo = pathinfo($path);
                $sourceFile = $path;//$this->source->getpathName();
                $sourceMimeType = $fileinfo['extension'];
                $ext = $fileinfo['extension'];
                $sourceFileName = $fileinfo['filename'];// $this->source->getClientOriginalName();
                $fileName = $sourceFileName."_".time().".".$ext;

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
                        if(true && file_exists($sourceFile)){ 
                            unlink($sourceFile);
                        }
                        return $result['ObjectURL'];
                } catch (S3Exception $e) {
                        return $e->getMessage();
                }
//            } else {
//                return false;
//            }
            
        }       
        #tenant based resume Upload To S3
        public function resumeUploadToS3() {
                        
                $sourceFile     = $this->source->getpathName();
                $sourceMimeType = $this->source->getmimeType();            
                $ext = $this->source->getClientOriginalExtension();
                $fileName = $this->documentid.".".$ext;

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
            
        } 
        
        public function uploadToS3() {
                        
          //  if ($this->source->isValid()) {

                $sourceFile = $this->source->getpathName();
                $sourceMimeType = $this->source->getmimeType();            
                $ext = $this->source->getClientOriginalExtension();
                $sourceFileName = $this->source->getClientOriginalName();
                $sourceFileName = basename($sourceFileName, ".".$ext);
                $sourceFileName = str_replace(' ', '-', $sourceFileName); // Replaces all spaces with hyphens.
                $sourceFileName = preg_replace('/[^A-Za-z0-9\-]/', '', $sourceFileName); // Removes special chars.
                $fileName = $sourceFileName."_".time().".".$ext;

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
//            } else {
//                return false;
//            }
            
        }       
}
