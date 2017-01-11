<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\View\Model\JsonModel;
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
        $routeMatch = $this->getEvent()->getRouteMatch();

        $isPopup = $this->getRequest()->isXmlHttpRequest();

        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);

        $viewModel->setTemplate('application/primary-attorney/person-form.twig');
        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;
        $viewModel->cancelRoute = $this->url()->fromRoute('lpa/primary-attorney', ['lpa-id' => $lpaId]);

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));

        $seedSelection = $this->seedDataSelector($viewModel, $form);
        if ($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            // reveived POST from attorney form submission.
            if (!$postData->offsetExists('pick-details')) {
                // handle primary attorney form submission
                $form->setData($postData);

                if ($form->isValid()) {
                    // persist data
                    $attorney = new Human($form->getModelDataFromValidatedForm());

                    if (!$this->getLpaApplicationService()->addPrimaryAttorney($lpaId, $attorney)) {
                        throw new \RuntimeException('API client failed to add a primary attorney for id: '.$lpaId);
                    }

                    // set this attorney as applicant if primary attorney acts jointly
                    // and applicant are primary attorneys
                    $this->resetApplicants();

                    if ($this->getRequest()->isXmlHttpRequest()) {
                        return new JsonModel(['success' => true]);
                    } else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
                    }
                }
            }
        } else {
            // load user's details into the form
            if ($this->params()->fromQuery('use-my-details')) {
                $form->bind($this->getUserDetailsAsArray());
            }
        }

        $viewModel->form = $form;

        // show user my details link (if the link has not been clicked and seed dropdown is not set in the view)
        if (($viewModel->seedDetailsPickerForm==null) && !$this->params()->fromQuery('use-my-details')) {
            $viewModel->useMyDetailsRoute = $this->url()->fromRoute('lpa/primary-attorney/add', ['lpa-id' => $lpaId]) . '?use-my-details=1';
        }

        // only provide add trust corp link if lpa has not a trust already and lpa is of PF type.
        if (!$this->hasTrust() && ($this->getLpa()->document->type == Document::LPA_TYPE_PF)) {
            $viewModel->addTrustCorporationRoute = $this->url()->fromRoute('lpa/primary-attorney/add-trust', ['lpa-id' => $lpaId]);
        }

        return $viewModel;
    }

    public function editAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();

        $isPopup = $this->getRequest()->isXmlHttpRequest();
        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);

        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;
        $viewModel->cancelRoute = $this->url()->fromRoute('lpa/primary-attorney', ['lpa-id' => $lpaId]);

        $currentRouteName = $routeMatch->getMatchedRouteName();

        $attorneyIdx = $routeMatch->getParam('idx');

        if (array_key_exists($attorneyIdx, $this->getLpa()->document->primaryAttorneys)) {
            $attorney = $this->getLpa()->document->primaryAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        if ($attorney instanceof Human) {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
            $viewModel->setTemplate('application/primary-attorney/person-form.twig');
        } else {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
            $viewModel->setTemplate('application/primary-attorney/trust-form.twig');
        }

        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId, 'idx' => $attorneyIdx]));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                // update attorney with new details
                if ($attorney instanceof Human) {
                    $attorney->populate($form->getModelDataFromValidatedForm());
                } else {
                    $attorney->populate($form->getModelDataFromValidatedForm());
                }

                // persist to the api
                if (!$this->getLpaApplicationService()->setPrimaryAttorney($lpaId, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update a primary attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
                }

                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel(['success' => true]);
                } else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
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

                if (($lpa->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) &&
                    ($lpa->document->replacementAttorneyDecisions->when != null)) {
                        $lpa->document->replacementAttorneyDecisions->when = null;
                        $lpa->document->replacementAttorneyDecisions->whenDetails = null;
                        $this->getLpaApplicationService()->setReplacementAttorneyDecisions($lpa->id, $lpa->document->replacementAttorneyDecisions);
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

            $deletionFlag = true;
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!$deletionFlag) {
            return $this->notFoundAction();
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            return new JsonModel(['success' => true]);
        } else {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
        }
    }

    public function addTrustAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();

        $isPopup = $this->getRequest()->isXmlHttpRequest();
        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);
        $viewModel->setTemplate('application/primary-attorney/trust-form.twig');

        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;
        $viewModel->cancelRoute = $this->url()->fromRoute('lpa/primary-attorney', ['lpa-id' => $lpaId]);

        // redirect to add human attorney if lpa is of hw type or a trust was added already.
        if (($this->getLpa()->document->type == Document::LPA_TYPE_HW) || $this->hasTrust()) {
            return $this->redirect()->toRoute('lpa/primary-attorney/add', ['lpa-id' => $lpaId]);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));

        $seedSelection = $this->seedDataSelector($viewModel, $form, true);
        if ($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            if (!$postData->offsetExists('pick-details')) {
                // handle trust corp form submission
                $form->setData($postData);

                if ($form->isValid()) {
                    // persist data
                    $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                    if (!$this->getLpaApplicationService()->addPrimaryAttorney($lpaId, $attorney)) {
                        throw new \RuntimeException('API client failed to add a trust corporation attorney for id: ' . $lpaId);
                    }

                    // set this attorney as applicant if primary attorney acts jointly
                    // and applicant are primary attorneys
                    $this->resetApplicants();

                    if ($this->getRequest()->isXmlHttpRequest()) {
                        return new JsonModel(['success' => true]);
                    } else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
                    }
                }
            }
        }

        $viewModel->form = $form;
        $viewModel->addAttorneyRoute = $this->url()->fromRoute('lpa/primary-attorney/add', ['lpa-id' => $lpaId]);

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
