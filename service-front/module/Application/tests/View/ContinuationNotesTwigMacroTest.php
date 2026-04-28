<?php

declare(strict_types=1);

namespace ApplicationTest\View;

use ApplicationTest\View\ViewModelRenderer;
use DOMDocument;
use DOMXpath;
use Laminas\View\Model\ViewModel;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * Unit tests for the Twig macro which renders continuation sheet prompts
 * for both the "Last steps" and date check pages.
 */
final class ContinuationNotesTwigMacroTest extends MockeryTestCase
{
    private ViewModelRenderer $renderer;

    public function setUp(): void
    {
        $this->renderer = new ViewModelRenderer();

        // stub out twig functions we don't care about
        $this->renderer->addFunction('url');
        $this->renderer->addFunction('form_linked_errors');
        $this->renderer->addFunction('formElementErrorsV2');
        $this->renderer->addFunction('formElement');
        $this->renderer->addFunction('serverUrl');

        // template will load the macro we're testing and call it
        $this->renderer->loadTemplateString("
            {% import \"application/macros.twig\" as macros %}
            {{ macros.continuationNotes(continuationNoteKeys) }}
        ");
    }

    // check that the macro correctly renders the notes about
    // continuation sheet 2s
    private function assertCS2NotesRendered(string $html): void
    {
        $dom = new DOMDocument();

        $dom->loadHTML($html);
        $xpath = new DOMXpath($dom);

        // check section title is in place with correct class
        $matches = $xpath->query('//h3[@class="heading-medium"]');
        $this->assertEquals(1, $matches->length, 'number of continuation sheet 2 headings is incorrect');

        // check section title has correct content
        $this->assertEquals(
            'Continuation sheets',
            trim(($matches[0])->textContent),
            'continuation sheet 2 heading contains incorrect text'
        );

        // check explanatory paragraph is present
        $matches = $xpath->query('//p');
        $this->assertEquals(
            1,
            $matches->length,
            'number of continuation sheet 2 explanatory paragraphs is incorrect'
        );

        // check explanatory paragraph has the correct text in it
        $this->assertEquals(
            'As there are extra instructions for the attorneys on the LPA, ' .
                'the donor will need to sign \'continuation sheet 2\'. ' .
                'The continuation sheet is included at the end of the LPA form.',
            trim(($matches[0])->textContent),
            'continuation sheet 2 explanatory paragraph contains incorrect text'
        );
    }

    public function testContinuationNotesCS2WhenHasAttorneyDecisions(): void
    {
        $viewModel = new ViewModel([
            'continuationNoteKeys' => ['HAS_ATTORNEY_DECISIONS'],
        ]);

        $html = $this->renderer->render($viewModel);

        $this->assertCS2NotesRendered($html);
    }

    public function testContinuationNotesCS2WhenLongPreferencesOrInstructions(): void
    {
        $viewModel = new ViewModel([
            'continuationNoteKeys' => ['LONG_INSTRUCTIONS_OR_PREFERENCES'],
        ]);

        $html = $this->renderer->render($viewModel);

        $this->assertCS2NotesRendered($html);
    }
}
