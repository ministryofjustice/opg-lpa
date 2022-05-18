<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class RouteName extends AbstractHelper
{
    /** @var array */
    private $routes;

    /**
     * @param string|null $currentRoute
     * @param string|null $previousRoute
     */
    public function __construct(?string $currentRoute, ?string $previousRoute)
    {
        $this->routes = [
            'current'   => $currentRoute ?? '',
            'previous'  => $previousRoute ?? ''
        ];
    }

    public function __invoke()
    {
        return $this->routes;
    }
}
