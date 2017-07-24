<?php

namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Service\PdftkInstance;

class PdftkInstanceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetInstance()
    {
        $pdftkInstanceObj1 = PdftkInstance::getInstance();
        $pdftkInstanceObj2 = PdftkInstance::getInstance();

        $this->assertNotEquals(spl_object_hash($pdftkInstanceObj1), spl_object_hash($pdftkInstanceObj2));
    }
}
