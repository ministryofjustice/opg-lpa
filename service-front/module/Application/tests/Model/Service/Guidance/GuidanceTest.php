<?php

namespace ApplicationTest\Model\Service\Guidance;

use Application\Model\Service\Guidance\Guidance;
use ApplicationTest\Model\Service\AbstractServiceTest;

class GuidanceTest extends AbstractServiceTest
{
    /**
     * @var $cwd string
     */
    private $cwd;

    /**
     * @var $service Guidance
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->service = new Guidance($this->authenticationService, []);

        $this->cwd = getcwd();

        chdir(__DIR__);
    }

    public function tearDown()
    {
        parent::tearDown();

        chdir($this->cwd);
    }

    public function testParseMarkdown() : void
    {
        $result = $this->service->parseMarkdown();

        $this->assertEquals(['sections' => [
            [
                'id' => 'topic-1',
                'title' => 'Topic 1',
                'html' => "<article id=\"topic-topic-1\"><h1>Content title</h1>\n" .
                    "\n" .
                    "<p>Here are some words (in parentheses), with punctuation!</p>\n" .
                    "\n" .
                    "<p><strong>Sub title</strong></p>\n" .
                    "\n" .
                    "<p>More words</p>\n" .
                    "</article>",
                'url' => '/guide#topic-topic-1',
                'dataJourney' => 'guidance:link:navigation: Topic 1',
            ],
            [
                'id' => 'topic-2',
                'title' => 'Topic 2',
                'html' => "<article id=\"topic-topic-2\">\n</article>",
                'url' => '/guide#topic-topic-2',
                'dataJourney' => 'guidance:link:navigation: Topic 2'
            ]]], $result);
    }

    public function testProcessSection() : void
    {
        $result = $this->service->processSection('1.md', 1);

        $this->assertEquals("<article id=\"topic-1\"><h1>Content title</h1>\n\n" .
            "<p>Here are some words (in parentheses), with punctuation!</p>\n\n" .
            "<p><strong>Sub title</strong></p>\n\n" .
            "<p>More words</p>\n" .
            "</article>", $result);
    }
}
