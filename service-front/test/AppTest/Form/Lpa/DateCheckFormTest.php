<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\DateCheckForm;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use Laminas\Form\Element\Submit;
use PHPUnit\Framework\TestCase;

final class DateCheckFormTest extends TestCase
{
    private function createAttorney(int $id = 1): Human
    {
        return new Human([
            'id'      => $id,
            'name'    => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney'],
            'dob'     => ['date' => '1970-01-01'],
            'address' => ['address1' => '1 Street', 'postcode' => 'SW1A 1AA'],
        ]);
    }

    private function createMinimalLpa(string $type = Document::LPA_TYPE_PF): Lpa
    {
        return new Lpa([
            'document' => [
                'type'                 => $type,
                'primaryAttorneys'     => [$this->createAttorney(1)],
                'replacementAttorneys' => [],
                'whoIsRegistering'     => null,
            ],
            'completedAt' => null,
        ]);
    }

    public function testFormBuildsWithPropertyAndFinancialLpa(): void
    {
        $lpa  = $this->createMinimalLpa(Document::LPA_TYPE_PF);
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        $this->assertTrue($form->has('submit'));
        $this->assertInstanceOf(Submit::class, $form->get('submit'));
    }

    public function testFormBuildsWithHealthAndWelfareLpa(): void
    {
        $lpa  = $this->createMinimalLpa(Document::LPA_TYPE_HW);
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        // HW adds an extra sign-date-donor-life-sustaining fieldset
        $this->assertTrue($form->has('sign-date-donor-life-sustaining'));
        $this->assertTrue($form->has('sign-date-donor'));
    }

    public function testFormBuildsPfWithoutLifeSustainingFieldset(): void
    {
        $lpa  = $this->createMinimalLpa(Document::LPA_TYPE_PF);
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        $this->assertFalse($form->has('sign-date-donor-life-sustaining'));
        $this->assertTrue($form->has('sign-date-donor'));
    }

    public function testFormAddsFieldsetForEachPrimaryAttorney(): void
    {
        $lpa = new Lpa([
            'document' => [
                'type'                 => Document::LPA_TYPE_PF,
                'primaryAttorneys'     => [$this->createAttorney(1), $this->createAttorney(2)],
                'replacementAttorneys' => [],
                'whoIsRegistering'     => null,
            ],
            'completedAt' => null,
        ]);
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        $this->assertTrue($form->has('sign-date-attorney-0'));
        $this->assertTrue($form->has('sign-date-attorney-1'));
    }

    public function testFormAddsApplicantFieldsetsWhenCompletedAndDonorRegistering(): void
    {
        $lpa = new Lpa([
            'document' => [
                'type'                 => Document::LPA_TYPE_PF,
                'primaryAttorneys'     => [$this->createAttorney()],
                'replacementAttorneys' => [],
                'whoIsRegistering'     => 'donor',
            ],
            'completedAt' => new \DateTime(),
        ]);
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        $this->assertTrue($form->has('sign-date-applicant-0'));
    }

    public function testFormAddsReplacementAttorneyFieldsets(): void
    {
        $lpa = new Lpa([
            'document' => [
                'type'                 => Document::LPA_TYPE_PF,
                'primaryAttorneys'     => [$this->createAttorney(1)],
                'replacementAttorneys' => [$this->createAttorney(2)],
                'whoIsRegistering'     => null,
            ],
            'completedAt' => null,
        ]);
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        $this->assertTrue($form->has('sign-date-replacement-attorney-0'));
    }

    public function testFormAcceptsLpaAsFirstArgumentToConstructor(): void
    {
        $lpa  = $this->createMinimalLpa();
        $form = new DateCheckForm(['lpa' => $lpa]);
        $form->init();

        $this->assertTrue($form->has('sign-date-donor'));
    }
}
