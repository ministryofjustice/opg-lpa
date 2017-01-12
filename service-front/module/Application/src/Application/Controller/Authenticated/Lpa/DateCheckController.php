<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Signatures\DateCheck;
use Zend\View\Model\ViewModel;

class DateCheckController extends AbstractLpaController
{
    protected $contentHeader = 'blank-header-partial.phtml';

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DateCheckForm', [
            'lpa' => $lpa,
        ]);

        //  Determine the return route
        $fromPage = $this->params()->fromRoute('from-page');

        $route = 'user/dashboard';
        $params = [];

        if ($fromPage == 'complete') {
            $route = 'lpa/complete';
            $params = ['lpa-id' => $lpa->get('id')];
        }

        $returnRoute = $this->url()->fromRoute($route, $params);

        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $returnRoute = $post['returnRoute'];

            $form->setData($post);

            $postArray = $post->toArray();

            if ($form->isValid()) {
                $attorneySignatureDates = [];

                foreach ($postArray as $name => $date) {
                    if (preg_match('/sign-date-(attorney|replacement-attorney)-\d/', $name)) {
                        $attorneySignatureDates[] = $date;
                    }
                }

                $result = DateCheck::checkDates([
                    'donor'                 => $postArray['sign-date-donor'],
                    'donor-life-sustaining' => $postArray['sign-date-donor-life-sustaining'] ?: null,
                    'certificate-provider'  => $postArray['sign-date-certificate-provider'],
                    'attorneys'             => $attorneySignatureDates,
                ]);

                if ($result === true) {
                    $viewParams['valid'] = true;
                } else {
                    $viewParams['dateError'] = $result;
                }
            }
        }

        $viewParams['form'] = $form;
        $viewParams['returnRoute'] = $returnRoute;

        return new ViewModel($viewParams);
    }
}
