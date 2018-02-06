<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Mail\Transport\MailTransport;
use DateTime;
use DateTimeZone;
use Exception;

class NotificationsController extends AbstractBaseController
{
    /**
     * @var MailTransport
     */
    private $mailTransport;

    public function expiryNoticeAction()
    {
        $token = $this->request->getHeader('Token');

        if (!$token || $token->getFieldValue() !== $this->config()['account-cleanup']['notification']['token']) {
            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');

            return $response;
        }

        $posts = $this->request->getPost();

        if (!isset($posts['Username']) || !isset($posts['Type']) || !isset($posts['Date'])) {
            $response = $this->getResponse();
            $response->setStatusCode(400);
            $response->setContent('Missing parameters');

            return $response;
        }

        $emailRef = '';
        $notificationType = $posts['Type'];

        if ($notificationType == '1-week-notice') {
            $emailRef = MailTransport::EMAIL_DELETE_NOTIFICATION_1_WEEK;
        } elseif ($notificationType == '1-month-notice') {
            $emailRef = MailTransport::EMAIL_DELETE_NOTIFICATION_1_MONTH;
        } else {
            $response = $this->getResponse();
            $response->setStatusCode(400);
            $response->setContent('Unknown type');

            return $response;
        }

        $deletionDate = new DateTime($posts['Date']);

        if ($deletionDate < new DateTime('+48 hours')) {
            $response = $this->getResponse();
            $response->setStatusCode(400);
            $response->setContent('Date must be at least 48 hours in the future.');

            return $response;
        }

        $to = $posts['Username'];

        $data = [
            'deletionDate' => $deletionDate,
            'notificationType' => $notificationType,
        ];

        $sendAt = new DateTime('today 11am', new DateTimeZone('Europe/London'));

        //  If the time above is after 11am today then send the email straight away
        //  Otherwise defer delivery to that time
        if ($sendAt->getTimestamp() > time()) {
            //The call to time() above can't be mocked so ignoring this line until this code is refactored
            //  @codeCoverageIgnoreStart
            $sendAt = null;
        }
        //  @codeCoverageIgnoreEnd

        $response = $this->getResponse();

        try {
            $this->mailTransport->sendMessageFromTemplate($to, $emailRef, $data, $sendAt);

            $response->setContent('Notification received');
        } catch (Exception $e) {
            $this->getLogger()->alert('Failed sending expiry notification email to ' . $posts['Username'] . ' due to: ' . $e->getMessage());

            $response->setStatusCode(500);
            $response->setContent('Error receiving notification');
        }

        return $response;
    }

    public function setMailTransport(MailTransport $mailTransport)
    {
        $this->mailTransport = $mailTransport;
    }
}
