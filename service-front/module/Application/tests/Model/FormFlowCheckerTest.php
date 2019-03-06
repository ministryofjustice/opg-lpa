<?php

namespace ApplicationTest\Model;

use Application\Model\FormFlowChecker;
use ApplicationTest\Bootstrap;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use DateTime;
use Exception;
use RuntimeException;

/**
 * FormFlowChecker unit test suite
 *
 * This set of unit tests with execute sequentially starting with a basic LPA datamodel - which will
 * check the correct position in the flow to return the user to
 */
class FormFlowCheckerTest extends MockeryTestCase
{
    /**
     * LPA document to test
     *
     * @var Lpa
     */
    private $lpa;

    /**
     * @var FormFlowChecker
     */
    private $checker;

    /**
     * Available routes concerning an LPA document
     *
     * @var array
     */
    private $lpaRoutes = [];

    /**
     * Next route
     *
     * @var array
     */
    private $nextRouteDestinations = [
        'lpa-type-no-id'                                => 'lpa/donor',
        'lpa/form-type'                                 => 'lpa/donor',
        'lpa/donor'                                     => [
            'lpa/when-lpa-starts',
            'lpa/life-sustaining'
        ],
        'lpa/donor/add'                                 => 'lpa/donor',
        'lpa/donor/edit'                                => 'lpa/donor',
        'lpa/when-lpa-starts'                           => 'lpa/primary-attorney',
        'lpa/life-sustaining'                           => 'lpa/primary-attorney',
        'lpa/primary-attorney'                          => [
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney'
        ],
        'lpa/primary-attorney/add'                      => 'lpa/primary-attorney',
        'lpa/primary-attorney/edit'                     => 'lpa/primary-attorney',
        'lpa/primary-attorney/delete'                   => 'lpa/primary-attorney',
        'lpa/primary-attorney/add-trust'                => 'lpa/primary-attorney',
        'lpa/how-primary-attorneys-make-decision'       => 'lpa/replacement-attorney',
        'lpa/replacement-attorney'                      => [
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider'
        ],
        'lpa/replacement-attorney/add'                  => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/edit'                 => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/delete'               => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/add-trust'            => 'lpa/replacement-attorney',
        'lpa/when-replacement-attorney-step-in'         => [
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider'
        ],
        'lpa/how-replacement-attorneys-make-decision'   => 'lpa/certificate-provider',
        'lpa/certificate-provider'                      => 'lpa/people-to-notify',
        'lpa/certificate-provider/add'                  => 'lpa/certificate-provider',
        'lpa/certificate-provider/edit'                 => 'lpa/certificate-provider',
        'lpa/certificate-provider/delete'               => 'lpa/certificate-provider',
        'lpa/people-to-notify'                          => 'lpa/instructions',
        'lpa/people-to-notify/add'                      => 'lpa/people-to-notify',
        'lpa/people-to-notify/edit'                     => 'lpa/people-to-notify',
        'lpa/people-to-notify/delete'                   => 'lpa/people-to-notify',
        'lpa/instructions'                              => 'lpa/applicant',
        'lpa/applicant'                                 => 'lpa/correspondent',
        'lpa/correspondent'                             => 'lpa/who-are-you',
        'lpa/correspondent/edit'                        => 'lpa/correspondent',
        'lpa/who-are-you'                               => 'lpa/repeat-application',
        'lpa/repeat-application'                        => 'lpa/fee-reduction',
        'lpa/fee-reduction'                             => 'lpa/checkout',
        'lpa/payment'                                   => 'lpa/payment/summary',
        'lpa/payment/summary'                           => 'lpa/complete',
        'lpa/checkout/cheque'                           => 'lpa/complete',
        'lpa/checkout/confirm'                          => 'lpa/complete',
        'lpa/checkout/pay/response'                     => 'lpa/complete',

    ];

    protected function setUp()
    {
        $config = require __DIR__ . '/../../config/module.routes.php';

        $allRoutes = $config['router']['routes'];

        //  Extract the LPA routes from the service manager
        $this->extractRoutes($allRoutes);

        //  Create as is done in API

        $this->lpa = new Lpa([
            'id'                => 1234567890,
            'startedAt'         => new DateTime(),
            'updatedAt'         => new DateTime(),
            'user'              => "8426351c938bf61759a57d0eb0823ebb",
            'locked'            => false,
            'whoAreYouAnswered' => false,
            'document'          => new Document(),
        ]);

        $this->checker = new FormFlowChecker($this->lpa);
    }

