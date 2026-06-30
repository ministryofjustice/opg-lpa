<?php

declare(strict_types=1);

namespace App\Service\Guidance;

use Michelf\Markdown;

class GuidanceService
{
    public const GUIDANCE_MARKDOWN_FOLDER = 'content/guidance';
    public const GUIDANCE_ROUTE = 'guide';

    public function __construct(
        private readonly string $markdownFolder = self::GUIDANCE_MARKDOWN_FOLDER,
    ) {
    }

    /**
     * Generate guidance sections and navigation from the guidance markdown files.
     *
     * @return array{sections: array}
     */
    public function parseMarkdown(): array
    {
        $sectionArray = [];
        $orderFileName = '/order.md';
        $lines = file($this->markdownFolder . $orderFileName);

        $sectionTitle = '';

        foreach ($lines as $line) {
            if (preg_match('/^\*\w*(.*)/', $line, $matches)) {
                $sectionTitle = trim($matches[1]);
            }

            if (preg_match('/^\s+\*\s*(.*\.md)/', $line, $matches)) {
                $sectionFilename = trim($matches[1]);

                $sectionTitleClean = str_replace(['?', ','], '', $sectionTitle);
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

    public function processSection(string $filename, string $sectionId): string
    {
        $md = Markdown::defaultTransform(file_get_contents($this->markdownFolder . '/' . $filename));

        $html = '<article id="topic-' . $sectionId . '">';

        // Add govuk-link + js-guidance to internal help topic links and rewrite to the guidance route
        $processed = preg_replace(
            '/<a href="\/help\/#topic-([^"]*)">([^"]*)<\/a>/',
            '<a href="/' . self::GUIDANCE_ROUTE . '#topic-${1}" class="govuk-link js-guidance" data-cy="${1}-link" data-analytics-click="guidance:link:help: ${1}">${2}</a>',
            $md
        );

        // Add govuk-link to all other plain <a> tags that don't already have a class attribute
        $processed = preg_replace(
            '/<a href="([^"]*)"(?![^>]*class=)/',
            '<a href="${1}" class="govuk-link"',
            $processed
        );

        $html .= $processed;

        $html .= '</article>';

        return $html;
    }
}
