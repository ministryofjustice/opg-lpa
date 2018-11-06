<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class UserHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $data = [];

        //  TODO...

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user', $data));
    }
}
