<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\Feedback;
use App\Handler\Traits\JwtTrait;
use App\Service\Feedback\FeedbackService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use DateTime;

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

        $feedback = null;
        $earliestAvailableTime = null;

        if ($request->getMethod() == 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  Get the data from the form
                $startDate = $form->getDateValue('start-date');
                $endDate = $form->getDateValue('end-date');

                $result = $this->feedbackService->search($startDate, $endDate);

                if ($result === false) {
                    //  Set a general error message
                    $form->setMessages([[
                        'There was a problem retrieving the feedback',
                    ]]);
                } else {
                    $feedback = $this->parseFeedbackResults($result['results']);
                    $earliestAvailableTime = $result['prunedBefore'];
                }
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::feedback', [
            'form'                  => $form,
            'feedback'              => $feedback,
            'earliestAvailableTime' => $earliestAvailableTime,
        ]));
    }

    /**
     * Parse the feedback results into a presentable or an exportable format
     *
     * @param array $feedbackResults
     * @return array
     */
    private function parseFeedbackResults(array $feedbackResults)
    {
        $parsedResults = [];

        $ratingMappings = [
            'very-satisfied'                    => 'Very satisfied',
            'satisfied'                         => 'Satisfied',
            'neither-satisfied-or-dissatisfied' => 'Neither satisfied nor dissatisfied',
            'dissatisfied'                      => 'Dissatisfied',
            'very-dissatisfied'                 => 'Very dissatisfied',
        ];

        foreach ($feedbackResults as $feedbackResult) {
            $receivedDate = new DateTime($feedbackResult['received']);
            $receivedDate = $receivedDate->format('H:i d/m/Y');

            $from = $phone = $rating = 'Unknown';

            if (!empty($feedbackResult['email'])) {
                $from = $feedbackResult['email'];
            }

            if (!empty($feedbackResult['phone'])) {
                $phone = $feedbackResult['phone'];
            }

            if (array_key_exists($feedbackResult['rating'], $ratingMappings)) {
                $rating = $ratingMappings[$feedbackResult['rating']];
            }

            $parsedResults['Received'][] = $receivedDate;
            $parsedResults['From'][] = $from;
            $parsedResults['Phone number'][] = $phone;
            $parsedResults['Rating'][] = $rating;
            $parsedResults['Details'][] = $feedbackResult['details'];
            $parsedResults['Page'][] = $feedbackResult['fromPage'];
            $parsedResults['Browser'][] = $feedbackResult['agent'];
        }

        return $parsedResults;
    }
}
