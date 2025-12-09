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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment as TwigEnvironment;

class FeedbackHandler implements RequestHandlerInterface
{
    private TwigEnvironment $twig;
    private FormElementManager $formElementManager;
    private Feedback $feedbackService;
    private SessionUtility $sessionUtility;
    private LoggerInterface $logger;

    public function __construct(
        TwigEnvironment $twig,
        FormElementManager $formElementManager,
        Feedback $feedbackService,
        SessionUtility $sessionUtility,
        LoggerInterface $logger
    ) {
        $this->twig               = $twig;
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
                    // Render same view with validation error
                    $html = $this->twig->render(
                        'application/general/feedback/index.twig',
                        [
                            'form'  => $form,
                            'error' => $ex->getMessage(),
                        ]
                    );

                    return new HtmlResponse($html);
                } catch (Throwable $ex) {
                    // Log and show generic error (same behaviour, but using PSR logger)
                    $this->logger->error(
                        'API exception while adding feedback from Feedback service',
                        ['exception' => $ex]
                    );

                    $html = $this->twig->render(
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

                $location = '/feedback/thanks';
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

        $html = $this->twig->render(
            'application/general/feedback/index.twig',
            [
                'form' => $form,
            ]
        );

        return new HtmlResponse($html);
    }
}
