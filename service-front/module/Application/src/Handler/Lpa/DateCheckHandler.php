<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Signatures\DateCheck;
use Application\Service\DateCheckViewModelHelper;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DateCheckHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $currentRouteName = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        // If the return route has been submitted in the post then just use it
        $postData = $request->getParsedBody() ?? [];
        if (!is_array($postData)) {
            $postData = [];
        }
        $returnRoute = $postData['return-route'] ?? null;

        if (is_null($returnRoute)) {
            // If we came from the "LPA complete" route then set the return target back there
            if ($currentRouteName === 'lpa/date-check/complete') {
                $returnRoute = 'lpa/complete';
            }
        }

        /** @var \Application\Form\Lpa\DateCheckForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\DateCheckForm', [
            'lpa' => $lpa,
        ]);

        $form->setAttribute('action', $this->urlHelper->generate(
            $currentRouteName,
            ['lpa-id' => $lpa->id],
        ));

        $helperResult = DateCheckViewModelHelper::build($lpa);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $form->setData($postData);

            if ($form->isValid()) {
                /** @var array $data */
                $data = $form->getData();

                // Extract the attorney dates from the post data
                $attorneySignatureDates = [];

                foreach ($data as $name => $date) {
                    if (preg_match('/sign-date-(attorney|replacement-attorney)-\d/', $name)) {
                        $attorneySignatureDates[] = $date;
                    }
                }

                // Extract the applicant dates from the post data
                $applicantSignatureDates = [];

                foreach ($data as $name => $date) {
                    if (preg_match('/sign-date-applicant-\d/', $name)) {
                        $applicantSignatureDates[] = $date;
                    }
                }

                $signDateDonorLifeSustaining = isset($data['sign-date-donor-life-sustaining']) ?
                    $this->dateArrayToTime($data['sign-date-donor-life-sustaining']) : null;

                // is the donor the applicant?
                $donorIsApplicant = false;
                if (count($helperResult['applicants']) === 1) {
                    $applicant = $helperResult['applicants'][0];
                    $donorIsApplicant = ($applicant['isDonor'] && $applicant['isHuman']);
                }

                $result = DateCheck::checkDates(
                    [
                        'sign-date-donor' => $this->dateArrayToTime($data['sign-date-donor']),
                        'sign-date-donor-life-sustaining' => $signDateDonorLifeSustaining,
                        'sign-date-certificate-provider' =>
                            $this->dateArrayToTime($data['sign-date-certificate-provider']),
                        'sign-date-attorneys' => array_map([$this, 'dateArrayToTime'], $attorneySignatureDates),
                        'sign-date-applicants' => array_map([$this, 'dateArrayToTime'], $applicantSignatureDates),
                    ],
                    empty($lpa->completedAt),
                    [
                        'canSign' => boolval($lpa->document->donor->canSign),
                        'isApplicant' => $donorIsApplicant,
                    ],
                );

                if ($result === true) {
                    $queryParams = [];

                    if (!empty($returnRoute)) {
                        $queryParams['return-route'] = $returnRoute;
                    }

                    $validUrl = $this->urlHelper->generate(
                        'lpa/date-check/valid',
                        ['lpa-id' => $lpa->id],
                        ['query' => $queryParams],
                    );

                    return new RedirectResponse($validUrl);
                }

                $form->setMessages($result);
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/date-check/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'returnRoute' => $returnRoute,
                ],
                $helperResult,
            )
        );

        return new HtmlResponse($html);
    }

    /**
     * @param array $dateArray
     * @return int|false
     */
    private function dateArrayToTime(array $dateArray): int|false
    {
        $day = $dateArray['day'];
        $month = $dateArray['month'];
        $year = $dateArray['year'];
        return strtotime("$day-$month-$year");
    }
}