    private function extractRoutes(array $routesData, $parentRoute = '')
    {
        foreach ($routesData as $routeName => $routeData) {
            $thisRouteName = $parentRoute . $routeName;

            //  Only add the route if it includes the string 'lpa'
            if (stripos($thisRouteName, 'lpa') === 0) {
                $this->lpaRoutes[] = $thisRouteName;
            }

            if (isset($routeData['child_routes']) && is_array($routeData['child_routes'])) {
                $this->extractRoutes($routeData['child_routes'], $thisRouteName . '/');
            }
        }
    }

    public function testRouteRedirectToDashboardNoLPA()
    {
        $checker = new FormFlowChecker();

        //  Loop through the LPA routes - we should be redirected to the dashboard if there is no LPA set
        foreach ($this->lpaRoutes as $lpaRoute) {
            $this->assertEquals('user/dashboard', $checker->getNearestAccessibleRoute($lpaRoute));
        }
    }

    public function testRouteException()
    {
        $this->expectException('RuntimeException');

        $this->checker->getNearestAccessibleRoute('invalid-current-route-name');
    }

    public function testRouteRedirectLockedLpa()
    {
        $lockedLpaPermittedPages = [
            'lpa/complete',
            'lpa/date-check',
            'lpa/date-check/complete',
            'lpa/date-check/valid',
            'lpa/download',
            'lpa/download/file',
            'lpa/download/check',
        ];

        //  Set up the LPA
        $this->lpa->locked = true;

        //  Loop through the LPA routes - we should be redirected to the view documents page if the page isn't permitted
        foreach ($this->lpaRoutes as $lpaRoute) {
            if (!in_array($lpaRoute, $lockedLpaPermittedPages)) {
                $this->assertEquals('lpa/view-docs', $this->checker->getNearestAccessibleRoute($lpaRoute));
            }
        }
    }

    public function testRouteRedirectToDashboard()
    {
        $lpa = new Lpa();
        $this->checker = new FormFlowChecker($lpa);

        $this->runAssertions('user/dashboard');
    }

    public function testRouteRedirectToType()
    {
        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
        ];

