<?php

/**
 * This class extends the core Encrypt class, and allows you
 * to use encrypted strings in your URLs.
 */

namespace lib\Parser;

class MyEncrypt {

    function str_encode($text) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $key = "TYEHDJHDHDYJDIDIUJDOIDUJ";
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv));
    }

    function str_decode($text) {

        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $key = "TYEHDJHDHDYJDIDIUJDOIDUJ";
        //I used trim to remove trailing spaces
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($text), MCRYPT_MODE_ECB, $iv));
    }

    /**
     * Encodes a string.
     *
     * @param string $string
     *        	The string to encrypt.
     * @param string $key[optional]
     *        	The key to encrypt with.
     * @param bool $url_safe[optional]
     *        	Specifies whether or not the
     *        	returned string should be url-safe.
     * @return string
     */
    function encode($string, $key = "", $url_safe = TRUE) {
        // $ret = parent::encode($string, $key);
        $ret = $this->str_encode($string);
        if ($url_safe) {
            $ret = strtr($ret, array(
                '+' => '.',
                '=' => '-',
                '/' => '~'
                    ));
        }

        return $ret;
    }

    /**
     * Decodes the given string.
     *
     * @access public
     * @param string $string
     *        	The encrypted string to decrypt.
     * @param string $key[optional]
     *        	The key to use for decryption.
     * @return string
     */
    function decode($string, $key = "") {
        $string = strtr($string, array(
            '.' => '+',
            '-' => '=',
            '~' => '/'
                ));

        // return parent::decode($string, $key);
        return $this->str_decode($string);
    }

    static function decrypt_blowfish($data, $key) {
        $iv = @pack("H*", substr($data, 0, 16));
        $x = @pack("H*", substr($data, 16));
        $res = @mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $x, MCRYPT_MODE_CBC, $iv);
        return $res;
    }

    static function encrypt_blowfish($data, $key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_CBC, $iv);
        return bin2hex($iv . $crypttext);
    }

}
