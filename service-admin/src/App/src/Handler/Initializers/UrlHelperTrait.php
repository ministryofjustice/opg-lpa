<?php

namespace App\Handler\Initializers;

use Mezzio\Helper\UrlHelper;
use UnexpectedValueException;

/**
 * Getter and Setter, implementing the UrlHelperInterface.
 *
 * Class UrlHelperTrait
 * @package App\Handler\Initializers
 */
trait UrlHelperTrait
{
    /**
     * @var UrlHelper
     */
    private $helper;

    /**
     * @param UrlHelper $template
     */
    public function setUrlHelper(UrlHelper $template)
    {
        $this->helper = $template;
        return $this;
    }

    /**
     * @return UrlHelper
     */
    public function getUrlHelper(): UrlHelper
    {
        if (!$this->helper instanceof UrlHelper) {
            throw new UnexpectedValueException('UrlHelper not set');
        }

        return $this->helper;
    }
}
