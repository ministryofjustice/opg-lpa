<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Zend\View\Model\ViewModel;

class DonorController extends AbstractLpaActorController
{
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        $lpaId = $this->getLpa()->id;

        //  Set the add route in the view model
        $viewModel = new ViewModel(['addRoute' => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId])]);

        $donor = $this->getLpa()->document->donor;

        if ($donor instanceof Donor) {
            //  Set the donor data in the view model
            $viewModel = new ViewModel([
                'donor' => [
                    'name'    => $donor->name,
                    'address' => $donor->address,
                ],
                'editDonorUrl'  => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $lpaId]),
                'nextRoute'     => $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId])
            ]);
        }

        return $viewModel;
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/donor/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        //  If a donor has already been provided then redirect to the main donor screen
        if ($lpa->document->donor instanceof Donor) {
            return $this->redirect()->toRoute('lpa/donor', ['lpa-id'=>$lpaId]);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DonorForm');
        $routeMatch = $this->getEvent()->getRouteMatch();
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList($routeMatch));

        if ($this->request->isPost()) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $donor = new Donor($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setDonor($lpaId, $donor)) {
                    throw new \RuntimeException('API client failed to save LPA donor for id: '.$lpaId);
                }

                return $this->moveToNextRoute();
            }
        } else {
            $this->addReuseDetailsForm($viewModel, $form);
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
        $viewModel->setTemplate('application/donor/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DonorForm');
        $routeMatch = $this->getEvent()->getRouteMatch();
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList($routeMatch));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $postData['canSign'] = (bool) $postData['canSign'];

            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $donor = new Donor($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setDonor($lpaId, $donor)) {
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
