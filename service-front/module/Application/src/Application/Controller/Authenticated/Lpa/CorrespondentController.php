<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;
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
        //  Set hidden form for saving applicant as the default correspondent
        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\Lpa\CorrespondenceForm', [
                         'lpa' => $this->getLpa()
                     ]);

        //  Determine some details about the existing correspondent
        $correspondent = $this->getLpaCorrespondent();
        $correspondentEmailAddress = ($correspondent->email instanceof EmailAddress ? $correspondent->email : null);
        $correspondentPhoneNumber = (isset($correspondent->phone) && $correspondent->phone instanceof PhoneNumber ? $correspondent->phone->number : null);

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                //  If the correspondent data is not a correspondence type then transfer the data now
                if (!$correspondent instanceof Correspondence) {
                    $correspondent = new Correspondence([
                        'who'     => ($correspondent instanceof Donor ? Correspondence::WHO_DONOR : Correspondence::WHO_ATTORNEY),
                        'name'    => ($correspondent instanceof TrustCorporation ? null : $correspondent->name),
                        'company' => ($correspondent instanceof TrustCorporation ? $correspondent->name : null),
                        'address' => $correspondent->address,
                    ]);
                }

                //  Populate the remaining data for the correspondent from the form data
                $formData = $form->getData();
                $correspondenceFormData = $formData['correspondence'];

                $correspondent->contactByPost = (bool)$correspondenceFormData['contactByPost'];
                $correspondent->contactInWelsh = (bool)$correspondenceFormData['contactInWelsh'];

                if ($correspondenceFormData['contactByEmail']) {
                    $correspondent->email = [
                        'address' => $correspondenceFormData['email-address']
                    ];
                }

                if ($correspondenceFormData['contactByPhone']) {
                    $correspondent->phone = [
                        'number' => $correspondenceFormData['phone-number']
                    ];
                }

                if (!$this->getLpaApplicationService()->setCorrespondent($this->getLpa()->id, $correspondent)) {
                    throw new \RuntimeException('API client failed to set correspondent for id: '.$this->getLpa()->id);
                }

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $this->getLpa()->id]);
            }
        } else {
            //  Bind any required data to the correspondence form
            $form->bind(['correspondence' => [
                'contactByEmail' => !is_null($correspondentEmailAddress),
                'email-address'  => $correspondentEmailAddress,
                'contactByPhone' => !is_null($correspondentPhoneNumber),
                'phone-number'   => $correspondentPhoneNumber,
                'contactByPost'  => (isset($correspondent->contactByPost) ? $correspondent->contactByPost : false),
                'contactInWelsh' => (isset($correspondent->contactInWelsh) ? $correspondent->contactInWelsh : false),
            ]]);
        }

        //  Construct the correspondent's name to display - if there is a company then append those details also
        $correspondentName = (string) $correspondent->name;

        if (isset($correspondent->company) && !empty($correspondent->company)) {
            $correspondentName .= (empty($correspondentName) ? '' : ', ');
            $correspondentName .= $correspondent->company;
        }

        return new ViewModel([
            'form'                 => $form,
            'correspondentName'    => $correspondentName,
            'correspondentAddress' => $correspondent->address,
            'contactEmail'         => $correspondentEmailAddress,
            'contactPhone'         => $correspondentPhoneNumber,
            'changeRoute'          => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $this->getLpa()->id]),
            'allowEditButton'      => $this->allowCorrespondentToBeEdited(),
        ]);
    }

    /**
     * Simple function to get the best correspondent actor for the LPA
     *
     * @return \Opg\Lpa\DataModel\AbstractData
     */
    private function getLpaCorrespondent()
    {
        //  If a correspondent has not already been set.....
        //  1 - If the LPA is being registered by the donor then the correspondent will be the donor
        //  2 - If the LPA is being registered by a single attorney then the correspondent will be that attorney
        //  3 - If the LPA is being registered by multiple attorneys then the correspondent will be the first attorney in the attorney list
        $lpaDocument = $this->getLpa()->document;
        $correspondent = $lpaDocument->correspondent;

        if (is_null($correspondent)) {
            if ($lpaDocument->whoIsRegistering == Correspondence::WHO_DONOR) {
                $correspondent = $lpaDocument->donor;
            } else {
                $firstAttorneyId = array_values($lpaDocument->whoIsRegistering)[0];

                foreach ($lpaDocument->primaryAttorneys as $attorney) {
                    if ($attorney->id == $firstAttorneyId) {
                        $correspondent = $attorney;
                        break;
                    }
                }
            }
        }

        return $correspondent;
    }

    /**
     * Determine if the current correspondent data can be edited or not
     * A correspondent can only be edited if they have a type of 'other' and they are not the current logged in user
     *
     * @return bool
     */
    private function allowCorrespondentToBeEdited()
    {
        $correspondent = $this->getLpaCorrespondent();

        if ($correspondent instanceof Correspondence) {
            //  If the correspondent is of type "other" or is a trust then edit is allowed
            if ($correspondent->who == Correspondence::WHO_OTHER
                || ($correspondent->who == Correspondence::WHO_ATTORNEY && !is_null($correspondent->company))) {

                return true;
            }
        } elseif ($correspondent instanceof TrustCorporation) {
            //  This scenario occurs when a trust is by default the correspondent even though it has not been actively selected
            //  This happens when the trust was selected to be the applicant and is first in the list
            return true;
        }

        return false;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CorrespondentForm');
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $this->getLpa()->id]));

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                //  Extract the model data from the form and process it
                $correspondentData = $form->getModelDataFromValidatedForm();
                $correspondentData['contactDetailsEnteredManually'] = true;

                return $this->processCorrespondentData($correspondentData);
            }
        } else {
            $this->addReuseDetailsForm($viewModel, $form);

            if ($this->params()->fromQuery('reuse-details') == 'existing-correspondent') {
                //  Find the existing correspondent data and bind it to the form
                $existingCorrespondent = $this->getLpaCorrespondent();

                if ($existingCorrespondent instanceof Correspondence || $existingCorrespondent instanceof TrustCorporation) {
                    $form->bind($existingCorrespondent->flatten());
                }
            } else {
                //  Execute the parent function to determine if the back button URL should be set in the view model
                $this->addReuseDetailsBackButton($viewModel);

                if (!isset($viewModel->reuseDetailsForm)) {
                    //  Some selected data was bound to the actor form - check to see if it is editable
                    if (!$form->isEditable()) {
                        //  If it isn't then validate the form to set up the data, extract it and process the correspondent
                        $form->isValid();

                        return $this->processCorrespondentData($form->getModelDataFromValidatedForm());
                    }
                }
            }
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/correspondent');

        return $viewModel;
    }

    /**
     * Process the correspondent data and return an appropriate model
     *
     * @param   array   $correspondentData
     * @return  \Zend\Http\Response|JsonModel
     * @throws  \RuntimeException
     */
    private function processCorrespondentData(array $correspondentData)
    {
        $lpa = $this->getLpa();
        $lpaId = $lpa->id;
        $lpaCorrespondent = $lpa->document->correspondent;

        //  Set aside any data to retain that is not present in the form
        $existingDataToRetain = [];

        if ($lpaCorrespondent instanceof Correspondence) {
            $existingDataToRetain = [
                'contactByPost'  => $lpaCorrespondent->contactByPost,
                'contactInWelsh' => $lpaCorrespondent->contactInWelsh,
            ];
        }

        //  Create a new correspondence data model using the form data and any data to retain from a previous save
        $lpaCorrespondent = new Correspondence(array_merge($correspondentData, $existingDataToRetain));

        if (!$this->getLpaApplicationService()->setCorrespondent($lpaId, $lpaCorrespondent)) {
            throw new \RuntimeException('API client failed to update correspondent for id: '.$lpaId);
        }

        return $this->moveToNextRoute();
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

        $actorReuseDetails[] = $this->getReuseDetailsForActor($lpaDocument->donor->toArray(), Correspondence::WHO_DONOR, '(donor)');

        foreach ($lpaDocument->primaryAttorneys as $attorney) {
            $actorReuseDetails[] = $this->getReuseDetailsForActor($attorney->toArray(), Correspondence::WHO_ATTORNEY, '(attorney)');
        }

        return $actorReuseDetails;
    }
}
