<?php

namespace Application\Controller;

use Application\Controller\Authenticated\Lpa;
use Application\Form\Lpa\AbstractActorForm;
use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\Session\Container;
use Zend\Router;
use Zend\View\Model\ViewModel;

abstract class AbstractLpaActorController extends AbstractLpaController
{
    /**
     * Function to check if the reuse details options are available and if it is appropriate to redirect to them
     *
     * @return \Zend\Http\Response
     */
    protected function checkReuseDetailsOptions(ViewModel $viewModel)
    {
        //  If we are posting then do not execute a redirect just go back to the calling function
        if (!$this->request->isPost()) {
            $actorReuseDetailsCount = count($this->getActorReuseDetails());

            //  If there is only one actor details to reuse then it will be the session user
            if ($actorReuseDetailsCount == 1) {
                $viewModel->displayReuseSessionUserLink = true;
            } elseif ($actorReuseDetailsCount > 1) {
                //  Determine the actor name to pass to the reuse details screen
                $includeTrusts = false;
                $actorName = null;

                if ($this instanceof Lpa\CertificateProviderController) {
                    $actorName = 'Certificate provider';
                } elseif ($this instanceof Lpa\CorrespondentController) {
                    $actorName = 'Correspondent';
                } elseif ($this instanceof Lpa\DonorController) {
                    $actorName = 'Donor';
                } elseif ($this instanceof Lpa\PeopleToNotifyController) {
                    $actorName = 'Person to notify';
                } elseif ($this instanceof Lpa\PrimaryAttorneyController) {
                    $includeTrusts = true;
                    $actorName = 'Attorney';
                } elseif ($this instanceof Lpa\ReplacementAttorneyController) {
                    $includeTrusts = true;
                    $actorName = 'Replacement attorney';
                }

                //  Generate the URL to redirect to reuse details
                $reuseDetailsUrl = $this->getReuseDetailsUrl([
                    'calling-url'    => $this->getRequest()->getUri()->getPath(),
                    'include-trusts' => $includeTrusts,
                    'actor-name'     => $actorName,
                ]);

                return $this->redirect()->toUrl($reuseDetailsUrl);
            }
        }
    }

    /**
     * Construct the reuse details URL with the provided query parameters
     *
     * @param array $queryParams
     * @return string
     */
    protected function getReuseDetailsUrl(array $queryParams)
    {
        return $this->url()->fromRoute('lpa/reuse-details', [
            'lpa-id' => $this->getLpa()->id,
        ], [
            'query' => $queryParams
        ]);
    }

