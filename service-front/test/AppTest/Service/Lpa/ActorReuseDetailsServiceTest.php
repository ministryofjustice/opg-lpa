<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\ActorReuseDetailsService;
use App\Service\Lpa\Application as LpaApplicationService;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

use function array_filter;
use function array_map;
use function array_values;
use function count;

class ActorReuseDetailsServiceTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private ActorReuseDetailsService $service;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->service = new ActorReuseDetailsService(
            $this->lpaApplicationService,
        );
    }

    private function createLpaWithReplacementAttorneys(int $count): Lpa
    {
        $lpa                                 = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [];

        for ($i = 0; $i < $count; $i++) {
            $attorney                              = new Human();
            $attorney->id                          = $i + 1;
            $attorney->name                        = new Name(['title' => 'Mr', 'first' => 'Attorney', 'last' => (string) ($i + 1)]);
            $attorney->dob                         = new Dob(['date' => '1980-01-01']);
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

    public function testGetActorsListIncludesPeopleToNotify(): void
    {
        $lpa = FixturesData::getPfLpa();

        $person                        = new NotifiedPerson();
        $person->id                    = 1;
        $person->name                  = new Name(['title' => 'Mr', 'first' => 'Notified', 'last' => 'Person']);
        $lpa->document->peopleToNotify = [$person];

        $result = $this->service->getActorsList($lpa);

        $peopleToNotifyEntries = array_values(array_filter(
            $result,
            fn(array $entry) => $entry['type'] === 'person to notify'
        ));

        $this->assertCount(1, $peopleToNotifyEntries);
        $this->assertSame('Notified', $peopleToNotifyEntries[0]['firstname']);
        $this->assertSame('Person', $peopleToNotifyEntries[0]['lastname']);
    }

    public function testAllowTrustReturnsFalseForHwLpa(): void
    {
        $lpa = FixturesData::getHwLpa();

        $this->assertFalse($this->service->allowTrust($lpa));
    }

    public function testAllowTrustReturnsTrueForPfLpaWithNoExistingTrust(): void
    {
        $lpa                                 = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [];

        $this->assertTrue($this->service->allowTrust($lpa));
    }

    public function testAllowTrustReturnsFalseWhenTrustAlreadyExistsInReplacementAttorneys(): void
    {
        $lpa                                 = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [FixturesData::getAttorneyTrust()];

        $this->assertFalse($this->service->allowTrust($lpa));
    }

    public function testAllowTrustReturnsFalseWhenTrustAlreadyExistsInPrimaryAttorneys(): void
    {
        $lpa                               = FixturesData::getPfLpa();
        $lpa->document->primaryAttorneys[] = FixturesData::getAttorneyTrust();

        $this->assertFalse($this->service->allowTrust($lpa));
    }

    public function testSetSessionStoresSessionInstance(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $this->service->setSession($session);

        $property = new ReflectionProperty($this->service, 'session');

        $this->assertSame($session, $property->getValue($this->service));
    }

    public function testGetActorReuseDetailsBuildsReuseDetailsFromUserAndSeedActors(): void
    {
        $user = FixturesData::getUser();
        $lpa  = FixturesData::getPfLpa();
        $lpa->setId(9001)->setSeed(123);

        $seedDetails = [
            'donor'                => [
                'name'    => ['first' => 'Seed', 'last' => 'Donor'],
                'address' => ['address1' => '1 Seed Street'],
                'dob'     => ['date' => '1970-02-03T00:00:00.000Z'],
                'ignored' => 'remove-me',
            ],
            'correspondent'        => [
                'who'   => 'other',
                'name'  => ['first' => 'Seed', 'last' => 'Correspondent'],
                'email' => ['address' => 'seed@example.com'],
            ],
            'certificateProvider'  => [
                'who'     => 'other',
                'name'    => ['first' => 'Seed', 'last' => 'Certificate'],
                'address' => ['address1' => '2 Seed Street'],
            ],
            'primaryAttorneys'     => [
                [
                    'name'    => ['first' => 'Seed', 'last' => 'Primary'],
                    'type'    => 'human',
                    'address' => ['address1' => '3 Seed Street'],
                ],
                [
                    'name'   => 'Seed Trust',
                    'type'   => 'trust',
                    'number' => '123456',
                ],
            ],
            'replacementAttorneys' => [
                [
                    'name' => ['first' => 'Seed', 'last' => 'Replacement'],
                    'type' => 'human',
                ],
            ],
            'peopleToNotify'       => [
                [
                    'name' => ['first' => 'Seed', 'last' => 'Notify'],
                ],
            ],
        ];

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with('clone_123')
            ->willReturn($seedDetails);

        $this->lpaApplicationService->expects($this->never())
            ->method('getSeedDetails');

        $this->service->setSession($session);

        $result = $this->service->getActorReuseDetails($user, $lpa);

        $labels = array_map(fn (array $entry) => $entry['label'], array_values($result));

        $this->assertSame('Chris Smith (myself)', $result[0]['label']);
        $this->assertContains('Seed Donor (was the donor)', $labels);
        $this->assertContains('Seed Correspondent (was the correspondent)', $labels);
        $this->assertContains('Seed Certificate (was the certificate provider)', $labels);
        $this->assertContains('Seed Primary (was a primary attorney)', $labels);
        $this->assertContains('Seed Replacement (was a replacement attorney)', $labels);
        $this->assertContains('Seed Notify (was a person to be notified)', $labels);
        $this->assertArrayHasKey('t', $result);
        $this->assertSame('Seed Trust (was a primary attorney)', $result['t']['label']);
        $this->assertSame('Seed Trust', $result['t']['data']['company']);
        $this->assertSame('attorney', $result['t']['data']['who']);
        $this->assertSame('03', $result[1]['data']['dob-date']['day']);
        $this->assertArrayNotHasKey('ignored', $result[1]['data']);
    }

    public function testGetActorReuseDetailsSkipsTrustsWhenTheyAreExcluded(): void
    {
        $user = FixturesData::getUser();
        $lpa  = FixturesData::getPfLpa();
        $lpa->setSeed(456);

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('clone_456')->willReturn([
            'correspondent'    => [
                'who'  => 'donor',
                'name' => ['first' => 'Seed', 'last' => 'Correspondent'],
            ],
            'primaryAttorneys' => [
                [
                    'name' => 'Seed Trust',
                    'type' => 'trust',
                ],
            ],
        ]);

        $this->service->setSession($session);

        $result = $this->service->getActorReuseDetails($user, $lpa, false);
        $labels = array_map(fn (array $entry) => $entry['label'], $result);

        $this->assertSame(['Chris Smith (myself)'], $labels);
        $this->assertArrayNotHasKey('t', $result);
    }

    public function testGetCorrespondentReuseDetailsIncludesDocumentActorsAndSeedCorrespondent(): void
    {
        $user = FixturesData::getUser();
        $lpa  = FixturesData::getPfLpa();
        $lpa->setSeed(789);

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('clone_789')->willReturn([
            'correspondent' => [
                'who'  => 'donor',
                'name' => ['first' => 'Seed', 'last' => 'Correspondent'],
            ],
        ]);

        $this->service->setSession($session);

        $result = $this->service->getCorrespondentReuseDetails($user, $lpa);
        $labels = array_map(fn (array $entry) => $entry['label'], $result);

        $this->assertCount(10, $result);
        $this->assertSame('Chris Smith (myself)', $result[0]['label']);
        $this->assertContains('Ayden Armstrong (donor)', $labels);
        $this->assertContains('Lilly Simpson (primary attorney)', $labels);
        $this->assertContains('Dennis Jackson (replacement attorney)', $labels);
        $this->assertContains('Certy Edwards (certificate provider)', $labels);
        $this->assertContains('Seed Correspondent (was the correspondent)', $labels);
    }

    public function testAddCurrentUserDetailsForReuseSkipsUsersAlreadyUsedOnLpa(): void
    {
        $user              = FixturesData::getUser();
        $user->name        = new Name(['title' => 'Dr', 'first' => 'Lilly', 'last' => 'Simpson']);
        $lpa               = FixturesData::getPfLpa();
        $actorReuseDetails = [];

        $method = new ReflectionMethod($this->service, 'addCurrentUserDetailsForReuse');
        $args   = [$user, $lpa, &$actorReuseDetails, true];
        $method->invokeArgs($this->service, $args);

        $this->assertSame([], $actorReuseDetails);
    }

    public function testAddCurrentUserDetailsForReuseAddsFlattenedUserDetails(): void
    {
        $user              = FixturesData::getUser();
        $lpa               = FixturesData::getPfLpa();
        $actorReuseDetails = [];

        $method = new ReflectionMethod($this->service, 'addCurrentUserDetailsForReuse');
        $args   = [$user, $lpa, &$actorReuseDetails, true];
        $method->invokeArgs($this->service, $args);

        /** @var array<int, array<string, mixed>> $actorReuseDetails */
        $this->assertCount(1, $actorReuseDetails);
        $this->assertSame('Chris Smith (myself)', $actorReuseDetails[0]['label']);
        $this->assertSame('other', $actorReuseDetails[0]['data']['who']);
        $this->assertSame('01', $actorReuseDetails[0]['data']['dob-date']['day']);
        $this->assertSame('PL45 9JA', $actorReuseDetails[0]['data']['address-postcode']);
    }

    public function testGetSeedLpaActorDetailsReturnsEmptyArrayWithoutSeed(): void
    {
        $lpa = FixturesData::getPfLpa(); // seed defaults to null in fixture

        $method = new ReflectionMethod($this->service, 'getSeedLpaActorDetails');

        $this->assertSame([], $method->invoke($this->service, $lpa));
    }

    public function testGetSeedLpaActorDetailsFetchesAndCachesSeedDataWhenSessionIsEmpty(): void
    {
        $lpa         = FixturesData::getPfLpa();
        $lpa->setId(8001)->setSeed(999);
        $seedDetails = ['donor' => ['name' => ['first' => 'Seed', 'last' => 'Donor']]];

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with('clone_999')
            ->willReturn(null);
        $session->expects($this->once())
            ->method('set')
            ->with('clone_999', $seedDetails);

        $this->lpaApplicationService->expects($this->once())
            ->method('getSeedDetails')
            ->with(8001)
            ->willReturn($seedDetails);

        $this->service->setSession($session);

        $method = new ReflectionMethod($this->service, 'getSeedLpaActorDetails');

        $this->assertSame($seedDetails, $method->invoke($this->service, $lpa));
    }

    public function testGetReuseDetailsForActorFlattensHumanDataAndFiltersUnknownKeys(): void
    {
        $actor = [
            'name'    => ['first' => 'Jamie', 'last' => 'Example'],
            'address' => ['address1' => '1 Main Street', 'postcode' => 'AB1 2CD'],
            'dob'     => ['date' => '1988-05-06T00:00:00.000Z'],
            'phone'   => ['number' => '0123456789'],
            'extra'   => 'discard-me',
        ];

        $method = new ReflectionMethod($this->service, 'getReuseDetailsForActor');
        $result = $method->invoke($this->service, $actor, 'other', '(sample)');

        $this->assertSame('Jamie Example (sample)', $result['label']);
        $this->assertSame('Jamie', $result['data']['name-first']);
        $this->assertSame('Example', $result['data']['name-last']);
        $this->assertSame('06', $result['data']['dob-date']['day']);
        $this->assertSame('0123456789', $result['data']['phone-number']);
        $this->assertSame('other', $result['data']['who']);
        $this->assertArrayNotHasKey('extra', $result['data']);
    }

    public function testFlattenDataConvertsNestedArraysAndDobData(): void
    {
        $method = new ReflectionMethod($this->service, 'flattenData');
        $result = $method->invoke($this->service, [
            'name'    => ['first' => 'Jamie', 'last' => 'Example'],
            'dob'     => ['date' => '1999-11-12T00:00:00.000Z'],
            'address' => ['postcode' => 'AB1 2CD'],
            'type'    => 'human',
        ]);

        $this->assertSame('Jamie', $result['name-first']);
        $this->assertSame('Example', $result['name-last']);
        $this->assertSame('12', $result['dob-date']['day']);
        $this->assertSame('11', $result['dob-date']['month']);
        $this->assertSame('1999', $result['dob-date']['year']);
        $this->assertSame('AB1 2CD', $result['address-postcode']);
        $this->assertSame('human', $result['type']);
    }
}
