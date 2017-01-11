<?php

namespace Application\Controller\Authenticated\Lpa;

use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Zend\View\Model\JsonModel;
use Application\Model\Service\Lpa\Metadata;
use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class ReplacementAttorneyController extends AbstractLpaActorController
{

    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;

        // set hidden form for saving empty array to replacement attorneys.
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\BlankForm');

        if($this->request->isPost()) {

            $form->setData($this->request->getPost());

            if($form->isValid()) {

                // set user has confirmed if there are replacement attorneys
                $this->getServiceLocator()->get('Metadata')->setReplacementAttorneysConfirmed($this->getLpa());

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }

        // list replacement attorneys on the landing page if they've been added.
        $attorneysParams = [];
        foreach($this->getLpa()->document->replacementAttorneys as $idx=>$attorney) {
            $params = [
                    'attorney' => [
                            'address'   => $attorney->address
                    ],
                    'editRoute'     => $this->url()->fromRoute( $currentRouteName.'/edit', ['lpa-id' => $lpaId, 'idx' => $idx ]),
                    'deleteRoute'   => $this->url()->fromRoute( $currentRouteName.'/delete', ['lpa-id' => $lpaId, 'idx' => $idx ]),
            ];

            if($attorney instanceof Human) {
                $params['attorney']['name'] = $attorney->name;
            }
            else {
                $params['attorney']['name'] = $attorney->name;
            }

            $attorneysParams[] = $params;
        }

        $viewModelParams = [
                    'addRoute'  => $this->url()->fromRoute( $currentRouteName.'/add', ['lpa-id'=>$lpaId] ),
                    'lpaId'     => $lpaId,
                    'attorneys' => $attorneysParams,
                    'form'      => $form,
        ];

        return new ViewModel($viewModelParams);

    }

    public function addAction()
    {
        $lpaId = $this->getLpa()->id;
        $routeMatch = $this->getEvent()->getRouteMatch();
        $isPopup = $this->getRequest()->isXmlHttpRequest();

        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);

        $viewModel->setTemplate('application/replacement-attorney/person-form.twig');
        if ( $isPopup ) {
            $viewModel->setTerminal(true);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));

        $seedSelection = $this->seedDataSelector($viewModel, $form);
        if($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // received POST from replacement attorney form submission
            if(!$postData->offsetExists('pick-details')) {

                // handle replacement attorney form submission
                $form->setData($postData);
                if($form->isValid()) {

                    // persist to the api
                    $attorney = new Human($form->getModelDataFromValidatedForm());
                    if( !$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney) ) {
                        throw new \RuntimeException('API client failed to add a replacement attorney for id: '.$lpaId);
                    }

                    // set REPLACEMENT_ATTORNEYS_CONFIRMED flag in metadata
                    if(!array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->getLpa()->metadata)) {
                            $this->getServiceLocator()->get('Metadata')->setReplacementAttorneysConfirmed($this->getLpa());
                    }

                    // redirect to next page for non-js, or return a json to ajax call.
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
                    }
                }
            }
        }
        else {
            // load user's details into the form
            if($this->params()->fromQuery('use-my-details')) {
                $form->bind($this->getUserDetailsAsArray());
            }
        }

        $viewModel->form = $form;

        // show user my details link (if the link has not been clicked and seed dropdown is not set in the view)
        if(($viewModel->seedDetailsPickerForm==null) && !$this->params()->fromQuery('use-my-details')) {
            $viewModel->useMyDetailsRoute = $this->url()->fromRoute('lpa/replacement-attorney/add', ['lpa-id' => $lpaId]) . '?use-my-details=1';
        }

        // only provide add trust corp link if lpa has not a trust already and lpa is of PF type.
        if(!$this->hasTrust() && ($this->getLpa()->document->type == Document::LPA_TYPE_PF) ) {
            $viewModel->addTrustCorporationRoute = $this->url()->fromRoute( 'lpa/replacement-attorney/add-trust', ['lpa-id' => $lpaId] );
        }

        return $viewModel;
    }

    public function editAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();

        $isPopup = $this->getRequest()->isXmlHttpRequest();
        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);

        if ( $isPopup ) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;
        $currentRouteName = $routeMatch->getMatchedRouteName();

        $attorneyIdx = $routeMatch->getParam('idx');
        if( array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys) ) {
            $attorney = $this->getLpa()->document->replacementAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if(!isset($attorney)) {
            return $this->notFoundAction();
        }

        if($attorney instanceof Human) {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
            $viewModel->setTemplate('application/replacement-attorney/person-form.twig');
        }
        else {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
            $viewModel->setTemplate('application/replacement-attorney/trust-form.twig');
        }

        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId, 'idx'=>$attorneyIdx]));

        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);

            if($form->isValid()) {
                // update with new details
                if($attorney instanceof Human) {
                    $attorney->populate($form->getModelDataFromValidatedForm());
                }
                else {
                    $attorney->populate($form->getModelDataFromValidatedForm());
                }

                // persist to the api
                if(!$this->getLpaApplicationService()->setReplacementAttorney($lpaId, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update replacement attorney ' . $attorney->id . ' for id: ' . $lpaId);
                }

                if ( $this->getRequest()->isXmlHttpRequest() ) {
                    return new JsonModel(['success' => true]);
                }
                else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        }
        else {
            $flattenAttorneyData = $attorney->flatten();

            if($attorney instanceof Human) {
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
        $lpaId = $this->getLpa()->id;
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');

        if( array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys) ) {

            if(count($this->getLpa()->document->replacementAttorneys) <= 2) {
                if($this->getLpa()->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                    $this->getLpa()->document->replacementAttorneyDecisions->how = null;
                    $this->getLpa()->document->replacementAttorneyDecisions->when = null;
                    $this->getLpa()->document->replacementAttorneyDecisions->howDetails = null;
                    $this->getLpa()->document->replacementAttorneyDecisions->whenDetails = null;
                    $this->getLpaApplicationService()->setReplacementAttorneyDecisions($this->getLpa()->id, $this->getLpa()->document->replacementAttorneyDecisions);
                }
            }

            // persist data to the api
            if(!$this->getLpaApplicationService()->deleteReplacementAttorney($lpaId, $this->getLpa()->document->replacementAttorneys[$attorneyIdx]->id)) {
                throw new \RuntimeException('API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
            }

        }
        else {
            // if attorney idx does not exist in lpa, return 404.
            return $this->notFoundAction();
        }

        if ( $this->getRequest()->isXmlHttpRequest() ) {
            return new JsonModel(['success' => true]);
        }
        else {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
        }
    }

    public function addTrustAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();

        $isPopup = $this->getRequest()->isXmlHttpRequest();

        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);

        $viewModel->setTemplate('application/replacement-attorney/trust-form.twig');
        if ( $isPopup ) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;

        // redirect to add human attorney if lpa is of hw type or a trust was added already.
        if( ($this->getLpa()->document->type == Document::LPA_TYPE_HW) || $this->hasTrust() ) {
            return $this->redirect()->toRoute('lpa/replacement-attorney/add', ['lpa-id' => $lpaId]);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));

        $seedSelection = $this->seedDataSelector($viewModel, $form, true);
        if($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // received a POST from the trust corporation form submission
            if(!$postData->offsetExists('pick-details')) {

                // handle trust corp form submission
                $form->setData($postData);
                if($form->isValid()) {

                    // persist data to the api
                    $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                    if( !$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney) ) {
                        throw new \RuntimeException('API client failed to add trust corporation replacement attorney for id: '.$lpaId);
                    }

                    // set REPLACEMENT_ATTORNEYS_CONFIRMED flag in metadata
                    if(!array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->getLpa()->metadata)) {
                        $this->getServiceLocator()->get('Metadata')->setReplacementAttorneysConfirmed($this->getLpa());
                    }

                    // redirect to next page for non-js, or return a json to ajax call.
                    if ( $this->getRequest()->isXmlHttpRequest() ) {
                        return new JsonModel(['success' => true]);
                    }
                    else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
                    }
                }
            }
        }

        $viewModel->form = $form;
        $viewModel->addAttorneyRoute = $this->url()->fromRoute( 'lpa/replacement-attorney/add', ['lpa-id' => $lpaId] );

        return $viewModel;
    }
}