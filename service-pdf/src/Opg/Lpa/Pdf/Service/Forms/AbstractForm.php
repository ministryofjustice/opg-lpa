<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\DataModel\Lpa\Formatter;
use ZendPdf\PdfDocument as ZendPdfDocument;

abstract class AbstractForm
{
    const CHECK_BOX_ON = 'On';
    
    const CROSS_LINE_WIDTH = 10;
    
    const CONTENT_TYPE_ATTORNEY_DECISIONS           = 'cs2-is-decisions';
    const CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN = 'cs2-is-how-replacement-attorneys-step-in';
    const CONTENT_TYPE_PREFERENCES                  = 'cs2-is-preferences';
    const CONTENT_TYPE_INSTRUCTIONS                 = 'cs2-is-instructions';
    
    /**
     *
     * @var LPA model object
     */
    protected $lpa;

    /**
     * Data to be populated into PDF form elements.
     * 
     * @var array
     */
    protected $pdfFormData = [];
    
    /**
     * The path of the pdf file to be generated.
     * @var string
     */
    protected $generatedPdfFilePath = null;
    
    /**
     * Storage path for intermediate pdf files - needed for LP1F/H and LP3.
     * @var array
     */
    protected $interFileStack = array();
    
    /**
     * It's a ram disk folder and is the base path for storing 
     * intermediate files temporarily. Defined in config file.
     * @var string
     */
    protected $intermediateFileBasePath;
    
    /**
     * The folder that stores template PDFs which all form elements values are empty.
     * @var string
     */
    protected $pdfTemplatePath;
    
    /**
     * Store cross line strokes parameters.
     * @var array
     */
    protected $drawingTargets = array();
    
