<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use mikehaertl\pdftk\Pdf;

abstract class AbstractTopForm extends AbstractForm
{
    const CHECK_BOX_ON = 'On';

    protected function protectPdf()
    {
        $pdf = new Pdf($this->generatedPdfFilePath);

        $password = $this->config['pdf']['password'];

        $pdf->allow('Printing CopyContents')
            ->flatten()
            ->setPassword($password)
            ->saveAs($this->generatedPdfFilePath);
    }

    protected function mergerIntermediateFilePaths($paths)
    {
        foreach ($paths as $type => $path) {
            if (isset($this->interFileStack[$type])) {
                $this->interFileStack[$type] = array_merge($this->interFileStack[$type], $path);
            } else {
                $this->interFileStack[$type] = $path;
            }
        }
    }

    protected function nextTag($tag = '')
    {
        return ++$tag;
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
