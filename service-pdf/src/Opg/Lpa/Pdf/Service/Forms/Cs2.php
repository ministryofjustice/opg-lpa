<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;

class Cs2 extends AbstractForm
{
    private $contentType;
    private $content;
    
    const BOX_NO_OF_ROWS_CS2 = 17;
    
    /**
     * @param Lpa $lpa
     * @param enum $contentType - CONTENT_TYPE_ATTORNEY_DECISIONS | CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN | CONTENT_TYPE_PREFERENCES | CONTENT_TYPE_INSTRUCTIONS
     * @param string $content
     */
    public function __construct(Lpa $lpa, $contentType, $content)
    {
        parent::__construct($lpa);
        
        $this->contentType = $contentType;
        $this->content = $content;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Opg\Lpa\Pdf\Service\Forms\AbstractForm::generate()
     */
    public function generate()
    {
        $formatedContentLength = strlen($this->flattenTextContent($this->content));
        if(($this->contentType == self::CONTENT_TYPE_ATTORNEY_DECISIONS) || ($this->contentType == self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN)) {
            $contentLengthOnStandardForm = 0;
            $totalAdditionalPages = ceil($formatedContentLength/(Lp1::BOX_CHARS_PER_ROW * self::BOX_NO_OF_ROWS_CS2));
        }
        else {
            $contentLengthOnStandardForm = Lp1::BOX_CHARS_PER_ROW * Lp1::BOX_NO_OF_ROWS;
            $totalAdditionalPages = ceil(($formatedContentLength-$contentLengthOnStandardForm)/(Lp1::BOX_CHARS_PER_ROW * self::BOX_NO_OF_ROWS_CS2));
        }
        
        for($i=0; $i<$totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('CS2');
            
            if(($this->contentType == self::CONTENT_TYPE_ATTORNEY_DECISIONS) || ($this->contentType == self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN)) {
                $pageNo = $i;
            }
            else {
                $pageNo = $i+1;
            }
            
            $cs2 = PdfProcessor::getPdftkInstance($this->basePdfTemplatePath."/LPC_Continuation_Sheet_2.pdf");
            $cs2->fillForm(array(
                    $this->contentType  => self::CHECK_BOX_ON,
                    'cs-2-content'      => $this->getContentForBox($pageNo, $this->content, $this->contentType),
                    'donor-full-name'   => $this->fullName($this->lpa->document->donor->name)
            ))->needAppearances()
                ->flatten()
                ->saveAs($filePath);
//             print_r($cs2);
        }
        
        return $this->intermediateFilePaths;
    } // function addContinuationSheet()
    
    public function __destruct()
    {
        
    }
} // class Cs2