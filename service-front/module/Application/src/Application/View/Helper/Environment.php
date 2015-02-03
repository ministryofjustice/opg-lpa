<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Environment extends AbstractHelper
{
    public function __invoke()
    {
        // @todo
        return 'development';
    }
}