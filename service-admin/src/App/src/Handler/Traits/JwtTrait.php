<?php

namespace App\Handler\Traits;

use RuntimeException;

/**
 * Trait JwtTrait
 * @package App\Handler\Traits
 */
trait JwtTrait
{
    /**
     * @param $name
     * @param $value
     */
    private function addTokenData($name, $value)
    {
        $this->verifyTokenDataExists();

        $_SESSION['jwt-payload'][$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    private function getTokenData($name = null)
    {
        $this->verifyTokenDataExists();

        if (array_key_exists($name, $_SESSION['jwt-payload'])) {
            return $_SESSION['jwt-payload'][$name];
        }

        return null;
    }

    /**
     * @param $name
     */
    private function removeTokenData($name)
    {
        $this->verifyTokenDataExists();

        unset($_SESSION['jwt-payload'][$name]);
    }

    /**
     *  Clear all token data down
     */
    private function clearTokenData()
    {
        $this->verifyTokenDataExists();

        $_SESSION['jwt-payload'] = [];
    }

    /**
     * Centralised function to verify that the JWT token data is present in the session
     */
    private function verifyTokenDataExists()
    {
        //  TODO - Change this to not use session?
        if (!array_key_exists('jwt-payload', $_SESSION)) {
            throw new RuntimeException('JWT token not available');
        }
    }
}
