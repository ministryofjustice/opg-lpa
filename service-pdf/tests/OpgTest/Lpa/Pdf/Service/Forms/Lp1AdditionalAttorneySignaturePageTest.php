<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp1AdditionalAttorneySignaturePage;

class Lp1AdditionalAttorneySignaturePageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp1AdditionalAttorneySignaturePage = new Lp1AdditionalAttorneySignaturePage($lpa);

        $interFileStack = $lp1AdditionalAttorneySignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneySignature', $interFileStack);
        $this->assertCount(4, $interFileStack['AdditionalAttorneySignature']);

        $this->verifyFileNames($lpa, $interFileStack['AdditionalAttorneySignature'], 'AdditionalAttorneySignature');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp1AdditionalAttorneySignaturePage = new Lp1AdditionalAttorneySignaturePage($lpa);

        $interFileStack = $lp1AdditionalAttorneySignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneySignature', $interFileStack);
        $this->assertCount(4, $interFileStack['AdditionalAttorneySignature']);

        $this->verifyFileNames($lpa, $interFileStack['AdditionalAttorneySignature'], 'AdditionalAttorneySignature');
    }
}
