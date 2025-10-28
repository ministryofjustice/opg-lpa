<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use Laminas\Http\Header\Referer;
use Laminas\Http\Response as HttpResponse;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use Throwable;

class FeedbackController extends AbstractBaseController
{
    use LoggerTrait;

    /** @var Feedback */
    private $feedbackService;

    /**
     * Laminas indexAction() is not supposed to return an HttpResponse.
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return HttpResponse|ViewModel
     * @throws \Exception
     */
    public function indexAction()
    {
        $container = new Container('feedback');

        $form = $this->getFormElementManager()
                     ->get('Application\Form\General\FeedbackForm');

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                //  Inject extra details into the data before passing to the feedback service to send in an email
                $data['agent'] = htmlentities($_SERVER['HTTP_USER_AGENT']);
                $data['fromPage'] = (
                    is_string($container->feedbackLinkClickedFromPage) ?
                        $container->feedbackLinkClickedFromPage : 'Unknown'
                );

                try {
                    $this->feedbackService->add($data);
                } catch (FeedbackValidationException $ex) {
                    return new ViewModel([
                        'form' => $form,
                        'error' => $ex->getMessage(),
                    ]);
                } catch (Throwable $ex) {
                    $message = "API exception while adding feedback from Feedback service: " . $ex->getMessage();

                    $this->getLogger()->error($message, [
                        'trace' => $ex->getTrace(),
                    ]);

                    return new ViewModel([
                        'form' => $form,
                        'error' => 'An error occurred while submitting feedback',
                    ]);
                }

                //  Add any return target to the query params and redirect to thank you page
                $options = (is_null($container->feedbackLinkClickedFromPage) ? [] : [
                    'query' => [
                        'returnTarget' => urlencode($container->feedbackLinkClickedFromPage),
                    ],
                ]);

                return $this->redirect()->toRoute('feedback-thanks', [], $options);
            }
        } else {
            $container->setExpirationHops(1);

            /** @var Referer */
            $referer = $request->getHeader('Referer');

            if ($referer !== false) {
                $container->feedbackLinkClickedFromPage = $referer->uri()->getPath();
            } else {
                $container->feedbackLinkClickedFromPage = null;
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    /**
     * @return HttpResponse|ViewModel
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
