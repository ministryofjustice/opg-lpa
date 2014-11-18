<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Formatter;
use mikehaertl\pdftk\pdf as Pdf;
use Opg\Lpa\Pdf\Service\Forms\Lp1f;
use Opg\Lpa\DataModel\Lpa\Lpa;

class Lp1fTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var Lp1f $lp1f;
     */
    private $lp1f;

    /**
     *
     * @var Lpa $lpa
     */
    private $lpa;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        $this->lpa = new Lpa(
                file_get_contents(
                        __DIR__ . '/../../../../../test-data/test-1.json'));
        
        $this->lp1f = new Lp1f($this->lpa);
    }

    public function testPopulate ()
    {
        $this->assertTrue($this->lp1f instanceof Lp1f);
        
        $this->assertObjectHasAttribute('lpa', $this->lp1f);
        $this->assertObjectHasAttribute('pdf', $this->lp1f);
        $this->assertObjectHasAttribute('flattenLpa', $this->lp1f);
        
        $this->assertTrue($this->lp1f->generate() instanceof Lp1f);
        $this->assertTrue($this->lp1f->getPdfObject() instanceof Pdf);
        
        $fieldDataValues = $this->convertPdfDataFieldsResultToArray(
                (new Pdf($this->lp1f->getPdfObject()))->getDataFields());
        
        $pdfFormFields = $this->searchField($fieldDataValues, array(
                'lpa-id',
                'lpa-document-donor-name-title',
                'lpa-document-donor-name-first',
                'lpa-document-donor-name-last',
                'lpa-document-donor-otherNames',
                'lpa-document-donor-address-address1',
                'lpa-document-donor-address-address2',
                'lpa-document-donor-address-address3',
                'lpa-document-donor-address-postcode',
        ));
        
        $this->assertEquals(Formatter::id($this->lpa->id), $pdfFormFields['lpa-id']);
        
        $this->assertEquals($this->lpa->document->donor->name->title, $pdfFormFields['lpa-document-donor-name-title']);
        $this->assertEquals($this->lpa->document->donor->name->first, $pdfFormFields['lpa-document-donor-name-first']);
        $this->assertEquals($this->lpa->document->donor->name->last, $pdfFormFields['lpa-document-donor-name-last']);
        $this->assertEquals($this->lpa->document->donor->otherNames, $pdfFormFields['lpa-document-donor-otherNames']);
        $this->assertEquals($this->lpa->document->donor->address->address1, $pdfFormFields['lpa-document-donor-address-address1']);
        $this->assertEquals($this->lpa->document->donor->address->address2, $pdfFormFields['lpa-document-donor-address-address2']);
        $this->assertEquals($this->lpa->document->donor->address->address3, $pdfFormFields['lpa-document-donor-address-address3']);
        $this->assertEquals($this->lpa->document->donor->address->postcode, $pdfFormFields['lpa-document-donor-address-postcode']);
    }

    private function searchField ($pdfFieldDataValues, $searchForFieldNames)
    {
        $found = array();
        foreach($searchForFieldNames as $fieldName){
            foreach ($pdfFieldDataValues as $formField) {
                if ($formField['FieldName'] == $fieldName) {
                    $found[$fieldName] = $formField['FieldValue'];
                }
            }
        }
        
        return $found;
    }

    private function convertPdfDataFieldsResultToArray ($string)
    {
        $pdfFormFieldsData = explode("---\n", $string);
        $fieldDataValues = array();
        $i = 0;
        foreach ($pdfFormFieldsData as $fieldData) {
            if (empty($fieldData)) {
                continue;
            }
            $fields = explode("\n", trim($fieldData));
            $fieldDataValues[++ $i] = array();
            foreach ($fields as $line) {
                if (empty($line)) {
                    continue;
                }
                $item = explode(":", $line);
                if (! isset($item[1])) {
                    continue;
                }
                $fieldDataValues[$i]["{$item[0]}"] = trim($item[1]);
            }
        }
        return $fieldDataValues;
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        parent::tearDown();
    }
}