<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Signatures\DateCheck;
use Application\View\DateCheckViewModelHelper;
use Laminas\View\Model\ViewModel;

class DateCheckController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        // If the return route has been submitted in the post then just use it
        $returnRoute = $this->params()->fromPost('return-route', null);

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if (is_null($returnRoute)) {
            // If we came from the "LPA complete" route then set the return target back there
            if ($currentRouteName == 'lpa/date-check/complete') {
                $returnRoute = 'lpa/complete';
            }
        }

        // Create the date check form and set the action
        $form = $this->getFormElementManager()->get('Application\Form\Lpa\DateCheckForm', [
            'lpa' => $lpa,
        ]);

        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, [
            'lpa-id' => $lpa->id
        ]));

        $request = $this->convertRequest();

        if ($request->isPost()) {
            // Set the post data in the form and validate it
            $form->setData($request->getPost());

            if ($form->isValid()) {
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
                    boolval($lpa->document->donor->canSign),
                );

                if ($result === true) {
                    $queryParams = [];

                    if (!empty($returnRoute)) {
                        $queryParams['return-route'] = $returnRoute;
                    }

                    $validUrl = $this->url()->fromRoute(
                        'lpa/date-check/valid',
                        ['lpa-id' => $lpa->id,],
                        ['query' => $queryParams],
                    );

                    return $this->redirect()->toUrl($validUrl);
                } else {
                    $form->setMessages($result);
                }
            }
        }

        $helperResult = DateCheckViewModelHelper::build($lpa);

        $viewModel = new ViewModel([
            'form'        => $form,
            'returnRoute' => $returnRoute,
        ]);

        $viewModel->setVariables([
            'continuationNoteKeys' => $helperResult['continuationNoteKeys'],
            'continuationSheets' => $helperResult['continuationSheets'],
            'applicants' => $helperResult['applicants']
        ]);

        return $viewModel;
    }


    public function validAction()
    {
        // Generate the return target from the route
        // If there is no route then return to the dashboard
        $returnRoute = $this->params()->fromQuery('return-route', null);

        if (is_null($returnRoute)) {
            $returnRoute = 'user/dashboard';
        }

        return new ViewModel([
            'returnRoute' => $returnRoute,
        ]);
    }


    private function dateArrayToTime(array $dateArray)
    {
        $day = $dateArray['day'];
        $month = $dateArray['month'];
        $year = $dateArray['year'];
        return strtotime("$day-$month-$year");
    }
}
