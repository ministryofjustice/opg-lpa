<?php
namespace Application\Controller\General;

use DateTime, DateTimeZone;

use Application\Controller\AbstractBaseController;

use Zend\View\Model\ViewModel;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MessageService;

class NotificationsController extends AbstractBaseController {

    public function expiryNoticeAction(){

        $config = $this->getServiceLocator()->get('config');

        //---

        $token = $this->request->getHeader('Token');

        if( !$token || $token->getFieldValue() !== $config['account-cleanup']['notification']['token'] ){

            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');
            return $response;

        }

        //---

        $posts = $this->request->getPost();

        if( !isset($posts['Username']) || !isset($posts['Type']) || !isset($posts['Date']) ){

            $response = $this->getResponse();
            $response->setStatusCode(400);
            $response->setContent('Missing parameters');
            return $response;

        }

        //---

        $deletionDate = new DateTime( $posts['Date'] );

        if( $deletionDate < (new DateTime('+48 hours')) ){

            $response = $this->getResponse();
            $response->setStatusCode(400);
            $response->setContent('Date must be at least 48 hours in the future.');
            return $response;

        }

        //---

        $email = new MessageService();

        $email->addTo( $posts['Username'] );

        //--

        $email->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        //---

        $email->addCategory('opg');
        $email->addCategory('opg-lpa');
        $email->addCategory('opg-lpa-notification');
        $email->addCategory('opg-lpa-notification-'.$posts['Type']);

        //--


        switch($posts['Type']){
            case '1-week-notice':

                $email->setSubject( 'Final reminder: do you still need your online LPA account?' );

                break;
            case '1-month-notice':

                $email->setSubject( 'Do you still need your online lasting power of attorney account?' );

                break;
            default:
                $response = $this->getResponse();
                $response->setStatusCode(400);
                $response->setContent('Unknown type');
                return $response;
        }


        //---

        $template = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('account-deletion-notification.twig');

        $content = $template->render([
            'deletionDate' => $deletionDate
        ]);

        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html]);

        $email->setBody($mimeMessage);

        //---

        $sendAt = new DateTime('today 11am', new DateTimeZone('Europe/London'));

        // If now is before the time above, defer delivery of the email until that time...
        if ($sendAt->getTimestamp() > time()) {
            //The call to time() above can't be mocked so ignoring this line until this code is refactored
            // @codeCoverageIgnoreStart
            $email->setSendAt($sendAt->getTimestamp());
        }
        // @codeCoverageIgnoreEnd

        //---

        $response = $this->getResponse();

        try {

            $this->getServiceLocator()->get('MailTransport')->send($email);

            $response->setContent('Notification received');

        } catch ( \Exception $e ){

            $this->getLogger()->alert("Failed sending expiry notification email to ".$posts['Username']." due to: ".$e->getMessage());

            $response->setStatusCode(500);
            $response->setContent('Error receiving notification');

        }

        return $response;

    } // function

} // class