    /**
     * Function to inspect the current MVC route and determine if some reuse details are trying to be used
     * If they are then obtain them and bind them to the form and return an appropriate boolean value
     *
     * @param   AbstractActorForm $actorForm
     * @return  bool
     */
    protected function reuseActorDetails(AbstractActorForm $actorForm)
    {
        $routeMatch = $this->getEvent()->getRouteMatch();

        if ($routeMatch instanceof Router\RouteMatch) {
            $actorReuseDetails = $this->getActorReuseDetails();

            if ($routeMatch instanceof Router\Http\RouteMatch) {
                //  We can reuse the details from this point if a post value has been provided and there is exactly one reuse option available (i.e. the session user)
                $reuseDetailsIndex = $this->request->getPost('reuse-details');

                if ($reuseDetailsIndex == '0' && count($actorReuseDetails) == 1) {
                    $actorDetailsToReuse = array_pop($actorReuseDetails);
                    $actorForm->bind($actorDetailsToReuse['data']);

                    return true;
                }
            } else {
                //  Get the reuse details index from the route
                $reuseDetailsIndex = $routeMatch->getParam('reuseDetailsIndex');

                if ($reuseDetailsIndex >= -1 || $reuseDetailsIndex == 't') {
                    //  If we are using a proper actor index (i.e. zero, positive or 't' for trust) then attempt to get the actor reuse details and bind them to the abstract form
                    if (array_key_exists($reuseDetailsIndex, $actorReuseDetails)) {
                        //  Bind the actor data to the main form
                        $actorDetailsToReuse = $actorReuseDetails[$reuseDetailsIndex]['data'];
                        $actorForm->bind($actorDetailsToReuse);
                    }

                    return true;
                }
            }
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
        //  If required add the back button URL
        if (count($this->getActorReuseDetails()) > 1) {
            //  Get the back button URL from the current matched route name
            $routeMatch = $this->getEvent()->getRouteMatch();
            $backButtonUrl = $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $this->getLpa()->id]);

            //  If this request is from a forwarded request then try to extract the back button URL from the route params instead
            if ($routeMatch instanceof Router\RouteMatch && !$routeMatch instanceof Router\Http\RouteMatch) {
                $backButtonUrl = $routeMatch->getParam('callingUrl');
            }

            //  Add the back button URL but make sure that the add trust views go back to the normal add views
            $viewModel->backButtonUrl = str_replace('add-trust', 'add', $backButtonUrl);
        }
    }

    /**
     * Return an array of actor details that can be utilised in a "reuse" scenario
     * The boolean flag will determine if trusts should be included
     *
     * @param   boolean $includeTrusts
     * @param   boolean $forCorrespondent
     * @return  array
     */
    protected function getActorReuseDetails($includeTrusts = true, $forCorrespondent = false)
    {
        //  If this is the correspondent controller then the forCorrespondent flag MUST be true
        if ($this instanceof Lpa\CorrespondentController) {
            $forCorrespondent = true;
        }

        //  Initialise the reuse details details array
        $actorReuseDetails = [];

        //  If this is not a request to get trust data, and the session user data hasn't already been used, add it now
        $this->addCurrentUserDetailsForReuse($actorReuseDetails, !$forCorrespondent);

        //  If this is a request for the correspondent data then use the details from the current LPA
        if ($forCorrespondent) {
            //  Using the data from the LPA document add options for the donor and primary attorneys
            $lpaDocument = $this->getLpa()->document;

            $actorReuseDetails[] = $this->getReuseDetailsForActor($lpaDocument->donor->toArray(), Correspondence::WHO_DONOR, '(donor)');

            foreach ($lpaDocument->primaryAttorneys as $attorney) {
                $actorReuseDetails[] = $this->getReuseDetailsForActor($attorney->toArray(), Correspondence::WHO_ATTORNEY, '(primary attorney)');
            }

            foreach ($lpaDocument->getReplacementAttorneys() as $attorney) {
                $actorReuseDetails[] = $this->getReuseDetailsForActor($attorney->toArray(), Correspondence::WHO_ATTORNEY, '(replacement attorney)');
            }

            if ($lpaDocument->certificateProvider instanceof CertificateProvider) {
                $actorReuseDetails[] = $this->getReuseDetailsForActor($lpaDocument->certificateProvider->toArray(), Correspondence::WHO_CERTIFICATE_PROVIDER, '(certificate provider)');
            }
        } else {
            //  Loop through the seed details for this LPA
            foreach ($this->getSeedLpaActorDetails() as $type => $actorData) {
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
                            $isTrust = ($singleActorData['type'] == 'trust');

                            if ($isTrust && (!$includeTrusts || !$this->allowTrust())) {
                                continue;
                            }

                            $attorneyReuseActorDetails = $this->getReuseDetailsForActor($singleActorData, $actorType, $suffixText);

                            if ($isTrust) {
                                $actorReuseDetails['t'] = $attorneyReuseActorDetails;
                            } else {
                                $actorReuseDetails[] = $attorneyReuseActorDetails;
                            }
                        }
                        break;
                    case 'peopleToNotify':
                        foreach ($actorData as $singleActorData) {
                            $actorReuseDetails[] = $this->getReuseDetailsForActor($singleActorData, $actorType, '(was a person to be notified)');
                        }
                        break;
                }
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
    private function addCurrentUserDetailsForReuse(array &$actorReuseDetails, $checkIfAlreadyUsed = true)
    {
        //  Check that the current session user details have not already been used
        $currentUserDetailsUsedToBeAdded = true;
        $userDetailsObj = $this->getUser();

        //  Check to see if the user details have already been used if necessary
        if ($checkIfAlreadyUsed) {
            foreach ($this->getActorsList(null, false) as $actorsListItem) {
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
                'label' => sprintf('%s %s (myself)', $userDetailsObj->name->first, $userDetailsObj->name->last),
                'data'  => $userDetails,
            ];
        }
    }

    /**
     * Generate a list of actors already associated with the current LPA
     * If required filter by the calling action for the specific actor
     *
     * @param   integer $actorIndexToExclude
     * @param   boolean $filterByActorAction
     * @return  array
     */
    protected function getActorsList($actorIndexToExclude = null, $filterByActorAction = true)
    {
        $actorsList = [];

        //  Ensure the index to exclude is an integer or null
        $actorIndexToExclude = (is_null($actorIndexToExclude) ? null : intval($actorIndexToExclude));

        //  Determine which route we have come from so the results below can be filtered
        //  If the filter flag was passed into this function as false then set all flags below to false so no filtering takes place
        $isCertificateProviderRoute = ($filterByActorAction && $this instanceof Lpa\CertificateProviderController);
        $isDonorRoute = ($filterByActorAction && $this instanceof Lpa\DonorController);
        $isPeopleToModifyRoute = ($filterByActorAction && $this instanceof Lpa\PeopleToNotifyController);
        $isPrimaryAttorneyRoute = ($filterByActorAction && $this instanceof Lpa\PrimaryAttorneyController);
        $isReplacementAttorneyRoute = ($filterByActorAction && $this instanceof Lpa\ReplacementAttorneyController);

        $lpaDocument = $this->getLpa()->document;

        //  If there is a donor present in the LPA and we are editing it, or adding/editing people to notify then do NOT include in the actor list
        if (!$isDonorRoute && !$isPeopleToModifyRoute && $lpaDocument->donor instanceof Donor) {
            $actorsList[] = $this->getActorDetails($lpaDocument->donor, 'donor');
        }

        //  If there is a certificate provider present in the LPA and we are editing it, or adding/editing people to notify then do NOT include in the actor list
        if (!$isCertificateProviderRoute && !$isPeopleToModifyRoute && $lpaDocument->certificateProvider instanceof CertificateProvider) {
            $actorsList[] = $this->getActorDetails($lpaDocument->certificateProvider, 'certificate provider');
        }

        //  Include all of the primary attorney details unless we are adding/editing a replacement attorney or we are editing that particular primary attorney
        if (!$isReplacementAttorneyRoute) {
            foreach ($lpaDocument->primaryAttorneys as $idx => $attorney) {
                //  We are editing this attorney so do not add it to the actor list
                if ($isPrimaryAttorneyRoute && $actorIndexToExclude === $idx) {
                    continue;
                }

                if ($attorney instanceof Attorneys\Human) {
                    $actorsList[] = $this->getActorDetails($attorney, 'attorney');
                }
            }
        }

        //  Include all of the replacement attorney details unless we are adding/editing a primary attorney or we are editing that particular replacement attorney
        if (!$isPrimaryAttorneyRoute) {
            foreach ($lpaDocument->replacementAttorneys as $idx => $attorney) {
                //  We are editing this attorney so do not add it to the actor list
                if ($isReplacementAttorneyRoute && $actorIndexToExclude === $idx) {
                    continue;
                }

                if ($attorney instanceof Attorneys\Human) {
                    $actorsList[] = $this->getActorDetails($attorney, 'replacement attorney');
                }
            }
        }

        //  Include all of the people to notify unless we adding/editing a donor or certificate provider
        if (!$isDonorRoute && !$isCertificateProviderRoute) {
            foreach ($lpaDocument->peopleToNotify as $idx => $notifiedPerson) {
                //  We are editing this person to notify so do not add it to the actor list
                if ($isPeopleToModifyRoute && $actorIndexToExclude === $idx) {
                    continue;
                }

                $actorsList[] = $this->getActorDetails($notifiedPerson, 'person to notify');    //  Use "person" rather than "people" to ensure the JS warning is phrased correctly
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

        if (isset($actorData->name) && ($actorData->name instanceof Name || $actorData->name instanceof LongName)) {
            $actorDetails = [
                'firstname' => $actorData->name->first,
                'lastname'  => $actorData->name->last,
                'type'      => $actorType
            ];
        }

        return $actorDetails;
    }

    /**
     * Simple function to get the actor details from a seed LPA if there is one
     *
     * @return array
     */
    private function getSeedLpaActorDetails()
    {
        $seedDetails = [];
        $lpa = $this->getLpa();
        $seedId = $lpa->seed;

        if (!is_null($seedId)) {
            $cloneContainer = new Container('clone');

            if (!$cloneContainer->offsetExists($seedId)) {
                //  The data isn't in the session - get it now
                $seedActors = $this->getLpaApplicationService()->getSeedDetails($lpa->id);
                $cloneContainer->$seedId = $seedActors;
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
    private function getReuseDetailsForActor(array $actorData, $actorType, $suffixText = '')
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
     * DON'T allow if this is a Health and Well-being LPA or if a trust has already been used
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
     * If a correspondent is already set in the LPA and the core data of the actor selected (donor and attorney only) is updated/deleted then update/delete the data in the correspondent also
     *
     * @param AbstractData $actor
     * @param bool $isDelete
     */
    protected function updateCorrespondentData(AbstractData $actor, $isDelete = false)
    {
        $lpa = $this->getLpa();
        $correspondent = $lpa->document->correspondent;

        if ($correspondent instanceof Correspondence) {
            //  Only allow the data to be updated if the actor type is correct
            if (($actor instanceof Donor && $correspondent->who == Correspondence::WHO_DONOR)
                || ($actor instanceof Attorneys\AbstractAttorney && $correspondent->who == Correspondence::WHO_ATTORNEY)
                || ($actor instanceof CertificateProvider && $correspondent->who == Correspondence::WHO_CERTIFICATE_PROVIDER)) {

                if ($isDelete) {
                    if (!$this->getLpaApplicationService()->deleteCorrespondent($lpa)) {
                        throw new \RuntimeException('API client failed to delete correspondent for id: ' . $lpa->id);
                    }
                } else {
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
                            $correspondent->name = new LongName($actor->name->flatten());
                        }

                        $correspondent->address = $actor->address;

                        if (!$this->getLpaApplicationService()->setCorrespondent($lpa, $correspondent)) {
                            throw new \RuntimeException('API client failed to update correspondent for id: ' . $lpa->id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Simple method to return a boolean indicating if the provided attorney is also set as the correspondent for this LPA
     *
     * @param AbstractAttorney $attorney
     * @return bool
     */
    protected function attorneyIsCorrespondent(AbstractAttorney $attorney)
    {
        $correspondent = $this->getLpa()->getDocument()->getCorrespondent();

        if ($correspondent instanceof Correspondence && $correspondent->getWho() == Correspondence::WHO_ATTORNEY) {
            //  Compare the appropriate name and address
            $nameToCompare = ($attorney instanceof TrustCorporation ? $correspondent->getCompany() : new Name($correspondent->getName()->flatten()));
            return ($attorney->getName() == $nameToCompare && $correspondent->getAddress() == $attorney->getAddress());
        }

        return false;
    }
}
