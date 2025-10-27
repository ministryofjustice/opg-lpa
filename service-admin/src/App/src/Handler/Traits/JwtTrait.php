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
     * @param string $name
     * @param object|string $value
     * @return void
     */
    private function addTokenData(string $name, $value): void
    {
        $this->verifyTokenDataExists();

        $_SESSION['jwt-payload'][$name] = $value;
    }

    /**
     * @param string $name
     * @return string|null|object
     */
    private function getTokenData(?string $name = null)
    {
        $this->verifyTokenDataExists();

        if (!is_null($name) && array_key_exists($name, $_SESSION['jwt-payload'])) {
            return $_SESSION['jwt-payload'][$name];
        }

        return null;
    }

    /**
     * Clear all token data down
     *
     * @return void
     */
    private function clearTokenData(): void
    {
        $this->verifyTokenDataExists();

        $_SESSION['jwt-payload'] = [];
    }

    /**
     * Centralised function to verify that the JWT token data is present in the session
     *
     * @return void
     */
    private function verifyTokenDataExists(): void
    {
        if (!isset($_SESSION['jwt-payload'])) {
            throw new RuntimeException('JWT token not available');
        }
    }
}
