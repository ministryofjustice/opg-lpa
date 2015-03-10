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
        
        $message = new MailMessage();
        
        $message->addFrom('opg@lastingpowerofattorney.service.gov.uk', 'Office of the Public Guardian');
        
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
        
            return "failed-sending-email";
        
        }
        
        return true;
    }
}
