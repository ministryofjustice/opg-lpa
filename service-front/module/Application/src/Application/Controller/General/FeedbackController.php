<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Feedback\Feedback;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class FeedbackController extends AbstractBaseController
{
    /**
     * @var Feedback
     */
    private $feedbackService;

    public function indexAction()
    {
        $container = new Container('feedback');

        $form = $this->getFormElementManager()
                     ->get('Application\Form\General\FeedbackForm');

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $feedbackService = $this->feedbackService;

                $data = $form->getData();

                //  Inject extra details into the data before passing to the feedback service to send in an email
                $data['agent'] = $_SERVER['HTTP_USER_AGENT'];
                $data['fromPage'] = (is_string($container->feedbackLinkClickedFromPage) ? $container->feedbackLinkClickedFromPage : 'Unknown');

                $result = $feedbackService->sendMail($data);

                if ($result === true) {
                    //  Determine the return target to go to from the thank you page
                    $returnTarget = $container->feedbackLinkClickedFromPage;

                    if (is_null($returnTarget)) {
                        $returnTarget = $this->url()->fromRoute('home');
                    }

                    $successView = new ViewModel([
                        'returnTarget' => $returnTarget,
                    ]);

                    $successView->setTemplate('application/general/feedback/thankyou.twig');

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
                $container->feedbackLinkClickedFromPage = null;
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function setFeedbackService(Feedback $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }
}
