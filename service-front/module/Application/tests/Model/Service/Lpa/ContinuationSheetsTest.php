<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Lpa;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Application\Model\Service\Lpa\ContinuationSheets;

final class ContinuationSheetsTest extends AbstractHttpControllerTestCase
{
    private ContinuationSheets $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new ContinuationSheets();
    }

    public function testAttorneyOverflowGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'primaryAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human']
                ]
            ]
        ]);

        $this->assertEquals(
            ['PRIMARY_ATTORNEY_OVERFLOW', 'ANY_PEOPLE_OVERFLOW'],
            $this->service->getContinuationNoteKeys($mockLpa)
        );
    }

    public function testAnyOverflowGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'peopleToNotify' => [[], [], [], [], []]
            ]
        ]);

        $this->assertEquals(
            ['NOTIFY_OVERFLOW', 'ANY_PEOPLE_OVERFLOW'],
            $this->service->getContinuationNoteKeys($mockLpa)
        );
    }

    public function testLongInstructionGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'instruction' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                    incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                    exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure
                    dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
                    Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
                    mollit anim id est laborum.'
            ]
        ]);

        $this->assertEquals(['LONG_INSTRUCTIONS_OR_PREFERENCES'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testCantSignGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'donor' => [
                    'canSign' => false
                ]
            ]
        ]);

        $this->assertEquals(['CANT_SIGN'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testTrustAttorneyGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'primaryAttorneys' => [
                    ['type' => 'corporation', 'number' => '123']
                ]
            ]
        ]);

        $this->assertEquals(['HAS_TRUST_CORP'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testCombinationsGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'preference' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                    incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                    exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure
                    dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
                    Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
                    mollit anim id est laborum.',
                'primaryAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                ],
                'replacementAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                ],
                'primaryAttorneyDecisions' => [
                    'how' => AbstractDecisions::LPA_DECISION_HOW_DEPENDS,
                ]
            ]
        ]);

        $expectedResult = [
            'LONG_INSTRUCTIONS_OR_PREFERENCES',
            'REPLACEMENT_ATTORNEY_OVERFLOW',
            'ANY_PEOPLE_OVERFLOW',
            'HAS_ATTORNEY_DECISIONS'
        ];
        $this->assertEqualsCanonicalizing($expectedResult, $this->service->getContinuationNoteKeys($mockLpa));
    }
}
