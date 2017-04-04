<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Zend\View\Model\ViewModel;

class PrimaryAttorneyController extends AbstractLpaActorController
{

    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;

        if (count($this->getLpa()->document->primaryAttorneys) > 0) {
            $attorneysParams = [];
            foreach ($this->getLpa()->document->primaryAttorneys as $idx => $attorney) {
                $params = [
                    'attorney' => [
                        'address'   => $attorney->address
                    ],
                    'editRoute'     => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $lpaId, 'idx' => $idx]),
                    'deleteRoute'   => $this->url()->fromRoute($currentRouteName . '/delete', ['lpa-id' => $lpaId, 'idx' => $idx]),
                ];

                if ($attorney instanceof Human) {
                    $params['attorney']['name'] = $attorney->name;
                } else {
                    $params['attorney']['name'] = $attorney->name;
                }

                $attorneysParams[] = $params;
            }

            return new ViewModel([
                'addRoute'  => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId]),
                'attorneys' => $attorneysParams,
                'nextRoute' => $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId])
            ]);
        } else {
            return new ViewModel([
                'addRoute' => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId]),
            ]);
        }
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
                $updateCorrespondent = false;
                $correspondent = $lpaDocument->correspondent;

                if ($correspondent instanceof Correspondence && $correspondent->who == Correspondence::WHO_ATTORNEY) {
                    //  Compare the appropriate name and address
                    $nameToCompare = ($attorney instanceof TrustCorporation ? $correspondent->company : $correspondent->name);
                    $updateCorrespondent = ($attorney->name == $nameToCompare && $correspondent->address == $attorney->address);
                }

                // update attorney with new details
                $attorney->populate($form->getModelDataFromValidatedForm());

                // persist to the api
                if (!$this->getLpaApplicationService()->setPrimaryAttorney($lpaId, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update a primary attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
                }

                //  Attempt to update the LPA correspondent too if appropriate
                if ($updateCorrespondent) {
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

    public function deleteAction()
    {
        $lpa = $this->getLpa();

        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');

        $deletionFlag = true;


        if (array_key_exists($attorneyIdx, $lpa->document->primaryAttorneys)) {
            // check primaryAttorneyDecisions::how and replacementAttorneyDecisions::when
            if (count($lpa->document->primaryAttorneys) <= 2) {
                if (($lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) &&
                    ($lpa->document->primaryAttorneyDecisions->how != null)) {
                        $lpa->document->primaryAttorneyDecisions->how = null;
                        $lpa->document->primaryAttorneyDecisions->howDetails = null;
                        $this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpa->id, $lpa->document->primaryAttorneyDecisions);
                }
            }



            $attorneyId = $lpa->document->primaryAttorneys[$attorneyIdx]->id;

            // check whoIsRegistering
            if (is_array($lpa->document->whoIsRegistering)) {
                foreach ($lpa->document->whoIsRegistering as $idx => $aid) {
                    if ($aid == $attorneyId) {
                        unset($lpa->document->whoIsRegistering[$idx]);

                        if (count($lpa->document->whoIsRegistering) == 0) {
                            $lpa->document->whoIsRegistering = null;
                        }

                        $this->getLpaApplicationService()->setWhoIsRegistering($lpa->id, $lpa->document->whoIsRegistering);
                        break;
                    }
                }
            }

            // delete attorney
            if (!$this->getLpaApplicationService()->deletePrimaryAttorney($lpa->id, $attorneyId)) {
                throw new \RuntimeException('API client failed to delete a primary attorney ' . $attorneyIdx . ' for id: ' . $lpa->id);
            }

            $this->cleanUpReplacementAttorneyDecisions();

            $deletionFlag = true;
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!$deletionFlag) {
            return $this->notFoundAction();
        }

        return $this->moveToNextRoute();
    }

    private function cleanUpReplacementAttorneyDecisions(){
        $lpa = $this->getServiceLocator()->get('LpaApplicationService')->getApplication((int) $this->getLpa()->id);
        $RACleanupService = $this->getServiceLocator()->get('ReplacementAttorneyCleanup');
        $RACleanupService->cleanUp($lpa, $this->getLpaApplicationService());
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
            return $this->redirect()->toRoute('lpa/primary-attorney/add', ['lpa-id' => $lpaId]);
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
}
