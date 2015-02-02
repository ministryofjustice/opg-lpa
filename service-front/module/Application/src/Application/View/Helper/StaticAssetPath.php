<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class StaticAssetPath extends AbstractHelper
{
    public function __invoke($path)
    {
        return $path;
    }
}