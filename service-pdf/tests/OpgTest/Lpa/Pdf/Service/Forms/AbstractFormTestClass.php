<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use ConfigSetUp;

abstract class AbstractFormTestClass extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        ConfigSetUp::init();
    }

    protected function getLpa($isPfLpa = true)
    {
        $lpaDataFileName = __DIR__ . '/../../../../../fixtures/' . ($isPfLpa ? 'lpa-pf.json' : 'lpa-hw.json');

        return new Lpa(file_get_contents($lpaDataFileName));
    }

    protected function verifyFileNames(Lpa $lpa, $fileNames, $fileNamePrefix)
    {
        foreach ($fileNames as $fileName) {
            $this->verifyFileName($lpa, $fileName, $fileNamePrefix);
        }
    }

    protected function verifyFileName(Lpa $lpa, $fileName, $fileNamePrefix)
    {
        //  Construct the Regex for the expected filename for this file

        //  Format the LPA ID correctly
        $lpaId = 'A' . str_pad($lpa->id, 11, '0', STR_PAD_LEFT);

        $lpaIdFormatted = implode('-', [
            substr($lpaId, 0, 4),
            substr($lpaId, 4, 4),
            substr($lpaId, 8, 4),
        ]);

        $regex = '/tmp\/pdf_cache\/' . $fileNamePrefix . '-' . $lpaIdFormatted . '-\d{10}(-\d+)?.pdf/';

        $this->assertRegExp($regex, $fileName);
    }
}
