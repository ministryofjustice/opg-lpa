<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\Pdf\Service\Forms\Lp1h;
use mikehaertl\pdftk\Pdf;
use Mockery;

class Lp1hTest extends AbstractFormTestClass
{
    public function testGenerate()
    {
        $lpa = $this->getLpa(false);
        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateDraft()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Remove some details so the LPA is determined to be in a draft state
        $lpa->payment = null;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateSimpleLpaWithNoContinuationSheets()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Cut down the actors to the maximum number
        array_splice($lpa->document->primaryAttorneys, 4);
        array_splice($lpa->document->whoIsRegistering, 4);
        array_splice($lpa->document->replacementAttorneys, 2);
        array_splice($lpa->document->peopleToNotify, 4);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateCanSustainLifeFalse()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change when the life sustaining treatment questions can be made
        $lpa->document->primaryAttorneyDecisions->canSustainLife = false;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateDonorRegistering()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateSinglePrimaryAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateSingleReplacementAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of replacement attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateZeroReplacementAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateThreeAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys
        array_splice($lpa->document->primaryAttorneys, 3);
        array_splice($lpa->document->whoIsRegistering, 3);

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateTwoAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys
        array_splice($lpa->document->primaryAttorneys, 2);
        array_splice($lpa->document->whoIsRegistering, 2);

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateOneAttorneys()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];
        $lpa->document->replacementAttorneyDecisions = null;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateZeroAttorneys()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Remove primary attorneys and any concerned data
        $lpa->document->primaryAttorneys = [];
        $lpa->document->whoIsRegistering = [];

        //  Remove replacement attorneys and any concerned data
        $lpa->document->replacementAttorneys = [];

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePrimaryAttorneysActJointly()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the primary attorneys act to depends
        $lpa->document->primaryAttorneyDecisions->how = 'jointly';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateLessThanSixPeopleToNotify()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of people to notify to 3
        array_splice($lpa->document->peopleToNotify, 3);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateEmptyInstructionsAndPreferences()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Blank the instructions and preferences
        $lpa->document->instruction = '';
        $lpa->document->preference = '';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateContactDetailsEnteredManuallyFalse()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set contact details entered manually to false
        $lpa->document->correspondent->contactDetailsEnteredManually = false;

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateCorrespondentIsAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set the first attorney as the correspondent
        $firstAttorney = $lpa->document->primaryAttorneys[0];

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_ATTORNEY,
            'name'                          => $firstAttorney->name,
            'address'                       => $firstAttorney->address,
            'contactDetailsEnteredManually' => false,
        ]);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateCorrespondentIsAttorneyEnteredManually()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set the first attorney as the correspondent
        $firstAttorney = $lpa->document->primaryAttorneys[0];

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_ATTORNEY,
            'name'                          => $firstAttorney->name,
            'address'                       => $firstAttorney->address,
            'contactDetailsEnteredManually' => true,
        ]);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateCorrespondentIsCertificateProvider()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Set the certificate provider as the correspondent
        $certificateProvider = $lpa->document->certificateProvider;

        $lpa->document->correspondent = new Correspondence([
            'who'                           => Correspondence::WHO_CERTIFICATE_PROVIDER,
            'name'                          => $certificateProvider->name,
            'address'                       => $certificateProvider->address,
            'contactDetailsEnteredManually' => true,
        ]);

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testMergePdfsThrowsUnexpectedValueException()
    {
        //  Create a partially mocked version of the form to short circuit the generateCoversheets method
        $lp1f = Mockery::mock(Lp1h::class . '[generateCoversheets]', [$this->getLpa(false)])->shouldAllowMockingProtectedMethods();
        $lp1f->shouldReceive('generateCoversheets')
             ->andReturnNull();

        $this->setExpectedException('UnexpectedValueException', 'LP1 pdf was not generated before merging pdf intermediate files');

        $lp1f->generate();
    }

    public function testGenerateAdditionalPagesPrimaryAttorneysActDepends()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the primary attorneys act to depends
        $lpa->document->primaryAttorneyDecisions->how = 'depends';
        $lpa->document->primaryAttorneyDecisions->howDetails = 'Some information about how they act here';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesReplacementAttorneysActJointlyAndSeverally()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        //  Change how the primary attorneys act to depends
        $lpa->document->replacementAttorneyDecisions->how = 'jointly-attorney-severally';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesReplacementAttorneysActJointly()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        //  Change how the primary attorneys act to depends
        $lpa->document->replacementAttorneyDecisions->how = 'jointly';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesSingleReplacementAttorneyStepsInWhenFirst()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of replacement attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        //  Change how the primary attorneys act to depends
        $lpa->document->replacementAttorneyDecisions->when = 'first';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesSingleReplacementAttorneyStepsInDepends()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of replacement attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        //  Change when the replacement attorneys step in to depends
        $lpa->document->replacementAttorneyDecisions->when = 'depends';
        $lpa->document->replacementAttorneyDecisions->whenDetails = 'Some information about how they step in here';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesMultiReplacementAttorneysActJointlyAndSeverally()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the replacement attorneys act to jointly and severally
        $lpa->document->replacementAttorneyDecisions->how = 'jointly-attorney-severally';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesMultiReplacementAttorneysActJointly()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change how the replacement attorneys act to jointly
        $lpa->document->replacementAttorneyDecisions->how = 'jointly';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesMultiReplacementAttorneysStepsInDepends()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change when the replacement attorneys step in to depends
        $lpa->document->replacementAttorneyDecisions->when = 'depends';
        $lpa->document->replacementAttorneyDecisions->whenDetails = 'Some information about how they step in here';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateAdditionalPagesLongInstructionsAndPreferences()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Update the instructions and preferences details to be very long
        $lpa->document->instruction = 'Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here';
        $lpa->document->preference = 'Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here
        Some long preferences here Some long preferences here Some long preferences here Some long preferences here';

        $lp1f = new Lp1h($lpa);

        $form = $lp1f->generate();

        $this->assertInstanceOf(Lp1h::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP1H');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }
}
