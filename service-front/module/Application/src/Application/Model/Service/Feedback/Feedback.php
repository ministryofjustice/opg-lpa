<?php
namespace Application\Model\Service\Feedback;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

class Feedback implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    
    public function sendMail($data) {
        
        //-------------------------------
        // Send the email
        
        $this->getServiceLocator()->get('Logger')->info(
            'Sending feedback email',
            $data
        );
        
        $message = new MailMessage();
        
        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['feedback']['address'], $config['email']['sender']['feedback']['name']);
        
        $message->addTo( 
            $this->getServiceLocator()->get('Config')['sendFeedbackEmailTo']
        );
        
        $message->setSubject( 'LPA v2 User Feedback' );
        
        //---
        
        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-registration');
        
        $data['sentTime'] = date('Y/m/d H:i:s');
        //---
        
        // Load the content from the view and merge in our variables...
        $content = $this->getServiceLocator()->get('EmailPhpRenderer')->render('feedback', $data);
        
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
        
            $this->getServiceLocator()->get('Logger')->err(
                'Failed to send feedback email',
                $data
            );
            
            return "failed-sending-email";
        
        }
        
        return true;
    }
}
