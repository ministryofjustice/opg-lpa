<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\PdftkFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PdftkFactoryTest extends TestCase
{
    public function testPdftkNotAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $factory = new PdftkFactory('margleFooBandersnatchLoopSnaggler');
        $factory->create('foo.pdf');
    }
}
