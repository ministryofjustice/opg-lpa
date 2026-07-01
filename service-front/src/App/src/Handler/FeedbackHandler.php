<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Date\DateService;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\FeedbackValidationException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class FeedbackHandler implements RequestHandlerInterface
{
    private const int MIN_SUBMISSION_TIME_SECONDS = 3;
    private const SESSION_KEY_FORM_GENERATED_TIME = 'feedback_form_generated_time';
    private const SESSION_KEY_FROM_PAGE = 'feedback_from_page';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly FeedbackService $feedbackService,
        private readonly LoggerInterface $logger,
        private readonly DateService $dateService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        /** @var FormInterface $form */
        $form = $this->formElementManager->get('App\Form\General\FeedbackForm');

        if (strtoupper($request->getMethod()) === 'POST') {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            $formGeneratedTime = $session->get(self::SESSION_KEY_FORM_GENERATED_TIME) ?? 0;
            $session->unset(self::SESSION_KEY_FORM_GENERATED_TIME);

            if ($this->dateService->getNow()->getTimestamp() - $formGeneratedTime < self::MIN_SUBMISSION_TIME_SECONDS) {
                $this->logger->error('Feedback form submitted too quickly, possible bot submission');

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

                $fromPage = $session->get(self::SESSION_KEY_FROM_PAGE);
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

                $fromPage = $session->get(self::SESSION_KEY_FROM_PAGE);

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
            $session->set(
                self::SESSION_KEY_FORM_GENERATED_TIME,
                $this->dateService->getNow()->getTimestamp()
            );

            $referer = $request->getHeaderLine('Referer');
            $fromPage = null;

            if ($referer !== '') {
                $path = parse_url($referer, PHP_URL_PATH);
                if (is_string($path)) {
                    $fromPage = $path;
                }
            }

            $session->set(self::SESSION_KEY_FROM_PAGE, $fromPage);
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
