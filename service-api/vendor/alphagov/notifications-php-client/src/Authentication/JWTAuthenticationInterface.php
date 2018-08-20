<?php
namespace Alphagov\Notifications\Authentication;

/**
 * Interface representing a GOV.UK Notify compatible JSON Web Token generator.
 *
 * Interface JWTAuthenticationInterface
 * @package Alphagov\Notifications\Authentication
 */
interface JWTAuthenticationInterface {

    /**
     * Generate a JSON Web Token.
     *
     * @return string The generated token
     */
    public function createToken();

}