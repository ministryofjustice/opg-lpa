<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;

abstract class FormAbstract
{

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
    
    protected $generatedpPdfFilePath = null;

    /**
     * Map model generated data to pdf form fields; add additional mapping 
     * for the forms on extra pages.
     */
    abstract protected function mapData();
    
    /**
     * insert additional pages depend on form field data length and/or number of actors in the LPA.
     */
    abstract protected function attachAdditionalPages();

    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->flattenLpa = $lpa->flatten();
        
        // generate a file path with lpa id and timestamp;
        $this->generatedpPdfFilePath = 'output.pdf';
    }

    /**
     * Populate LPA data into PDF forms, generate pdf file and save into file path.
     * 
     * @return Form object
     */
    public function generate()
    {
        
        $flattenLpaData = $this->mapData();
        
        $this->attachAdditionalPages();
        
        $this->pdf->fillForm($flattenLpaData)
            ->needAppearances()
            ->saveAs($this->generatedpPdfFilePath);
        
        return $this;
    }
    
    public function getPdfFilePath()
    {
        return $this->generatedpPdfFilePath;
    }
    
    public function getPdfObject()
    {
        return $this->pdf;
    }

    public function clear()
    {}
} // class