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
     * @param UrlHelper $helper
     */
    public function setUrlHelper(UrlHelper $helper)
    {
        $this->helper = $helper;
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
