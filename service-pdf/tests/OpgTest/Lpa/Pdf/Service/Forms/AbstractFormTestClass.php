<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\AbstractForm;
use Opg\Lpa\DataModel\Lpa\Lpa;
use ConfigSetUp;
use mikehaertl\pdftk\FdfFile;
use mikehaertl\pdftk\Pdf;

abstract class AbstractFormTestClass extends \PHPUnit_Framework_TestCase
{
    private $drawingTargetsReflectionProperty;

    protected function setUp()
    {
        ConfigSetUp::init();

        $formReflectionClass = new \ReflectionClass('Opg\Lpa\Pdf\Service\Forms\Lp1');
        $this->drawingTargetsReflectionProperty = $formReflectionClass->getProperty('drawingTargets');
        $this->drawingTargetsReflectionProperty->setAccessible(true);
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

        $regex = '/tmp\/' . $fileNamePrefix . '-' . $lpaIdFormatted . '-\d{10}(-\d+)?.pdf/';

        $this->assertRegExp($regex, $fileName);
    }

    /**
     * Extract data from the Pdf object by reversing the process in the Pdftk library
     *
     * @param Pdf $pdf
     * @return array
     */
    protected function extractPdfFormData(Pdf $pdf)
    {
        $fdfFilename = $pdf->getCommand()
                           ->getOperationArgument();

        //  Get the encoded data from the file and remove the header and the footer
        $fdfFileDataStr = file_get_contents($fdfFilename);
        $fdfFileDataStr = str_replace(FdfFile::FDF_HEADER, '', $fdfFileDataStr);
        $fdfFileDataStr = str_replace(FdfFile::FDF_FOOTER, '', $fdfFileDataStr);

        $dataLineStart = "<</T(";
        $dataLineMid = ")/V(".chr(0xFE).chr(0xFF);
        $dataLineEnd = ")>>\n";

        //  Explode the data string into an array - each line containing a field name and value
        $data = [];
        $fdfFileData = explode($dataLineEnd, $fdfFileDataStr);

        foreach ($fdfFileData as $fdfFileDataLine) {
            //  Replace the start of the first line, then split the line into field name and value
            $fdfFileDataLine = str_replace($dataLineStart, '', $fdfFileDataLine);

            if (!empty($fdfFileDataLine)) {
                $fdfFileDataLineData = explode($dataLineMid, $fdfFileDataLine);
                $fieldName = $fdfFileDataLineData[0];

                //  Do the final decoding steps on the field value
                $fieldValue = $fdfFileDataLineData[1];

                //  Unescape parenthesis
                $fieldValue = strtr($fieldValue, [
                    '\\(' => '(',
                    '\\)' => ')'
                ]);

                // See http://blog.tremily.us/posts/PDF_forms/
                $fieldValue = mb_convert_encoding($fieldValue, 'UTF-8', 'UTF-16BE');

                $data[$fieldName] = $fieldValue;
            }
        }

        return $data;
    }

    /**
     * Extract the set crossed lines targets using reflection
     *
     * @param AbstractForm $form
     */
    protected function extractCrossedLines(AbstractForm $form)
    {
        return $this->drawingTargetsReflectionProperty->getValue($form);
    }
}
