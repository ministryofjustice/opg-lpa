<?php

declare(strict_types=1);

function make_token(int $length = 16): string
{
    $token = sprintf("0x%s", bin2hex(random_bytes($length)));

    // Use base62 for shorter tokens
    $strBase62 = gmp_strval($token, 62);
    return str_pad($strBase62, 20, '0', STR_PAD_LEFT);
}
