<?php

namespace ApplicationTest\View\Helper;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\View\Helper\Traits\ConcatNamesTrait;

class ApplicantNamesTest extends MockeryTestCase
{
    use ConcatNamesTrait;

    public function testInvoke():void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->document->whoIsRegistering = ["donor"];

        if( is_array( $lpa->document->whoIsRegistering ) && is_array( $lpa->document->primaryAttorneys ) ) {

            $humans = array();
            $expectedHumans = "Dr Lilly Simpson, Mr Marcel Tanner and Mrs Annabella Collier";


            foreach ($lpa->document->primaryAttorneys as $attorney) {
                 $humans[] = $attorney;
            }

            $result =  $this->concatNames($humans);

            $this->assertEquals($expectedHumans, $result);
        }

        $expectedWhoIsRegistering = "donor";
        $this->assertIsArray($lpa->document->whoIsRegistering);
        $this->assertEquals($expectedWhoIsRegistering, $lpa->document->whoIsRegistering[0]);
        $this->assertNotNull($lpa->document->primaryAttorneys);
    }
}