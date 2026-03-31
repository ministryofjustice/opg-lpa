<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionUtility;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActorReuseDetailsServiceTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private SessionUtility&MockObject $sessionUtility;
    private ActorReuseDetailsService $service;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->sessionUtility = $this->createMock(SessionUtility::class);

        $this->service = new ActorReuseDetailsService(
            $this->lpaApplicationService,
            $this->sessionUtility,
        );
    }

    private function createLpaWithReplacementAttorneys(int $count): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [];

        for ($i = 0; $i < $count; $i++) {
            $attorney = new Human();
            $attorney->id = $i + 1;
            $attorney->name = new Name(['title' => 'Mr', 'first' => 'Attorney', 'last' => (string) ($i + 1)]);
            $attorney->dob = new Dob(['date' => '1980-01-01']);
            $lpa->document->replacementAttorneys[] = $attorney;
        }

        return $lpa;
    }

    public function testGetActorsListIncludesAllReplacementAttorneysByDefault(): void
    {
        $lpa = $this->createLpaWithReplacementAttorneys(2);

        $result = $this->service->getActorsList($lpa);

        $replacementAttorneyEntries = array_filter(
            $result,
            fn(array $entry) => $entry['type'] === 'replacement attorney'
        );

        $this->assertCount(2, $replacementAttorneyEntries);
    }

    public function testGetActorsListExcludesSpecifiedReplacementAttorneyIndex(): void
    {
        $lpa = $this->createLpaWithReplacementAttorneys(2);

        $result = $this->service->getActorsList($lpa, true, 0);

        $replacementAttorneyEntries = array_filter(
            $result,
            fn(array $entry) => $entry['type'] === 'replacement attorney'
        );

        $this->assertCount(1, $replacementAttorneyEntries);
        // The remaining one should be index 1 (last name '2')
        $remaining = array_values($replacementAttorneyEntries);
        $this->assertEquals('2', $remaining[0]['lastname']);
    }

    public function testGetActorsListExclusionDoesNotAffectOtherActorTypes(): void
    {
        $lpa = $this->createLpaWithReplacementAttorneys(2);

        $primaryAttorneyCountBefore = count(array_filter(
            $this->service->getActorsList($lpa),
            fn(array $entry) => $entry['type'] === 'attorney'
        ));

        $result = $this->service->getActorsList($lpa, true, 0);

        $primaryAttorneyCountAfter = count(array_filter(
            $result,
            fn(array $entry) => $entry['type'] === 'attorney'
        ));

        $this->assertEquals($primaryAttorneyCountBefore, $primaryAttorneyCountAfter);
    }

    public function testGetActorsListWithNullExclusionIncludesAllAttorneys(): void
    {
        $lpa = $this->createLpaWithReplacementAttorneys(3);

        $result = $this->service->getActorsList($lpa, true, null);

        $replacementAttorneyEntries = array_filter(
            $result,
            fn(array $entry) => $entry['type'] === 'replacement attorney'
        );

        $this->assertCount(3, $replacementAttorneyEntries);
    }

    public function testGetActorsListExcludesLastIndex(): void
    {
        $lpa = $this->createLpaWithReplacementAttorneys(3);

        $result = $this->service->getActorsList($lpa, true, 2);

        $replacementAttorneyEntries = array_filter(
            $result,
            fn(array $entry) => $entry['type'] === 'replacement attorney'
        );

        $this->assertCount(2, $replacementAttorneyEntries);
    }

    public function testAllowTrustReturnsFalseForHwLpa(): void
    {
        $lpa = FixturesData::getHwLpa();

        $this->assertFalse($this->service->allowTrust($lpa));
    }

    public function testAllowTrustReturnsTrueForPfLpaWithNoExistingTrust(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [];

        $this->assertTrue($this->service->allowTrust($lpa));
    }

    public function testAllowTrustReturnsFalseWhenTrustAlreadyExistsInReplacementAttorneys(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [FixturesData::getAttorneyTrust()];

        $this->assertFalse($this->service->allowTrust($lpa));
    }

    public function testAllowTrustReturnsFalseWhenTrustAlreadyExistsInPrimaryAttorneys(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $this->assertFalse($this->service->allowTrust($lpa));
    }
}
