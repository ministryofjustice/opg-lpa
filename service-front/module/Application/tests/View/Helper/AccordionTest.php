<?php

namespace ApplicationTest\View\Helper;

use Application\View\Helper\Accordion;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Mockery;
use Zend\Router\RouteMatch;

/**
 * AccordionTest
 */
class AccordionTest extends MockeryTestCase
{
    public function testLpaType()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/donor',
            'lpa/life-sustaining',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/form-type', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testDonor()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/life-sustaining',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/donor', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testLifeSustaining()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/lpa/how-primary-attorneys-make-decision', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testWhenLpaStarts()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/when-lpa-starts', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testPrimaryAttorney()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/primary-attorney', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testPrimaryAttorneyDecision()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/how-primary-attorneys-make-decision', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testReplacementAttorney()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/replacement-attorney', $expectedTopRoutes, $expectedBottomRoutes);

        //  Change to one primary attorney
        $lpa->document->primaryAttorneys = [
            $lpa->document->primaryAttorneys[0]
        ];

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
        ];

        //  Removing the primary attorney forces the user to confirm replacement attorneys and how they make decisions before they can continue so no bottom routes are accessible
        $expectedBottomRoutes = [];

        $this->assertAccordionRoutes($lpa, 'lpa/replacement-attorney', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testReplacementAttorneyStepIn()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/when-replacement-attorney-step-in', $expectedTopRoutes, $expectedBottomRoutes);
    }



    public function testReplacementAttorneyMakeDecision()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/how-replacement-attorneys-make-decision', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testCertificateProvider()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/certificate-provider', $expectedTopRoutes, $expectedBottomRoutes);

        //  Change when decisions
        $lpa->document->replacementAttorneyDecisions->when = 'first';

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/certificate-provider', $expectedTopRoutes, $expectedBottomRoutes);

        //  Change when decisions
        $lpa->document->primaryAttorneyDecisions->how = 'depends';

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/certificate-provider', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testPeopleToNotify()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/people-to-notify', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testInstructions()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'review-link'
        ];

        $expectedBottomRoutes = [
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/instructions', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testApplicant()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'review-link',
        ];

        $expectedBottomRoutes = [
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/applicant', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testCorrespondent()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'review-link',
        ];

        $expectedBottomRoutes = [
            'lpa/who-are-you',
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/correspondent', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testWhoAreYou()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'review-link',
        ];

        $expectedBottomRoutes = [
            'lpa/repeat-application',
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/who-are-you', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testRepeatApplication()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'review-link',
        ];

        $expectedBottomRoutes = [
            'lpa/fee-reduction',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/repeat-application', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testFeeReduction()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));
        $lpa->id = 99999999;
        $lpa->createdAt = new \DateTime();

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'lpa/certificate-provider',
            'lpa/people-to-notify',
            'lpa/instructions',
            'lpa/applicant',
            'lpa/correspondent',
            'lpa/who-are-you',
            'lpa/repeat-application',
            'review-link',
        ];

        $expectedBottomRoutes = [];

        $this->assertAccordionRoutes($lpa, 'lpa/fee-reduction', $expectedTopRoutes, $expectedBottomRoutes);
    }

    private function assertAccordionRoutes(Lpa $lpa, $currentRoute, array $expectedTopRoutes, array $expectedBottomRoutes)
    {
        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')
                   ->andReturn($currentRoute);

        $accordion = new Accordion($routeMatch);

        //  Create the expected top routes in the required format
        $expectedTopRoutesFormatted = [];

        foreach ($expectedTopRoutes as $expectedTopRoute) {
            $expectedTopRoutesFormatted[] = [
                'routeName' => $expectedTopRoute,
            ];
        }

        $topRoutes = $accordion->__invoke($lpa)->top();
        $this->assertEquals($expectedTopRoutesFormatted, $topRoutes);

        //  Create the expected bottom routes in the required format
        $expectedBottomRoutesFormatted = [];

        foreach ($expectedBottomRoutes as $expectedBottomRoute) {
            $expectedBottomRoutesFormatted[] = [
                'routeName' => $expectedBottomRoute,
            ];
        }

        $bottomRoutes = $accordion->__invoke($lpa)->bottom();
        $this->assertEquals($expectedBottomRoutesFormatted, $bottomRoutes);
    }
}
