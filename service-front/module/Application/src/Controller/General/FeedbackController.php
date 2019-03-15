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

    /**
     * @return \Zend\Http\Response|ViewModel
     * @throws \Exception
     */
    public function indexAction()
    {
        $container = new Container('feedback');

        $form = $this->getFormElementManager()
                     ->get('Application\Form\General\FeedbackForm');

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                //  Inject extra details into the data before passing to the feedback service to send in an email
                $data['agent'] = $_SERVER['HTTP_USER_AGENT'];
                $data['fromPage'] = (is_string($container->feedbackLinkClickedFromPage) ? $container->feedbackLinkClickedFromPage : 'Unknown');

                $result = $this->feedbackService->add($data);

                if ($result === true) {
                    //  Add any return target to the query params and redirect to thank you page
                    $options = (is_null($container->feedbackLinkClickedFromPage) ? [] : [
                        'query' => [
                            'returnTarget' => urlencode($container->feedbackLinkClickedFromPage),
                        ],
                    ]);

                    return $this->redirect()->toRoute('feedback-thanks', [], $options);
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

    /**
     * @return ViewModel
     */
    public function thanksAction()
    {
        $returnTarget = urldecode($this->params()->fromQuery('returnTarget'));

        if (empty($returnTarget)) {
            //  Default to home
            $returnTarget = $this->url()->fromRoute('home');
        }

        return new ViewModel([
            'returnTarget' => $returnTarget,
        ]);
    }

    public function setFeedbackService(Feedback $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }
}
