<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Model\ViewModel;

class ReplacementAttorneyController extends AbstractLpaActorController
{

    public function indexAction()
    {
        $lpa = $this->getLpa();

        // set hidden form for saving empty array to replacement attorneys.
        $form = $this->getFormElementManager()->get('Application\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa
        ]);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // set user has confirmed if there are replacement attorneys
                $this->getMetadata()->setReplacementAttorneysConfirmed($lpa);

                return $this->moveToNextRoute();
            }
        }

        // list replacement attorneys on the landing page if they've been added.
        $attorneysParams = [];
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        foreach ($lpa->document->replacementAttorneys as $idx => $attorney) {
            $params = [
                'attorney' => [
                    'address'   => $attorney->address
                ],
                'editRoute'     => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $lpa->id, 'idx' => $idx]),
                'confirmDeleteRoute'   => $this->url()->fromRoute($currentRouteName . '/confirm-delete', ['lpa-id' => $lpa->id, 'idx' => $idx]),
                'deleteRoute'   => $this->url()->fromRoute($currentRouteName . '/delete', ['lpa-id' => $lpa->id, 'idx' => $idx]),
            ];

            $params['attorney']['name'] = $attorney->name;

            $attorneysParams[] = $params;
        }

        return new ViewModel([
            'addRoute'  => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpa->id]),
            'lpaId'     => $lpa->id,
            'attorneys' => $attorneysParams,
            'form'      => $form,
        ]);
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/authenticated/lpa/replacement-attorney/person-form.twig');

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

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/replacement-attorney/add', ['lpa-id' => $lpa->id]));
        $form->setActorData('replacement attorney', $this->getActorsList());

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist to the api
                $attorney = new Human($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->addReplacementAttorney($lpa, $attorney)) {
                    throw new \RuntimeException('API client failed to add a replacement attorney for id: ' . $lpa->id);
                }

                // set REPLACEMENT_ATTORNEYS_CONFIRMED flag in metadata
                if (!array_key_exists(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED, $lpa->metadata)) {
                    $this->getMetadata()->setReplacementAttorneysConfirmed($lpa);
                }

                $this->cleanUpReplacementAttorneyDecisions();

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  If appropriate add an add trust link route
        if ($this->allowTrust()) {
            $viewModel->switchAttorneyTypeRoute = 'lpa/replacement-attorney/add-trust';
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();

        $attorneyIdx = $this->params()->fromRoute('idx');

        if (array_key_exists($attorneyIdx, $lpa->document->replacementAttorneys)) {
            $attorney = $lpa->document->replacementAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        if ($attorney instanceof Human) {
            $form = $this->getFormElementManager()->get('Application\Form\Lpa\AttorneyForm');
            $form->setActorData('replacement attorney', $this->getActorsList($attorneyIdx));
            $viewModel->setTemplate('application/authenticated/lpa/replacement-attorney/person-form.twig');
        } else {
            $form = $this->getFormElementManager()->get('Application\Form\Lpa\TrustCorporationForm');
            $viewModel->setTemplate('application/authenticated/lpa/replacement-attorney/trust-form.twig');
        }

        $form->setAttribute('action', $this->url()->fromRoute('lpa/replacement-attorney/edit', ['lpa-id' => $lpa->id, 'idx' => $attorneyIdx]));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                //  Update the attorney with new details
                $attorney->populate($form->getModelDataFromValidatedForm());

                // persist to the api
                if (!$this->getLpaApplicationService()->setReplacementAttorney($lpa, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update replacement attorney ' . $attorney->id . ' for id: ' . $lpa->id);
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
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }

    public function confirmDeleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $lpaDocument = $this->getLpa()->document;

        $attorneyIdx = $this->params()->fromRoute('idx');

        if (array_key_exists($attorneyIdx, $lpaDocument->replacementAttorneys)) {
            $attorney = $lpaDocument->replacementAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        // Setting the trust flag
        $isTrust = isset($attorney->number);

        $viewModel = new ViewModel([
            'deleteRoute' => $this->url()->fromRoute('lpa/replacement-attorney/delete', ['lpa-id' => $lpaId, 'idx' => $attorneyIdx]),
            'attorneyName' => $attorney->name,
            'attorneyAddress' => $attorney->address,
            'isTrust' => $isTrust,
        ]);

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }

    public function deleteAction()
    {
        $lpa = $this->getLpa();
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');

        if (array_key_exists($attorneyIdx, $lpa->document->replacementAttorneys)) {
            $attorney = $lpa->document->replacementAttorneys[$attorneyIdx];

            //  If this attorney is set as the correspondent then delete those details too
            if ($this->attorneyIsCorrespondent($attorney)) {
                $this->updateCorrespondentData($attorney, true);
            }

            if (!$this->getLpaApplicationService()->deleteReplacementAttorney($lpa, $attorney->id)) {
                throw new \RuntimeException('API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpa->id);
            }

            $this->cleanUpReplacementAttorneyDecisions();
        } else {
            // if attorney idx does not exist in lpa, return 404.
            return $this->notFoundAction();
        }

        $route = 'lpa/replacement-attorney';

        return $this->redirect()->toRoute($route, ['lpa-id' => $lpa->id], $this->getFlowChecker()->getRouteOptions($route));
    }

    public function addTrustAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/authenticated/lpa/replacement-attorney/trust-form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();

        //  Redirect to human add attorney if trusts are not allowed
        if (!$this->allowTrust()) {
            $route = 'lpa/replacement-attorney/add';

            return $this->redirect()->toRoute($route, ['lpa-id' => $lpa->id], $this->getFlowChecker()->getRouteOptions($route));
        }

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\TrustCorporationForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/replacement-attorney/add-trust', ['lpa-id' => $lpa->id]));

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data to the api
                $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->addReplacementAttorney($lpa, $attorney)) {
                    throw new \RuntimeException('API client failed to add trust corporation replacement attorney for id: '.$lpa->id);
                }

                // set REPLACEMENT_ATTORNEYS_CONFIRMED flag in metadata
                if (!array_key_exists(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->getLpa()->metadata)) {
                    $this->getMetadata()->setReplacementAttorneysConfirmed($this->getLpa());
                }

                $this->cleanUpReplacementAttorneyDecisions();

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;
        $viewModel->switchAttorneyTypeRoute = 'lpa/replacement-attorney/add';

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }
}
