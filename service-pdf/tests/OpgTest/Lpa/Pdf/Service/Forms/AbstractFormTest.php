<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\AbstractForm;

class AbstractFormTest extends AbstractFormTestClass
{
    public function testGetContentForBoxReturnsNull()
    {
        $lpa = $this->getLpa();
        $testForm = new AbstractTesterForm($lpa);

        $this->assertNull($testForm->getContentForBoxExt(11, 'Some short content', AbstractForm::CONTENT_TYPE_INSTRUCTIONS));

        $this->assertNull($testForm->getContentForBoxExt(12, 'More short content', 'random-content-type'));
    }

    public function testCleanUp()
    {
        $lpa = $this->getLpa();
        $testForm = new AbstractTesterForm($lpa);

        $this->assertTrue(file_exists($testForm->getPdfFilePath()));

        $testForm->cleanup();

        $this->assertFalse(file_exists($testForm->getPdfFilePath()));
    }

    public function nextTagDataProvider()
    {
        return [
            ['A', 'B'],
            ['B', 'C'],
            ['C', 'D'],
            ['D', 'E'],
            ['E', 'F'],
            ['F', 'G'],
            ['G', 'H'],
            ['H', 'I'],
            ['I', 'J'],
            ['J', 'K'],
            ['K', 'L'],
            ['L', 'M'],
            ['M', 'N'],
            ['N', 'O'],
            ['O', 'P'],
            ['P', 'Q'],
            ['Q', 'R'],
            ['R', 'S'],
            ['S', 'T'],
            ['T', 'U'],
            ['U', 'V'],
            ['V', 'W'],
            ['W', 'X'],
            ['X', 'Y'],
            ['Y', 'Z'],
            ['Z', 'AA'],
            ['AA', 'AB'],
            ['', 1],
        ];
    }

    /**
     * @dataProvider nextTagDataProvider
     */
    public function testNextTag($input, $expected)
    {
        $lpa = $this->getLpa();
        $testForm = new AbstractTesterForm($lpa);

        $this->assertEquals($testForm->nextTagExt($input), $expected);
    }
}
