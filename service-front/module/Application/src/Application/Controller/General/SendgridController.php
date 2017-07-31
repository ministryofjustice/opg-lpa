<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Mail\Message as MessageService;
use Zend\Mime\Message;
use Zend\Mime\Part;
use Exception;

class SendgridController extends AbstractBaseController
{
    /**
     * No reply email address to use
     *
     * @var string
     */
    private $blackHoleAddress = 'blackhole@lastingpowerofattorney.service.gov.uk';

    public function bounceAction()
    {
        $fromAddress = $this->request->getPost('from');
        $originalToAddress = $this->request->getPost('to');

        //  Form the basic logging data
        $loggingData = [
            'from-address' => $fromAddress,
            'to-address'   => $originalToAddress,
        ];

        //  If there is no from email address, or the user has responded to the blackhole email address then do nothing
        if (!is_string($fromAddress) || !is_string($originalToAddress) || strpos(strtolower($originalToAddress), $this->blackHoleAddress) !== false) {
            $this->log()->err('Sender or recipient missing, or email sent to ' . $this->blackHoleAddress . ' - the message message will not be sent to SendGrid', $loggingData);

            return $this->getResponse();
        }

        $config = $this->getServiceLocator()->get('config');
        $emailConfig = $config['email'];

        //  Get the email blacklist to check if the destination email address is on that
        $blacklistEmailAddresses = $emailConfig['blacklist'];

        if (in_array($fromAddress, $blacklistEmailAddresses)) {
            $this->log()->err('From email address is blacklisted - the unmonitored email will not be sent to this user', $loggingData);

            return $this->getResponse();
        }

        $token = $this->params()->fromRoute('token');

        if (!$token || $token !== $emailConfig['sendgrid']['webhook']['token']) {
            //  Add some info to the logging data
            $loggingData['token'] = $token;

            $this->log()->err('Missing or invalid bounce token used', $loggingData);

            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');

            return $response;
        }

        //  Log the attempt to compose the email
        $messageService = new MessageService();
        $messageService->addFrom($this->blackHoleAddress, $emailConfig['sender']['default']['name']);
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
            //  Add some info to the logging data
            $loggingData['token'] = $token;
            $loggingData['subject'] = $subject;

            $this->log()->alert("Failed sending email due to:\n" . $e->getMessage(), $loggingData);

            return "failed-sending-email";
        }

        $response = $this->getResponse();
        $response->setStatusCode(200);

        return $response;
    }
}
