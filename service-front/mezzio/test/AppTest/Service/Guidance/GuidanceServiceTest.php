<?php

declare(strict_types=1);

namespace AppTest\Service\Guidance;

use App\Service\Guidance\GuidanceService;
use PHPUnit\Framework\TestCase;

final class GuidanceServiceTest extends TestCase
{
    private GuidanceService $service;

    public function setUp(): void
    {
        $this->service = new GuidanceService();
    }

    // -------------------------------------------------------------------------
    // parseMarkdown
    // -------------------------------------------------------------------------

    public function testParseMarkdownReturnsExpectedFirstSection(): void
    {
        $result = $this->service->parseMarkdown();

        $this->assertArrayHasKey('sections', $result);
        $this->assertNotEmpty($result['sections']);

        $first = $result['sections'][0];
        $this->assertSame('what-is-an-lpa', $first['id']);
        $this->assertSame('What is an LPA?', $first['title']);
        $this->assertSame('/guide#topic-what-is-an-lpa', $first['url']);
        $this->assertSame('guidance:link:navigation: What is an LPA?', $first['dataJourney']);
        $this->assertSame('what-is-an-lpa-nav-link', $first['dataCy']);
        $this->assertStringContainsString('<article id="topic-what-is-an-lpa">', $first['html']);
    }

    // -------------------------------------------------------------------------
    // processSection — link class injection
    // -------------------------------------------------------------------------

    public function testProcessSectionAddsGovukLinkToPlainExternalLinks(): void
    {
        // "About LPAs_Life-sustaining treatment.md" contains plain <a href="http://..."> links
        $html = $this->service->processSection('About LPAs_Life-sustaining treatment.md', 'life-sustaining');

        // Both external links in this file should get govuk-link
        $this->assertStringContainsString('class="govuk-link"', $html);
        // Confirm the href is preserved and the link is not double-classed
        $this->assertStringNotContainsString('class="govuk-link" class=', $html);
    }

    public function testProcessSectionDoesNotAddGovukLinkToLinksAlreadyHavingAClass(): void
    {
        $html = $this->service->processSection('About LPAs_Life-sustaining treatment.md', 'life-sustaining');

        // No link should end up with two class attributes
        $this->assertStringNotContainsString('class="govuk-link" class=', $html);
        $this->assertStringNotContainsString('class=" class=', $html);
    }

    public function testProcessSectionConvertsInternalHelpTopicLinksToGuidanceRoute(): void
    {
        // "About LPAs_Life-sustaining treatment.md" contains a /help/#topic- markdown link
        $html = $this->service->processSection('About LPAs_Life-sustaining treatment.md', 'life-sustaining');

        // Internal topic links must be rewritten to /guide#topic-... with govuk-link js-guidance
        $this->assertStringContainsString('/guide#topic-preferences-and-instructions', $html);
        $this->assertStringContainsString('class="govuk-link js-guidance"', $html);
        // The old /help/ URL must not appear in the output
        $this->assertStringNotContainsString('/help/#topic-', $html);
    }

    public function testProcessSectionWrapsOutputInArticleTag(): void
    {
        $html = $this->service->processSection('About LPAs_Mental capacity.md', 'mental-capacity');

        $this->assertStringStartsWith('<article id="topic-mental-capacity">', $html);
        $this->assertStringEndsWith('</article>', $html);
    }
}
