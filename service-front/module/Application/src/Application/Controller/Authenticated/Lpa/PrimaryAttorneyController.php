<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Zend\View\Model\ViewModel;

class PrimaryAttorneyController extends AbstractLpaActorController
{

    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;

        $viewModel = new ViewModel();

        $viewModel->addUrl = $this->url()->fromRoute('lpa/primary-attorney/add', ['lpa-id' => $lpaId]);

        if (count($this->getLpa()->document->primaryAttorneys) > 0) {
            $attorneysParams = [];

            foreach ($this->getLpa()->document->primaryAttorneys as $idx => $attorney) {
                $routeParams = [
                    'lpa-id' => $lpaId,
                    'idx' => $idx
                ];

                $attorneysParams[] = [
                    'attorney' => [
                        'address' => $attorney->address,
                        'name'    => $attorney->name,
                    ],
                    'editUrl'          => $this->url()->fromRoute('lpa/primary-attorney/edit', $routeParams),
                    'confirmDeleteUrl' => $this->url()->fromRoute('lpa/primary-attorney/confirm-delete', $routeParams),
                ];
            }

            $viewModel->attorneys = $attorneysParams;

            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            $nextRoute = $this->getFlowChecker()->nextRoute($currentRouteName);

            $viewModel->nextUrl = $this->url()->fromRoute($nextRoute, ['lpa-id' => $lpaId], $this->getFlowChecker()->getRouteOptions($nextRoute));
        }

        return $viewModel;
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/primary-attorney/person-form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Execute the parent check function to determine what reuse options might be available and what should happen
        $reuseRedirect = $this->checkReuseDetailsOptions($viewModel);

        if (!is_null($reuseRedirect)) {
            return $reuseRedirect;
        }

        $lpaId = $this->getLpa()->id;

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/primary-attorney/add', ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList());

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $attorney = new Human($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->addPrimaryAttorney($lpaId, $attorney)) {
                    throw new \RuntimeException('API client failed to add a primary attorney for id: '.$lpaId);
                }

                // set this attorney as applicant if primary attorney acts jointly
                // and applicant are primary attorneys
                $this->resetApplicants();

                $this->cleanUpReplacementAttorneyDecisions();
                $this->cleanUpApplicant();

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  If appropriate add an add trust link route
        if ($this->allowTrust()) {
            $viewModel->switchAttorneyTypeRoute = 'lpa/primary-attorney/add-trust';
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/primary-attorney');

        return $viewModel;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpaId = $this->getLpa()->id;
        $lpaDocument = $this->getLpa()->document;

        $attorneyIdx = $this->params()->fromRoute('idx');

        if (array_key_exists($attorneyIdx, $lpaDocument->primaryAttorneys)) {
            $attorney = $lpaDocument->primaryAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        if ($attorney instanceof Human) {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
            $form->setExistingActorNamesData($this->getActorsList($attorneyIdx));
            $viewModel->setTemplate('application/primary-attorney/person-form.twig');
        } else {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
            $viewModel->setTemplate('application/primary-attorney/trust-form.twig');
        }

        $form->setAttribute('action', $this->url()->fromRoute('lpa/primary-attorney/edit', ['lpa-id' => $lpaId, 'idx' => $attorneyIdx]));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                //  Before going any further determine if the data for the attorney we are editing has also been saved in the correspondence data
                $isCorrespondent = $this->attorneyIsCorrespondent($attorney);

                //  Update the attorney with new details and transfer across the ID value
                $attorneyId = $attorney->id;
                if ($attorney instanceof Human) {
                    $attorney = new Human($form->getModelDataFromValidatedForm());
                } else {
                    $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                }
                $attorney->id = $attorneyId;

                // persist to the api
                if (!$this->getLpaApplicationService()->setPrimaryAttorney($lpaId, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update a primary attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
                }

                //  Attempt to update the LPA correspondent too if appropriate
                if ($isCorrespondent) {
                    $this->updateCorrespondentData($attorney);
                }

                return $this->moveToNextRoute();
            }
        } else {
            $flattenAttorneyData = $attorney->flatten();

            if ($attorney instanceof Human) {
                $dob = $attorney->dob->date;
                $flattenAttorneyData['dob-date'] = [
                    'day'   => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year'  => $dob->format('Y'),
                ];
            }

            $form->bind($flattenAttorneyData);
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/primary-attorney');

        return $viewModel;
    }

    public function confirmDeleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $lpaDocument = $this->getLpa()->document;

        $attorneyIdx = $this->params()->fromRoute('idx');

        if (array_key_exists($attorneyIdx, $lpaDocument->primaryAttorneys)) {
            $attorney = $lpaDocument->primaryAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        // Setting the trust flag
        $isTrust = isset($attorney->number);

        $viewModel = new ViewModel([
            'deleteRoute' => $this->url()->fromRoute('lpa/primary-attorney/delete', ['lpa-id' => $lpaId, 'idx' => $attorneyIdx]),
            'attorneyName' => $attorney->name,
            'attorneyAddress' => $attorney->address,
            'isTrust' => $isTrust,
        ]);

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/primary-attorney');

        return $viewModel;
    }

    public function deleteAction()
    {
        $lpa = $this->getLpa();
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');

        if (array_key_exists($attorneyIdx, $lpa->document->primaryAttorneys)) {
            $attorney = $lpa->document->primaryAttorneys[$attorneyIdx];

            //  If this attorney is set as the correspondent then delete those details too
            if ($this->attorneyIsCorrespondent($attorney)) {
                $this->updateCorrespondentData($attorney, true);
            }

            //  If the deletion of the attorney means there are no longer multiple attorneys then reset the how decisions
            if (count($lpa->document->primaryAttorneys) <= 2) {
                $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;

                if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions && $primaryAttorneyDecisions->how != null) {
                    $primaryAttorneyDecisions->how = null;
                    $primaryAttorneyDecisions->howDetails = null;

                    $this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpa->id, $primaryAttorneyDecisions);
                }
            }

            //  If the attorney being removed was set as registering the LPA then remove from there too
            $whoIsRegistering = $lpa->document->whoIsRegistering;

            if (is_array($whoIsRegistering)) {
                foreach ($whoIsRegistering as $idx => $aid) {
                    if ($aid == $attorney->id) {
                        unset($whoIsRegistering[$idx]);

                        if (count($whoIsRegistering) == 0) {
                            $whoIsRegistering = null;
                        }

                        $this->getLpaApplicationService()->setWhoIsRegistering($lpa->id, $whoIsRegistering);
                        break;
                    }
                }
            }

            // delete attorney
            if (!$this->getLpaApplicationService()->deletePrimaryAttorney($lpa->id, $attorney->id)) {
                throw new \RuntimeException('API client failed to delete a primary attorney ' . $attorneyIdx . ' for id: ' . $lpa->id);
            }

            $this->cleanUpReplacementAttorneyDecisions();
            $this->cleanUpApplicant();
        } else {
            // if attorney idx does not exist in lpa, return 404.
            return $this->notFoundAction();
        }

