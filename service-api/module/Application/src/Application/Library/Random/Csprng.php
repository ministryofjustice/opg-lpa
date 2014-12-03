<?php
namespace Application\Library\Random;

// Cryptographically Secure Pseudo-Random String Generator (CSPRSG) and CSPRNG.
// (C) 2014 CubicleSoft.  All Rights Reserved.
// Under The MIT License

use Exception;

/**
 * LPA amended version of php-csprng. The only change being we force the use of openssl_random_pseudo_bytes and
 * have removed unused methods.
 *
 * SOME CODE HAS BEEN REMOVED. NO CODE HAD BEEN ADDED.
 *
 * Class CSPRNG
 */
class Csprng {

    private static $alphanum = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    private $mode, $fp, $cryptosafe;

    // Crypto-safe uses the best quality sources (e.g. /dev/random), but those sources can hang the application.
    // Will raise an exception if the constructor can't find a suitable source of randomness.
    public function __construct(){

        $this->mode = false;
        $this->fp = false;
        $this->cryptosafe = true;

        // OpenSSL first.
        if (function_exists("openssl_random_pseudo_bytes"))
        {
            // PHP 5.4.0 introduced native Windows CryptGenRandom() integration via php_win32_get_random_bytes() for performance.
            @openssl_random_pseudo_bytes(4, $strong);
            if ($strong)  $this->mode = "openssl";
        }

        // Throw an exception if unable to find a suitable entropy source.
        if ($this->mode === false) {
            throw new Exception("Unable to locate a suitable entropy source.");
        }

    }

    public function GetBytes($length)
    {
        if ($this->mode === false)  return false;

        $length = (int)$length;
        if ($length < 1)  return false;

        $result = "";
        do
        {

            $data = openssl_random_pseudo_bytes($length, $strong);

            if (!$strong)  $data = false;

            if ($data === false)  return false;

            $result .= $data;
        } while (strlen($result) < $length);

        return substr($result, 0, $length);
    }

    public function GenerateToken($length = 64)
    {
        $data = $this->GetBytes($length);
        if ($data === false)  return false;

        return bin2hex($data);
    }

    // Get a random number between $min and $max (inclusive).
    public function GetInt($min, $max)
    {
        $min = (int)$min;
        $max = (int)$max;
        if ($max < $min)  return false;
        if ($min == $max)  return $min;

        $range = $max - $min + 1;

        $bits = 1;
        while ((1 << $bits) <= $range)  $bits++;

        $numbytes = (int)(($bits + 7) / 8);
        $mask = (1 << $bits) - 1;

        do
        {
            $data = $this->GetBytes($numbytes);
            if ($data === false)  return false;

            $result = 0;
            for ($x = 0; $x < $numbytes; $x++)
            {
                $result = ($result * 256) + ord($data{$x});
            }

            $result = $result & $mask;
        } while ($result >= $range);

        return $result + $min;
    }

    // Convenience method to generate a random alphanumeric string.
    public function GenerateString($size = 32)
    {
        $result = "";
        for ($x = 0; $x < $size; $x++)
        {
            $data = $this->GetInt(0, 61);
            if ($data === false)  return false;

            $result .= self::$alphanum{$data};
        }

        return $result;
    }

}
