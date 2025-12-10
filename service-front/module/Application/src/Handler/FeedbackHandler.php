<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Session\Container;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class FeedbackHandler implements RequestHandlerInterface
{
    private TemplateRendererInterface $renderer;
    private FormElementManager $formElementManager;
    private Feedback $feedbackService;
    private SessionUtility $sessionUtility;
    private LoggerInterface $logger;

    public function __construct(
        TemplateRendererInterface $renderer,
        FormElementManager $formElementManager,
        Feedback $feedbackService,
        SessionUtility $sessionUtility,
        LoggerInterface $logger
    ) {
        $this->renderer           = $renderer;
        $this->formElementManager = $formElementManager;
        $this->feedbackService    = $feedbackService;
        $this->sessionUtility     = $sessionUtility;
        $this->logger             = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // needed for setExpirationHops
        $container = new Container('feedback');

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('Application\Form\General\FeedbackForm');

        if (strtoupper($request->getMethod()) === 'POST') {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $data = $form->getData();

                $data['agent'] = htmlentities($_SERVER['HTTP_USER_AGENT'] ?? '');

                $fromPage = $this->sessionUtility->getFromMvc(
                    'feedback',
                    'feedbackLinkClickedFromPage'
                );
                $data['fromPage'] = is_string($fromPage) ? $fromPage : 'Unknown';

                try {
                    $this->feedbackService->add($data);
                } catch (FeedbackValidationException $ex) {
                    $html = $this->renderer->render(
                        'application/general/feedback/index.twig',
                        [
                            'form'  => $form,
                            'error' => $ex->getMessage(),
                        ]
                    );

                    return new HtmlResponse($html);
                } catch (Throwable $ex) {
                    $this->logger->error(
                        'API exception while adding feedback from Feedback service',
                        ['exception' => $ex]
                    );

                    $html = $this->renderer->render(
                        'application/general/feedback/index.twig',
                        [
                            'form'  => $form,
                            'error' => 'An error occurred while submitting feedback',
                        ]
                    );

                    return new HtmlResponse($html);
                }

                // Re-read fromPage for redirect
                $fromPage = $this->sessionUtility->getFromMvc(
                    'feedback',
                    'feedbackLinkClickedFromPage'
                );

                $query = [];
                if (is_string($fromPage) && $fromPage !== '') {
                    $query['returnTarget'] = urlencode($fromPage);
                }

                $location = '/feedback-thanks';
                if (!empty($query)) {
                    $location .= '?' . http_build_query($query);
                }

                return new RedirectResponse($location);
            }
        } else {
            $container->setExpirationHops(1);

            $referer = $request->getHeaderLine('Referer');
            $fromPage = null;

            if ($referer !== '') {
                $path = parse_url($referer, PHP_URL_PATH);
                if (is_string($path)) {
                    $fromPage = $path;
                }
            }

            $this->sessionUtility->setInMvc(
                'feedback',
                'feedbackLinkClickedFromPage',
                $fromPage
            );
        }

        $html = $this->renderer->render(
            'application/general/feedback/index.twig',
            [
                'form' => $form,
            ]
        );

        return new HtmlResponse($html);
    }
}
