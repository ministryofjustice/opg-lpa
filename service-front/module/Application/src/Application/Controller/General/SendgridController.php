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

        //  Get the additional data from the sendgrid inbound parse post
        $subject = $this->request->getPost('subject');
        $spamScore = $this->request->getPost('spam_score');
        $emailText = $this->request->getPost('text');

        //  Check the email text to see if the message contains the text "Sent from Mail for Windows 10"
        $sentFromMailForWindows10 = null;   //  null means that we can't make a determination for this

        if (is_string($emailText)) {
            $sentFromMailForWindows10 = !(strpos($emailText, 'Sent from Mail for Windows 10') === false);
        }

        //  Form the basic logging data
        $loggingData = [
            'from-address'          => $fromAddress,
            'to-address'            => $originalToAddress,
            'subject'               => $subject,
            'spam-score'            => $spamScore,
            'sent-from-windows-10'  => $sentFromMailForWindows10,
        ];

        //  If there is no from email address, or the user has responded to the blackhole email address then do nothing
        if (!is_string($fromAddress) || !is_string($originalToAddress) || strpos(strtolower($originalToAddress), $this->blackHoleAddress) !== false) {
            $this->log()->err('Sender or recipient missing, or email sent to ' . $this->blackHoleAddress . ' - the message message will not be sent to SendGrid', $loggingData);

            return $this->getResponse();
        }

        $config = $this->getServiceLocator()->get('config');
        $emailConfig = $config['email'];

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
//            $this->getServiceLocator()
//                 ->get('MailTransport')
//                 ->send($messageService);
//
//            echo 'Email sent';

            //  Unmonitored mailbox emails will not be sent temporarily while we monitor the usage (and abuse!) of this end point
            //  For now just log the data from the email
            $this->log()->info('Logging SendGrid inbound parse usage - this will not trigger an email', $loggingData);

            echo 'Email not sent - data gathering';
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
