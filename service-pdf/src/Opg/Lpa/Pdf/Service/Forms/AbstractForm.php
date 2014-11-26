<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;

abstract class AbstractForm
{
    const CHECK_BOX_ON = 'On';
    
    /**
     *
     * @var LPA model object
     */
    protected $lpa;

    /**
     *
     * @var PDFTK pdf object
     */
    protected $pdf;
    
    /**
     * 
     * @var array
     */
    protected $flattenLpa;
    
    /**
     * The path of the pdf file to be generated.
     * @var string
     */
    protected $generatedPdfFilePath = null;
    
    /**
     * Intermediate pdf files - needed for LP1F/H
     * @var array
     */
    protected $intermediatePdfFilePaths = array();
    
    /**
     * Map model generated data to pdf form fields; add additional mapping 
     * for the forms on extra pages.
     */
    abstract protected function mapData();
    
    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->flattenLpa = $lpa->flatten();
    }

    public function getPdfFilePath()
    {
        return $this->generatedPdfFilePath;
    }
    
    public function getPdfObject()
    {
        return $this->pdf;
    }
    
    protected function fullName($person)
    {
        return $person->name->title . ' '. $person->name->first . ' '. $person->name->last; 
    }

    public function __destruct()
    {
        if((count($this->intermediatePdfFilePaths) == 1) && isset($this->intermediatePdfFilePaths['LP1'])) {
            return;
        }
        
        // remove all generated intermediate pdf files
        foreach($this->intermediatePdfFilePaths as $type => $paths) {
            if(is_string($paths)) {
                if(\file_exists($paths)) {
                    unlink($paths);
                }
            }
            else {
                foreach($paths as $path) {
                    if(\file_exists($path)) {
                        unlink($path);
                    }
                }
            }
        }
        
        if(\file_exists($this->generatedPdfFilePath)) {
            unlink($this->generatedPdfFilePath);
        }
    }
} // class