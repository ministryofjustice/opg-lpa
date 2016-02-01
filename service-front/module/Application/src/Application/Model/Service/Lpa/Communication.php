<?php
namespace Application\Model\Service\Lpa;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * A model service class for sending emails on LPA creation and completion.
 * 
 * Class Communication
 * @package Application\Model\Service\Lpa
 */
class Communication implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---
    
    public function sendRegistrationCompleteEmail( Lpa $lpa, $signinUrl )
    {
        $this->sendDelayedSurveyEmail( $lpa, $signinUrl );
        
        return $this->sendEmail('lpa-registration.twig', $lpa, $signinUrl, 'Lasting power of attorney for '.$lpa->document->donor->name.' is ready to register', 'opg-lpa-complete-registration');
        
    }
    
    private function sendDelayedSurveyEmail( Lpa $lpa, $signinUrl ) {
        
        $startDate = '2015-10-12';
        $durationSeconds = 7 * 24 * 3600; // 1 week
        $emailDelaySeconds = 71 * 3600; // 71 hours
        
        $startTimestamp = strtotime($startDate);
        $endTimestamp = $startTimestamp + $durationSeconds;
        
        $now = time();
        
        // Uncomment the second part of the condition to re-enabled time limit
        if ($now > $startTimestamp /* && $now <= $endTimestamp */) {
        
            $sendAt = time() + $emailDelaySeconds;
            $this->sendEmail('feedback-survey.twig', $lpa, $signinUrl, 'Online Lasting Power of Attorney', 'opg-lpa-feedback-survey', $sendAt);
        }
    }
    
    private function sendEmail($emailTemplate, Lpa $lpa, $signinUrl, $subject, $category, $sendAt = null)
    {
    
        //-------------------------------
        // Send the email

        $message = new MailMessage();
        
        $config = $this->getServiceLocator()->get('config');
        
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);
        
        $userSession = $this->getServiceLocator()->get('UserDetailsSession');
        
        $message->addTo( $userSession->user->email->address );
        
        $message->setSubject( $subject );
        
        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory($category);
        $message->setSendAt($sendAt);

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate($emailTemplate)->render([
            'lpa' => $lpa,
            'signinUrl' => $signinUrl,
            'isHealthAndWelfare' => ( $lpa->document->type === \Opg\Lpa\DataModel\Lpa\Document\Document::LPA_TYPE_HW ),
        ]);

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
            
            $this->getServiceLocator()->get('Logger')->alert("Failed sending '".$subject."' email to ".$userSession->user->email->address." due to:\n".$e->getMessage());
            
            return "failed-sending-email";

        }

        return true;

    } // function

} // class