    /**
     * bx - bottom x 
     * by - bottom y
     * tx - top x
     * ty - top y
     * @var array - cross lines corrrdinates
     */
    protected $crossLineParams = array(
        'primaryAttorney-1-hw'             => array('bx'=>313, 'by'=>243, 'tx'=>550, 'ty'=>546),
        'primaryAttorney-1-pf'             => array('bx'=>313, 'by'=>178, 'tx'=>550, 'ty'=>471),
        'primaryAttorney-2'                => array('bx'=>45,  'by'=>375, 'tx'=>282, 'ty'=>679),
        'primaryAttorney-3'                => array('bx'=>313, 'by'=>375, 'tx'=>550, 'ty'=>679),
        'replacementAttorney-0-hw'         => array('bx'=>45,  'by'=>317, 'tx'=>283, 'ty'=>538),
        'replacementAttorney-1-hw'         => array('bx'=>313, 'by'=>317, 'tx'=>551, 'ty'=>538),
        'replacementAttorney-0-pf'         => array('bx'=>45,  'by'=>308, 'tx'=>283, 'ty'=>530),
        'replacementAttorney-1-pf'         => array('bx'=>313, 'by'=>308, 'tx'=>551, 'ty'=>530),
        'life-sustain-A'                   => array('bx'=>44,  'by'=>265, 'tx'=>283, 'ty'=>478),
        'life-sustain-B'                   => array('bx'=>307, 'by'=>265, 'tx'=>550, 'ty'=>478),
        'people-to-notify-0'               => array('bx'=>44,  'by'=>335, 'tx'=>283, 'ty'=>501),
        'people-to-notify-1'               => array('bx'=>312, 'by'=>335, 'tx'=>552, 'ty'=>501),
        'people-to-notify-2'               => array('bx'=>44,  'by'=>127, 'tx'=>283, 'ty'=>294),
        'people-to-notify-3'               => array('bx'=>312, 'by'=>127, 'tx'=>552, 'ty'=>294),
        'preference'                       => array('bx'=>41,  'by'=>423, 'tx'=>554, 'ty'=>532),
        'instruction'                      => array('bx'=>41,  'by'=>122, 'tx'=>554, 'ty'=>231),
        'attorney-signature-hw'            => array('bx'=>42,  'by'=>143, 'tx'=>553, 'ty'=>317),
        'attorney-signature-pf'            => array('bx'=>42,  'by'=>131, 'tx'=>553, 'ty'=>306),
        'applicant-0-hw'                   => array('bx'=>42,  'by'=>315, 'tx'=>283, 'ty'=>413),
        'applicant-1-hw'                   => array('bx'=>308, 'by'=>315, 'tx'=>549, 'ty'=>413),
        'applicant-2-hw'                   => array('bx'=>42,  'by'=>147, 'tx'=>283, 'ty'=>245),
        'applicant-3-hw'                   => array('bx'=>308, 'by'=>147, 'tx'=>549, 'ty'=>245),
        'applicant-0-pf'                   => array('bx'=>42,  'by'=>319, 'tx'=>283, 'ty'=>417),
        'applicant-1-pf'                   => array('bx'=>308, 'by'=>319, 'tx'=>549, 'ty'=>417),
        'applicant-2-pf'                   => array('bx'=>42,  'by'=>155, 'tx'=>283, 'ty'=>253),
        'applicant-3-pf'                   => array('bx'=>308, 'by'=>155, 'tx'=>549, 'ty'=>253),
        'additional-applicant-1-hw'        => array('bx'=>308, 'by'=>315, 'tx'=>549, 'ty'=>413),
        'additional-applicant-2-hw'        => array('bx'=>42,  'by'=>147, 'tx'=>283, 'ty'=>245),
        'additional-applicant-3-hw'        => array('bx'=>308, 'by'=>147, 'tx'=>549, 'ty'=>245),
        'additional-applicant-1-pf'        => array('bx'=>308, 'by'=>319, 'tx'=>549, 'ty'=>417),
        'additional-applicant-2-pf'        => array('bx'=>42,  'by'=>155, 'tx'=>283, 'ty'=>253),
        'additional-applicant-3-pf'        => array('bx'=>308, 'by'=>155, 'tx'=>549, 'ty'=>253),
        'correspondent-empty-address'      => array('bx'=>42, 'by'=>362, 'tx'=>284, 'ty'=>433),
        'correspondent-empty-name-address' => array('bx'=>42, 'by'=>362, 'tx'=>413, 'ty'=>565),
        'cs1'                              => array('bx'=>313, 'by'=>262, 'tx'=>558, 'ty'=>645),
        'lp3-primaryAttorney-1'            => array('bx'=>312, 'by'=>458, 'tx'=>552, 'ty'=>602),
        'lp3-primaryAttorney-2'            => array('bx'=>43,  'by'=>242, 'tx'=>283, 'ty'=>386),
        'lp3-primaryAttorney-3'            => array('bx'=>312, 'by'=>242, 'tx'=>552, 'ty'=>386)
    );
    
    abstract protected function generate();
    
