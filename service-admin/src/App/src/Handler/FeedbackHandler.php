<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\Feedback;
use App\Handler\Traits\JwtTrait;
use App\Service\Feedback\FeedbackService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class FeedbackHandler extends AbstractHandler
{
    use JwtTrait;

    /**
     * @var FeedbackService
     */
    private $feedbackService;

    /**
     * FeedbackHandler constructor.
     * @param FeedbackService $feedbackService
     */
    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new Feedback([
            'csrf' => $this->getTokenData('csrf'),
        ]);

        $feedback = [];

        if ($request->getMethod() == 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  TODO
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::feedback', [
            'form'      => $form,
            'feedback'  => $feedback,
        ]));
    }
}
