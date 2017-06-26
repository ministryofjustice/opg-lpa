<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Application\Form\General\FeedbackForm;
use Zend\Session\Container;

class FeedbackController extends AbstractBaseController
{
    public function indexAction()
    {
        $container = new Container('feedback');

        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\General\FeedbackForm');

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $feedbackService = $this->getServiceLocator()
                                        ->get('Feedback');

                $data = $form->getData();

                $result = $feedbackService->sendMail([
                    'rating'    => $data['rating'],
                    'details'   => $data['details'],
                    'email'     => $data['email'],
                    'phone'     => $data['phone'],
                    'agent'     => $_SERVER['HTTP_USER_AGENT'],
                    'fromPage'  => $container->feedbackLinkClickedFromPage,
                ]);

                if ($result === true) {
                    $successView = new ViewModel();
                    $successView->setTemplate('application/feedback/thankyou');

                    return $successView;
                } else {
                    throw new \Exception('Error sending feedback email');
                }
            }
        } else {
            $container->setExpirationHops(1);

            if ($this->getRequest()->getHeader('Referer') != false) {
                $container->feedbackLinkClickedFromPage = $this->getRequest()->getHeader('Referer')->uri()->getPath();
            } else {
                $container->feedbackLinkClickedFromPage = 'Unknown';
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
