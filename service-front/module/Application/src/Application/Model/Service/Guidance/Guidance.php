<?php
namespace Application\Model\Service\Guidance;

use Michelf\Markdown;

class Guidance
{
    const GUIDANCE_MARKDOWN_FOLDER = 'public/guidance';
    const SUPPORT_PHONE = '0300 456 0300';
    const SUPPORT_EMAIL = 'customerservices@PublicGuardian.gsi.gov.uk';

    /**
     * Generate HTML from the guidance markdown files
     * 
     * @return string The generated HTML
     */
    function generateHtmlFromMarkdown()
    {
        $html = '';
        $sections = '';
        $lines = file(self::GUIDANCE_MARKDOWN_FOLDER . '/order.md');
        
        $navHtml = '';
        foreach ($lines as $line) {
            if (preg_match('/^\*\w*(.*)/', $line, $matches)) {
                $sectionTitle = trim($matches[1]);
            }
    
            if (preg_match('/^\s+\*\s*(.*\.md)/', $line, $matches)) {
                $sectionFilename = trim($matches[1]);
                $sectionId = trim(strtolower(str_replace(' ', '-', $sectionTitle)));
                $sections .= $this->processSection($sectionFilename, $sectionId);
                $url = '/help/#topic-' . $sectionId;
                $dataJourney = 'stageprompt.lpa:help:' . $sectionId;
                $navHtml .= '<li><a class="js-guidance" href="' . $url . '" data-journey="' . $dataJourney . '">' . $sectionTitle . '</a></li>';
            }
        }
        
        $html .= '<div class="action group">';
        $html .= '<p>';
        $html .= 'Need help? Ring us on <strong>' . SUPPORT_PHONE . '</strong>. ';
        $html .= 'Alternatively, email us at ';
        $html .= '<strong><a href="mailto:' . SUPPORT_EMAIL . '?subject=Digital%20LPA%20Enquiry">' . SUPPORT_EMAIL . '</a></strong>';
        $html .= '</p>';
        $html .= '<hr>';
        $html .= '<a href="#" class="js-popup-close button-secondary">Close help</a>';
        $html .= '</div>';
        $html .= '</section>';
    
        return [
            'navHtml' => $navHtml,
            'bodyHtml' => $html,
            'sectionsHtml' => $sections,
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