<?php

namespace Application\Controller\General;

use Application\Model\Service\System\Status;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class PingController extends AbstractActionController
{
    /** @var array */
    private $config;

    /** @var Status */
    private $statusService;

    public function indexAction()
    {
        $result = $this->statusService->check();
        return new ViewModel(['status' => $result]);
    }

    public function jsonAction()
    {
        $result = $this->statusService->check();
        $result['tag'] = $this->config['version']['tag'];
        return new JsonModel($result);
    }

    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     */
    public function elbAction()
    {
        $response = $this->getResponse();
        $response->setContent('Happy face');
        return $response;
    }

    public function pingdomAction()
    {
        $start = round(microtime(true) * 1000);

        $response = new \Laminas\Http\Response();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string(
            "<?xml version='1.0' ?>" .
            "<pingdom_http_custom_check>" .
            "<status></status>" .
            "<response_time></response_time>" .
            "</pingdom_http_custom_check>"
        );

        $result = $this->statusService->check();

        if (in_array($result['status'], [Status::STATUS_PASS, Status::STATUS_WARN])) {
            $xml->status = 'OK';
        } else {
            $response->setStatusCode(500);
            $xml->status = 'ERROR';
        }

        $end = round(microtime(true) * 1000);

        $xml->response_time = ($end - $start) / 1000;

        $response->setContent($xml->asXML());

        return $response;
    }

    public function setStatusService(Status $statusService)
    {
        $this->statusService = $statusService;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }
}
