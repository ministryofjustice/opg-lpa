<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\Pdf\Config\Config;

abstract class AbstractForm
{
    const CHECK_BOX_ON = 'On';
    
    const STROKE_LINE_WIDTH = 10;
    
    const CONTENT_TYPE_ATTORNEY_DECISIONS           = 'cs-2-is-decisions';
    const CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN = 'cs-2-is-how-replacement-attorneys-step-in';
    const CONTENT_TYPE_PREFERENCES                  = 'cs-2-is-preferences';
    const CONTENT_TYPE_INSTRUCTIONS                 = 'cs-2-is-instructions';
    
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
    
    /**
     * bx - bottom x 
     * by - bottom y
     * tx - top x
     * ty - top y
     * @var array - stroke corrrdinates
     */
    protected $strokeParams = array(
        'primaryAttorney-1'      => array('bx'=>313, 'by'=>243, 'tx'=>550, 'ty'=>545),
        'primaryAttorney-2'      => array('bx'=>45,  'by'=>359, 'tx'=>283, 'ty'=>662),
        'primaryAttorney-3'      => array('bx'=>313, 'by'=>359, 'tx'=>550, 'ty'=>662),
        'replacementAttorney-0'  => array('bx'=>45,  'by'=>315, 'tx'=>283, 'ty'=>536),
        'replacementAttorney-1'  => array('bx'=>313, 'by'=>315, 'tx'=>550, 'ty'=>536),
        'life-sustain-A'         => array('bx'=>44,  'by'=>275, 'tx'=>283, 'ty'=>485),
        'life-sustain-B'         => array('bx'=>307, 'by'=>275, 'tx'=>550, 'ty'=>485),
        'people-to-notify-0'     => array('bx'=>44,  'by'=>335, 'tx'=>283, 'ty'=>501),
        'people-to-notify-1'     => array('bx'=>312, 'by'=>335, 'tx'=>552, 'ty'=>501),
        'people-to-notify-2'     => array('bx'=>44,  'by'=>127, 'tx'=>283, 'ty'=>294),
        'people-to-notify-3'     => array('bx'=>312, 'by'=>127, 'tx'=>552, 'ty'=>294),
        'preference-pf'          => array('bx'=>41,  'by'=>419, 'tx'=>554, 'ty'=>529),
        'instruction-pf'         => array('bx'=>41,  'by'=>118, 'tx'=>554, 'ty'=>227),
        'preference-hw'          => array('bx'=>41,  'by'=>439, 'tx'=>554, 'ty'=>529),
        'instruction-hw'         => array('bx'=>41,  'by'=>157, 'tx'=>554, 'ty'=>248),
        'attorney-signature'     => array('bx'=>42,  'by'=>144, 'tx'=>553, 'ty'=>317),
        'applicant-0'            => array('bx'=>42,  'by'=>315, 'tx'=>283, 'ty'=>412),
        'applicant-1'            => array('bx'=>308, 'by'=>315, 'tx'=>549, 'ty'=>412),
        'applicant-2'            => array('bx'=>42,  'by'=>147, 'tx'=>283, 'ty'=>245),
        'applicant-3'            => array('bx'=>308, 'by'=>147, 'tx'=>549, 'ty'=>245),
        'additional-applicant-1' => array('bx'=>308, 'by'=>303, 'tx'=>549, 'ty'=>401),
        'additional-applicant-2' => array('bx'=>42,  'by'=>139, 'tx'=>283, 'ty'=>237),
        'additional-applicant-3' => array('bx'=>308, 'by'=>139, 'tx'=>549, 'ty'=>237),
        'cs1'                    => array('bx'=>313, 'by'=>262, 'tx'=>558, 'ty'=>645),
        'lp3-primaryAttorney-1'  => array('bx'=>312, 'by'=>458, 'tx'=>552, 'ty'=>602),
        'lp3-primaryAttorney-2'  => array('bx'=>43,  'by'=>242, 'tx'=>283, 'ty'=>386),
        'lp3-primaryAttorney-3'  => array('bx'=>312, 'by'=>242, 'tx'=>552, 'ty'=>386)
    );
    
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
    } // function countIntermediateFiles()
    
    /**
     * 
     * @param string $fileType
     * @return string
     */
    protected function getTmpFilePath($fileType)
    {
        $filePath = '/tmp/pdf-'.$fileType.'-'.$this->lpa->id.'-'.microtime(true).'.pdf';
        return $filePath;
    } // function getTmpFilePath($fileType)
    
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
    } // function registerTempFile()
    
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
     * Convert all new lines with spaces to fill out to the end of each line
     *
     * @param string $content
     * @return string
     */
    protected function flattenTextContent($content)
    {
        // strip space & new lines chars at both ends.
        $content = trim($content);
    
        $paragraphs = explode("\n", $content);
        foreach($paragraphs as &$paragraph) {
            $paragraph = trim($paragraph);
            if(strlen($paragraph) == 0) {
                $paragraph = str_repeat(" ", Lp1::BOX_CHARS_PER_ROW);
            }
            else {
                // calculate how many space chars to be appended to replace the new line in this paragraph.
                $noOfSpaces = Lp1::BOX_CHARS_PER_ROW - strlen($paragraph) % Lp1::BOX_CHARS_PER_ROW;
                if($noOfSpaces > 0) {
                    $paragraph .= str_repeat(" ", $noOfSpaces);
                }
            }
        }
    
        return implode("", $paragraphs);
    } // function flattenBoxContent($content)
    
    protected function mergerIntermediateFilePaths($paths)
    {
        foreach($paths as $type=>$path) {
            if(isset($this->intermediateFilePaths[$type])) {
                $this->intermediateFilePaths[$type] = array_merge($this->intermediateFilePaths[$type], $path);
            }
            else {
                $this->intermediateFilePaths[$type] = $path;
            }
        }
    } // function mergerIntermediateFilePaths()
    
    /**
     * Get content for a multiline text box.
     * 
     * @param int $pageNo
     * @param string $content - user input content for preference/instruction/decisions/step-in
     * @param enum $contentType - CONTENT_TYPE_ATTORNEY_DECISIONS | CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN | CONTENT_TYPE_PREFERENCES | CONTENT_TYPE_INSTRUCTIONS
     * @return string|NULL
     */
    protected function getContentForBox($pageNo, $content, $contentType)
    {
        $flattenContent = $this->flattenTextContent($content);
    
        // return content for preference or instruction in section 7.
        if(($contentType==self::CONTENT_TYPE_INSTRUCTIONS) || ($contentType==self::CONTENT_TYPE_PREFERENCES)) {
            if($pageNo == 0) {
                return "\n".substr($flattenContent, 0, Lp1::BOX_CHARS_PER_ROW * Lp1::BOX_NO_OF_ROWS);
            }
            else {
                $chunks = str_split(substr($flattenContent, Lp1::BOX_CHARS_PER_ROW * Lp1::BOX_NO_OF_ROWS), Lp1::BOX_CHARS_PER_ROW * Cs2::BOX_NO_OF_ROWS_CS2+1);
                if(isset($chunks[$pageNo-1])) {
                    return "\n".$chunks[$pageNo-1];
                }
                else {
                    return null;
                }
            }
        }
        else {
            $chunks = str_split($flattenContent, Lp1::BOX_CHARS_PER_ROW * Cs2::BOX_NO_OF_ROWS_CS2+1);
            if(isset($chunks[$pageNo])) {
                return "\n".$chunks[$pageNo];
            }
            else {
                return null;
            }
        }
    
        $chunks = str_split(substr($flattenContent, Lp1::BOX_CHARS_PER_ROW * Lp1::BOX_NO_OF_ROWS), Lp1::BOX_CHARS_PER_ROW * Cs2::BOX_NO_OF_ROWS_CS2+1);
        if(isset($chunks[$pageNo-1])) {
            return "\n".$chunks[$pageNo-1];
        }
        else {
            return null;
        }
    } // function getContentForBox()
    
    /**
     * Check if the text content can fit into the text box in the Section 7 page in the base PDF form.
     *
     * @return boolean
     */
    protected function canFitIntoTextBox($content)
    {
        $flattenContent = $this->flattenTextContent($content);
        return strlen($flattenContent) <= Lp1::BOX_CHARS_PER_ROW * Lp1::BOX_NO_OF_ROWS;
    } // function canFitIntoTextBox()
    
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