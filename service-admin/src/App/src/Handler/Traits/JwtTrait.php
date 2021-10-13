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
    // phpstan wants a typehint for $value, but PHP 7 won't accept union typehints;
    // consquently, we just tell phpstan to ignore this method signature
    /* @phpstan-ignore-next-line */
    private function addTokenData(string $name, $value): void
    {
        $this->verifyTokenDataExists();

        $_SESSION['jwt-payload'][$name] = $value;
    }

    /**
     * @param string $name
     * @return string|null|object
     */
    // phpstan wants a return type, but PHP 7 won't accept union typehints;
    // consquently, we just tell phpstan to ignore this method signature
    /* @phpstan-ignore-next-line */
    private function getTokenData(string $name = null)
    {
        $this->verifyTokenDataExists();

        if (!is_null($name) && array_key_exists($name, $_SESSION['jwt-payload'])) {
            return $_SESSION['jwt-payload'][$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @return void
     */
    private function removeTokenData(string $name): void
    {
        $this->verifyTokenDataExists();

        unset($_SESSION['jwt-payload'][$name]);
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
        if (!array_key_exists('jwt-payload', $_SESSION)) {
            throw new RuntimeException('JWT token not available');
        }
    }
}
