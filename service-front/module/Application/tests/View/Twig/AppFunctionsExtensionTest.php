<?php

declare(strict_types=1);

namespace ApplicationTest\View\Twig;

use Application\Form\Error\FormLinkedErrors;
use Application\Model\FormFlowChecker;
use Application\View\Twig\AppFunctionsExtension;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\TestCase;

final class AppFunctionsExtensionTest extends TestCase
{
    private AppFunctionsExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $formLinkedErrors = $this->createMock(FormLinkedErrors::class);
        $this->extension = new AppFunctionsExtension([], $formLinkedErrors);
    }

    public function testRegistersApplicantNamesFunction(): void
    {
        $functions = $this->extension->getFunctions();

        $names = array_map(
            static fn($fn) => $fn->getName(),
            $functions
        );

        $this->assertContains('applicant_names', $names);
    }

    public function testApplicantNamesReturnsTheDonorWhenWhoIsRegisteringIsDonor(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->document->whoIsRegistering = 'donor';

        $result = $this->extension->applicantNames($lpa);

        $this->assertSame('the donor', $result);
    }

    public function testApplicantNamesReturnsConcatenatedPrimaryAttorneyNamesWhenTheyAreRegistering(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $whoIsRegistering = [];
        foreach ($lpa->document->primaryAttorneys as $attorney) {
            $whoIsRegistering[] = $attorney->id;
        }
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        $expectedHumans = 'Dr Lilly Simpson, Mr Marcel Tanner and Mrs Annabella Collier';

        $result = $this->extension->applicantNames($lpa);

        $this->assertSame($expectedHumans, $result);
    }

    public function testApplicantNamesReturnsNullWhenWhoIsRegisteringIsNotSet(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $lpa->document->whoIsRegistering = null;

        $result = $this->extension->applicantNames($lpa);

        $this->assertNull($result);
    }

    public function testApplicantNamesReturnsNullForUnsupportedWhoIsRegisteringType(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $lpa->document->whoIsRegistering = 12345;

        $result = $this->extension->applicantNames($lpa);

        $this->assertNull($result);
    }

    public function testFinalCheckAccessibleDelegatesToFormFlowChecker(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $expected = FormFlowChecker::isFinalCheckAccessible($lpa);

        $actual = $this->extension->finalCheckAccessible($lpa);

        $this->assertSame($expected, $actual);
    }
}
