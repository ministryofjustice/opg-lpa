<?php
namespace Application\Model\Service\Guidance;

use Michelf\Markdown;

class Guidance
{
    const GUIDANCE_MARKDOWN_FOLDER = 'public/guidance';

    /**
     * Generate guidance sections and navigation from the guidance markdown files
     * 
     * @return string The generated HTML
     */
    function generateHtmlFromMarkdown()
    {
        $html = '';
        $sectionArray = [];
        $lines = file(self::GUIDANCE_MARKDOWN_FOLDER . '/order.md');
        
        $navHtml = '';
        foreach ($lines as $line) {
            if (preg_match('/^\*\w*(.*)/', $line, $matches)) {
                $sectionTitle = trim($matches[1]);
            }
    
            if (preg_match('/^\s+\*\s*(.*\.md)/', $line, $matches)) {
                $sectionFilename = trim($matches[1]);
                
                $sectionId = trim(strtolower(str_replace(' ', '-', $sectionTitle)));
                
                $sectionArray[] = [
                    'id' => $sectionId,
                    'title' => $sectionTitle,
                    'html' => $this->processSection($sectionFilename, $sectionId),
                    'url' => '/help/#topic-' . $sectionId,
                    'dataJourney' => 'stageprompt.lpa:help:' . $sectionId,
                ];
            }
        }
        
        return [
            'navHtml' => $navHtml,
            'bodyHtml' => $html,
            'sections' => $sectionArray,
        ];
    }
    
    /**
     * Create HTML for a single section of guidance text
     * 
     * @param string $filename
     * @param string $sectionId
     * 
     * @return string The generated HTML
     */
    function processSection($filename, $sectionId)
    {
        $md = Markdown::defaultTransform(file_get_contents(self::GUIDANCE_MARKDOWN_FOLDER . '/' . $filename));
        $retval = '';
        $retval.= PHP_EOL . '<article id="topic-' . $sectionId . '">';
        $retval.= preg_replace('/<a href="\/help\/#topic-(.+)">(.+)<\/a>/', '<a href="/help/#topic-${1}" class="js-guidance" data-journey="stageprompt.lpa:help:${1}">${2}</a>', $md);
        $retval.= PHP_EOL . '</article>';
        return $retval;
    }
}