        $this->runAssertions('lpa/form-type', $permittedRoutes);
    }

    public function testRouteRedirectToDonor()
    {
        //  Set up the LPA
        $this->setLpaTypePF();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
        ];

        $this->runAssertions('lpa/donor', $permittedRoutes);
    }

    public function testRouteRedirectToWhenLpaStarts()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
        ];

        $this->runAssertions('lpa/when-lpa-starts', $permittedRoutes);
    }

    public function testRouteRedirectToLifeSustainingTreatment()
    {
        //  Set up the LPA
        $this->setLpaTypeHW()
             ->setLpaDonor();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/life-sustaining',
        ];

        $this->runAssertions('lpa/life-sustaining', $permittedRoutes);
    }

    public function testRouteRedirectToPrimaryAttorneyPF()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
        ];

        $this->runAssertions('lpa/primary-attorney', $permittedRoutes);
    }

    public function testRouteRedirectToPrimaryAttorneyHW()
    {
        //  Set up the LPA
        $this->setLpaTypeHW()
             ->setLpaDonor()
             ->setLpaLifeSustainingTreatment();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/life-sustaining',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
        ];

        $this->runAssertions('lpa/primary-attorney', $permittedRoutes);
    }

    public function testRouteRedirectToPrimaryAttorneyWhenTrustAdded()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney(false);

        //  Test the redirect back to primary attorney screen if trying to access add trust when a trust has already been added
        $this->assertEquals('lpa/primary-attorney', $this->checker->getNearestAccessibleRoute('lpa/primary-attorney/add-trust'));
    }

    public function testRouteRedirectToHowPrimaryAttorneysMakeDecisions()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
        ];

        $this->runAssertions('lpa/how-primary-attorneys-make-decision', $permittedRoutes);
    }

    public function testRouteRedirectToReplacementAttorney()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
        ];

        //  Set any special cases for redirects
        $specialCases = [
            //  If there is only one primary attorney then the make decisions route is not accessible
            'lpa/how-primary-attorneys-make-decision' => 'lpa/primary-attorney',
        ];

        $this->runAssertions('lpa/replacement-attorney', $permittedRoutes, $specialCases);
    }

    public function testRouteRedirectToReplacementAttorneyWhenTrustAdded()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addReplacementAttorney(false);

        //  Test the redirect back to primary attorney screen if trying to access add trust when a trust has already been added
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/replacement-attorney/add-trust'));
    }

    public function testRouteRedirectToReplacementAttorneyWhenMultiplePrimaryAttorneysDoNotMakeDecisionsJointlyAndSeverally()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionDepends()
             ->addReplacementAttorney()
             ->addReplacementAttorney();

        //  Test the redirect back to primary attorney screen if trying to access add trust when a trust has already been added
        $this->assertEquals('lpa/replacement-attorney', $this->checker->getNearestAccessibleRoute('lpa/how-replacement-attorneys-make-decision'));
    }

    public function testRouteRedirectToWhenReplacementAttorneysStepIn()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
        ];

        $this->runAssertions('lpa/when-replacement-attorney-step-in', $permittedRoutes);
    }

    public function testRouteRedirectToHowReplacementAttorneysMakeDecisions()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
        ];

        $this->runAssertions('lpa/how-replacement-attorneys-make-decision', $permittedRoutes);
    }

    public function testRouteRedirectToCertificateProvider()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
        ];

        $this->runAssertions('lpa/certificate-provider', $permittedRoutes);
    }

    public function testRouteRedirectToPeopleToNotify()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
        ];

        $this->runAssertions('lpa/people-to-notify', $permittedRoutes);
    }

    public function testRouteRedirectToInstructionsAndPreferences()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
        ];

        $this->runAssertions('lpa/instructions', $permittedRoutes);
    }

    public function testRouteRedirectToApplicant()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/valid',
        ];

        $this->runAssertions('lpa/applicant', $permittedRoutes);
    }

    public function testRouteRedirectToCorrespondent()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/valid',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/correspondent/edit',
        ];

        $this->runAssertions('lpa/correspondent', $permittedRoutes);
    }

    public function testRouteRedirectToWhoAreYou()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant()
             ->setCorrespondent();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/valid',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/correspondent/edit',
        ];

        $this->runAssertions('lpa/who-are-you', $permittedRoutes);
    }

    public function testRouteRedirectToRepeatApplication()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant()
             ->setCorrespondent()
             ->setWhoAreYou();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/valid',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/correspondent/edit',
            'lpa/who-are-you',
        ];

        $this->runAssertions('lpa/repeat-application', $permittedRoutes);
    }

    public function testRouteRedirectToFeeReduction()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant()
             ->setCorrespondent()
             ->setWhoAreYou()
             ->setRepeatApplication();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/valid',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/correspondent/edit',
            'lpa/who-are-you',
            'lpa/repeat-application',
        ];

        $this->runAssertions('lpa/fee-reduction', $permittedRoutes);
    }

    public function testRouteRedirectToCheckout()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant()
             ->setCorrespondent()
             ->setWhoAreYou()
             ->setRepeatApplication()
             ->setFeeReduction();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/valid',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/correspondent/edit',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
            'lpa/checkout/cheque',
            'lpa/checkout/pay',
            'lpa/checkout/pay/response',
            'lpa/checkout/confirm',
        ];

        $this->runAssertions('lpa/checkout', $permittedRoutes);
    }

    public function testRouteRedirectToComplete()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
             ->setReplacementAttorneysMakeDecisionJointlySeverally()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant()
             ->setCorrespondent()
             ->setWhoAreYou()
             ->setRepeatApplication()
             ->setFeeReduction()
             ->setPayment()
             ->setLpaCompleted();

        $permittedRoutes = [
            'lpa',
            'lpa-type-no-id',
            'lpa/form-type',
            'lpa/donor',
            'lpa/donor/add',
            'lpa/donor/edit',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/primary-attorney/add',
            'lpa/primary-attorney/add-trust',
            'lpa/primary-attorney/edit',
            'lpa/primary-attorney/confirm-delete',
            'lpa/primary-attorney/delete',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/replacement-attorney/add',
            'lpa/replacement-attorney/add-trust',
            'lpa/replacement-attorney/edit',
            'lpa/replacement-attorney/confirm-delete',
            'lpa/replacement-attorney/delete',
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider',
            'lpa/certificate-provider/add',
            'lpa/certificate-provider/edit',
            'lpa/certificate-provider/confirm-delete',
            'lpa/certificate-provider/delete',
            'lpa/people-to-notify',
            'lpa/people-to-notify/add',
            'lpa/people-to-notify/edit',
            'lpa/people-to-notify/confirm-delete',
            'lpa/people-to-notify/delete',
            'lpa/summary',
            'lpa/instructions',
            'lpa/date-check',
            'lpa/date-check/complete',
            'lpa/date-check/valid',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/correspondent/edit',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
            'lpa/checkout',
            'lpa/checkout/cheque',
            'lpa/checkout/pay',
            'lpa/checkout/pay/response',
            'lpa/checkout/confirm',
            'lpa/view-docs',
        ];

        $this->runAssertions('lpa/complete', $permittedRoutes);
    }

    private function runAssertions($earliestRedirectRoute, array $permittedRoutes = [], array $specialCases = [])
    {
        //  Set up any routes that are always permitted
        $permittedRoutes = array_merge($permittedRoutes, [
            'lpa/more-info-required',
            'lpa/download',
            'lpa/download/draft',
            'lpa/download/file',
            'lpa/download/check',
            'lpa/reuse-details',
        ]);

        //  Set up any special cases for all tests against specific types of LPA
        if (isset($this->lpa->document) && isset($this->lpa->document->type)) {
            if ($this->lpa->document->type == Document::LPA_TYPE_PF) {
                //  Life sustaining treatment question is not applicable for P&F LPA
                $specialCases['lpa/life-sustaining'] = 'lpa/donor';
            } elseif ($this->lpa->document->type == Document::LPA_TYPE_HW) {
                //  When LPA starts question is not applicable for H&W LPA
                $specialCases['lpa/when-lpa-starts'] = 'lpa/donor';
            }
        }

        //  Loop through the LPA routes - we should be redirected to the dashboard if the LPA doesn't have a document set
        foreach ($this->lpaRoutes as $lpaRoute) {
            $expectedRoute = $earliestRedirectRoute;

            if (in_array($lpaRoute, $permittedRoutes)) {
                $expectedRoute = $lpaRoute;
            } elseif (array_key_exists($lpaRoute, $specialCases)) {
                $expectedRoute = $specialCases[$lpaRoute];
            }

            $this->assertEquals($expectedRoute, $this->checker->getNearestAccessibleRoute($lpaRoute));
        }
    }

    public function testNextRoutesForAllRoutes()
    {
        foreach ($this->lpaRoutes as $lpaRoute) {
            //  Based on this route determine which route should come next
            $expectedRoute = $lpaRoute;

            if (array_key_exists($lpaRoute, $this->nextRouteDestinations)) {
                $expectedRoute = $this->nextRouteDestinations[$lpaRoute];

                //  If the expected route is an array then just set the expected route back to the lpaRoute value
                //  We will check the permutations of array values in the next tests
                if (is_array($expectedRoute)) {
                    $expectedRoute = $lpaRoute;
                }
            }

            $this->assertEquals($expectedRoute, $this->checker->nextRoute($lpaRoute));
        }
    }

    public function testNextRoutesFromDonorToWhenLpaStarts()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor();

        $this->assertEquals('lpa/when-lpa-starts', $this->checker->nextRoute('lpa/donor'));
    }

    public function testNextRoutesFromDonorToLifeSustainingTreatment()
    {
        //  Set up the LPA
        $this->setLpaTypeHW()
             ->setLpaDonor();

        $this->assertEquals('lpa/life-sustaining', $this->checker->nextRoute('lpa/donor'));
    }

    public function testNextRoutesFromPrimaryAttorneysToHowMakeDecisions()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney();

        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->nextRoute('lpa/primary-attorney'));
    }

    public function testNextRoutesFromPrimaryAttorneysToReplacementAttorneys()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->addPrimaryAttorney()
             ->setLpaStartsWhenNoCapacity();

        $this->assertEquals('lpa/replacement-attorney', $this->checker->nextRoute('lpa/primary-attorney'));
    }

    public function testNextRoutesFromReplacementAttorneysToWhenStepIn()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney();

        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->nextRoute('lpa/replacement-attorney'));
    }

    public function testNextRoutesFromReplacementAttorneysToHowMakeDecisions()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addReplacementAttorney()
             ->addReplacementAttorney();

        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->nextRoute('lpa/replacement-attorney'));
    }

    public function testNextRoutesFromReplacementAttorneysToCertificateProvider()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addReplacementAttorney();

        $this->assertEquals('lpa/certificate-provider', $this->checker->nextRoute('lpa/replacement-attorney'));
    }

    public function testNextRoutesFromWhenStepInToHowMakeDecisions()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();

        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->nextRoute('lpa/when-replacement-attorney-step-in'));
    }

    public function testNextRoutesFromWhenStepInToCertificateProvider()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally()
             ->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();

        $this->assertEquals('lpa/certificate-provider', $this->checker->nextRoute('lpa/when-replacement-attorney-step-in'));
    }

    public function testNextRoutesToFinalCheckForCompletedLpa()
    {
        //  Set up the LPA
        $this->setLpaTypePF()
             ->setLpaDonor()
             ->setLpaStartsWhenNoCapacity()
             ->addPrimaryAttorney()
             ->addReplacementAttorney()
             ->addCertificateProvider()
             ->addPersonToNotify()
             ->setInstructons()
             ->setLpaCreated()
             ->setApplicant()
             ->setCorrespondent()
             ->setWhoAreYou()
             ->setRepeatApplication()
             ->setFeeReduction();

        //  Loop through the LPA routes - if the LPA has been to the final check then the next route should always come back as the final check route
        foreach ($this->lpaRoutes as $lpaRoute) {
            $this->assertEquals('lpa/checkout', $this->checker->nextRoute($lpaRoute));
        }
    }

    public function testBackToFormPF()
    {
        //  Gradually build up a P&F LPA (as would happen if it was being filled in online)
        //  and keep checking the back to form result

        //  Check for LPA type
        $this->assertEquals('lpa/form-type', $this->checker->backToForm());

        //  Check for donor
        $this->setLpaTypePF()
             ->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->backToForm());

        //  Check for when LPA starts
        $this->setLpaStartsWhenNoCapacity();
        $this->assertEquals('lpa/when-lpa-starts', $this->checker->backToForm());

        //  Check for primary attorney
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->backToForm());

        //  Check for how primary attorneys make decisions
        $this->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->backToForm());

        //  Check for replacement attorney
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->backToForm());

        //  Check for when replacement attorney steps in
        $this->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->backToForm());

        //  Check for when replacement attorney steps in
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->backToForm());

        //  Check for certificate provider
        $this->addCertificateProvider();
        $this->assertEquals('lpa/certificate-provider', $this->checker->backToForm());

        //  Check for people to notify
        $this->addPersonToNotify();
        $this->assertEquals('lpa/people-to-notify', $this->checker->backToForm());

        //  Check for instructions and preferences
        $this->setInstructons();
        $this->assertEquals('lpa/instructions', $this->checker->backToForm());

        //  Check for applicant
        $this->setApplicant();
        $this->assertEquals('lpa/applicant', $this->checker->backToForm());

        //  Check for correspondent
        $this->setCorrespondent();
        $this->assertEquals('lpa/correspondent', $this->checker->backToForm());

        //  Check for who are you
        $this->setWhoAreYou();
        $this->assertEquals('lpa/who-are-you', $this->checker->backToForm());

        //  Check for repeat application
        $this->setRepeatApplication();
        $this->assertEquals('lpa/repeat-application', $this->checker->backToForm());

        //  Check for fee reduction
        $this->lpa->payment = new Payment();    //  Use a fully unpopulated payment object to get to the fee reduction screen
        $this->assertEquals('lpa/fee-reduction', $this->checker->backToForm());

        //  Check for checkout
        $this->setPayment();
        $this->assertEquals('lpa/checkout', $this->checker->backToForm());

        //  Check for view docs
        $this->setLpaCompleted();
        $this->assertEquals('lpa/view-docs', $this->checker->backToForm());
    }

    public function testBackToFormHW()
    {
        //  Gradually build up a H&W LPA (as would happen if it was being filled in online)
        //  and keep checking the back to form result

        //  Check for LPA type
        $this->assertEquals('lpa/form-type', $this->checker->backToForm());

        //  Check for donor
        $this->setLpaTypeHW()
             ->setLpaDonor();
        $this->assertEquals('lpa/donor', $this->checker->backToForm());

        //  Check for when life sustaining treatment
        $this->setLpaLifeSustainingTreatment();
        $this->assertEquals('lpa/life-sustaining', $this->checker->backToForm());

        //  Check for primary attorney
        $this->addPrimaryAttorney();
        $this->assertEquals('lpa/primary-attorney', $this->checker->backToForm());

        //  Check for how primary attorneys make decisions
        $this->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/how-primary-attorneys-make-decision', $this->checker->backToForm());

        //  Check for replacement attorney
        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->backToForm());

        //  Check for when replacement attorney steps in
        $this->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertEquals('lpa/when-replacement-attorney-step-in', $this->checker->backToForm());

        //  Check for when replacement attorney steps in
        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertEquals('lpa/how-replacement-attorneys-make-decision', $this->checker->backToForm());

        //  Check for certificate provider
        $this->addCertificateProvider();
        $this->assertEquals('lpa/certificate-provider', $this->checker->backToForm());

        //  Check for people to notify
        $this->addPersonToNotify();
        $this->assertEquals('lpa/people-to-notify', $this->checker->backToForm());

        //  Check for instructions and preferences
        $this->setInstructons();
        $this->assertEquals('lpa/instructions', $this->checker->backToForm());

        //  Check for applicant
        $this->setApplicant();
        $this->assertEquals('lpa/applicant', $this->checker->backToForm());

        //  Check for correspondent
        $this->setCorrespondent();
        $this->assertEquals('lpa/correspondent', $this->checker->backToForm());

        //  Check for who are you
        $this->setWhoAreYou();
        $this->assertEquals('lpa/who-are-you', $this->checker->backToForm());

        //  Check for repeat application
        $this->setRepeatApplication();
        $this->assertEquals('lpa/repeat-application', $this->checker->backToForm());

        //  Check for fee reduction
        $this->lpa->payment = new Payment();    //  Use a fully unpopulated payment object to get to the fee reduction screen
        $this->assertEquals('lpa/fee-reduction', $this->checker->backToForm());

        //  Check for checkout
        $this->setPayment();
        $this->assertEquals('lpa/checkout', $this->checker->backToForm());

        //  Check for view docs
        $this->setLpaCompleted();
        $this->assertEquals('lpa/view-docs', $this->checker->backToForm());
    }

    public function testBackToFormAccessNAResponses()
    {
        //  This test is to access the NA responses in the returnToWhenReplacementAttorneyStepIn and returnToHowReplacementAttorneysMakeDecision functions
        $this->setLpaTypeHW()
             ->setLpaDonor()
             ->setLpaLifeSustainingTreatment()
             ->addPrimaryAttorney()
             ->addPrimaryAttorney()
             ->setPrimaryAttorneysMakeDecisionDepends()
             ->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->backToForm());

        $this->addReplacementAttorney();
        $this->assertEquals('lpa/replacement-attorney', $this->checker->backToForm());
    }

    public function testFinalCheckAccessibleNoLpa()
    {
        $checker = new FormFlowChecker();

        $this->assertFalse($checker->finalCheckAccessible());
    }

    public function testFinalCheckAccessible()
    {
        //  Gradually build up a P&F LPA (as would happen if it was being filled in online)
        //  and keep checking the final check accessible result
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setLpaTypePF()
             ->setLpaDonor();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setLpaStartsWhenNoCapacity();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->addPrimaryAttorney();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->addPrimaryAttorney()
            ->setPrimaryAttorneysMakeDecisionJointlySeverally();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->addReplacementAttorney();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->addReplacementAttorney()
             ->setReplacementAttorneysStepInWhenLastPrimaryUnableAct();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setReplacementAttorneysMakeDecisionJointlySeverally();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->addCertificateProvider();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->addPersonToNotify();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setInstructons();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setApplicant();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setCorrespondent();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setWhoAreYou();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setRepeatApplication();
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->lpa->payment = new Payment();    //  Use a fully unpopulated payment object to get to the fee reduction screen
        $this->assertFalse($this->checker->finalCheckAccessible());

        $this->setPayment();
        $this->assertTrue($this->checker->finalCheckAccessible());
    }

    /*
     * ####################################
     * #### LPA set up functions below ####
     * ####################################
     */

    private function setLpaTypePF()
    {
        $this->lpa->document->type = Document::LPA_TYPE_PF;

        return $this;
    }

    private function setLpaTypeHW()
    {
        $this->lpa->document->type = Document::LPA_TYPE_HW;

        return $this;
    }

    private function setLpaDonor()
    {
        $this->lpa->document->donor = new Donor();

        return $this;
    }

    private function setLpaStartsWhenNoCapacity()
    {
        $this->setPrimaryAttorneyDecisions([
            'when'  => PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY,
        ]);

        return $this;
    }

    private function setLpaLifeSustainingTreatment()
    {
        $this->setPrimaryAttorneyDecisions([
            'canSustainLife' => true,
        ]);

        return $this;
    }

    private function addPrimaryAttorney($isHuman = true)
    {
        $this->lpa->document->primaryAttorneys[] = ($isHuman ? new Human() : new TrustCorporation());

        return $this;
    }

    private function setPrimaryAttorneysMakeDecisionDepends()
    {
        $this->setPrimaryAttorneyDecisions([
            'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
        ]);

        return $this;
    }

    private function setPrimaryAttorneysMakeDecisionJointlySeverally()
    {
        $this->setPrimaryAttorneyDecisions([
            'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ]);

        return $this;
    }

    private function setPrimaryAttorneyDecisions($params)
    {
        //  If the primary attorney decisions have not yet been set do that now
        if (!$this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            $this->lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
        }

        foreach ($params as $property => $value) {
            if (!property_exists($this->lpa->document->primaryAttorneyDecisions, $property)) {
                throw new RuntimeException('Unknown property for primaryAttorneyDecisions: ' . $property);
            }

            $this->lpa->document->primaryAttorneyDecisions->$property = $value;
        }

        return $this;
    }

    private function addReplacementAttorney($isHuman = true)
    {
        $this->lpa->document->replacementAttorneys[] = ($isHuman ? new Human() : new TrustCorporation());
        $this->lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED] = 1;

        return $this;
    }

    private function setReplacementAttorneysMakeDecisionJointlySeverally()
    {
        $this->setReplacementAttorneyDecisions([
            'how' => AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ]);

        return $this;
    }

    private function setReplacementAttorneysStepInWhenLastPrimaryUnableAct()
    {
        $this->setReplacementAttorneyDecisions([
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ]);

        return $this;
    }

    private function setReplacementAttorneyDecisions($params)
    {
        //  If the replacement attorney decisions have not yet been set do that now
        if (!$this->lpa->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
            $this->lpa->document->replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
        }

        foreach ($params as $property => $value) {
            if (!property_exists($this->lpa->document->replacementAttorneyDecisions, $property)) {
                throw new RuntimeException('Unknown property for replacementAttorneyDecisions: ' . $property);
            }

            $this->lpa->document->replacementAttorneyDecisions->$property = $value;
        }

        return $this;
    }

    private function addCertificateProvider()
    {
        $this->lpa->document->certificateProvider = new CertificateProvider();

        return $this;
    }

    private function addPersonToNotify()
    {
        $this->lpa->document->peopleToNotify[] = new NotifiedPerson();
        $this->lpa->metadata[Lpa::PEOPLE_TO_NOTIFY_CONFIRMED] = 1;

        return $this;
    }

    private function setInstructons()
    {
        $this->lpa->document->instruction = '...instructions...';

        return $this;
    }

    private function setLpaCreated()
    {
        $this->lpa->createdAt = new DateTime();

        return $this;
    }

    private function setApplicant()
    {
        $this->lpa->document->whoIsRegistering = 'donor';

        return $this;
    }

    private function setCorrespondent()
    {
        $this->lpa->document->correspondent = new Correspondence();

        return $this;
    }

    private function setWhoAreYou()
    {
        $this->lpa->whoAreYouAnswered = true;

        return $this;
    }

    private function setRepeatApplication()
    {
        $this->lpa->repeatCaseNumber = '123456';
        $this->lpa->metadata[Lpa::REPEAT_APPLICATION_CONFIRMED] = 1;

        return $this;
    }

    private function setFeeReduction()
    {
        if (!$this->lpa->payment instanceof Payment) {
            $this->lpa->payment = new Payment();
        }

        $this->lpa->payment->reducedFeeReceivesBenefits = false;
        $this->lpa->payment->reducedFeeAwardedDamages = null;
        $this->lpa->payment->reducedFeeLowIncome = true;
        $this->lpa->payment->reducedFeeUniversalCredit = null;

        //  Calculate the amount
        Calculator::calculate($this->lpa);

        return $this;
    }

    private function setPayment()
    {
        if (!$this->lpa->payment instanceof Payment) {
            $this->lpa->payment = new Payment();
        }

        $this->lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
        $this->lpa->payment->reference = "PAYMENT RECEIVED";

        //  Calculate the amount
        Calculator::calculate($this->lpa);

        return $this;
    }

    private function setLpaCompleted()
    {
        $this->lpa->completedAt = new DateTime();

        return $this;
    }
}
