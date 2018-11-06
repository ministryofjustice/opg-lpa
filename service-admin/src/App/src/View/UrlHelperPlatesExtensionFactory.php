<?php

namespace App\View\Url;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Helper\UrlHelper;

/**
 * Class UrlHelperPlatesExtensionFactory
 * @package App\View\Url
 */
class UrlHelperPlatesExtensionFactory
{
    public function __invoke(ContainerInterface $container) : UrlHelperPlatesExtension
    {
        return new UrlHelperPlatesExtension(
            $container->get(UrlHelper::class)
        );
    }
}