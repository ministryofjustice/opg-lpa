<?php
namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;

class Cs2 extends AbstractForm
{
    private $contentType;
    private $content;
    
    const BOX_NO_OF_ROWS_CS2 = 14;
    
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
        Logger::getInstance()->info(
            'Generating Cs2',
            [
                'lpaId' => $this->lpa->id
            ]
        );
        
        $cs2Continued = '';
        $formatedContentLength = strlen($this->flattenTextContent($this->content));
        if(($this->contentType == self::CONTENT_TYPE_ATTORNEY_DECISIONS) || ($this->contentType == self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN)) {
            $contentLengthOnStandardForm = 0;
            $totalAdditionalPages = ceil($formatedContentLength/((Lp1::BOX_CHARS_PER_ROW + 2)* self::BOX_NO_OF_ROWS_CS2));
        }
        else {
            $contentLengthOnStandardForm = (Lp1::BOX_CHARS_PER_ROW + 2) * Lp1::BOX_NO_OF_ROWS;
            $totalAdditionalPages = ceil(($formatedContentLength-$contentLengthOnStandardForm)/((Lp1::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2));
        }
        
        for($i=0; $i<$totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('CS2');
            
            if(($this->contentType == self::CONTENT_TYPE_ATTORNEY_DECISIONS) || ($this->contentType == self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN)) {
                $pageNo = $i;
            }
            else {
                $pageNo = $i+1;
            }
            
            if(($i>0)||
                ($this->contentType==self::CONTENT_TYPE_PREFERENCES)||
                ($this->contentType==self::CONTENT_TYPE_INSTRUCTIONS)) {
                    $cs2Continued = '(Continued)';
            }
            
            $cs2 = PdfProcessor::getPdftkInstance($this->pdfTemplatePath."/LPC_Continuation_Sheet_2.pdf");
            $cs2->fillForm(array(
                    $this->contentType  => self::CHECK_BOX_ON,
                    'cs2-content'       => $this->getContentForBox($pageNo, $this->content, $this->contentType),
                    'donor-full-name'   => $this->fullName($this->lpa->document->donor->name),
                    'cs2-continued'     => $cs2Continued,
                    'footer_right'    => Config::getInstance()['footer']['cs2'],
            ))
            ->flatten()
            ->saveAs($filePath);
        }
        
        return $this->interFileStack;
    } // function generate()
    
    public function __destruct()
    {
        
    }
} // class Cs2