<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\AbstractForm;
use Opg\Lpa\DataModel\Lpa\Lpa;

class AbstractTesterForm extends AbstractForm
{
    public function __construct(Lpa $lpa)
    {
        parent::__construct($lpa);

        //  Set up the files to clean up
        $this->generatedPdfFilePath = '/tmp/genPdfFile.pdf';
        file_put_contents($this->generatedPdfFilePath, 'Some data to go in the file');

        $this->interFileStack = [
            '/tmp/file1.pdf',
            [
                '/tmp/file2.pdf',
                '/tmp/file3.pdf',
            ],
        ];

        file_put_contents('/tmp/file1.pdf', 'Some more data to go in the file 1');
        file_put_contents('/tmp/file2.pdf', 'Some more data to go in the file 2');
        file_put_contents('/tmp/file3.pdf', 'Some more data to go in the file 3');
    }

    protected function generate()
    {
        //  Do nothing

        return $this;
    }

    public function getContentForBoxExt($pageNo, $content, $contentType)
    {
        return $this->getContentForBox($pageNo, $content, $contentType);
    }

    public function nextTagExt($tag)
    {
        return $this->nextTag($tag);
    }
}
