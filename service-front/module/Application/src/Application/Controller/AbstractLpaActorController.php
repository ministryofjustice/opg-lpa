<?php

namespace Application\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\User\Dob;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

abstract class AbstractLpaActorController extends AbstractLpaController
{
    /**
     * Return an appropriate view model to move to the next route from the current route
     *
     * @return ViewModel|\Zend\Http\Response
     */
    protected function moveToNextRoute()
    {
        if ($this->isPopup()) {
            return new JsonModel(['success' => true]);
        }

        //  Get the current route and the LPA ID to move to the next route
        $currentRoute = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;

        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRoute), [
            'lpa-id' => $lpaId
        ]);
    }

    /**
     * Return a flag indicating if this is a request from a popup (XmlHttpRequest)
     *
     * @return bool
     */
    protected function isPopup()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     * Get the reuse details form
     *
     * @param   ViewModel           $viewModel
     * @param   AbstractActorForm   $actorForm
     */
    protected function addReuseDetailsForm(ViewModel $viewModel, AbstractActorForm $actorForm)
    {
        //  Attempt to get the seed details for reuse
        $actorReuseDetails = $this->getActorReuseDetails();

        if (is_array($actorReuseDetails) && !empty($actorReuseDetails)) {
            //  If a reuse details value has been provided then try to obtain the actor details
            //  Get the reuse details index from the query parameters if it is present
            $reuseDetailsIndex = $this->params()->fromQuery('reuse-details');

            if (array_key_exists($reuseDetailsIndex, $actorReuseDetails)) {
                $actorDetailsToReuse = $actorReuseDetails[$reuseDetailsIndex]['data'];

                //  Check that the actor details selected are appropriate for the current route
                //  i.e. trusts can only be viewed in the trust templates
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

                //  Determine if the selected actor is a trust and is we are using a trust only route
                //  Human and trust attorneys can both be used for the correspondent
                if ($currentRouteName != 'lpa/correspondent/edit') {
                    $isTrust = (isset($actorDetailsToReuse['type']) && $actorDetailsToReuse['type'] == 'trust');
                    $isTrustRoute = (strpos($currentRouteName, 'add-trust') !== false);
                    $redirectionRoute = null;

                    if ($isTrust && !$isTrustRoute) {
                        $redirectionRoute = $currentRouteName . '-trust';
                    } elseif (!$isTrust && $isTrustRoute) {
                        $redirectionRoute = str_replace('add-trust', 'add', $currentRouteName);
                    }

                    //  If required redirect the request
                    if (!is_null($redirectionRoute)) {
                        $params = [
                            'lpa-id' => $this->getLpa()->id,
                        ];

                        $options = [
                            'query' => $this->params()->fromQuery()
                        ];

                        $this->redirect()->toRoute($redirectionRoute, $params, $options);
                    }
                }

                //  Bind the actor data to the main form
                $actorForm->bind($actorDetailsToReuse);
            } elseif ($reuseDetailsIndex != -1 && !is_string($reuseDetailsIndex)) {
                //  If no option has been selected (including the "none of the above option" which is -1) then set the reuse details form in the view
                $reuseDetailsForm = $this->getServiceLocator()
                                         ->get('FormElementManager')
                                         ->get('Application\Form\Lpa\ReuseDetailsForm', [
                                             'actorReuseDetails' => $actorReuseDetails,
                                         ]);

                $viewModel->reuseDetailsForm = $reuseDetailsForm;
            }
        }
    }

    /**
     * Return an array of actor details that can be utilised in a "reuse" scenario
     *
     * @return  array
     */
    protected function getActorReuseDetails()
    {
        //  Initialise the reuse details details array
        $actorReuseDetails = [];

        //  If this is not a request to get trust data, and the session user data hasn't already been used, add it now
        $this->addCurrentUserDetailsForReuse($actorReuseDetails);

        //  Get any seed details for this LPA
        $seedDetails = $this->getSeedDetails();

        foreach ($seedDetails as $type => $actorData) {
            //  Initialise the actor type
            $actorType = (isset($actorData['who']) ? $actorData['who'] : 'other');

            switch ($type) {
                case 'donor':
                    $actorType = 'donor';
                    $actorReuseDetails[] = $this->getReuseDetailsForActor($actorData, $actorType, '(was the donor)');
                    break;
                case 'correspondent':
                    //  Only add the correspondent details if it is not the donor or an attorney
                    if ($actorType == 'other') {
                        $actorReuseDetails[] = $this->getReuseDetailsForActor($actorData, $actorType, '(was the correspondent)');
                    }
                    break;
                case 'certificateProvider':
                    $actorReuseDetails[] = $this->getReuseDetailsForActor($actorData, $actorType, '(was the certificate provider)');
                    break;
                case 'primaryAttorneys':
                case 'replacementAttorneys':
                    $actorType = 'attorney';
                    $suffixText = '(was a primary attorney)';

                    if ($type == 'replacementAttorneys') {
                        $suffixText = '(was a replacement attorney)';
                    }

                    foreach ($actorData as $singleActorData) {
                        //  If a trust has already been used then don't present the trust option
                        if ($singleActorData['type'] == 'trust' && !$this->allowTrust()) {
                            continue;
                        }

                        $actorReuseDetails[] = $this->getReuseDetailsForActor($singleActorData, $actorType, $suffixText);
                    }
                    break;
                case 'peopleToNotify':
                    foreach ($actorData as $singleActorData) {
                        $actorReuseDetails[] = $this->getReuseDetailsForActor($singleActorData, $actorType, '(was a person to be notified)');
                    }
                    break;
            }
        }

        return $actorReuseDetails;
    }

    /**
     * Add the current user details to the reuse details array
     *
     * @param array $actorReuseDetails
     * @param bool $checkIfAlreadyUsed
     */
    protected function addCurrentUserDetailsForReuse(array &$actorReuseDetails, $checkIfAlreadyUsed = true)
    {
        //  Check that the current session user details have not already been used
        $currentUserDetailsUsedToBeAdded = true;
        $userDetailsObj = $this->getUserDetails();

        //  Check to see if the user details have already been used if necessary
        if ($checkIfAlreadyUsed) {
            foreach ($this->getActorsList() as $actorsListItem) {
                if (strtolower($userDetailsObj->name->first) == strtolower($actorsListItem['firstname'])
                    && strtolower($userDetailsObj->name->last) == strtolower($actorsListItem['lastname'])
                ) {
                    $currentUserDetailsUsedToBeAdded = false;
                    break;
                }
            }
        }

        if ($currentUserDetailsUsedToBeAdded) {
            //  Flatten the user details and reformat the DOB before adding the details to the reuse details array
            $userDetails = $userDetailsObj->flatten();

            //  Add the additional data required by the form on the correspondence edit view
            $userDetails['who'] = 'other';

            //  If a date of birth is present then replace it as an array of day, month and year
            if (($dateOfBirth = $userDetailsObj->dob) instanceof Dob) {
                $userDetails['dob-date'] = [
                    'day'   => $dateOfBirth->date->format('d'),
                    'month' => $dateOfBirth->date->format('m'),
                    'year'  => $dateOfBirth->date->format('Y'),
                ];
            }

            $actorReuseDetails[] = [
                'label' => $userDetailsObj->name . ' (myself)',
                'data'  => $userDetails,
            ];
        }
    }

    /**
     * Generate a list of actors already associated with the current LPA
     *
     * @param RouteMatch $routeMatch
     * @return array
     */
    protected function getActorsList(RouteMatch $routeMatch = null)
    {
        $actorsList = [];

        //  Get the route details
        $matchedRoute = null;
        $routeIndex = null;

        if ($routeMatch instanceof RouteMatch) {
            $matchedRoute = $routeMatch->getMatchedRouteName();
            $routeIndex = $routeMatch->getParam('idx');
        }

        $lpa = $this->getLpa();

        if (($matchedRoute != 'lpa/donor/edit') && ($lpa->document->donor instanceof Donor)) {
            $actorsList[] = $this->getActorDetails($lpa->document->donor, 'donor');
        }

        // when edit a cp or on np add/edit page, do not include this cp
        if (($lpa->document->certificateProvider instanceof CertificateProvider) && !in_array($matchedRoute, ['lpa/certificate-provider/edit','lpa/people-to-notify/add','lpa/people-to-notify/edit'])) {
            $actorsList[] = $this->getActorDetails($lpa->document->certificateProvider, 'certificate provider');
        }

        foreach ($lpa->document->primaryAttorneys as $idx => $attorney) {
            if ($matchedRoute == 'lpa/primary-attorney/edit' && $routeIndex == $idx) {
                continue;
            }

            if ($attorney instanceof Attorneys\Human) {
                $actorsList[] = $this->getActorDetails($attorney, 'attorney');
            }
        }

        foreach ($lpa->document->replacementAttorneys as $idx => $attorney) {
            if ($matchedRoute == 'lpa/replacement-attorney/edit' && $routeIndex == $idx) {
                continue;
            }

            if ($attorney instanceof Attorneys\Human) {
                $actorsList[] = $this->getActorDetails($attorney, 'replacement attorney');
            }
        }

        // on cp page, do not include np names for duplication check
        if ($matchedRoute != 'lpa/certificate-provider/add' && $matchedRoute != 'lpa/certificate-provider/edit') {
            foreach ($lpa->document->peopleToNotify as $idx => $notifiedPerson) {
                // when edit an np, do not include this np
                if ($matchedRoute == 'lpa/people-to-notify/edit' && $routeIndex == $idx) {
                    continue;
                }

                $actorsList[] = $this->getActorDetails($notifiedPerson, 'people to notify');
            }
        }

        return $actorsList;
    }

    /**
     * Simple function to format the actor details is a consistent manner
     *
     * @param AbstractData $actorData
     * @param $actorType
     * @return array
     */
    private function getActorDetails(AbstractData $actorData, $actorType)
    {
        $actorDetails = [];

        if (isset($actorData->name) && $actorData->name instanceof Name) {
            $actorDetails = [
                'firstname' => $actorData->name->first,
                'lastname'  => $actorData->name->last,
                'type'      => $actorType
            ];
        }

        return $actorDetails;
    }

    /**
     * Simple function to get the seed details from the backend or from the user session if already retrieved
     *
     * @return array
     */
    private function getSeedDetails()
    {
        $seedDetails = [];
        $lpa = $this->getLpa();
        $seedId = $lpa->seed;

        if (!is_null($seedId)) {
            $cloneContainer = new Container('clone');

            if (!$cloneContainer->offsetExists($seedId)) {
                //  The data isn't in the session - get it now
                $cloneContainer->$seedId = $this->getLpaApplicationService()->getSeedDetails($lpa->id);
            }

            if (is_array($cloneContainer->$seedId)) {
                $seedDetails = $cloneContainer->$seedId;
            }
        }

        return $seedDetails;
    }

    /**
     * Simple function to return filtered actor details for reuse
     *
     * @param array $actorData
     * @param string $actorType
     * @param string $suffixText
     * @return array
     */
    protected function getReuseDetailsForActor(array $actorData, $actorType, $suffixText = '')
    {
        //  Set the sctor type in the data
        $actorData['who'] = $actorType;

        //  Initialise the label text - this will be the value if the actor is a trust
        $label = $actorData['name'];

        //  If this is a trust then set the company name - this is used for the correspondent edit form
        if (isset($actorData['type']) && $actorData['type'] == 'trust') {
            $actorData['company'] = $label;
        } elseif (is_array($actorData['name'])) {
            //  Try to create a full name for a non trust
            $label = $actorData['name']['first'] . ' ' . $actorData['name']['last'];
        }

        //  Filter the actor data
        foreach ($actorData as $actorDataKey => $actorDataValue) {
            if (!in_array($actorDataKey, ['name', 'number', 'otherNames', 'address', 'dob', 'email', 'case', 'phone', 'who', 'company', 'type'])) {
                unset($actorData[$actorDataKey]);
            }
        }

        return [
            'label' => trim($label . ' ' . $suffixText),
            'data'  => $this->flattenData($actorData),
        ];
    }

    /**
     * Simple function to indicated whether a trust should be allowed for the current LPA
     *
     * @return bool
     */
    protected function allowTrust()
    {
        if ($this->getLpa()->document->type != Document::LPA_TYPE_HW) {
            $attorneys = array_merge($this->getLpa()->document->primaryAttorneys, $this->getLpa()->document->replacementAttorneys);

            foreach ($attorneys as $attorney) {
                if ($attorney instanceof Attorneys\TrustCorporation) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Add a flag to allow the reuse details back button if the situation dictates it can be used
     *
     * @param ViewModel $viewModel
     */
    protected function addReuseDetailsBackButton(ViewModel $viewModel)
    {
        $allowBackButton = false;

        //  If the reuse details form has already been set then we can check the options available instead of getting all the actor details again
        if (isset($viewModel->reuseDetailsForm)) {
            $allowBackButton = $viewModel->reuseDetailsForm->reusingPreviousLpaOptions();
        } elseif (count($this->getActorReuseDetails()) > 1) {
            $allowBackButton = true;
        }

        //  If required add the back button URL
        if ($allowBackButton) {
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

            //  Add the back button URL but make sure that the add trust views go back to the normal add views
            $viewModel->backButtonUrl = str_replace('add-trust', 'add', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $this->getLpa()->id]));
        }
    }

    /**
     * Simple function to add a cancel URL to the view model parameters
     *
     * @param ViewModel $viewModel
     * @param $route
     */
    protected function addCancelUrlToView(ViewModel $viewModel, $route)
    {
        //  If a route string is provided then add it now
        if (is_string($route)) {
            $viewModel->cancelUrl = $this->url()->fromRoute($route, ['lpa-id' => $this->getLpa()->id]);
        }
    }

    /**
     * If a correspondent is already set in the LPA and the core data of the actor selected (donor and attorney only) then update the data in the correspondent data also
     *
     * @param AbstractData $actor
     */
    protected function updateCorrespondentData(AbstractData $actor)
    {
        $correspondent = $this->getLpa()->document->correspondent;

        if ($correspondent instanceof Correspondence) {
            //  Only allow the data to be updated if the actor type is correct
            if (($actor instanceof Donor && $correspondent->who == Correspondence::WHO_DONOR)
                || ($actor instanceof Attorneys\AbstractAttorney && $correspondent->who == Correspondence::WHO_ATTORNEY)) {

                //  Get the correct name to compare (for a trust that will be the company name)
                $isTrust = ($actor instanceof Attorneys\TrustCorporation);
                $nameToCompare = ($isTrust ? $correspondent->name : $correspondent->company);

                //  Determine if the correspondent data needs to be updated or not
                if ($actor->name != $nameToCompare || $actor->address != $correspondent->address) {
                    //  Create an updated correspondent datamodel with the data from the existing correspondent EXCEPT the name
                    //  This is necessary because we may need to null the name field if this actor is a trust
                    $correspondentData = $correspondent->toArray();
                    unset($correspondentData['name']);
                    $correspondent = new Correspondence($correspondentData);

                    //  Update the required values
                    if ($isTrust) {
                        $correspondent->company = $actor->name;
                    } else {
                        $correspondent->name = $actor->name;
                    }

                    $correspondent->address = $actor->address;

                    if (!$this->getLpaApplicationService()->setCorrespondent($this->getLpa()->id, $correspondent)) {
                        throw new \RuntimeException('API client failed to update correspondent for id: ' . $this->getLpa()->id);
                    }
                }
            }
        }
    }
}
