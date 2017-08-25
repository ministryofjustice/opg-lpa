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

    /**
     * Get generated PDF file path
     * TODO - Only used for unit tests presently
     *
     * @return string
     */
    public function getPdfFilePath()
    {
        return $this->generatedPdfFilePath;
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

    public function getBlankPdfTemplateFilePath()
    {
        return $this->getPdfTemplateFilePath('blank.pdf');
    }

    protected function nextTag($tag = '')
    {
        return ++$tag;
    }

    public function cleanup()
    {
        //  TODO - Refactor this...
        if (\file_exists($this->generatedPdfFilePath)) {
            unlink($this->generatedPdfFilePath);
        }

        // remove all generated intermediate pdf files
        foreach ($this->interFileStack as $type => $paths) {
            if (is_string($paths)) {
                if (\file_exists($paths)) {
                    unlink($paths);
                }
            } elseif (is_array($paths)) {
                foreach ($paths as $path) {
                    if (\file_exists($path)) {
                        unlink($path);
                    }
                }
            }
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
