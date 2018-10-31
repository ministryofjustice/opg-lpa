<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class HomeHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $data = [];

        //  Container data
        $data['containerName'] = 'Zend Servicemanager';
        $data['containerDocs'] = 'https://docs.zendframework.com/zend-servicemanager/';

        //  Router data
        $data['routerName'] = 'FastRoute';
        $data['routerDocs'] = 'https://github.com/nikic/FastRoute';

        //  View data
        $data['templateName'] = 'Zend View';
        $data['templateDocs'] = 'https://docs.zendframework.com/zend-view/';

        return new HtmlResponse($this->getTemplateRenderer()->render('app::home-page', $data));
    }
}
