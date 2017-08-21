<?php

namespace Opg\Lpa\Pdf\Service\Forms;

abstract class AbstractCoversheet extends AbstractForm
{
    /**
     * @return array
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $filePath = $this->registerTempFile('Coversheet');

        //  Get the appropriate PDF coversheet template
        $pdf = $this->getPdfObject();
        $pdf->flatten()
            ->saveAs($filePath);

        return $this->interFileStack;
    }
}
