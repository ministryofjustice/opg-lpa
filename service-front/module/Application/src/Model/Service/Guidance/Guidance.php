<?php

namespace Application\Model\Service\Guidance;

use Application\Model\Service\AbstractService;
use DateTimeImmutable;
use Michelf\Markdown;

class Guidance extends AbstractService
{
    const GUIDANCE_MARKDOWN_FOLDER = 'content/guidance';
    const GUIDANCE_ROUTE = 'guide';

    /**
     * Generate guidance sections and navigation from the guidance markdown files
     *
     * @return array An array of guidance section details
     */
    function parseMarkdown()
    {
        $sectionArray = [];
        $feeEffectiveDate = new DateTimeImmutable(getenv('LPA_FEE_EFFECTIVE_DATE') ?: '2025-11-17T00:00:00');
        $timeNow = new DateTimeImmutable('now');
        $orderFileName = ($timeNow >= $feeEffectiveDate) ? '/order.md' : '/order_old.md';
        $lines = file(self::GUIDANCE_MARKDOWN_FOLDER . $orderFileName);

        foreach ($lines as $line) {
            if (preg_match('/^\*\w*(.*)/', $line, $matches)) {
                $sectionTitle = trim($matches[1]);
            }

            if (preg_match('/^\s+\*\s*(.*\.md)/', $line, $matches)) {
                $sectionFilename = trim($matches[1]);

                // Cleaning out characters that won't play nicely in a url
                $sectionTitleClean = str_replace(array('?',','), '', $sectionTitle);

                $sectionId = trim(strtolower(str_replace(' ', '-', $sectionTitleClean)));

                $sectionArray[] = [
                    'id' => $sectionId,
                    'title' => $sectionTitle,
                    'html' => $this->processSection($sectionFilename, $sectionId),
                    'url' => '/' . self::GUIDANCE_ROUTE . '#topic-' . $sectionId,
                    'dataJourney' => 'guidance:link:navigation: ' . $sectionTitle,
                    'dataCy' => $sectionId . '-nav-link',
                ];
            }
        }

        return [
            'sections' => $sectionArray,
        ];
    }

    /**
     * Transform markdown of a guidance section into HTML
     *
     * @param string $filename
     * @param string $sectionId
     *
     * @return string The generated HTML
     */
    function processSection($filename, $sectionId)
    {
        $md = Markdown::defaultTransform(file_get_contents(self::GUIDANCE_MARKDOWN_FOLDER . '/' . $filename));

        $html = '<article id="topic-' . $sectionId . '">';

        $html .= preg_replace(
            '/<a href="\/help\/#topic-([^"]*)">([^"]*)<\/a>/',
            '<a href="/' . self::GUIDANCE_ROUTE . '#topic-${1}" class="js-guidance" data-cy="${1}-link" data-analytics-click="guidance:link:help: ${1}">${2}</a>',
            $md
        );

        $html .= '</article>';

        return $html;
    }
}
