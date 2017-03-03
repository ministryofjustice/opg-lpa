<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class CorrespondentController extends AbstractLpaActorController
{
    /*
     * Page loads:
     *  If correspondent details are set, they are used;
     *  Else we should the details of the default correspondent (taken from applicant).
     *
     * Page saved:
     *  If correspondent details are set, the fields are merged.
     *  Else we pull in the details of the default correspondent, then merge in other fields.
     *
     */
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        $lpaId = $this->getLpa()->id;

        /**
         * @var $correspondent
         * if $lpa->document->correspondent is a Correspondent object, $correspondent = $lpa->document->correspondent
         * else if applicant is donor, $correspondent = $lpa->document->donor
         * else if applicant is attorney, $correspondent is an attorney that is the first one in the applicants list.
         */
        if ($this->getLpa()->document->correspondent === null) {
            if ($this->getLpa()->document->whoIsRegistering == 'donor') {
                $correspondent = $this->getLpa()->document->donor;
            } else {
                $firstAttorneyId = array_values($this->getLpa()->document->whoIsRegistering)[0];

                foreach ($this->getLpa()->document->primaryAttorneys as $attorney) {
                    if ($attorney->id == $firstAttorneyId) {
                        $correspondent = $attorney;
                        break;
                    }
                }
            }
        } else {
            $correspondent = $this->getLpa()->document->correspondent;
        }

        // set hidden form for saving applicant as the default correspondent
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CorrespondenceForm', ['lpa' => $this->getLpa()]);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $validatedFormData = $form->getData();

                // save default correspondent if it has not been set
                if ($this->getLpa()->document->correspondent === null) {
                    $applicants = $this->getLpa()->document->whoIsRegistering;

                    // work out the default correspondent - donor or an attorney.
                    if ($applicants == 'donor') {
                        $correspondent = $this->getLpa()->document->donor;
                        $who = 'donor';
                    } else {
                        $who = 'attorney';
                        $firstAttorneyId = array_values($applicants)[0];

                        foreach ($this->getLpa()->document->primaryAttorneys as $attorney) {
                            if ($attorney->id == $firstAttorneyId) {
                                $correspondent = $attorney;
                                break;
                            }
                        }
                    }

                    // save correspondent via api
                    $params = [
                        'who'       => $who,
                        'name'      => ((!$correspondent instanceof TrustCorporation)? $correspondent->name:null),
                        'company'   => (($correspondent instanceof TrustCorporation)? $correspondent->name:null),
                        'address'   => $correspondent->address,
                    ];
                } else {
                    $correspondent = $this->getLpa()->document->correspondent;

                    $params = [
                        'who'       => $correspondent->who,
                        'name'      => $correspondent->name,
                        'company'   => $correspondent->company,
                        'address'   => $correspondent->address,
                    ];
                }

                $params = array_merge($params, [
                    'contactByPost'  => (bool)$validatedFormData['correspondence']['contactByPost'],
                    'contactInWelsh' => (bool)$validatedFormData['correspondence']['contactInWelsh'],
                ]);

                if ($validatedFormData['correspondence']['contactByEmail']) {
                    $params['email'] = [ 'address' => $validatedFormData['correspondence']['email-address'] ];
                }

                if ($validatedFormData['correspondence']['contactByPhone']) {
                    $params['phone'] = [ 'number' => $validatedFormData['correspondence']['phone-number'] ];
                }

                if (!$this->getLpaApplicationService()->setCorrespondent($lpaId, new Correspondence($params))) {
                    throw new \RuntimeException('API client failed to set correspondent for id: '.$lpaId);
                }

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        } else {
            // bind data to the form and set params to the view.
            if ($correspondent instanceof Correspondence) {
                $correspondentName = trim((string)$correspondent->name);
                if ($correspondentName == '') {
                    $correspondentName = $correspondent->company;
                } else {
                    if ($correspondent->company != null) {
                        $correspondentName .= ', ' . $correspondent->company;
                    }
                }

                $form->bind(['correspondence' => [
                    'email-address' => ($correspondent->email instanceof EmailAddress) ? $correspondent->email : null,
                    'phone-number' => ($correspondent->phone instanceof PhoneNumber) ? $correspondent->phone->number : null,
                    'contactByEmail' => ($correspondent->email instanceof EmailAddress) ? true : false,
                    'contactByPhone' => ($correspondent->phone instanceof PhoneNumber) ? true : false,
                    'contactByPost' => $correspondent->contactByPost,
                    'contactInWelsh' => $correspondent->contactInWelsh,
                ]]);
            } else { // donor or attorney is correspondent
                $correspondentName = (string)$correspondent->name;

                $form->bind(['correspondence' => [
                    'email-address' => ($correspondent->email instanceof EmailAddress) ? $correspondent->email : null,
                    'phone-number' => (isset($correspondent->phone) && $correspondent->phone instanceof PhoneNumber) ? $correspondent->phone->number : null,
                    'contactByEmail' => ($correspondent->email instanceof EmailAddress) ? true : false,
                ]]);
            }
        }

        return new ViewModel([
            'form'              => $form,
            'correspondent'     => [
                'name'         => $correspondentName,
                'address'      => $correspondent->address,
                'contactEmail' => ($correspondent->email instanceof EmailAddress)?$correspondent->email->address:null,
                'contactPhone' => (isset($correspondent->phone) && $correspondent->phone instanceof PhoneNumber) ? $correspondent->phone->number : null,
            ],
            'editRoute'         => $this->url()->fromRoute($currentRouteName.'/edit', ['lpa-id' => $lpaId])
        ]);
    }

    public function editAction()
    {
        $isPopup = $this->getRequest()->isXmlHttpRequest();

        $viewModel = new ViewModel(['isPopup' => $isPopup]);

        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;
        $lpaDocument = $lpa->document;
        $lpaCorrespondent = $lpaDocument->correspondent;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CorrespondentForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                //  Set aside any data to retain that is not present in the form
                $existingDataToRetain = [];

                if ($lpaCorrespondent instanceof Correspondence) {
                    $existingDataToRetain = [
                        'contactByPost'  => $lpaCorrespondent->contactByPost,
                        'contactInWelsh' => $lpaCorrespondent->contactInWelsh,
                    ];
                }

                //  Create a new correspondence data model using the form data and any data to retain from a previous save
                $lpaCorrespondent = new Correspondence(array_merge($form->getModelDataFromValidatedForm(), $existingDataToRetain));


                // Let the PDF module know that we can't rely on the default donor or attorney values any more
                $lpaCorrespondent->set('contactDetailsEnteredManually', true);

                if (!$this->getLpaApplicationService()->setCorrespondent($lpaId, $lpaCorrespondent)) {
                    throw new \RuntimeException('API client failed to update correspondent for id: '.$lpaId);
                }

                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel(['success' => true]);
                } else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        } else {
            $this->addReuseDetailsForm($viewModel, $form);

            //  If this isn't a request to reuse some details right now then bind the initial default values
            $reuseDetailsIndex = $this->params()->fromQuery('reuse-details');

            if (!is_numeric($reuseDetailsIndex) || $reuseDetailsIndex < 0) {
                //  Bind the initial default values to the empty form
                $form->bind([
                    'who' => 'other',
                    'name-title' => ' ',
                ]);
            }
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/correspondent');

        return $viewModel;
    }

    /**
     * Return an array of actor details that can be utilised in a "reuse" scenario
     * This function is overridden here so the the reuse details form contains the correct data from the current LPA
     *
     * @return  array
     */
    protected function getActorReuseDetails()
    {
        //  Initialise the reuse details details array
        $actorReuseDetails = [];

        //  Add the details for the current user
        $this->addCurrentUserDetailsForReuse($actorReuseDetails, false);

        //  Using the data from the LPA document add options for the donor and primary attorneys
        $lpaDocument = $this->getLpa()->document;

        $actorReuseDetails[] = $this->getReuseDetailsForActor($lpaDocument->donor->toArray(), 'donor', '(donor)');

        foreach ($lpaDocument->primaryAttorneys as $attorney) {
            $actorReuseDetails[] = $this->getReuseDetailsForActor($attorney->toArray(), 'attorney', '(attorney)');
        }

        return $actorReuseDetails;
    }
}
