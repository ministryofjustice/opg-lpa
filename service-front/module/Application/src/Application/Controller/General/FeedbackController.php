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

        $form = new FeedbackForm();

        $type = $form->get('rating');
        $typeValueOptions = $type->getOptions()['value_options'];

        $typeValueOptions['very-satisfied']['label'] = 'Very satisfied';
        $typeValueOptions['satisfied']['label'] = 'Satisfied';
        $typeValueOptions['neither-satisfied-or-dissatisfied']['label'] = 'Neither satisfied nor dissatisfied';
        $typeValueOptions['dissatisfied']['label'] = 'Dissatisfied';
        $typeValueOptions['very-dissatisfied']['label'] = 'Very dissatisfied';

        $type->setOptions([
            'value_options' => $typeValueOptions
        ]);

        $model = new ViewModel([
            'form'=>$form
        ]);

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {

                $feedbackService = $this->getServiceLocator()->get('Feedback');
                $data = $form->getData();

                $result = $feedbackService->sendMail([
                    'rating' => $data['rating'],
                    'details' => $data['details'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'agent' => $_SERVER['HTTP_USER_AGENT'],
                    'fromPage' => $container->feedbackLinkClickedFromPage,
                ]);

                if ($result === true) {
                    return (new ViewModel)->setTemplate('application/feedback/thankyou');
                } else {
                    throw new \Exception('Error sending feedback email');
                }
            }
        } else {
            $container->setExpirationHops(1);
            if( $this->getRequest()->getHeader('Referer')  != false ){
                $container->feedbackLinkClickedFromPage = $this->getRequest()->getHeader('Referer')->uri()->getPath();
            } else {
                $container->feedbackLinkClickedFromPage = 'Unknown';
            }
        }

        return $model;
    }
}
