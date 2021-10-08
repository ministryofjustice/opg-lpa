<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\Feedback;
use App\Handler\Traits\JwtTrait;
use App\Service\Feedback\FeedbackService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
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
    public function handle(ServerRequestInterface $request): ResponseInterface
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
                    $earliestAvailableTime = new DateTime($result['prunedBefore']);

                    //  Check to see if this is an export request
                    $queryParams = $request->getQueryParams();
                    $isExport = (array_key_exists('export', $queryParams) && $queryParams['export'] === 'true');

                    if ($isExport) {
                        $this->exportToCsv($feedback);
                    }
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
     * @param array<array> $feedbackResults
     * @return array<string, mixed>
     */
    private function parseFeedbackResults(array $feedbackResults): array
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
            $receivedDate = $receivedDate->format('d/m/Y H:i');

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

    /**
     * Export the contents of the data array to a CSV file
     *
     * @param array<string, mixed> $data
     * @return void
     */
    private function exportToCsv(array $data): void
    {
        $filename = sprintf('FeedbackExport_%s_%s.csv', date('Y-m-d'), date('h.i.s'));
        $fullFilename = '/tmp/' . $filename;

        $file = fopen($fullFilename, "w");

        //  Write the headings to the first line
        $headings = array_keys($data);
        fputcsv($file, $headings);

        //  Determine the number of rows
        $numberOfRows = count(end($data));

        //  Loop through the data and extract out the lines using the headings
        for ($i = 0; $i < $numberOfRows; $i++) {
            $thisLineData = [];

            foreach ($headings as $heading) {
                $thisLineData[] = $data[$heading][$i];
            }

            //  Add the line to the CSV
            fputcsv($file, $thisLineData);
        }

        fclose($file);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-disposition: attachment; filename=" . $filename);
        header('Pragma: no-cache');
        header("Expires: 0");

        //  Dump the file and remove the local copy
        fpassthru(fopen($fullFilename, 'rb'));
        unlink($fullFilename);

        exit;
    }
}
