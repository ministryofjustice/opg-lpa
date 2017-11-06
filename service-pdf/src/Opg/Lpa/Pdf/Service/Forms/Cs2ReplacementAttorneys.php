<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class Cs2ReplacementAttorneys extends AbstractCs2
{
    /**
     * (non-PHPdoc)
     * @see \Opg\Lpa\Pdf\Service\Forms\AbstractForm::generate()
     */
    public function generate()
    {
        $this->logGenerationStatement();

        //  Determine the content to add to the continuation sheet
        $content = '';

        if ((count($this->lpa->document->primaryAttorneys) == 1
                || (count($this->lpa->document->primaryAttorneys) > 1
                    && $this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY))
            && count($this->lpa->document->replacementAttorneys) > 1) {

            switch ($this->lpa->document->replacementAttorneyDecisions->how) {
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $content = "Replacement attorneys are to act jointly and severally\r\n";
                    break;
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                    $content = "Replacement attorneys are to act jointly for some decisions and jointly and severally for others, as below:\r\n" . $this->lpa->document->replacementAttorneyDecisions->howDetails . "\r\n";
                    break;
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                    // default arrangement
                    break;
            }
        } elseif (count($this->lpa->document->primaryAttorneys) > 1 && $this->lpa->document->primaryAttorneyDecisions->how == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) {
            if (count($this->lpa->document->replacementAttorneys) == 1) {
                switch ($this->lpa->document->replacementAttorneyDecisions->when) {
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST:
                        // default arrangement, as per how primary attorneys making decision arrangement
                        break;
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST:
                        $content = "Replacement attorney to step in only when none of the original attorneys can act\r\n";
                        break;
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS:
                        $content = "How replacement attorneys will replace the original attorneys:\r\n" . $this->lpa->document->replacementAttorneyDecisions->whenDetails;
                        break;
                }
            } elseif (count($this->lpa->document->replacementAttorneys) > 1) {
                if ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) {
                    $content = "Replacement attorneys to step in only when none of the original attorneys can act\r\n";

                    switch ($this->lpa->document->replacementAttorneyDecisions->how) {
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                            $content .= "Replacement attorneys are to act jointly and severally\r\n";
                            break;
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                            $content .= "Replacement attorneys are to act joint for some decisions, joint and several for other decisions, as below:\r\n" . $this->lpa->document->replacementAttorneyDecisions->howDetails . "\r\n";
                            break;
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                            // default arrangement
                            $content = "";
                            break;
                    }
                } elseif ($this->lpa->document->replacementAttorneyDecisions->when == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $content = "How replacement attorneys will replace the original attorneys:\r\n" . $this->lpa->document->replacementAttorneyDecisions->whenDetails;
                }
            }
        }

        $formattedContentLength = strlen($this->flattenTextContent($content));

        $totalAdditionalPages = ceil($formattedContentLength / ((self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2));

        for ($i = 0; $i < $totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('CS2');

            //  Set the PDF form data
            $formData = [];
            $formData['cs2-is'] = 'how-replacement-attorneys-step-in';
            $formData['cs2-content'] = $this->getFormattedContent($i, $content);
            $formData['cs2-donor-full-name'] = $this->lpa->document->donor->name->__toString();
            $formData['cs2-continued'] = ($i > 0 ? '(Continued)' : '');
            $formData['cs2-footer-right'] = $this->config['footer']['cs2'];

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($formData)
                ->flatten()
                ->saveAs($filePath);
        }

        return $this->interFileStack;
    }
}
