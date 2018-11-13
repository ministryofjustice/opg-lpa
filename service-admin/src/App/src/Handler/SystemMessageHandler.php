<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class SystemMessageHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $data = [];

        //  TODO...

        return new HtmlResponse($this->getTemplateRenderer()->render('app::system-message', $data));
    }
}
