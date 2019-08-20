<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Zend\View\Model\ViewModel;

class DonorController extends AbstractLpaActorController
{
    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;

        //  Set the add route in the view model
        $viewModel = new ViewModel();
        $viewModel->addUrl = $this->url()->fromRoute('lpa/donor/add', ['lpa-id' => $lpaId]);

        if ($this->getLpa()->document->donor instanceof Donor) {
            //  Determine the next route
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            $nextRoute = $this->getFlowChecker()->nextRoute($currentRouteName);

            //  Set the donor data in the view model
            $viewModel->editUrl = $this->url()->fromRoute('lpa/donor/edit', ['lpa-id' => $lpaId]);
            $viewModel->nextUrl =  $this->url()->fromRoute($nextRoute, ['lpa-id' => $lpaId], $this->getFlowChecker()->getRouteOptions($nextRoute));
        }

        return $viewModel;
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/authenticated/lpa/donor/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Execute the parent check function to determine what reuse options might be available and what should happen
        $reuseRedirect = $this->checkReuseDetailsOptions($viewModel);

        if (!is_null($reuseRedirect)) {
            return $reuseRedirect;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        //  If a donor has already been provided then redirect to the main donor screen
        if ($lpa->document->donor instanceof Donor) {
            $route = 'lpa/donor';

            return $this->redirect()->toRoute($route, ['lpa-id' => $lpaId], $this->getFlowChecker()->getRouteOptions($route));
        }

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\DonorForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/donor/add', ['lpa-id' => $lpaId]));
        $form->setActorData('donor', $this->getActorsList());

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $donor = new Donor($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setDonor($lpa, $donor)) {
                    throw new \RuntimeException('API client failed to save LPA donor for id: '.$lpaId);
                }

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/donor');

        return $viewModel;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/authenticated/lpa/donor/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\DonorForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/donor/edit', ['lpa-id' => $lpaId]));
        $form->setActorData('donor', $this->getActorsList());

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $postData['canSign'] = (bool) $postData['canSign'];

            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $donor = new Donor($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setDonor($lpa, $donor)) {
                    throw new \RuntimeException('API client failed to update LPA donor for id: '.$lpaId);
                }

                //  Attempt to update the LPA correspondent too
                $this->updateCorrespondentData($donor);

                return $this->moveToNextRoute();
            }
        } else {
            $donor = $lpa->document->donor->flatten();
            $dob = $lpa->document->donor->dob->date;

            $donor['dob-date'] = [
                'day'   => $dob->format('d'),
                'month' => $dob->format('m'),
                'year'  => $dob->format('Y'),
            ];

            $form->bind($donor);
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/donor');

        return $viewModel;
    }
}
