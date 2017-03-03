<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class CertificateProviderController extends AbstractLpaActorController
{
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;

        $cp = $this->getLpa()->document->certificateProvider;

        if ($cp instanceof CertificateProvider) {
            return new ViewModel([
                'certificateProvider' => [
                    'name' => $cp->name,
                    'address' => $cp->address,
                ],
                'editRoute' => $this->url()->fromRoute($currentRouteName.'/edit', ['lpa-id' => $lpaId]),
                'nextRoute' => $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]),
            ]);
        } else {
            return new ViewModel(['addRoute' => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId])]);
        }
    }

    public function addAction()
    {
        $lpa = $this->getLpa();

        if ($lpa->document->certificateProvider instanceof CertificateProvider) {
            return $this->redirect()->toRoute('lpa/certificate-provider', ['lpa-id' => $lpaId]);
        }

        $routeMatch = $this->getEvent()->getRouteMatch();
        $isPopup = $this->getRequest()->isXmlHttpRequest();

        $viewModel = new ViewModel(['isPopup' => $isPopup]);
        $viewModel->setTemplate('application/certificate-provider/form.twig');
        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $lpa->id;

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList($routeMatch));

        if ($this->request->isPost()) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $cp = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to save certificate provider for id: '.$lpaId);
                }

                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel(['success' => true]);
                } else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
                }
            }
        } else {
            $this->addReuseDetailsForm($viewModel, $form);
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/certificate-provider');

        return $viewModel;
    }

    public function editAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $isPopup = $this->getRequest()->isXmlHttpRequest();
        $viewModel = new ViewModel(['isPopup' => $isPopup]);

        $viewModel->setTemplate('application/certificate-provider/form.twig');

        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $currentRouteName = $routeMatch->getMatchedRouteName();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList($routeMatch));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $cp = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to update certificate provider for id: '.$lpaId);
                }

                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel(['success' => true]);
                } else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        } else {
            $cp = $lpa->document->certificateProvider->flatten();
            $form->bind($cp);
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/certificate-provider');

        return $viewModel;
    }
}
