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
        
        $messageService = new MessageService();
        
        $config = $this->getServiceLocator()->get('config');
        $messageService->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);
        
        $userEmail = $this->params()->fromQuery('from');
        
        if(!$userEmail) {
            return $this->getResponse();
        }
        
        if (preg_match('/\<(.*)\>$/', $userEmail, $matches)) {
            $userEmail = $matches[1];
        }
        
        $messageService->addTo( $userEmail );
        
        $messageService->setSubject( 'This mailbox is not monitored' );
        
        //---
        
        // Load the content from the view and merge in our variables...
        $viewModel = new \Zend\View\Model\ViewModel();
        $viewModel->setTemplate('email/bounce.phtml');
        
        $content = $this->getServiceLocator()->get('ViewRenderer')->render( $viewModel );
        
        //---
        
        $html = new Part( $content );
        $html->type = "text/html";
        
        $mimeMessage = new \Zend\Mime\Message();
        $mimeMessage->setParts([$html]);
        
        $messageService->setBody($mimeMessage);
        
        //---
        
        try {
        
            $this->getServiceLocator()->get('MailTransport')->send($messageService);
        
        } catch ( \Exception $e ){
        
            $this->getServiceLocator()->get('Logger')->alert("Failed sending 'This mailbox is not monitored' email to ".$userEmail." due to:\n".$e->getMessage());
        
            return "failed-sending-email";
        
        }
        
        $response = $this->getResponse();
        $response->setStatusCode(200);
        return $response;
    }
}
