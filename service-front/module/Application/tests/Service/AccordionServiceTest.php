<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Service\AccordionService;
use DateTime;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\TestCase;

final class AccordionServiceTest extends TestCase
{
    /** @var string[] */
    private array $bars = [
        'lpa/form-type',
        'lpa/donor',
        'lpa/when-lpa-starts',
        'lpa/life-sustaining',
        'lpa/primary-attorney',
        'lpa/how-primary-attorneys-make-decision',
        'lpa/replacement-attorney',
        'lpa/when-replacement-attorney-step-in',
        'lpa/how-replacement-attorneys-make-decision',
        'lpa/certificate-provider',
        'lpa/people-to-notify',
        'lpa/instructions',
        'lpa/applicant',
        'lpa/correspondent',
        'lpa/who-are-you',
        'lpa/repeat-application',
        'lpa/fee-reduction',
    ];

    public function testLpaType(): void
    {
        $lpa = $this->loadLpa('hw.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = ['review-link'];

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

    public function testDonor(): void
    {
        $lpa = $this->loadLpa('hw.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'review-link',
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

    public function testLifeSustaining(): void
    {
        $lpa = $this->loadLpa('hw.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'review-link',
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

        $this->assertAccordionRoutes($lpa, 'lpa/life-sustaining', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testWhenLpaStarts(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'review-link',
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

    public function testPrimaryAttorney(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'review-link',
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

    public function testPrimaryAttorneyDecision(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'review-link',
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

    public function testReplacementAttorney(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'review-link',
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

        // Change to one primary attorney
        $lpa->document->primaryAttorneys = [
            $lpa->document->primaryAttorneys[0],
        ];

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
        ];

        // Removing the primary attorney forces the user to confirm replacement attorneys
        // and how they make decisions before they can continue so no bottom routes are accessible
        $expectedBottomRoutes = [];

        $this->assertAccordionRoutes($lpa, 'lpa/replacement-attorney', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testReplacementAttorneyStepIn(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'review-link',
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

    public function testReplacementAttorneyMakeDecision(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'review-link',
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

    public function testCertificateProvider(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in',
            'review-link',
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

        // Change when decisions
        $lpa->document->replacementAttorneyDecisions->when = 'first';
        $this->assertAccordionRoutes($lpa, 'lpa/certificate-provider', $expectedTopRoutes, $expectedBottomRoutes);

        // Change how decisions
        $lpa->document->primaryAttorneyDecisions->how = 'depends';

        $expectedTopRoutes = [
            'lpa/form-type',
            'lpa/donor',
            'lpa/when-lpa-starts',
            'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney',
            'review-link',
        ];

        $this->assertAccordionRoutes($lpa, 'lpa/certificate-provider', $expectedTopRoutes, $expectedBottomRoutes);
    }

    public function testPeopleToNotify(): void
    {
        $lpa = $this->loadLpa('pf.json');
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
            'review-link',
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

    public function testInstructions(): void
    {
        $lpa = $this->loadLpa('pf.json');
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
            'review-link',
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

    public function testApplicant(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;
        $lpa->createdAt = new DateTime();

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

    public function testCorrespondent(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;
        $lpa->createdAt = new DateTime();

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

    public function testWhoAreYou(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;
        $lpa->createdAt = new DateTime();

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

    public function testRepeatApplication(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;
        $lpa->createdAt = new DateTime();

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

    public function testFeeReduction(): void
    {
        $lpa = $this->loadLpa('pf.json');
        $lpa->id = 99999999;
        $lpa->createdAt = new DateTime();

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

    private function assertAccordionRoutes(
        Lpa $lpa,
        string $currentRoute,
        array $expectedTopRoutes,
        array $expectedBottomRoutes
    ): void {
        $service = new AccordionService($this->bars);

        $expectedTopRoutesFormatted = array_map(
            static fn (string $r): array => ['routeName' => $r],
            $expectedTopRoutes
        );

        $expectedBottomRoutesFormatted = array_map(
            static fn (string $r): array => ['routeName' => $r],
            $expectedBottomRoutes
        );

        $topRoutes = $service->top($lpa, $currentRoute);
        $this->assertEquals($expectedTopRoutesFormatted, $topRoutes);

        $bottomRoutes = $service->bottom($lpa, $currentRoute);
        $this->assertEquals($expectedBottomRoutesFormatted, $bottomRoutes);
    }

    private function loadLpa(string $fixtureName): Lpa
    {
        $path = __DIR__ . '/../fixtures/' . $fixtureName;
        return new Lpa(file_get_contents($path));
    }
}
