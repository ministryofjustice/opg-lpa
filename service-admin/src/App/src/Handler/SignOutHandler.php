<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SignOutHandler extends AbstractHandler
{
    /**
     * @var array
     */
    private $jwtConfig;

    /**
     * SignOutHandler constructor.
     * @param array $jwtConfig
     */
    public function __construct(array $jwtConfig)
    {
        $this->jwtConfig = $jwtConfig;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        //  Set the JWT cookie to expire and redirect to sign in
        setcookie($this->jwtConfig['cookie'], '', time() - 3600, '', '', true);

        return $this->redirectToRoute('sign.in');
    }
}
