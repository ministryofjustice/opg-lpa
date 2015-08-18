<?php
namespace OpgTest\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Config\Config;
class Lp1hTest extends BaseClass
{
    public function testPdfFooter()
    {
        $this->lpa->document->type = 'health-and-welfare';
        $this->lpa->document->whoIsRegistering = 'donor';
        $this->deleteTrustCorp('primary');
        
        // create PDF, then extract form data
        $formData = $this->extractFormDataFromPdf('LP1');
        
        // test footer
        $this->assertEquals(Config::getInstance()['footer']['lp1h']['instrument'], $formData['footer-instrument-right']);
        
    }
}