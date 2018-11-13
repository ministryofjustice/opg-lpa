<?php

declare(strict_types=1);

namespace App\Handler;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use DateTime;

/**
 * Class SignInHandler
 * @package App\Handler
 */
class SignInHandler extends AbstractHandler
{
    /**
     * @var array
     */
    private $jwtConfig;

    /**
     * SignInHandler constructor.
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
        $data = [];

        //  If there already is a valid JWT then redirect home
        //  NOTE - we can not inspect the token set in the request here because this sign.in route doesn't go via the JWT middleware
        if (array_key_exists($this->jwtConfig['cookie'], $_COOKIE)) {
            return $this->redirectToRoute('home');
        }

        if ($request->getMethod() == 'POST') {

            //  TODO - authenticate against LPA Online here and set the details in the JWT payload... what to do if fail?
            $response = [
                'userId'    => 'QWERTYUIO123456789',
                'username'  => 'a@b.com',
                'token'     => 'auth-tok',
            ];

            //  Filter the response to extract the required values
            $jwtPayload = array_intersect_key($response, array_flip([
                'userId',
                'username',
                'token',
            ]));

            //  Generate the token and set the value in the cookie
            $token = JWT::encode($jwtPayload, $this->jwtConfig['secret'], $this->jwtConfig['algo']);
            $ttl = new DateTime(sprintf('+%s seconds', $this->jwtConfig['ttl']));

            setcookie($this->jwtConfig['cookie'], $token, $ttl->getTimeStamp(), '', '', true);

            //  Redirect to the home page
            return $this->redirectToRoute('home');
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::sign-in', $data));
    }
}
