<?php

namespace Opg\Lpa\Pdf\Service\Forms;

abstract class AbstractCs2 extends AbstractForm
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile = 'LPC_Continuation_Sheet_2.pdf';

    /**
     * @param $pageNo
     * @param $content
     * @return null|string
     */
    protected function getFormattedContent($pageNo, $content)
    {
        $flattenContent = $this->flattenTextContent($content);

        $chunks = str_split($flattenContent, (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2);

        $formattedContent = null;

        if (isset($chunks[$pageNo])) {
            $formattedContent = "\r\n" . $chunks[$pageNo];
        }

        return $formattedContent;
    }
}
