<?php

namespace App\Handler\Initializers;

use Mezzio\Helper\UrlHelper;

/**
 * Declares handler Middleware support for UrlHelper
 *
 * Interface UrlHelperInterface
 * @package App\handler\Initializers
 */
interface UrlHelperInterface
{
    /**
     * @param UrlHelper $template
     */
    public function setUrlHelper(UrlHelper $template);

    /**
     * @return UrlHelper
     */
    public function getUrlHelper(): UrlHelper;
}
