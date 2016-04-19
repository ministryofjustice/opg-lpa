<?php
namespace ZFConfigDump\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;

use Symfony\Component\VarDumper\Cloner\VarCloner;

use ZFConfigDump\Dumper\CliDumper;

class DumpConfigController extends AbstractActionController {

    public function dumpAction(){

        if (!$this->getRequest() instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from the console');
        }

        //---

        $config = $this->getServiceLocator()->get('Config');

        //---

        // By default, hide the following common keys.
        // @todo - add flags to allow these to be included.

        $hide = [
            'controllers',
            'service_manager',
            'view_manager',
            'email_view_manager',
            'view_helpers',
            'router',
            'console'
        ];

        foreach($hide as $key){
            unset($config[$key]);
        }

        //---

        $filter = $this->getRequest()->getParam('filter');

        if( $filter ){

            $filters = explode('.', $filter);

            foreach( $filters as $key ){

                if( array_key_exists( $key, $config ) ){
                    $config = $config[$key];
                } else {
                    die("Unable to find config value for key {$filter}\n");
                }

            }

        }

        (new CliDumper)->dump(
            (new VarCloner)->cloneVar( $config )
        );

    }

}
