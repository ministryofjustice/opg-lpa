<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Http\Header\Referer;
use Laminas\Http\Response as HttpResponse;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use Throwable;

class FeedbackController extends AbstractBaseController
{
    use LoggerTrait;

    private ?Feedback $feedbackService;
    private ?SessionUtility $sessionUtility;

    public function __construct(
        AbstractPluginManager $formElementManager,
        SessionManagerSupport $sessionManagerSupport,
        AuthenticationService $authenticationService,
        array $config,
        ?Feedback $feedbackService = null,
        ?SessionUtility $sessionUtility = null,
    ) {
        parent::__construct(
            $formElementManager,
            $sessionManagerSupport,
            $authenticationService,
            $config,
        );

        $this->feedbackService = $feedbackService;
        $this->sessionUtility  = $sessionUtility;
    }

    /**
     * Laminas indexAction() is not supposed to return an HttpResponse.
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return HttpResponse|ViewModel
     * @throws \Exception
     */
    public function indexAction()
    {
        $container = new Container('feedback'); // needed for setExpirationHops

        $form = $this->getFormElementManager()
            ->get('Application\Form\General\FeedbackForm');
        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                $data['agent'] = htmlentities($_SERVER['HTTP_USER_AGENT']);

                $fromPage = $this->sessionUtility->getFromMvc(
                    'feedback',
                    'feedbackLinkClickedFromPage'
                );
                $data['fromPage'] = is_string($fromPage) ? $fromPage : 'Unknown';

                try {
                    $this->feedbackService->add($data);
                } catch (FeedbackValidationException $ex) {
                    return new ViewModel([
                        'form'  => $form,
                        'error' => $ex->getMessage(),
                    ]);
                } catch (Throwable $ex) {
                    $this->getLogger()->error('API exception while adding feedback from Feedback service', [
                        'error_code' => 'ADDING_FEEDBACK_FAILED',
                        'status' => $ex->getStatusCode(),
                        'exception' => $ex,
                    ]);

                    return new ViewModel([
                        'form'  => $form,
                        'error' => 'An error occurred while submitting feedback',
                    ]);
                }

                $fromPage = $this->sessionUtility->getFromMvc(
                    'feedback',
                    'feedbackLinkClickedFromPage'
                );

                $options = (is_null($fromPage) ? [] : [
                    'query' => [
                        'returnTarget' => urlencode($fromPage),
                    ],
                ]);

                return $this->redirect()->toRoute('feedback-thanks', [], $options);
            }
        } else {
            $container->setExpirationHops(1);

            /** @var Referer $referer */
            $referer = $request->getHeader('Referer');

            $fromPage = null;

            if ($referer !== false) {
                $fromPage = $referer->uri()->getPath();
            }

            $this->sessionUtility->setInMvc(
                'feedback',
                'feedbackLinkClickedFromPage',
                $fromPage
            );
        }

        return new ViewModel([
            'form' => $form,
        ]);
    }

    /**
     * @return HttpResponse|ViewModel
     */
    public function thanksAction()
    {
        $returnTarget = urldecode($this->params()->fromQuery('returnTarget'));

        if (empty($returnTarget)) {
            $returnTarget = $this->url()->fromRoute('home');
        }

        return new ViewModel([
            'returnTarget' => $returnTarget,
        ]);
    }

    public function setFeedbackService(Feedback $feedbackService): void
    {
        $this->feedbackService = $feedbackService;
    }

    public function setSessionUtility(SessionUtility $sessionUtility): void
    {
        $this->sessionUtility = $sessionUtility;
    }
}