        $route = 'lpa/primary-attorney';

        return $this->redirect()->toRoute($route, ['lpa-id' => $lpa->id], $this->getFlowChecker()->getRouteOptions($route));
    }

    public function addTrustAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/primary-attorney/trust-form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpaId = $this->getLpa()->id;

        //  Redirect to human add attorney if trusts are not allowed
        if (!$this->allowTrust()) {
            $route = 'lpa/primary-attorney/add';

            return $this->redirect()->toRoute($route, ['lpa-id' => $lpaId], $this->getFlowChecker()->getRouteOptions($route));
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/primary-attorney/add-trust', ['lpa-id' => $lpaId]));

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->addPrimaryAttorney($lpaId, $attorney)) {
                    throw new \RuntimeException('API client failed to add a trust corporation attorney for id: ' . $lpaId);
                }

                // set this attorney as applicant if primary attorney acts jointly
                // and applicant are primary attorneys
                $this->resetApplicants();

                $this->cleanUpReplacementAttorneyDecisions();
                $this->cleanUpApplicant();

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;
        $viewModel->switchAttorneyTypeRoute = 'lpa/primary-attorney/add';

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/primary-attorney');

        return $viewModel;
    }

    /**
     * Reset whoIsRegistering value by collecting all primary attorneys ids.
     * This is due to new attorney has been added, therefore if applicant are attorneys and
     * they act jointly, applicants need to be updated.
     */
    protected function resetApplicants()
    {
        // set this attorney as applicant if primary attorney act jointly.
        if (($this->getLpa()->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) && is_array($this->getLpa()->document->whoIsRegistering)) {
            $primaryAttorneys = $this->getLpaApplicationService()->getPrimaryAttorneys($this->getLpa()->id);
            $this->getLpa()->document->whoIsRegistering = [];

            foreach ($primaryAttorneys as $attorney) {
                $this->getLpa()->document->whoIsRegistering[] = $attorney->id;
            }

            $this->getLpaApplicationService()->setWhoIsRegistering($this->getLpa()->id, $this->getLpa()->document->whoIsRegistering);
        }
    }

    /**
     * Simple method to return a boolean indicating if the provided attorney is also set as the correspondent for this LPA
     *
     * @param AbstractAttorney $attorney
     * @return bool
     */
    private function attorneyIsCorrespondent(AbstractAttorney $attorney)
    {
        $correspondent = $this->getLpa()->document->correspondent;

        if ($correspondent instanceof Correspondence && $correspondent->who == Correspondence::WHO_ATTORNEY) {
            //  Compare the appropriate name and address
            $nameToCompare = ($attorney instanceof TrustCorporation ? $correspondent->company : $correspondent->name);
            return ($attorney->name == new Name($nameToCompare->flatten()) && $correspondent->address == $attorney->address);
        }

        return false;
    }
}
