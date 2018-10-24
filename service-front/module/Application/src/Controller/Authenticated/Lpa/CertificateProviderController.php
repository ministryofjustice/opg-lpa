<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Model\ViewModel;

class CertificateProviderController extends AbstractLpaActorController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        //  Set hidden form for setting metadata to skip certificate provider if required
        $form = $this->getFormElementManager()->get('Application\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa
        ]);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $this->getMetadata()->setCertificateProviderSkipped($this->getLpa());

                return $this->moveToNextRoute();
            }
        }

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $nextRoute = $this->getFlowChecker()->nextRoute($currentRouteName);

        return new ViewModel([
            'nextRoute' => $nextRoute,
            'form'      => $form,
        ]);
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/authenticated/lpa/certificate-provider/form.twig');

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
            $route = 'lpa/certificate-provider';

            return $this->redirect()->toRoute($route, ['lpa-id' => $lpaId], $this->getFlowChecker()->getRouteOptions($route));
        }

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/certificate-provider/add', ['lpa-id' => $lpaId]));
        $form->setActorData('certificate provider', $this->getActorsList());

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                if (!$this->getLpaApplicationService()->setCertificateProvider($lpa, new CertificateProvider($form->getModelDataFromValidatedForm()))) {
                    throw new \RuntimeException('API client failed to save certificate provider for id: '.$lpaId);
                }

                //  Remove the skipped metadata tag if it was set
                $this->getMetadata()->removeMetadata($this->getLpa(), Lpa::CERTIFICATE_PROVIDER_SKIPPED);

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
        $viewModel->setTemplate('application/authenticated/lpa/certificate-provider/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/certificate-provider/edit', ['lpa-id' => $lpaId]));
        $form->setActorData('certificate provider', $this->getActorsList());

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $certificateProvider = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setCertificateProvider($lpa, $certificateProvider)) {
                    throw new \RuntimeException('API client failed to update certificate provider for id: '.$lpaId);
                }

                //  Attempt to update the LPA correspondent too
                $this->updateCorrespondentData($certificateProvider);

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

    public function confirmDeleteAction()
    {
        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $certificateProvider = $lpa->document->certificateProvider;

        $viewModel = new ViewModel([
            'deleteRoute' => $this->url()->fromRoute('lpa/certificate-provider/delete', ['lpa-id' => $lpaId]),
            'certificateProviderName' => $certificateProvider->name,
            'certificateProviderAddress' => $certificateProvider->address
        ]);

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/certificate-provider');

        return $viewModel;
    }

    public function deleteAction()
    {
        $lpa = $this->getLpa();

        //  If the certificate provider is also set as the correspondent then this will delete those details too
        $this->updateCorrespondentData($this->getLpa()->document->certificateProvider, true);

        // delete certificate provider
        if (!$this->getLpaApplicationService()->deleteCertificateProvider($lpa)) {
            throw new \RuntimeException('API client failed to delete certificate provider for id: ' . $lpa->id);
        }

        return $this->redirect()->toRoute('lpa/certificate-provider', [
            'lpa-id' => $lpa->id
        ]);
    }
}
