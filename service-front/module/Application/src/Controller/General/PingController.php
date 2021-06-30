<?php

namespace Application\Controller\General;

use Application\Model\Service\System\Status;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class PingController extends AbstractActionController
{
    /**
     * @var Status
     */
    private $statusService;

    public function indexAction(){
        $result = $this->statusService->check();
        return new ViewModel(['status'=>$result]);
    }

    public function jsonAction(): JsonModel {
        $result = $this->statusService->check();
        $result['tag'] = $this->config['version']['tag'];
        return new JsonModel($result);
    }

    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     *
     * @return \Laminas\Stdlib\ResponseInterface
     */
    public function elbAction(): \Laminas\Stdlib\ResponseInterface {
        $response = $this->getResponse();

        $path = '/etc/ssl/certs/b204d74a.0';

        if (!is_link($path) | !is_readable($path) || !is_link($path) || empty(file_get_contents($path))){

            $response->setStatusCode(500);
            $response->setContent('Sad face');
        }
        else {
            $response->setContent('Happy face');
        }

        return $response;
    }

    public function pingdomAction(): \Laminas\Http\Response {
        $start = round(microtime(true) * 1000);

        $response = new \Laminas\Http\Response();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string("<?xml version='1.0' ?><pingdom_http_custom_check><status></status><response_time></response_time></pingdom_http_custom_check>");

        $result = $this->statusService->check();

        if ($result['ok'] == true) {
            $xml->status = 'OK';
        }
        else {
            $response->setStatusCode(500);
            $xml->status = 'ERROR';
        }

        $end = round(microtime(true) * 1000);

        $xml->response_time = ( $end - $start ) / 1000;

        $response->setContent($xml->asXML());

        return $response;
    }

    public function setStatusService(Status $statusService): void
    {
        $this->statusService = $statusService;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
} // class
