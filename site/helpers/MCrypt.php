<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

// No direct access.
defined('_JEXEC') or die;

class MCrypt
{
    private $key; // #Same as in your IMC Options
    private $iv;

    public function __construct()
    {
    }

    /**
     * @param mixed $key 16 chars
     */
    public function setKey($key)
    {
        $this->key = $key;
        $this->iv = $key;
    }

    public function encrypt($str) {
        $td = mcrypt_module_open('rijndael-128', '', 'cbc', $this->iv);
        mcrypt_generic_init($td, $this->key, $this->iv);
        $encrypted = mcrypt_generic($td, $str);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return bin2hex($encrypted);
    }

    public function decrypt($code) {
        $code = $this->hex2bin($code);
        $td = mcrypt_module_open('rijndael-128', '', 'cbc', $this->iv);
        mcrypt_generic_init($td, $this->key, $this->iv);
        $decrypted = mdecrypt_generic($td, $code);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return utf8_encode(trim($decrypted));
    }

    protected function hex2bin($hexdata) {
        $bindata = '';
        for ($i = 0; $i < strlen($hexdata); $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
        return $bindata;
    }
}