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

        $posts = $this->request->getPost();

        if( !isset($posts['Username']) || !isset($posts['Type']) ){

            $response = $this->getResponse();
            $response->setStatusCode(400);
            $response->setContent('Missing parameters');
            return $response;

        }

        //---

        $email = new MessageService();

        $email->addTo( $posts['Username'] );

        //--

        $config = $this->getServiceLocator()->get('config');
        $email->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        //---

        $email->addCategory('opg');
        $email->addCategory('opg-lpa');
        $email->addCategory('opg-lpa-notification');
        $email->addCategory('opg-lpa-notification-'.$posts['Type']);

        //--

        switch($posts['Type']){
            case '1-week-notice':
                
                $template = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('notification-1-week-notice.twig');
                
                if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $template, $matches) === 1) {
                    $email->setSubject($matches[1]);
                } else {
                    $email->setSubject( 'If you still need your lasting power of attorney online account, please sign back in in the next seven days' );
                }
               
                break;
            case '1-month-notice':
                $template = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('notification-1-month-notice.twig');
                
                if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $template, $matches) === 1) {
                    $email->setSubject($matches[1]);
                } else {
                    $email->setSubject( 'Do you still need your lasting power of attorney online account?' );
                }
                
                break;
            default:
                $response = $this->getResponse();
                $response->setStatusCode(400);
                $response->setContent('Unknown type');
                return $response;
        }


        //---

        $content = $template->render([]);

        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html]);

        $email->setBody($mimeMessage);

        //---

        $sendAt = new DateTime('today 11am', new DateTimeZone('Europe/London'));

        // If now is before the time above, defer delivery of the email until that time...
        if( $sendAt->getTimestamp() > time() ) {
            $email->setSendAt( $sendAt->getTimestamp() );
        }

        //---

        $response = $this->getResponse();

        try {

            $this->getServiceLocator()->get('MailTransport')->send($email);

            $response->setContent('Notification received');

        } catch ( \Exception $e ){

            $this->getServiceLocator()->get('Logger')->alert("Failed sending expiry notification email to ".$posts['Username']." due to: ".$e->getMessage());

            $response->setStatusCode(500);
            $response->setContent('Error receiving notification');

        }

        return $response;

    } // function

} // class
