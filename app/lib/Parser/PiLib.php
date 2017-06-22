<?php

namespace lib\Parser;

class PiLib {

    static function piIsset($source, $key, $default) {

        if (isset($source[$key]) && !empty($source[$key])) {

            return $source[$key];
        } else {
            return $default;
        }
    }

    function time_elapsed_string($datetime, $full = false) {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'y',
            'm' => 'm',
            'w' => 'w',
            'd' => 'day',
            'h' => 'h',
            'i' => 'm',
            's' => 's'
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . '' . $v . ($diff->$k > 1 ? '' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);

        if (isset($string['w']) && $string['w'] != '1w') {
            return 'On ' . date('m/d', strtotime($datetime));
           
        }
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    static function piDate($date=null, $format=null) {
        if (!empty($format)) {
            $format = $format;
        } else {
            $format = 'Y-m-d H:i:s';
        }
        if (!empty($date)) {
            $dateFormat = date($format, strtotime($date));
        } else {
            $currentDate = date('Y-m-d H:i:s');
            $dateFormat = date($format, strtotime($currentDate));
        }
        return $dateFormat;
    }

    public function getSlug($string) {
        return preg_replace('/\s+/', '_', strtolower($string));
    }

    static function piRound() {
        
    }

    public function status() {
        
    }

    static function piEncrypt($id) {
        if (is_array($id)) {
            return array_map(array(__CLASS__, 'arrayEncryptMap'), $id);
        }
        $objEncrypt = new MyEncrypt();
        $encrypt = $objEncrypt->encode($id);
        return $encrypt;
    }

    static function piDecrypt($id) {
        if (is_array($id)) {
            return array_map(array(__CLASS__, 'arrayDecryptMap'), $id);
        }
        $objEncrypt = new MyEncrypt();
        $decrypt = $objEncrypt->decode($id);
        return $decrypt;
    }

    static function arrayEncryptMap($id) {

        $objEncrypt = new MyEncrypt();
        return $objEncrypt->encode($id);
    }

    static function arrayDecryptMap($id) {

        $objEncrypt = new MyEncrypt();
        return $objEncrypt->decode($id);
    }

}
