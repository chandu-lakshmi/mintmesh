<?php
namespace lib\MyExcel;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use PHPExcel_IOFactory; 
use PHPExcel_Cell;


class MyExcel {

    function __construct() {
        
    }

     public static function uploadExcel($uploader,$filename='') {  
         $result = array();
           if (isset($uploader['qqfile']['tmp_name'])) {
                $upload = UploadType::Factory('Regular');
                $files = $uploader['qqfile'];
            } else {
                if (!empty($uploader['HTTP_X_FILE_NAME'])) {
                    $files = $uploader['HTTP_X_FILE_NAME'];
                } else {
                    $files = $filename;
                }

                $upload = UploadType::Factory('Stream');
            }

            $upload->setSize(130 * 1024 * 1024);
            $upload->setAllowedTypes(array('xls', 'xlsx'));
            $upload->setContainer(public_path() . '/import/');

            $tmpfile = $upload->save($files);

            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
                $source = $tmpfile['filename'];
                $size = $upload->fileSize($source);
                
                $result = array($tmpfile['error'] => true, "source" => public_path() . "/import/" . basename($source));
            } else {
                $result = array("success" => false);
            }
            return $result; 
    }
    
    
    public static function read_excel1_sheets_headers($filename,$header_row=1)
    {
       if(file_exists($filename))
       {
        set_time_limit(0);
        $data = array();
        $inputFileType = PHPExcel_IOFactory::identify($filename);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(false);
        $reader = $objReader->load($filename);
        $count =1;// 
        $sheetNames = $reader->getSheetNames();
        $headers_names=array();
		
        for ($i = 0; $i < count($sheetNames); $i++) {
            $clm_names=array();
            $data['sheets'][$i] = $sheetNames[$i];
            $objWorksheet = $reader->setActiveSheetIndex($i); // first sheet  
            $highestRow = $objWorksheet->getHighestRow(); // here 5  
            $highestColumn = $objWorksheet->getHighestColumn(); // here 'E'  
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);  // here 5 

            for ($row = $header_row; $row <= $header_row; ++$row) {
                                    $j=0;
                for ($col = 0; $col < $highestColumnIndex; ++$col) {

                        $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                        if (!empty($value)) {
                               $clm_names[] = array('id'=>$j,'label'=>$value);
                        }
                    $j++;
                }
            }
            $data['column_names'][$i] = $clm_names;
            }
        return $data;
         }else{
                 throw new Exception('File not Found');  
         }
      }
		  
    static function readExcel($filename) {
        
        ini_set('memory_limit','4048M');
       if(file_exists($filename))
       {   
            set_time_limit(0);

            $data = array();
            $inputFileType = PHPExcel_IOFactory::identify($filename);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(false);
            $reader = $objReader->load($filename);
            $count = $reader->getSheetCount(); 
            $sheetNames = $reader->getSheetNames();
            for ($i = 0; $i < 1; $i++) {
                $objWorksheet   = $reader->setActiveSheetIndex($i); // first sheet  
                $highestRow     = $objWorksheet->getHighestRow(); // here 5  
                 $highestColumn  = 6;//$objWorksheet->getHighestColumn(); // here 'E'  
                 $highestColumnIndex = 6;//PHPExcel_Cell::columnIndexFromString($highestColumn);  // here 5 
//exit;
                for ($row = 1; $row <= $highestRow; ++$row) {

                    for ($col = 0; $col < $highestColumnIndex; ++$col) {

                        $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                        if (is_array($data)) {
                            //if (!empty($value)) {
                                   $data[$i][$row][] = $value;
                            //}
                        }
                    }
                }
            }  
            return $data;
       } else{
            throw new Exception('File not Found');
       }  
 }	 
 
 static function readExcel_sheet($filename,$sheet_num,$row_id=2) {
     
       ini_set('memory_limit','512M');
       if(file_exists($filename))
       {   
            set_time_limit(0);
            $data = array();
            $inputFileType  = PHPExcel_IOFactory::identify($filename);
            $objReader      = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(false);
            $reader = $objReader->load($filename);

            $count      = $reader->getSheetCount(); 
            $sheetNames = $reader->getSheetNames();
          
                $objWorksheet       = $reader->setActiveSheetIndex($sheet_num); // first sheet  
                $highestRow         = $objWorksheet->getHighestRow(); // here 5  
                $highestColumn      = $objWorksheet->getHighestColumn(); // here 'E'  
                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);  // here 5 

                for ($row = $row_id; $row <= $highestRow; ++$row) {

                    for ($col = 0; $col < $highestColumnIndex; ++$col) {
                            $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                            if (is_array($data)) {
                                    $data[$row][] = $value;
                            }
                        }
                    } 
            return $data;
       } else{
            throw new Exception('File not Found');
       }  
 }	
 static function readExcel_sheet_media($filename,$sheet_num,$row_id=2) {
     
       ini_set('memory_limit','512M');
       if(file_exists($filename))
       {   
            set_time_limit(0);
            $data = array();
            $inputFileType = PHPExcel_IOFactory::identify($filename);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(false);
            $reader = $objReader->load($filename);

            $count      = $reader->getSheetCount(); 
            $sheetNames = $reader->getSheetNames();
            $objWorksheet   = $reader->setActiveSheetIndex($sheet_num); // first sheet  
            $highestRow     = $objWorksheet->getHighestRow(); // here 5  
            $highestColumn  = $objWorksheet->getHighestColumn(); // here 'E'  
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);  // here 5 

            for ($row = $row_id; $row <= $highestRow; ++$row) {

                for ($col = 0; $col < $highestColumnIndex; ++$col) {

                    if($objWorksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue()/*$col==9*/){

                             $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    }else{
                             $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                    }

                    if (is_array($data)) {
                               $data[$row][] = $value;
                    }
                }
            }
            return $data;
       } else{
            throw new Exception('File not Found');
       }  
 }	
 
 static function readExcel_sheet_header($filename,$sheet_num,$row_id=1) {
        ini_set('memory_limit','512M');
       if(file_exists($filename))
       {   
            set_time_limit(0);
            $data = array();
            $inputFileType = PHPExcel_IOFactory::identify($filename);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(false);
            $reader = $objReader->load($filename);

            $count = $reader->getSheetCount(); 
            $sheetNames = $reader->getSheetNames();
            $objWorksheet = $reader->setActiveSheetIndex($sheet_num); // first sheet  
            $highestRow = $objWorksheet->getHighestRow(); // here 5  
            $highestColumn = $objWorksheet->getHighestColumn(); // here 'E'  
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);  // here 5 

            for ($row = $row_id; $row <= $row_id; ++$row) {
                $j=0;
                for ($col = 0; $col < $highestColumnIndex; ++$col) {

                        $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                        if (is_array($data)) {
                            $data[$j] = $value; 
                        }
                        $j++;
                    }
                }
            return $data;
       } else{
            throw new Exception('File not Found');
       }  
 }	  
 public static function read_excel1_sheets_headers_col($filename,$sheet_num,$header_row=1){
     
       if(file_exists($filename))
       {
        //  Read your Excel workbook
        set_time_limit(0);
        $data = $headers_names = $clm_names = array();
        $inputFileType = PHPExcel_IOFactory::identify($filename);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(false);
        $reader = $objReader->load($filename);
        $count =1;// 
        $sheetNames = $reader->getSheetNames();
		
        $objWorksheet   = $reader->setActiveSheetIndex($sheet_num); // first sheet  
        $highestRow     = $objWorksheet->getHighestRow(); // here 5  
        $highestColumn  = $objWorksheet->getHighestColumn(); // here 'E'  
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);  // here 5 

        for ($row = $header_row; $row <= $header_row; ++$row) {
                                $j=0;
            for ($col = 0; $col < $highestColumnIndex; ++$col) {
                    $value=$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                    if (!empty($value)) {
                        $clm_names[] = array('id'=>$j,'label'=>$value);
                    }
                  $j++;
                }
            }
            $data = $clm_names;
            return $data;
        }else{
             throw new Exception('File not Found');  
        }
    }  
}
