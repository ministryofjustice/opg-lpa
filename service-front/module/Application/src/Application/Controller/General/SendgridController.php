<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Mail\Message as MessageService;
use Zend\Mime\Part;

class SendgridController extends AbstractBaseController
{
    public function bounceAction()
    {

        $blackHoleAddress = 'blackhole@lastingpowerofattorney.service.gov.uk';

        $originalToAddress = $this->request->getPost('to');

        // If sent to this address, don't respond.
        if( !is_string($originalToAddress) || strpos( strtolower($originalToAddress), $blackHoleAddress ) !== false ){
            return $this->getResponse();
        }

        //---
        
        $messageService = new MessageService();
        
        $config = $this->getServiceLocator()->get('config');
        $messageService->addFrom($blackHoleAddress, $config['email']['sender']['default']['name']);

        $userEmail = $this->request->getPost('from');

        $messageService->addCategory('opg');
        $messageService->addCategory('opg-lpa');
        $messageService->addCategory('opg-lpa-autoresponse');
        
        if(!$userEmail) {
            return $this->getResponse();
        }
        
        if (preg_match('/\<(.*)\>$/', $userEmail, $matches)) {
            $userEmail = $matches[1];
        }
        
        $messageService->addTo( $userEmail );
        
        $messageService->setSubject( 'This mailbox is not monitored' );
        
        //---
        
        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('bounce.twig')->render([]);
        
        
        //---
        
        $html = new Part( $content );
        $html->type = "text/html";
        
        $mimeMessage = new \Zend\Mime\Message();
        $mimeMessage->setParts([$html]);
        
        $messageService->setBody($mimeMessage);
        
        //---
        
        try {
        
            $this->getServiceLocator()->get('MailTransport')->send($messageService);

            echo 'Email sent';
        
        } catch ( \Exception $e ){
        
            $this->getServiceLocator()->get('Logger')->alert("Failed sending 'This mailbox is not monitored' email to ".$userEmail." due to:\n".$e->getMessage());
        
            return "failed-sending-email";
        
        }
        
        $response = $this->getResponse();
        $response->setStatusCode(200);
        return $response;
    }
}
