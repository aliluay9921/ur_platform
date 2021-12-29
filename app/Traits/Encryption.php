<?php

namespace App\Traits;

trait Encryption
{
    // encription use ceaser chpher 
    // function Cipher($ch, $key)
    // {
    //     if (!ctype_alpha($ch))
    //         return $ch;

    //     $offset = ord(ctype_upper($ch) ? 'A' : 'a');
    //     return chr(fmod(((ord($ch) + $key) - $offset), 26) + $offset);
    // }

    // function Encipher($input, $key)
    // {
    //     $output = "";

    //     $inputArr = str_split($input);
    //     foreach ($inputArr as $ch)
    //         $output .= $this->Cipher($ch, $key);

    //     return $output;
    // }
    // function Decipher($input, $key)
    // {
    //     return $this->Encipher($input, 26 - $key);
    // }

    // encription in des cipher 

    public function desEncrypt($str, $key)
    {
        $iv = $key;

        $data = openssl_encrypt($str, "DES-CBC", $key, OPENSSL_RAW_DATA, $iv);

        $data = strtolower(bin2hex($data));

        return $data;
    }
    public function desDecrypt($str, $key)
    {
        $iv = $key;

        return openssl_decrypt(hex2bin($str), 'DES-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}