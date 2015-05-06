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
            <div class="help-notice application-notice">
	           <p>$message</p>
            </div>
SYSMESS;
        }
    }
}