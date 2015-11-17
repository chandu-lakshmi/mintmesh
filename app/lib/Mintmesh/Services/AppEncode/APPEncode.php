<?php namespace Mintmesh\Services\APPEncode;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of APPEncode
 *
 * @author shweta
 */

 class  APPEncode {
    //put your code here
    
    
    public function filterString($str) {
        if (isset($str) && $str != '' && $str != '0' && !is_array($str)) {
            $str = stripslashes($str) ;
            //$str = addcslashes($str,'\\') ;
            $str = (trim(htmlentities($str, ENT_QUOTES,"UTF-8")));
            $str = str_replace("&amp;", "&", $str);

            return $str;
        }
        else
            return $str;
    }

   public function filterStringDecode($str) {
        if (isset($str) && $str != '' && $str != '0') {
            return trim(htmlspecialchars_decode(html_entity_decode($str, ENT_QUOTES,'UTF-8')));
            // return $str;
        }
        else
            return $str;
     }
     
      public function filterStringLashes($str) {
        if (isset($str) && $str != '' && $str != '0') {
            return trim(addslashes($str));
            // return $str;
        }
        else
            return $str;
     }
     
     function array_sort($array, $on, $order=SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                break;
                case SORT_DESC:
                    arsort($sortable_array);
                break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }
    
    function dateDiff($dt1, $dt2) {
        $date1 = strtotime(date('Y-m',strtotime($dt1)));
        $date2 = strtotime(date('Y-m',strtotime($dt2)));
        if ($dt2 > $dt1)
        {
            $months = 0;
            while (($date1 = strtotime('+1 MONTH', $date1)) <= $date2)
                $months++;

            return $months+1;
        }
        else
        {
            return 0 ;
        }
        
      }
      
      function calculateYear($months=0)
      {
          if (!empty($months))
          {
              if ($months > 12)
                {
                    $y = $months/12 ;
                    $years = floor($y) ;
                    $m =  $months-($years*12) ;
                    $exp = $years." Year ".$m." Months" ;
                }
                else if ($months == 12)
                {
                    $exp = "1 Year " ;
                }
                else
                {
                    $exp = $months." Months" ;
                }
                return $exp ;
          }
          else
          {
              return 0;
          }
      }
     
}
