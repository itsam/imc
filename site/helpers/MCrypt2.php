<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

class MCrypt
{
    private $key; // #Same as in your IMC Options
    private $iv;

    public function __construct()
    { }

    /**
     * @param mixed $key 16 chars
     */
    public function setKey($key)
    {
        $this->key = $key;
        $this->iv = $key;
    }

    public function encrypt($str)
    {
        // if ($m = strlen($str) % 8) {
        //     $str .= str_repeat("\x00",  8 - $m);
        // }
        $openssl = openssl_encrypt($str, 'AES-128-CBC', $this->key, 0, $this->iv);
        return bin2hex($openssl);
    }

    public function decrypt($code)
    {
        $code = $this->hex2bin($code);
        $decrypted = openssl_decrypt($code, 'AES-128-CBC', $this->key, 0, $this->iv);
        return utf8_encode(trim($decrypted));
    }

    protected function hex2bin($hexdata)
    {
        $bindata = '';
        for ($i = 0; $i < strlen($hexdata); $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
        return $bindata;
    }

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
