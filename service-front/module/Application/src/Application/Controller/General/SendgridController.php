<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Mail\Message as MessageService;
use Zend\Mime\Part;

class SendgridController extends AbstractBaseController
{
    public function bounceAction()
    {

        $config = $this->getServiceLocator()->get('config');

        //---

        $token = $this->params()->fromRoute('token');

        if( !$token || $token !== $config['email']['sendgrid']['webhook']['token'] ){
            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');
            return $response;
        }

        //---

        $blackHoleAddress = 'blackhole@lastingpowerofattorney.service.gov.uk';

        $originalToAddress = $this->request->getPost('to');

        // If sent to this address, don't respond.
        if( !is_string($originalToAddress) || strpos( strtolower($originalToAddress), $blackHoleAddress ) !== false ){
            return $this->getResponse();
        }

        //---

        $messageService = new MessageService();

        $messageService->addFrom($blackHoleAddress, $config['email']['sender']['default']['name']);

        $userEmail = $this->request->getPost('from');

        $messageService->addCategory('opg');
        $messageService->addCategory('opg-lpa');
        $messageService->addCategory('opg-lpa-autoresponse');

        if(!$userEmail) {
            return $this->getResponse();
        }

        if (preg_match('/\<(.*)\>$/', $userEmail, $matches)) {
            $userEmail = $matches[1];
        }

        $messageService->addTo( $userEmail );

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('bounce.twig')->render([]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $messageService->setSubject($matches[1]);
        } else {
            $messageService->setSubject( 'This mailbox is not monitored' );
        }

        //---

        $html = new Part( $content );
        $html->type = "text/html";

        $mimeMessage = new \Zend\Mime\Message();
        $mimeMessage->setParts([$html]);

        $messageService->setBody($mimeMessage);

        //---

        try {

            $this->getServiceLocator()->get('MailTransport')->send($messageService);

            echo 'Email sent';

        } catch ( \Exception $e ){

            $this->log()->alert("Failed sending 'This mailbox is not monitored' email to ".$userEmail." due to:\n".$e->getMessage());

            return "failed-sending-email";

        }

        $response = $this->getResponse();
        $response->setStatusCode(200);
        return $response;
    }
}
