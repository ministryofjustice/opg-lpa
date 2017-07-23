<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Signatures\DateCheck;
use Zend\View\Model\ViewModel;

class DateCheckController extends AbstractLpaController
{
    public function indexAction()
    {
        $viewModel = new ViewModel();

        $lpa = $this->getLpa();

        //  If the return route has been submitted in the post then just use it
        $returnRoute = $this->params()->fromPost('return-route', null);

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if (is_null($returnRoute)) {
            //  If we came from the "LPA complete" route then set the return target back there
            if ($currentRouteName == 'lpa/date-check/complete') {
                $returnRoute = 'lpa/complete';
            }
        }

        //  Create the date check form and set the action
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DateCheckForm', [
            'lpa' => $lpa,
        ]);
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, [
            'lpa-id' => $lpa->id
        ]));

        if ($this->request->isPost()) {
            //  Set the post data in the form and validate it
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                //  Extract the attorney dates from the post data
                $attorneySignatureDates = [];

                foreach ($data as $name => $date) {
                    if (preg_match('/sign-date-(attorney|replacement-attorney)-\d/', $name)) {
                        $attorneySignatureDates[] = $date;
                    }
                }

                $result = DateCheck::checkDates([
                    'donor'                 => $this->dateArrayToTime($data['sign-date-donor']),
                    'donor-life-sustaining' => isset($data['sign-date-donor-life-sustaining']) ? $this->dateArrayToTime($data['sign-date-donor-life-sustaining']) : null,
                    'certificate-provider'  => $this->dateArrayToTime($data['sign-date-certificate-provider']),
                    'attorneys'             => array_map([$this, 'dateArrayToTime'], $attorneySignatureDates),
                ]);

                if ($result === true) {
                    $queryParams = [];

                    if (!empty($returnRoute)) {
                        $queryParams['return-route'] = $returnRoute;
                    }

                    $validUrl = $this->url()->fromRoute('lpa/date-check/valid', [
                        'lpa-id' => $lpa->id,
                    ], [
                        'query' => $queryParams
                    ]);

                    return $this->redirect()->toUrl($validUrl);
                } else {
                    $viewModel->dateError = $result;
                }
            }
        }

        $viewModel->form = $form;
        $viewModel->returnRoute = $returnRoute;

        return $viewModel;
    }


    public function validAction()
    {
        //  Generate the return target from the route
        //  If there is no route then return to the dashboard
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
