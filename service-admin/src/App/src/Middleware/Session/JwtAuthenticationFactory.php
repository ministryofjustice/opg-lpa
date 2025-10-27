<?php

namespace App\Middleware\Session;

use Firebase\JWT\JWT;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tuupola\Middleware\JwtAuthentication;
use DateTime;

/**
 * Class JwtAuthenticationFactory
 * @package App\Middleware\Session
 *
 * We are maintaining this here until such time as we can reinstate
 * the Tuupola JWT middleware, but commented out.
 * @psalm-suppress UnusedClass
 */
class JwtAuthenticationFactory
{
    /**
     * @param ContainerInterface $container
     * @return JwtAuthentication
     */
    /*public function __invoke(ContainerInterface $container)
    {
        $jwtConfig = $container->get('config')['jwt'];

        //  Add JWT callback handlers to the config
        $jwtHandlers = [
            'before' => function (ServerRequestInterface $request, $params) {
                //  Move the existing JWT data to the session so we can get it after processing
                $_SESSION['jwt-payload'] = $request->getAttribute('token');
            },
            'after' => function (ResponseInterface $response, $params) use ($jwtConfig) {
                //  Re-set the JWT cookie using the updated data and a new timestamp
                $ttl = new DateTime(sprintf('+%s seconds', $jwtConfig['ttl']));

                $jwtCookie = JWT::encode($_SESSION['jwt-payload'], $jwtConfig['secret'], $jwtConfig['algo']);

                setcookie($jwtConfig['cookie'], $jwtCookie, [
                    'expires' => $ttl->getTimeStamp(),
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);
            },
        ];

        return new JwtAuthentication(array_merge($jwtConfig, $jwtHandlers));
    }*/
}
