<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Initializers\TemplatingSupportInterface;
use App\Handler\Initializers\TemplatingSupportTrait;
use App\Handler\Initializers\UrlHelperInterface;
use App\Handler\Initializers\UrlHelperTrait;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

/**
 * Class AbstractHandler
 * @package App\Handler
 */
abstract class AbstractHandler implements RequestHandlerInterface, TemplatingSupportInterface, UrlHelperInterface
{
    use TemplatingSupportTrait;
    use UrlHelperTrait;

    /**
     * Redirect to the specified route
     *
     * @param $route
     * @param array $routeParams
     * @param array $queryParams
     * @return Response\RedirectResponse
     */
    protected function redirectToRoute($route, $routeParams = [], $queryParams = [])
    {
        return new Response\RedirectResponse(
            $this->getUrlHelper()->generate($route, $routeParams, $queryParams)
        );
    }
}
