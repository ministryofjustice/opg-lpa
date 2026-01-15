<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Application\Form\Lpa\CorrespondentForm;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\PhoneNumber;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class CorrespondentController extends AbstractLpaActorController
{
    use LoggerTrait;

    /* Page loads:
     *  If correspondent details are set, they are used;
     *  Else we should the details of the default correspondent (taken from applicant).
     *
     * Page saved:
     *  If correspondent details are set, the fields are merged.
     *  Else we pull in the details of the default correspondent, then merge in other fields.
     */
    public function indexAction()
    {
        $lpa = $this->getLpa();

        // Set hidden form for saving applicant as the default correspondent
        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\CorrespondenceForm', [
                         'lpa' => $lpa,
                     ]);
        $form->setAttribute('action', $this->url()->fromRoute('lpa/correspondent', [
            'lpa-id' => $lpa->id,
        ]));

        // Determine some details about the existing correspondent
        $correspondent = $this->getLpaCorrespondent();
        $correspondentEmailAddress = (
            $correspondent->email instanceof EmailAddress ? $correspondent->email : null
        );
        $correspondentPhoneNumber = (
            isset($correspondent->phone) && $correspondent->phone instanceof PhoneNumber
                ? $correspondent->phone->number : null
        );

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                // Set the initial correspondent data - if the correspondent
                // isn't a correspondent object then set up for the first time
                $correspondentData = [];

                // If the correspondent data is a correspondence object then unset some data now
                if ($correspondent instanceof Correspondence) {
                    $correspondentData = array_replace_recursive($correspondent->toArray(), $correspondentData);

                    // Remove the email address and telephone number - they will be added back in below if necessary;
                    // note that if not set, unset() has no effect and doesn't throw an exception
                    unset($correspondentData['email']);
                    unset($correspondentData['phone']);
                } else {
                    $correspondentData['who'] = (
                        $correspondent instanceof Donor ? Correspondence::WHO_DONOR : Correspondence::WHO_ATTORNEY
                    );
                    $correspondentData['name'] = (
                        $correspondent instanceof TrustCorporation ? null : $correspondent->name->toArray()
                    );
                    $correspondentData['company'] = (
                        $correspondent instanceof TrustCorporation ? $correspondent->name : null
                    );
                    $correspondentData['address'] = $correspondent->address->toArray();
                }

                // Recreate the correspondent object with the data
                $correspondent = new Correspondence($correspondentData);

                // Populate the remaining data for the correspondent from the form data
                $formData = $form->getData();

                $correspondent->contactInWelsh = (bool)$formData['contactInWelsh'];

                $correspondenceFormData = $formData['correspondence'];

                $correspondent->contactByPost = (bool)$correspondenceFormData['contactByPost'];

                if ($correspondenceFormData['contactByEmail']) {
                    $correspondent->setEmail(new EmailAddress([
                        'address' => $correspondenceFormData['email-address']
                    ]));
                }

                // Populate the phone details
                if ($correspondenceFormData['contactByPhone']) {
                    $correspondent->setPhone(new PhoneNumber([
                        'number' => $correspondenceFormData['phone-number']
                    ]));
                }

                if (!$this->getLpaApplicationService()->setCorrespondent($lpa, $correspondent)) {
                    throw new \RuntimeException('API client failed to set correspondent for id: ' . $lpa->id);
                }

                return $this->moveToNextRoute();
            }
        } else {
            // Bind any required data to the correspondence form
            $form->bind([
                'contactInWelsh' => (
                    isset($correspondent->contactInWelsh) ? $correspondent->contactInWelsh : false
                ),
                'correspondence' => [
                    'contactByEmail' => !is_null($correspondentEmailAddress),
                    'email-address' => $correspondentEmailAddress,
                    'contactByPhone' => !is_null($correspondentPhoneNumber),
                    'phone-number' => $correspondentPhoneNumber,
                    'contactByPost' => (
                        isset($correspondent->contactByPost) ? $correspondent->contactByPost : false
                    ),
                ]
            ]);
        }

        // Construct the correspondent's name to display - if there is a company then append those details also
        $correspondentName = (string) $correspondent->name;

        if (isset($correspondent->company) && !empty($correspondent->company)) {
            $correspondentName .= (empty($correspondentName) ? '' : ', ');
            $correspondentName .= $correspondent->company;
        }

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        return new ViewModel([
            'form'                 => $form,
            'correspondentName'    => $correspondentName,
            'correspondentAddress' => $correspondent->address,
            'contactEmail'         => $correspondentEmailAddress,
            'contactPhone'         => $correspondentPhoneNumber,
            'changeRoute'          => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $lpa->id]),
            'allowEditButton'      => $this->allowCorrespondentToBeEdited(),
        ]);
    }

    /**
     * Simple function to get the best correspondent actor for the LPA
     *
     * @return \MakeShared\DataModel\AbstractData|null
     */
    private function getLpaCorrespondent()
    {
        // If a correspondent has not already been set.....
        // 1 - If the LPA is being registered by the donor then the correspondent will be the donor
        // 2 - If the LPA is being registered by a single attorney then the correspondent will be that attorney
        // 3 - If the LPA is being registered by multiple attorneys then the
        //     correspondent will be the first attorney in the attorney list
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
            // If the correspondent is of type "other" or is a trust then edit is allowed
            if (
                $correspondent->who == Correspondence::WHO_OTHER
                || ($correspondent->who == Correspondence::WHO_ATTORNEY && !is_null($correspondent->company))
            ) {
                return true;
            }
        } elseif ($correspondent instanceof TrustCorporation) {
            // This scenario occurs when a trust is by default the correspondent even though it has not been
            // actively selected
            // This happens when the trust was selected to be the applicant and is first in the list
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

        // Determine if we are directly editing the existing correspondent
        $editingExistingCorrespondent = ($this->params()->fromQuery('reuse-details') == 'existing-correspondent');

        // If we are not directly editing the existing correspondent then execute the parent
        // function to determine if we should redirect to the reuse details view
        if (!$editingExistingCorrespondent) {
            $reuseRedirect = $this->checkReuseDetailsOptions($viewModel);

            if (!is_null($reuseRedirect)) {
                return $reuseRedirect;
            }
        }

        /** @var CorrespondentForm */
        $form = $this->getFormElementManager()->get('Application\Form\Lpa\CorrespondentForm');

        $form->setAttribute(
            'action',
            $this->url()->fromRoute('lpa/correspondent/edit', ['lpa-id' => $this->getLpa()->id])
        );

        $request = $this->convertRequest();

        if ($request->isPost()) {
            // If this is reusing actor details then check to see if we
            // can just process the data without displaying it to the user
            if ($this->reuseActorDetails($form)) {
                // If the form is not editable then just validate and process it now
                if (!$form->isEditable()) {
                    // If it isn't then validate the form to set up the data,
                    // extract it and process the correspondent
                    $form->isValid();

                    // Extract the model data from the form and process it
                    $correspondentData = $form->getModelDataFromValidatedForm();

                    return $this->processCorrespondentData($correspondentData);
                }
            } else {
                // This is a regular post from the form so just validate and save the data
                $form->setData($request->getPost());

                if ($form->isValid()) {
                    // Extract the model data from the form and process it
                    $correspondentData = $form->getModelDataFromValidatedForm();
                    $correspondentData['contactDetailsEnteredManually'] = true;

                    return $this->processCorrespondentData($correspondentData);
                }
            }
        } elseif ($editingExistingCorrespondent) {
            // Find the existing correspondent data and bind it to the form
            $existingCorrespondent = $this->getLpaCorrespondent();

            if (
                $existingCorrespondent instanceof Correspondence ||
                $existingCorrespondent instanceof TrustCorporation
            ) {
                $form->bind($existingCorrespondent->flatten());
            }
        }

        // If we're not editing the existing correspondent then execute the parent function
        // to determine if the back button URL should be set in the view model
        if (!$editingExistingCorrespondent) {
            $this->addReuseDetailsBackButton($viewModel);
        }

        $viewModel->form = $form;

        // Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/correspondent');

        return $viewModel;
    }

    /**
     * Process the correspondent data and return an appropriate model
     *
     * @param   array   $correspondentData
     * @return  RedirectResponse|JsonModel
     * @throws  \RuntimeException
     */
    private function processCorrespondentData(array $correspondentData)
    {
        $lpa = $this->getLpa();
        $lpaCorrespondent = $lpa->document->correspondent;

        // Set aside any data to retain that is not present in the form
        $existingDataToRetain = [];

        if ($lpaCorrespondent instanceof Correspondence) {
            $existingDataToRetain = [
                'contactByPost'  => $lpaCorrespondent->contactByPost,
                'contactInWelsh' => $lpaCorrespondent->contactInWelsh,
            ];
        }

        // Create a new correspondence data model using the form data and any data to retain from a previous save
        $lpaCorrespondent = new Correspondence(array_merge($correspondentData, $existingDataToRetain));

        if (!$this->getLpaApplicationService()->setCorrespondent($lpa, $lpaCorrespondent)) {
            throw new \RuntimeException('API client failed to update correspondent for id: ' . $lpa->id);
        }

        if ($this->isPopup()) {
            return new JsonModel(['success' => true]);
        }

        $nextRoute = $this->getFlowChecker()->nextRoute('lpa/correspondent/edit');

        return $this->redirectToRoute(
            $nextRoute,
            ['lpa-id' => $lpa->id],
            $this->getFlowChecker()->getRouteOptions($nextRoute)
        );
    }
}
