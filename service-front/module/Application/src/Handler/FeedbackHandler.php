<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Date\IDateService;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Session\Container;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class FeedbackHandler implements RequestHandlerInterface
{
    use LoggerTrait;

    private const int MIN_SUBMISSION_TIME_SECONDS = 3;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly Feedback $feedbackService,
        private readonly SessionUtility $sessionUtility,
        LoggerInterface $logger,
        private readonly ?IDateService $dateService = null,
    ) {
        $this->setLogger($logger);
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

            $formGeneratedTime = $container->form_generated_time ?? 0;
            unset($container->form_generated_time);

            if ($this->dateService->getNow()->getTimestamp() - $formGeneratedTime < self::MIN_SUBMISSION_TIME_SECONDS) {
                $this->getLogger()->error('Feedback form submitted too quickly, possible bot submission');

                $html = $this->renderer->render(
                    'application/general/feedback/index.twig',
                    [
                        'form'  => $form,
                        'error' => 'An error occurred while submitting feedback. Please try again.',
                    ]
                );

                return new HtmlResponse($html);
            }

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

            $container->form_generated_time = $this->dateService->getNow()->getTimestamp();

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
