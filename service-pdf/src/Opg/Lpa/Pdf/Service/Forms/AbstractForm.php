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
    protected $intermediateFilePaths = array();
    
    /**
     * @var string
     */
    protected $basePdfTemplatePath;
    
    protected $drawingTargets = array();
    
    abstract protected function generate();
    
    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
        $this->flattenLpa = $lpa->flatten();
        $config = Config::getInstance();
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
        foreach($this->intermediateFilePaths as $type=>$paths) {
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
     * 
     * @param string $fileType
     * @return string
     */
    protected function getTmpFilePath($fileType)
    {
        $filePath = '/tmp/pdf-'.$fileType.'-'.$this->lpa->id.'-'.microtime(true).'.pdf';
        return $filePath;
    }
    
    /**
     * Register a temp file in self::$intermediateFilePaths
     * @param string $fileType
     * @param string $path
     */
    public function registerTempFile($fileType)
    {
        $path = $this->getTmpFilePath($fileType);
        if(!isset($this->intermediateFilePaths[$fileType])) {
            $this->intermediateFilePaths[$fileType] = array($path);
        }
        else {
            $this->intermediateFilePaths[$fileType][] = $path;
        }
        
        return $path;
    }
    
    /**
     * Draw cross lines
     * @param string $filePath
     * @param array $params[pageNo=>strokeParamName]
     */
    protected function stroke($filePath, $params)
    {
        // draw strokes
        $pdf = PdfProcessor::load($filePath);
        foreach($params as $pageNo => $blockNames) {
            $page = $pdf->pages[$pageNo]->setLineWidth(self::STROKE_LINE_WIDTH);
            foreach($blockNames as $blockName) {
                $page->drawLine(
                        $this->strokeParams[$blockName]['bx'],
                        $this->strokeParams[$blockName]['by'],
                        $this->strokeParams[$blockName]['tx'],
                        $this->strokeParams[$blockName]['ty']
                );
            }
        } // foreach
    
        $pdf->save($filePath);
    
    } // function stroke()
    
    /**
     * clean up intermediate files.
     */
    public function __destruct()
    {
        if(\file_exists($this->generatedPdfFilePath)) {
            unlink($this->generatedPdfFilePath);
        }
        
        // remove all generated intermediate pdf files
        foreach($this->intermediateFilePaths as $type => $paths) {
            if(is_string($paths)) {
                if(\file_exists($paths)) {
                    unlink($paths);
                }
            }
            elseif(is_array($paths)) {
                foreach($paths as $path) {
                    if(\file_exists($path)) {
                        unlink($path);
                    }
                }
            }
        }
        
    }
} // class