<?php
namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Lp1f;
use OpgTest\Lpa\Pdf\AbstractPdfTestClass;
use PHPUnit\Framework\TestCase;


/**
 * Tests of the AbstractLp1 test through its concrete subclasses.
 */
class AbstractLp1Test extends AbstractPdfTestClass
{
    public function testPopulatePageTwoThreeFour()
    {
        // Modify LPA data so it has a single primary attorney
        $data = $this->getPfLpaJSON();

        $primaryAttorneys = $data["document"]["primaryAttorneys"];
        $data["document"]["primaryAttorneys"] = array_slice($primaryAttorneys, 0, 1);

        // We also need to modify who is registering, so it references the
        // single attorney rather than all of them
        $data["document"]["whoIsRegistering"] = ["1"];

        // Load the data to make our amended LPA
        $lpa = $this->buildLpaFromJSON($data);
        $pdf = new Lp1f($lpa, [], $this->factory);
        $pdf->generate();

        // Check the single attorney data's will be injected into the PDF


        // Check that there are strikethroughs on all pages except the one
        // showing the single attorney


        // TODO remove this
        $this->assertTrue(TRUE);
    }
}
