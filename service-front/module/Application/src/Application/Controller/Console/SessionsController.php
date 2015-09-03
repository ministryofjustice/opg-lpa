<?php
namespace Application\Controller\Console;

use Zend\Mvc\Controller\AbstractActionController;

use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;

class SessionsController extends AbstractActionController {

    public function gcAction(){

        $saveHandler = $this->getServiceLocator()->get('SessionManager')->getSaveHandler();


        if( $saveHandler instanceof DynamoDbSessionHandler ){

            $saveHandler->garbageCollect();

        }

        die("Done\n");

    }

} // class
