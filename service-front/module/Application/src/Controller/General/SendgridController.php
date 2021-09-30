<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;

class SendgridController extends AbstractBaseController
{
    /**
     * No reply email address
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
        $sentFromMailForWindows10 = null;   // null means that we can't make a determination for this

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
        if (
            !is_string($fromAddress) || !is_string($originalToAddress) || strpos(
                strtolower($originalToAddress),
                $this->blackHoleAddress
            ) !== false
        ) {
            $this->getLogger()->err('Sender or recipient missing, or email sent to ' .
                $this->blackHoleAddress . ' - the message message will not be sent to SendGrid', $loggingData);

            return $this->getResponse();
        }

        $token = $this->params()->fromRoute('token');

        if (!$token || $token !== $this->config()['email']['sendgrid']['webhook']['token']) {
            //  Add some info to the logging data
            $loggingData['token'] = $token;

            $this->getLogger()->err('Missing or invalid bounce token used', $loggingData);

            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');

            return $response;
        }

        //  Log unmonitored mailbox emails
        $this->getLogger()->info('Logging SendGrid inbound parse usage - this will not trigger an email', $loggingData);
        echo 'Email not sent - data gathering';

        $response = $this->getResponse();
        $response->setStatusCode(200);

        return $response;
    }
}
