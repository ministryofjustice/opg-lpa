<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class SystemMessage extends AbstractHelper
{
    public function __invoke()
    {
        $cache = $this->getView()
                      ->getHelperPluginManager()
                      ->getServiceLocator()
                      ->get('Cache');

        $message = trim($cache->getItem('system-message'));

        if ($message != '') {
            echo <<<SYSMESS
            <div class="notice">
              <i class="icon icon-important"></i>
              <p>
                <strong class="bold-small text">$message</strong>
              </p>
            </div>
SYSMESS;
        }
    }
}