    public function __construct(Lpa $lpa)
    {
        $this->lpa = $lpa;
        $config = Config::getInstance();
        $this->pdfTemplatePath = $config['service']['assets']['template_path_on_ram_disk'];
        $this->intermediateFileBasePath = $config['service']['assets']['intermediate_file_path'];
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
        foreach($this->interFileStack as $type=>$paths) {
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
        $filePath = $this->intermediateFileBasePath.'/'.$fileType.'-'.str_replace(array(' ','.'), '-', Formatter::id($this->lpa->id).'-'.microtime(true)).'.pdf';
        return $filePath;
    } // function getTmpFilePath($fileType)
    
    /**
     * Register a temp file in self::$interFileStack
     * @param string $fileType
     * @param string $path
     */
    public function registerTempFile($fileType)
    {
        $path = $this->getTmpFilePath($fileType);
        if(!isset($this->interFileStack[$fileType])) {
            $this->interFileStack[$fileType] = array($path);
        }
        else {
            $this->interFileStack[$fileType][] = $path;
        }
        
        return $path;
    } // function registerTempFile()
    
    /**
     * Draw cross lines
     * @param string $filePath
     * @param array $params[pageNo=>crossLineParamName]
     */
    protected function drawCrossLines($filePath, $params)
    {
        // draw cross lines
        $pdf = ZendPdfDocument::load($filePath);
        foreach($params as $pageNo => $blockNames) {
            $page = $pdf->pages[$pageNo]->setLineWidth(self::CROSS_LINE_WIDTH);
            foreach($blockNames as $blockName) {
                $page->drawLine(
                        $this->crossLineParams[$blockName]['bx'],
                        $this->crossLineParams[$blockName]['by'],
                        $this->crossLineParams[$blockName]['tx'],
                        $this->crossLineParams[$blockName]['ty']
                );
            }
        } // foreach
    
        $pdf->save($filePath);
    
    } // function drawCrossLines()
    
    /**
     * Convert all new lines with spaces to fill out to the end of each line
     *
     * @param string $content
     * @return string
     */
    protected function flattenTextContent($content)
    {
        $content = $this->linewrap(trim($content), Lp1::BOX_CHARS_PER_ROW);
        
        $paragraphs = explode("\r\n", $content);
        $lines = count($paragraphs);
        for($i=0; $i<$lines; $i++) {
            $paragraphs[$i] = trim($paragraphs[$i]);
            if(strlen($paragraphs[$i]) == 0) {
                unset($paragraphs[$i]);
            }
            else {
                // calculate how many space chars to be appended to replace the new line in this paragraph.
                if(strlen($paragraphs[$i]) % Lp1::BOX_CHARS_PER_ROW) {
                    $noOfSpaces = Lp1::BOX_CHARS_PER_ROW - strlen($paragraphs[$i]) % Lp1::BOX_CHARS_PER_ROW;
                    if($noOfSpaces > 0) {
                        $paragraphs[$i] .= str_repeat(" ", $noOfSpaces);
                    }
                }
            }
        }
        
        return implode("\r\n", $paragraphs);
    } // function flattenBoxContent($content)
    
    protected function mergerIntermediateFilePaths($paths)
    {
        if(empty($paths)) return;
        
        foreach($paths as $type=>$path) {
            if(isset($this->interFileStack[$type])) {
                $this->interFileStack[$type] = array_merge($this->interFileStack[$type], $path);
            }
            else {
                $this->interFileStack[$type] = $path;
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
                return "\r\n".substr($flattenContent, 0, (Lp1::BOX_CHARS_PER_ROW + 2) * Lp1::BOX_NO_OF_ROWS);
            }
            else {
                $chunks = str_split(substr($flattenContent, (Lp1::BOX_CHARS_PER_ROW + 2) * Lp1::BOX_NO_OF_ROWS), (Lp1::BOX_CHARS_PER_ROW + 2) * Cs2::BOX_NO_OF_ROWS_CS2);
                if(isset($chunks[$pageNo-1])) {
                    return "\r\n".$chunks[$pageNo-1];
                }
                else {
                    return null;
                }
            }
        }
        else {
            $chunks = str_split($flattenContent, (Lp1::BOX_CHARS_PER_ROW + 2)* Cs2::BOX_NO_OF_ROWS_CS2);
            if(isset($chunks[$pageNo])) {
                return "\r\n".$chunks[$pageNo];
            }
            else {
                return null;
            }
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
        return strlen($flattenContent) <= (Lp1::BOX_CHARS_PER_ROW + 2) * Lp1::BOX_NO_OF_ROWS;
    } // function canFitIntoTextBox()
    
    public function cleanup()
    {
        if(\file_exists($this->generatedPdfFilePath)) {
            unlink($this->generatedPdfFilePath);
        }
        
        // remove all generated intermediate pdf files
        foreach($this->interFileStack as $type => $paths) {
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
    
    protected function linewrap($string, $width, $break="\r\n", $cut=false)
    {
        $array = explode("\r\n", $string);
        $string = "";
        foreach($array as $key => $val) {
            $string .= wordwrap($val, $width, $break, $cut);
            $string .= "\r\n";
        }
        return $string;
    }
    
    /**
     * clean up intermediate files.
     */
    public function __destruct()
    {
        $this->cleanup();
    }
} // class
