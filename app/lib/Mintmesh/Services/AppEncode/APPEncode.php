<?php namespace Mintmesh\Services\APPEncode;
use Cache;
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

    protected $profanity_list = array('word');
    protected $stdCodes = array('0','1');
    public function filterString($str) {
        if (isset($str) && $str != '' && $str != '0' && !is_array($str)) {
            $str = stripslashes($str) ;
            //$str = addcslashes($str,'\\') ;
            $search = array('\\', "\0", "\n", "\r", "'", '"', "\x1a");
            $replace = array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z');
            $str = str_replace($search, $replace, $str);
            //$str = (trim(htmlentities($str, ENT_QUOTES,"UTF-8")));
            //$str = str_replace("&amp;", "&", $str);
            //$str = addslashes($str);
            return $str;
        }
        else
            return $str;
    }

   public function filterStringDecode($str) {
        if (isset($str) && $str != '' && $str != '0' && !is_array($str)) {
            //$str = stripslashes($str) ;
            //$str = addcslashes($str,'\\') ;
            $replace = array('\\', "\0", "\n", "\r", "'", '"', "\x1a");
            $search = array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z');
            $str = str_replace($search, $replace, $str);
            //$str = (trim(htmlentities($str, ENT_QUOTES,"UTF-8")));
            //$str = str_replace("&amp;", "&", $str);
            
            return $str;
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
        else if($dt2 == $dt1){
            return 1 ;
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
                    if ($years > 1 && $m > 1){
                        $exp = $years." Years ".$m." Months" ;
                    }
                    else if ($years > 1 && $m <= 1){
                        $exp = $years." Years ".$m." Month" ;
                    }
                    else if ($years <= 1 && $m <= 1){
                        $exp = $years." Year ".$m." Month" ;
                    }
                    else{
                       $exp = $years." Year ".$m." Months" ; 
                    }
                }
                else if ($months == 12)
                {
                    $exp = "1 Year " ;
                }
                else
                {
                    if ($months > 1){
                        $exp = $months." Months" ;
                    }else{
                        $exp = $months." Month" ;
                    }
                }
                return $exp ;
          }
          else
          {
              return 0;
          }
      }
      
        function cleanBadWords_mounika($str)
        {
            $strorg = $str;
            if (Cache::has('badWords')) { 
                $dbwords = Cache::get('badWords');
            } 
            $odbwords=$dbwords;
//            echo "<pre>";print_r($odbwords);exit;
            array_walk($odbwords,function(&$v,$k){$v='/\b'.$v.'\b/i';});
            array_walk($dbwords,function(&$v,$k){$v=substr($v,0,1).str_repeat("*",(strlen($v)-2)).substr($v,(strlen($v)-1),1);});
            $str=preg_replace($odbwords, $dbwords, $str);
//            $str=str_ireplace($odbwords,$dbwords,$str);
            return $str;
            
        }
        
       function cleanBadWords($str)
        {
            if (Cache::has('badWords')) { 
                $this->profanity_list = Cache::get('badWords');
            } 
            if (!empty($this->profanity_list)) {
                foreach ($this->profanity_list as $k=>$v)
                {
                    $this->profanity_list[$k]=trim(strtolower($v));
                }
            }
            $explodeString = explode(" ", $str) ;
            if (is_string($str)) {
                if (in_array(strtolower($str), $this->profanity_list)) {
                    $strlenght = strlen($str);
                    $str = substr($str,0,1).str_repeat("*",($strlenght-2)).substr($str,($strlenght-1),1);
                    
//                    $temp = substr($str, 1,-1);
//                    $replacedString = str_replace($temp, '*',$str);
//                    $str = $replacedString;
                } else {
                    $explodeStringArray = explode(" ", $str) ;
                    if (is_array($explodeStringArray)) {
                        foreach ($explodeStringArray as $key=>$val) {
                            if (in_array(strtolower($val), $this->profanity_list)) {
                                $temp = substr($val, 1,-1);
                                $replacedString = str_replace($temp, str_repeat('*',strlen($temp)),$val);
                                $explodeString[$key] = $replacedString;
                            }
                        }
                        $str = implode(' ', $explodeString);
                    } else {
                        if (in_array(strtolower($str), $this->profanity_list)) {
                            $temp = substr($val, 1,-1);
                            $replacedString = str_replace($temp, '*',$str);
                            $str = $replacedString;
                        }
                    }
                }
                return $str ; 
            } else {
                return '';
            }
        }
        function callFirstNameSort($array = array()){
             usort($array, array($this, "sortByFirstName"));
             return $array ;
        }
        function sortByFirstName($a, $b)
        {
            return strcmp(strtolower(trim($a["firstname"])), strtolower(trim($b["firstname"])));
        }
        
        function formatphoneNumbers($number=''){
            $number = preg_replace('/[^0-9+]/', '', $number);
            //remove front 0 or 1..which implies india and us country codes
            $firstCharacter =  substr($number,0, 1);
            if (in_array($firstCharacter, $this->stdCodes)){
                $number = substr($number,1);
            }
            return $number;
        }
        
        function UserTimezoneGmt($gmttime,$usertimezone)
        {
    
            $userTime=strtotime($gmttime);
            $tm = $userTime+($usertimezone*60);
            return  date("Y-m-d H:i:s",$tm);
        }
        
        function UserTimezone($gmttime, $usertimezone) {
            $userTime = strtotime($gmttime);
            $tm = $userTime - ($usertimezone * 60);
            return date("Y-m-d H:i:s", $tm);
       }
       
       static function getParserValues($resumeText){
           $records = array();
	$fileInfo = explode(PHP_EOL, $resumeText);
               // $records = [];
			/*	echo "<pre>";
				print_r($fileInfo);exit;*/
				   foreach ($fileInfo as $row) {
                    // if($row == '') continue;
                    // $parts = explode(',12', $row);
                    $parts = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $row);
                    foreach ($parts as $part) {
                        if ($part == '') {
                            continue;
                        }
                    // echo $part.'<br><br>';
                        $part = strtolower($part);

                        //  ***************  EMAIL  **************

                        if (strpos($part, '@') || strpos($part, 'mail')) {
                            $pattern = '/[a-z0-9_.\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
                            preg_match_all($pattern, $part, $matches);
                            foreach ($matches[0] as $match) {
                                $records['email'][] = $match;
                            }
                        }

                        //  ***************  DOB  **************

                        if (preg_match('/dob|d.o.b|date of birth/', $part)) {
                            $dob = preg_split('/:|-/', $part);
                            foreach ($dob as $db) {
                                $date = date_parse($db);
                                if ($date['error_count'] == 0) {
                                    $records['dob'][] = $date['year'].'-'.$date['month'].'-'.$date['day'];
                                }
                            }
                        }

                       /* $date = @find_date($part);
                        if ($date) {
                            $records['dob'][] = $date['year'].'-'.$date['month'].'-'.$date['day'];
                        }
                       */

                        $p = '{.*?(\d\d?)[\\/\.\-]([\d]{2})[\\/\.\-]([\d]{4}).*}';
                        if (preg_match($p, $part)) {
                            $date = preg_replace($p, '$3-$2-$1', $part);
                            $dd = new \DateTime($date);
                            $records['dob'][] = $dd->format('Y-m-d');
                        }

                        //  ***************  MOBILE  **************

                        preg_match_all('/\d{10}/', $part, $matches);
                        if (count($matches[0])) {
                            foreach ($matches[0] as $mob) {
                                $records['mobile'][] = $mob;
                            }
                        }

                        preg_match_all('/\d{12}/', $part, $matches);
                        if (count($matches[0])) {
                            foreach ($matches[0] as $mob) {
                                $records['mobile'][] = $mob;
                            }
                        }

                        preg_match_all('/(\d{5}) (\d{5})/', $part, $matches);
                        if (count($matches[0])) {
                            foreach ($matches[0] as $mob) {
                                $records['mobile'][] = $mob;
                            }
                        }

                        //  ***************  SKILLS  **************

                        preg_match_all('/production|sales|call center|Accounts|marketting|tele communication|engineer|software engineering|software engineer|manufacturing|office administration|human resource|admin|secretarial|customer service|call center|finance account/', $part, $matches);
                        if (count($matches[0])) {
                            foreach ($matches[0] as $skill) {
                                $records['skills'][] = $skill;
                            }
                        }

                        //  ***************  PLACE  **************

                       /* preg_match_all($places_regx, $part, $matches);
                        if (count($matches[0])) {
                            foreach ($matches[0] as $skill) {
                                $records['place'][] = ucwords($skill);
                            }
                        }*/

                        //  ***************  NAME  **fe************

                        if (isset($records['email'])) {
                            foreach ($records['email'] as $email) {
                                $e = explode('@', $email);
                                $records['name'][] = $e[0];
                            // code...
                            }
                        }
                    }
                }
				foreach ($records as $key => $value) {
                    $records[$key] = array_unique($value);
                }
				return $records;
}



}
