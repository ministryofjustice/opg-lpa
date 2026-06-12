<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\ApplicantForm;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\TestCase;

final class ApplicantFormTest extends TestCase
{
    private function createAttorney(int $id, string $first = 'Test', string $last = 'Attorney'): Human
    {
        return new Human([
            'id'      => $id,
            'name'    => ['title' => 'Mr', 'first' => $first, 'last' => $last],
            'dob'     => ['date' => '1970-01-01'],
            'address' => ['address1' => '1 Street', 'postcode' => 'SW1A 1AA'],
        ]);
    }

    private function createLpa(array $attorneys, string $how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY): Lpa
    {
        return new Lpa([
            'document' => [
                'type'                     => Document::LPA_TYPE_PF,
                'primaryAttorneys'         => $attorneys,
                'replacementAttorneys'     => [],
                'primaryAttorneyDecisions' => ['how' => $how],
                'whoIsRegistering'         => null,
            ],
        ]);
    }

    public function testFormName(): void
    {
        $lpa  = $this->createLpa([$this->createAttorney(1)]);
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();
        $this->assertSame('form-applicant', $form->getName());
    }

    public function testHasWhoIsRegisteringRadioElement(): void
    {
        $lpa  = $this->createLpa([$this->createAttorney(1)]);
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();
        $this->assertTrue($form->has('whoIsRegistering'));
        $this->assertInstanceOf(Radio::class, $form->get('whoIsRegistering'));
    }

    public function testSingleAttorneyDoesNotAddAttorneyList(): void
    {
        $lpa  = $this->createLpa([$this->createAttorney(1)]);
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();
        $this->assertFalse($form->has('attorneyList'));
    }

    public function testMultipleAttorneysJointlyDoNotAddAttorneyList(): void
    {
        $lpa  = $this->createLpa(
            [$this->createAttorney(1), $this->createAttorney(2)],
            PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
        );
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();
        $this->assertFalse($form->has('attorneyList'));
    }

    public function testMultipleAttorneysJointlyAndSeverallyAddsAttorneyList(): void
    {
        $lpa  = $this->createLpa(
            [$this->createAttorney(1), $this->createAttorney(2)],
            PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY
        );
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();
        $this->assertTrue($form->has('attorneyList'));
    }

    public function testAttorneyValueOptionContainsAttorneyId(): void
    {
        $attorney = $this->createAttorney(42);
        $lpa      = $this->createLpa([$attorney]);
        $form     = new ApplicantForm(['lpa' => $lpa]);
        $form->init();

        $options = $form->get('whoIsRegistering')->getValueOptions();
        $this->assertSame('42', $options['attorney']['value']);
    }

    public function testDonorWhoIsRegisteringIsValid(): void
    {
        $lpa  = $this->createLpa([$this->createAttorney(1)]);
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();

        $form->setData(['whoIsRegistering' => 'donor']);
        $this->assertTrue($form->isValid());
    }

    public function testEmptyWhoIsRegisteringIsInvalid(): void
    {
        $lpa  = $this->createLpa([$this->createAttorney(1)]);
        $form = new ApplicantForm(['lpa' => $lpa]);
        $form->init();

        $form->setData(['whoIsRegistering' => '']);
        $this->assertFalse($form->isValid());
    }
}
