<?php
namespace Application\Model\Service\Feedback;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

class Feedback implements ServiceLocatorAwareInterface
{
    use LoggerTrait;
    use ServiceLocatorAwareTrait;
    
    public function sendMail($data) {
        
        //-------------------------------
        // Send the email

        $this->getLogger()->info(
            'Sending feedback email',
            $data
        );
        
        $message = new MailMessage();
        
        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['feedback']['address'], $config['email']['sender']['feedback']['name']);
        
        $message->addTo( 
            $this->getServiceLocator()->get('Config')['sendFeedbackEmailTo']
        );
        
        
        //---
        
        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-feedback');
        
        $data['sentTime'] = date('Y/m/d H:i:s');
        //---
        
        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('feedback.twig')->render($data);
        
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject( 'LPA v2 User Feedback' );
        }
        
        //---
        
        $html = new MimePart( $content );
        $html->type = "text/html";
        
        $body = new MimeMessage();
        $body->setParts([$html]);
        
        $message->setBody($body);
        
        //---
        
        try {
        
            $this->getServiceLocator()->get('MailTransport')->send($message);
        
        } catch ( \Exception $e ){

            $this->getLogger()->err(
                'Failed to send feedback email',
                $data
            );
            
            return "failed-sending-email";
        
        }
        
        return true;
    }
}
