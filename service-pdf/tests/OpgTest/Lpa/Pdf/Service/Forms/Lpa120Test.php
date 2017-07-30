<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lpa120;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use mikehaertl\pdftk\Pdf;

class Lps120Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateNoRepeatCaseNumberException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Remove the repeat case number and blank the payment
        $lpa->repeatCaseNumber = null;
        $lpa->payment = new Payment();

        $lpa120 = new Lpa120($lpa);

        $this->setExpectedException('RuntimeException', 'LPA120 is not available for this LPA.');

        $lpa120->generate();
    }

    public function testGeneratePFAttorneyCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney
        $lpa->document->correspondent->who = 'attorney';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFOtherCorrespondentEnteredManually()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an other party
        $lpa->document->correspondent->who = 'other';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFDonorCorrespondent()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to the donor and remove the manually entered data
        $lpa->document->correspondent = null;
        $lpa->document->whoIsRegistering = 'donor';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFAttorneyCorrespondent()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney and remove the manually entered data
        $lpa->document->correspondent = null;
        $lpa->document->whoIsRegistering = [1];

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFOtherCorrespondentThrowsException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent to an attorney and remove the manually entered data
        $lpa->document->correspondent = null;
        $lpa->document->whoIsRegistering = false;

        $lpa120 = new Lpa120($lpa);

        $this->setExpectedException('Exception', 'When generating LPA120, applicant was found invalid');

        $form = $lpa120->generate();
    }

    public function testGeneratePFApplicantTitleOther()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the correspondent title to a custom value
        $lpa->document->correspondent->name->title = 'Sir';

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFBooleanAsNo()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change a value to return a "No" for a false boolean
        $lpa->payment->reducedFeeReceivesBenefits = false;

        $lpa120 = new Lpa120($lpa);

        $form = $lpa120->generate();

        $this->assertInstanceOf(Lpa120::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'LPA120');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }
}
