<?php

namespace Application\Controller\General;
// Work around due to issues with code base, unless in place this displays warnings when endpoint it accessed
// Ticket has been created in the backlog to address this LPAL-267
error_reporting(E_ERROR);

use Application\Model\Service\System\Status;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class PingController extends AbstractBaseController
{
    /**
     * @var Status
     */
    private $statusService;

    public function indexAction(){

        $result = $this->statusService->check();

        return new ViewModel( [ 'status'=>$result ] );

    }

    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     */
    //This method contains calls that can't be mocked so ignoring until this code is refactored
    // @codeCoverageIgnoreStart
    public function elbAction(){

        $response = $this->getResponse();

        //---

        // Include a sanity check on ssl certs

        $path = '/etc/ssl/certs/b204d74a.0';

        if( !is_link($path) | !is_readable($path) || !is_link($path) || empty(file_get_contents($path)) ){

            $response->setStatusCode(500);
            $response->setContent('Sad face');

        } else {

            $response->setContent('Happy face');

        }

        //---

        return $response;

    } // function
    // @codeCoverageIgnoreEnd

    public function jsonAction(){

        $result = $this->statusService->check();

        $result['tag'] = $this->config()['version']['tag'];

        return new JsonModel( $result );

    }

    public function pingdomAction(){

        $start = round(microtime(true) * 1000);

        $response = new \Laminas\Http\Response();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string("<?xml version='1.0' ?><pingdom_http_custom_check><status></status><response_time></response_time></pingdom_http_custom_check>");

        //---

        $result = $this->statusService->check();

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

    public function setStatusService(Status $statusService)
    {
        $this->statusService = $statusService;
    }
} // class
