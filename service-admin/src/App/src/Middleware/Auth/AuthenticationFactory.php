<?php

namespace App\Middleware\Auth;

use Interop\Container\ContainerInterface;
use Tuupola\Middleware\JwtAuthentication;
use DateTime;

/**
 * Class AuthenticationFactory
 * @package App\Middleware\Auth
 */
class AuthenticationFactory
{
    /**
     * @param ContainerInterface $container
     * @return JwtAuthentication
     */
    public function __invoke(ContainerInterface $container)
    {
        $jwtConfig = $container->get('config')['jwt'];

        //  Add JWT callback handlers to the config
        $jwtHandlers = [
            'before' => function ($request, $params) use ($jwtConfig) {
                //  Get the existing token value and new TTL
                $jwtCookie = $_COOKIE[$jwtConfig['cookie']];
                $ttl = new DateTime(sprintf('+%s seconds', $jwtConfig['ttl']));

                setcookie($jwtConfig['cookie'], $jwtCookie, $ttl->getTimeStamp(), '', '', true);
            }
        ];

        return new JwtAuthentication(array_merge($jwtConfig, $jwtHandlers));
    }
}
