<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class FeedbackHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $data = [];

        //  TODO...

        return new HtmlResponse($this->getTemplateRenderer()->render('app::feedback', $data));
    }
}
