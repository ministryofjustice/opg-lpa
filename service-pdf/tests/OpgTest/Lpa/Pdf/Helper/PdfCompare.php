<?php

declare(strict_types=1);

namespace OpgTest\Lpa\Pdf\Helper;

use Imagick;
use PHPUnit\Framework\Assert;

class PdfCompare
{
    /**
     * @param float $threshold How different the images need to be to fail the test, between 0
     *                         and 1. 0 will always fail, 1 will always pass
     */
    public function compare(string $pathOld, string $pathNew, int $numberOfPages, float $threshold = 1 / 1000000): void
    {
        for ($page = 1; $page <= $numberOfPages; $page++) {
            $beforePng = new Imagick($this->pdfToPng($pathOld, 'before'));
            $afterPng = new Imagick($this->pdfToPng($pathNew, 'after'));

            $result = $beforePng->compareImages($afterPng, Imagick::METRIC_MEANSQUAREERROR);

            Assert::assertLessThan($threshold, $result[1], 'Change detected on page ' . $page . ' of ' . $pathOld . ' baseline PDF');
        }
    }

    private function pdfToPng(string $pdfPath, string $name)
    {
        exec(implode(' ', [
            'pdftocairo',
            '-png',
            '-singlefile',
            $pdfPath,
            $name,
        ]));

        return $name . '.png';
    }
}
