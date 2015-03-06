<?php
namespace Application\Model\Service\Guidance;

use Michelf\Markdown;

class Guidance
{
    const GUIDANCE_MARKDOWN_FOLDER = 'public/guidance';
    
    public function generateHtmlFromMarkdown()
    {
        return $this->buildGuidance();
    }
    
    function buildGuidance()
    {
        $navigationHtml = '
      <nav class="help-navigation">
        <div class="group">
    	    <h2>Help topics</h2>
    		  <ul class="help-topics">';
    
        $sections = '';
        $lines = file(self::GUIDANCE_MARKDOWN_FOLDER . '/order.md');
        foreach ($lines as $line) {
            if (preg_match('/^\*\w*(.*)/', $line, $matches)) {
                $sectionTitle = trim($matches[1]);
            }
    
            if (preg_match('/^\s+\*\s*(.*\.md)/', $line, $matches)) {
                $sectionFilename = trim($matches[1]);
                $sectionId = trim(strtolower(str_replace(' ', '-', $sectionTitle)));
                $sections.= $this->processSection($sectionFilename, $sectionId);
                $navigationHtml .= '
            <li><a class="js-guidance" href="/help/#topic-' . $sectionId . '" data-journey="stageprompt.lpa:help:' . $sectionId . '">' . $sectionTitle . '</a></li>' . PHP_EOL;
            }
        }
    
        $navigationHtml .= '
          </ul>
        </div>
      </nav>';
    
        $html .= '
    <section id="help-system">
      <header>
        <p>A guide to making your lasting power of attorney</p>
      </header>' . $navigationHtml .
    
        '
      <div class="content help-sections">' . $sections . 
      '</div>
      <div class="action group">
        <p>Need help? Ring us on <strong>0300 456 0300</strong>. Alternatively, email us at <strong><a href="mailto:customerservices@PublicGuardian.gsi.gov.uk?subject=Digital%20LPA%20Enquiry">customerservices@PublicGuardian.gsi.gov.uk</a></strong>.</p>
        <hr>
        <a href="#" class="js-popup-close button-secondary">Close help</a>
      </div>
    </section>';
    
        return $html;
    }
    
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