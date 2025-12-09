<?php

namespace App\Middleware\Session;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class JwtMiddlewareFactory
 * @package App\Middleware\Session
 */
class JwtMiddlewareFactory
{
    /**
     * @param ContainerInterface $container
     * @return JwtMiddleware
     */
    public function __invoke(ContainerInterface $container)
    {
        $jwtConfig = $container->get('config')['jwt'];

        $options = [
            "before" => function (ServerRequestInterface $request, $_): void {
                // Move the existing JWT data to the session so we can get it after processing
                $_SESSION['jwt-payload'] = $request->getAttribute('token');
            },
            "after" => function (ResponseInterface $_1, $_2) use ($jwtConfig): void {
                // Re-set the JWT cookie using the updated data and a new timestamp
                $ttl = new DateTimeImmutable(sprintf('+%s seconds', $jwtConfig['ttl']));

                $jwtCookie = JWT::encode(
                    $_SESSION['jwt-payload'],
                    $jwtConfig['secret'],
                    $jwtConfig['algo'],
                );

                setcookie($jwtConfig['cookie'], $jwtCookie, [
                    'expires' => $ttl->getTimeStamp(),
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);
            },
        ];

        $options = array_merge($options, $jwtConfig);

        return new JwtMiddleware($options);
    }
}
