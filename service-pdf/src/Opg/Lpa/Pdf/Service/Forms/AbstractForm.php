<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\Pdf\Config\Config;

abstract class AbstractForm
{
    const CHECK_BOX_ON = 'On';
    
    const STROKE_LINE_WIDTH = 10;
    
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
     * @var string
     */
    protected $basePdfTemplatePath;
    
    protected $drawingTargets = array();
    
    abstract protected function generate();
    
    public function __construct(Lpa $lpa, Config $config)
    {
        $this->lpa = $lpa;
        $this->flattenLpa = $lpa->flatten();
        $this->basePdfTemplatePath = $config['service']['assets']['path'].'/v2';
    }
    
    /**
     * Get generated PDF file path
     * @return string
     */
    public function getPdfFilePath()
    {
        return $this->generatedPdfFilePath;
    }
    
    public function getPdfObject()
    {
        return $this->pdf;
    }
    
    /**
     * helper function - get fullname for a person
     * @param Opg\Lpa\DataModel\Lpa\Elements\Name $personName
     * @return string
     */
    protected function fullName(Name $personName)
    {
        return $personName->title . ' '. $personName->first . ' '. $personName->last; 
    }
    
    /**
     * Count no of generated intermediate files
     * @return number
     */
    protected function countIntermediateFiles()
    {
        $count = 0;
        foreach($this->intermediatePdfFilePaths as $type=>$paths) {
            if(is_array($paths)) {
                $count += count($paths);
            }
            else {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * clean up intermediate files.
     */
    public function __destruct()
    {
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