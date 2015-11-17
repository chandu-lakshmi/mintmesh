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


}
