<?php

namespace App\View\Url;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Zend\Expressive\Helper\UrlHelper;

class UrlHelperPlatesExtension implements ExtensionInterface
{
    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * UrlHelperPlatesExtension constructor
     * @param UrlHelper $urlHelper
     */
    public function __construct(UrlHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    public function register(Engine $engine)
    {
        $engine->registerFunction('generateUrl', [$this->urlHelper, 'generate']);
    }
}