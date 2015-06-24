<?php

namespace Application\Controller\General;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class PingController extends AbstractBaseController {

    public function indexAction()
    {

        return new ViewModel();
    }

    public function jsonAction(){

        $result = $this->getServiceLocator()->get('SiteStatus')->check();

        return new JsonModel( $result );

    }
    
    public function pingdomAction(){

        $start = round(microtime(true) * 1000);

        $response = new \Zend\Http\Response();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string("<?xml version='1.0' ?><pingdom_http_custom_check><status></status><response_time></response_time></pingdom_http_custom_check>");

        //---

        $result = $this->getServiceLocator()->get('SiteStatus')->check();

        if( $result['ok'] == true ){
            $xml->status = 'OK';
        } else {
            $response->setStatusCode(500);
            $xml->status = 'ERROR';
        }

        //---

        $end = round(microtime(true) * 1000);

        $xml->response_time = ( $end - $start ) / 1000;

        $response->setContent($xml->asXML());

        return $response;

    }

} // class
