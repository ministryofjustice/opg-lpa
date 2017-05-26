<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
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
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/certificate-provider/form.twig');

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

        //  If a certificate provider has already been provided then redirect to the main certificate provider screen
        if ($lpa->document->certificateProvider instanceof CertificateProvider) {
            return $this->redirect()->toRoute('lpa/certificate-provider', ['lpa-id' => $lpaId]);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/certificate-provider/add', ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList());

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $cp = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to save certificate provider for id: '.$lpaId);
                }

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/certificate-provider');

        return $viewModel;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/certificate-provider/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/certificate-provider/edit', ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList());

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $cp = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to update certificate provider for id: '.$lpaId);
                }

                //  Attempt to update the LPA correspondent too
                $this->updateCorrespondentData($cp);

                return $this->moveToNextRoute();
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
