<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Mail\Message as MessageService;
use Zend\Mime\Message;
use Zend\Mime\Part;
use Exception;

class SendgridController extends AbstractBaseController
{
    public function bounceAction()
    {
        $token = $this->params()->fromRoute('token');

        $config = $this->getServiceLocator()->get('config');
        $emailConfig = $config['email'];

        if (!$token || $token !== $emailConfig['sendgrid']['webhook']['token']) {
            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');

            return $response;
        }

        $blackHoleAddress = 'blackhole@lastingpowerofattorney.service.gov.uk';

        $fromAddress = $this->request->getPost('from');
        $originalToAddress = $this->request->getPost('to');

        //  If there is no from email address, or the user has responded to the blackhole email address then do nothing
        if (!is_string($fromAddress) || !is_string($originalToAddress) || strpos(strtolower($originalToAddress), $blackHoleAddress) !== false) {
            return $this->getResponse();
        }

        $messageService = new MessageService();
        $messageService->addFrom($blackHoleAddress, $emailConfig['sender']['default']['name']);
        $messageService->addCategory('opg');
        $messageService->addCategory('opg-lpa');
        $messageService->addCategory('opg-lpa-autoresponse');

        if (preg_match('/\<(.*)\>$/', $fromAddress, $matches)) {
            $fromAddress = $matches[1];
        }

        $messageService->addTo($fromAddress);

        //  Set the subject in the message
        $content = $this->getServiceLocator()
                        ->get('TwigEmailRenderer')
                        ->loadTemplate('bounce.twig')
                        ->render([]);

        $subject = 'This mailbox is not monitored';

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $subject = $matches[1];
        }

        $messageService->setSubject($subject);

        //  Set the content in a mime message
        $mimeMessage = new Message();
        $html = new Part($content);
        $html->type = "text/html";
        $mimeMessage->setParts([$html]);

        $messageService->setBody($mimeMessage);

        try {
            $this->getServiceLocator()
                 ->get('MailTransport')
                 ->send($messageService);

            echo 'Email sent';
        } catch (Exception $e) {
            $this->log()->alert("Failed sending '" . $subject . "' email to " . $fromAddress . " due to:\n" . $e->getMessage());

            return "failed-sending-email";
        }

        $response = $this->getResponse();
        $response->setStatusCode(200);

        return $response;
    }
}
