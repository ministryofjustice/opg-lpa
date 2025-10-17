<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Guidance;

use Application\Model\Service\Guidance\Guidance;
use ApplicationTest\Model\Service\AbstractServiceTest;

final class GuidanceTest extends AbstractServiceTest
{
    private string $cwd;
    private Guidance $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new Guidance($this->authenticationService, []);

        $this->cwd = getcwd();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testParseMarkdown(): void
    {
        $result = $this->service->parseMarkdown();

        $this->assertStringContainsString('A lasting power of attorney (LPA) is a legal document that lets someone', $result['sections'][0]['html']);
        $this->assertEquals('what-is-an-lpa', $result['sections'][0]['id']);
        $this->assertEquals('What is an LPA?', $result['sections'][0]['title']);
        $this->assertEquals('/guide#topic-what-is-an-lpa', $result['sections'][0]['url']);
        $this->assertEquals('guidance:link:navigation: What is an LPA?', $result['sections'][0]['dataJourney']);
        $this->assertEquals('what-is-an-lpa-nav-link', $result['sections'][0]['dataCy']);

        $this->assertStringContainsString('A person with mental capacity is able to make a specific decision ', $result['sections'][1]['html']);
        $this->assertEquals('mental-capacity', $result['sections'][1]['id']);
        $this->assertEquals('Mental capacity', $result['sections'][1]['title']);
        $this->assertEquals('/guide#topic-mental-capacity', $result['sections'][1]['url']);
        $this->assertEquals('guidance:link:navigation: Mental capacity', $result['sections'][1]['dataJourney']);
        $this->assertEquals('mental-capacity-nav-link', $result['sections'][1]['dataCy']);
    }

    public function testProcessSection(): void
    {
        $result = $this->service->processSection('People_Donor.md', 1);
        $this->assertStringContainsString('<article id="topic-1"><h2>The donor</h2>', $result);
        $this->assertStringContainsString("<p>This will help attorneys deal with banks and other organisations on the donor’s behalf.</p>", $result);
        $this->assertStringContainsString("<li>the donor has property outside England and Wales</li>\n", $result);
        $this->assertStringContainsString("<h3>If the donor lives outside England and Wales, or has property outside England and Wales</h3>", $result);
        $this->assertStringContainsString('</article>', $result);
    }
}
