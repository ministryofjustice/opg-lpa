<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp3;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use mikehaertl\pdftk\Pdf;

class Lp3Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFNoPeopleToNotifyException()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Remove the people to notify
        $lpa->document->peopleToNotify = [];

        $lp3 = new Lp3($lpa);

        $this->setExpectedException('RuntimeException', 'LP3 is not available for this LPA.');

        $lp3->generate();
    }

    public function testGeneratePFSinglePrimaryAttorney()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFDonorRegistering()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGeneratePFTrustAttorneyOnly()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Ensure that there is only a trust primary attorney
        foreach ($lpa->document->primaryAttorneys as $attorneyKey => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                //  Set the trust as the only attorney registering the LPA
                $lpa->document->whoIsRegistering = [$attorney->id];
            } else {
                //  Remove the attorney
                unset($lpa->document->primaryAttorneys[$attorneyKey]);
            }
        }

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateHWNoPeopleToNotifyException()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Remove the people to notify
        $lpa->document->peopleToNotify = [];

        $lp3 = new Lp3($lpa);

        $this->setExpectedException('RuntimeException', 'LP3 is not available for this LPA.');

        $lp3->generate();
    }

    public function testGenerateHWSinglePrimaryAttorney()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }

    public function testGenerateHWDonorRegistering()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA data as required
        //  Change the person registering the LPA to the donor
        $lpa->document->whoIsRegistering = 'donor';

        $lp3 = new Lp3($lpa);

        $form = $lp3->generate();

        $this->assertInstanceOf(Lp3::class, $form);

        $this->verifyFileName($lpa, $form->getPdfFilePath(), 'PDF-LP3');

        //  TODO - Verify the drawing targets

        $this->assertInstanceOf(Pdf::class, $form->getPdfObject());
    }
}
