<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Authentication\AuthenticationService;
use App\Service\ApiClient\Client;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Lpa\Application;
use ArrayObject;
use DateTime;
use GuzzleHttp\Psr7\Utils;
use Http\Client\Exception;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use MakeSharedTest\DataModel\FixturesData;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class ApplicationTest extends MockeryTestCase
{
    private MockInterface|AuthenticationService $authenticationService;
    private MockInterface|Client $apiClient;
    private Application $service;

    private function modifiedLPA(int $id = 5531003157, $completedAt = null, $processingStatus = null, $rejectedDate = null)
    {
        $decodeJsonAsArray = true;
        $arr = json_decode(FixturesData::getHwLpaJson(), $decodeJsonAsArray);

        $arr['id'] = $id;

        if ($completedAt != null) {
            $arr['completedAt'] = $completedAt;
        }

        if ($processingStatus) {
            $arr['metadata'][Lpa::SIRIUS_PROCESSING_STATUS] = $processingStatus;
        }

        if ($rejectedDate != null) {
            $arr['metadata'][Lpa::APPLICATION_REJECTED_DATE] = $rejectedDate;
        }

        return $arr;
    }

    public function setUp(): void
    {
        $logger = Mockery::spy(LoggerInterface::class);
        $identity = Mockery::mock(\App\Model\Service\Authentication\Identity\User::class);
        $identity->shouldReceive('id')->andReturn('4321');

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($identity);

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Application($this->authenticationService, [
            'processing-status' => ['track-from-date' => '2019-01-01'],
        ]);
        $this->service->setApiClient($this->apiClient);
        $this->service->setLogger($logger);
    }

    public function testGetApplication(): void
    {
        $this->apiClient->shouldReceive('httpGet')->andReturn([])->once();

        $result = $this->service->getApplication(1234);

        $expectedResult = new Lpa();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationWithNewToken(): void
    {
        $this->apiClient->shouldReceive('httpGet')->andReturn([])->once();
        $this->apiClient->shouldReceive('updateToken')->withArgs(['new token'])->once();

        $result = $this->service->getApplication(1234, 'new token');

        $expectedResult = new Lpa();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetApplicationFailure(): void
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody')->andReturn(Utils::streamFor('{}'))->once();

        $this->apiClient->shouldReceive('httpGet')->andThrow(new ApiException($mockResponse));

        $result = $this->service->getApplication(1234);

        $this->assertFalse($result);
    }

    public function testGetStatuses(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andReturn(['4321' => ['found' => true, 'status' => 'Concluded']]);

        $result = $this->service->getStatuses('4321');

        $this->assertEquals(['4321' => ['found' => true, 'status' => 'Concluded']], $result);
    }

    public function testGetStatusesNull(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andReturn(null);

        $result = $this->service->getStatuses('4321');

        $this->assertEquals(['4321' => ['found' => false]], $result);
    }

    public function testGetStatusesException(): void
    {
        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody')->andReturn(Utils::streamFor('{}'))->once();
        $this->apiClient->shouldReceive('httpGet')
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $result = $this->service->getStatuses('4321');

        $this->assertEquals(['4321' => ['found' => false]], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesNoApplications(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => []]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [], 'trackingEnabled' => true], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLpaSummariesMultipleApplications(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications', ['search' => null]])
            ->once()
            ->andReturn(['applications' => [FixturesData::getHwLpaJson(), $this->modifiedLPA()]]);

        $result = $this->service->getLpaSummaries();

        $this->assertEquals(['applications' => [
            new ArrayObject([
                'id' => 5531003156,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Completed',
                'rejectedDate' => null,
                'refreshId' => null,
                'isReusable' => true,
            ]),
            new ArrayObject([
                'id' => 5531003157,
                'version' => 2,
                'donor' => 'Hon Ayden Armstrong',
                'type' => 'health-and-welfare',
                'updatedAt' => new DateTime('2017-03-24T16:21:52.804000+0000'),
                'progress' => 'Completed',
                'rejectedDate' => null,
                'refreshId' => null,
                'isReusable' => true,
            ]),
        ], 'trackingEnabled' => true], $result);
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
                    ['type' => 'human'],
                ],
            ],
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
                'peopleToNotify' => [[], [], [], [], []],
            ],
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
                                  mollit anim id est laborum.',
            ],
        ]);

        $this->assertEquals(['LONG_INSTRUCTIONS_OR_PREFERENCES'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testCantSignGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'donor' => ['canSign' => false],
            ],
        ]);

        $this->assertEquals(['CANT_SIGN'], $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testTrustAttorneyGetContinuationNoteKeys(): void
    {
        $mockLpa = new Lpa([
            'document' => [
                'primaryAttorneys' => [
                    ['type' => 'corporation', 'number' => '123'],
                ],
            ],
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
                'replacementAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                ],
                'primaryAttorneyDecisions' => [
                    'howDetails' => 'Decisions must be made at midnight',
                ],
            ],
        ]);

        $expectedResult = [
            'LONG_INSTRUCTIONS_OR_PREFERENCES',
            'REPLACEMENT_ATTORNEY_OVERFLOW',
            'ANY_PEOPLE_OVERFLOW',
            'HAS_ATTORNEY_DECISIONS',
        ];
        $this->assertEqualsCanonicalizing($expectedResult, $this->service->getContinuationNoteKeys($mockLpa));
    }

    public function testSetWhoIsRegisteringWithKeyPresent(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/who-is-registering', ['whoIsRegistering' => [1, 2]]])
            ->once()
            ->andReturn(['whoIsRegistering' => [1, 2]]);

        $result = $this->service->setWhoIsRegistering($lpa, [1, 2]);

        $this->assertTrue($result);
        $this->assertEquals([1, 2], $lpa->document->whoIsRegistering);
    }

    public function testSetWhoIsRegisteringKeyAbsentInResponse(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/who-is-registering', ['whoIsRegistering' => null]])
            ->once()
            ->andReturn([]); // API omits the key when value is null

        $result = $this->service->setWhoIsRegistering($lpa, null);

        $this->assertTrue($result);
        $this->assertNull($lpa->document->whoIsRegistering);
    }

    public function testGetAuthenticationService(): void
    {
        $this->assertSame($this->authenticationService, $this->service->getAuthenticationService());
    }

    public function testGetConfig(): void
    {
        $this->assertSame(['processing-status' => ['track-from-date' => '2019-01-01']], $this->service->getConfig());
    }

    public function testCreateApplication(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/user/4321/applications'])
            ->once()
            ->andReturn(['id' => 1234, 'document' => []]);

        $result = $this->service->createApplication();

        $this->assertInstanceOf(Lpa::class, $result);
        $this->assertSame(1234, $result->id);
    }

    public function testCreateApplicationFailure(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(500);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor('{}'));

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/user/4321/applications'])
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $this->assertFalse($this->service->createApplication());
    }

    public function testUpdateApplication(): void
    {
        $data = ['status' => 'updated'];

        $this->apiClient->shouldReceive('httpPatch')
            ->withArgs(['/v2/user/4321/applications/1234', $data])
            ->once()
            ->andReturn(['id' => 1234, 'document' => ['type' => 'health-and-welfare']]);

        $result = $this->service->updateApplication(1234, $data);

        $this->assertInstanceOf(Lpa::class, $result);
        $this->assertSame(1234, $result->id);
        $this->assertSame('health-and-welfare', $result->document->type);
    }

    public function testUpdateApplicationFailure(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(422);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor('{"validation":{"status":"invalid"}}'));

        $this->apiClient->shouldReceive('httpPatch')
            ->withArgs(['/v2/user/4321/applications/1234', ['status' => 'updated']])
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $this->assertFalse($this->service->updateApplication(1234, ['status' => 'updated']));
    }

    public function testDeleteApplication(): void
    {
        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/4321/applications/1234'])
            ->once();

        $this->assertTrue($this->service->deleteApplication(1234));
    }

    public function testDeleteApplicationFailure(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(500);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor('{}'));

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/4321/applications/1234'])
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $this->assertFalse($this->service->deleteApplication(1234));
    }

    public function testGetSeedDetails(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications/1234/seed'])
            ->once()
            ->andReturn(['seed' => 4567]);

        $this->assertSame(['seed' => 4567], $this->service->getSeedDetails(1234));
    }

    public function testGetPdf(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/4321/applications/1234/pdfs/lpa12'])
            ->once()
            ->andReturn(['filename' => 'lpa12.pdf']);

        $this->assertSame(['filename' => 'lpa12.pdf'], $this->service->getPdf(1234, 'lpa12'));
    }

    public function testGetPdfContents(): void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs([
                '/v2/user/4321/applications/1234/pdfs/lpa12.pdf',
                [],
                false,
                false,
                ['Accept' => 'application/pdf'],
            ])
            ->once()
            ->andReturn('%PDF');

        $this->assertSame('%PDF', $this->service->getPdfContents(1234, 'lpa12'));
    }

    public function testAddPrimaryAttorney(): void
    {
        $lpa = FixturesData::getPfLpa();
        $primaryAttorney = new Human([
            'id' => 99,
            'type' => 'human',
            'name' => ['title' => 'Ms', 'first' => 'Ada', 'last' => 'Lovelace'],
            'address' => ['address1' => '1 Binary Way', 'postcode' => 'AB1 2CD'],
            'dob' => ['date' => '1980-01-01'],
        ]);

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/primary-attorneys', $primaryAttorney->toArray()])
            ->once()
            ->andReturn($primaryAttorney->toArray());

        $result = $this->service->addPrimaryAttorney($lpa, $primaryAttorney);

        $this->assertTrue($result);
        $this->assertInstanceOf(Human::class, $lpa->document->primaryAttorneys[array_key_last($lpa->document->primaryAttorneys)]);
        $this->assertSame(99, $lpa->document->primaryAttorneys[array_key_last($lpa->document->primaryAttorneys)]->id);
    }

    public function testAddReplacementAttorney(): void
    {
        $lpa = FixturesData::getPfLpa();
        $replacementAttorney = new TrustCorporation([
            'id' => 77,
            'name' => ['company' => 'Trust Co'],
            'address' => ['address1' => '2 Trust Street', 'postcode' => 'ZX9 9ZX'],
            'number' => '12345',
            'type' => 'trust',
        ]);

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/replacement-attorneys', $replacementAttorney->toArray()])
            ->once()
            ->andReturn($replacementAttorney->toArray());

        $result = $this->service->addReplacementAttorney($lpa, $replacementAttorney);

        $this->assertTrue($result);
        $this->assertInstanceOf(
            TrustCorporation::class,
            $lpa->document->replacementAttorneys[array_key_last($lpa->document->replacementAttorneys)]
        );
        $this->assertSame('12345', $lpa->document->replacementAttorneys[array_key_last($lpa->document->replacementAttorneys)]->number);
    }

    /** @psalm-suppress UndefinedInterfaceMethod */
    public function testAddNotifiedPerson(): void
    {
        $lpa = FixturesData::getPfLpa();
        $notifiedPerson = new NotifiedPerson([
            'id' => 88,
            'name' => ['title' => 'Mr', 'first' => 'John', 'last' => 'Smith'],
            'address' => ['address1' => '3 Notify Road', 'postcode' => 'AA1 1AA'],
        ]);

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/notified-people', $notifiedPerson->toArray()])
            ->once()
            ->andReturn($notifiedPerson->toArray());

        $result = $this->service->addNotifiedPerson($lpa, $notifiedPerson);

        $this->assertTrue($result);
        /** @var array<int, NotifiedPerson> $peopleToNotify */
        $peopleToNotify = iterator_to_array($lpa->document->peopleToNotify);
        $this->assertCount(1, $peopleToNotify);
        $this->assertInstanceOf(NotifiedPerson::class, $peopleToNotify[0]);
    }

    public function testSetPrimaryAttorney(): void
    {
        $lpa = FixturesData::getPfLpa();
        $primaryAttorney = new Human([
            'id' => 1,
            'type' => 'human',
            'name' => ['title' => 'Dr', 'first' => 'Updated', 'last' => 'Attorney'],
            'address' => ['address1' => '4 Update Lane', 'postcode' => 'BB1 2BB'],
            'dob' => ['date' => '1970-01-01'],
        ]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/primary-attorneys/1', $primaryAttorney->toArray()])
            ->once()
            ->andReturn($primaryAttorney->toArray());

        $result = $this->service->setPrimaryAttorney($lpa, $primaryAttorney, 1);

        $this->assertTrue($result);
        $this->assertSame('Updated', $lpa->document->primaryAttorneys[0]->name->first);
    }

    public function testSetReplacementAttorney(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [
            new TrustCorporation([
                'id' => 7,
                'name' => ['company' => 'Old Trust'],
                'address' => ['address1' => '5 Old Street', 'postcode' => 'CC1 3CC'],
                'number' => '11111',
                'type' => 'trust',
            ]),
        ];
        $replacementAttorney = new TrustCorporation([
            'id' => 7,
            'name' => ['company' => 'New Trust'],
            'address' => ['address1' => '6 New Street', 'postcode' => 'DD1 4DD'],
            'number' => '22222',
            'type' => 'trust',
        ]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/replacement-attorneys/7', $replacementAttorney->toArray()])
            ->once()
            ->andReturn($replacementAttorney->toArray());

        $result = $this->service->setReplacementAttorney($lpa, $replacementAttorney, 7);

        $this->assertTrue($result);
        $this->assertInstanceOf(TrustCorporation::class, $lpa->document->replacementAttorneys[0]);
        $this->assertSame('22222', $lpa->document->replacementAttorneys[0]->number);
    }

    /** @psalm-suppress InvalidArgument */
    public function testDeletePrimaryAttorney(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/primary-attorneys/1'])
            ->once();

        $result = $this->service->deletePrimaryAttorney($lpa, 1);

        $this->assertTrue($result);
        $this->assertCount(2, $lpa->document->primaryAttorneys);
        $this->assertSame([2, 3], array_values(array_map(fn($attorney) => $attorney->id, iterator_to_array($lpa->document->primaryAttorneys))));
    }

    public function testDeleteReplacementAttorney(): void
    {
        $lpa = FixturesData::getPfLpa();

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/4321/applications/' . $lpa->id . '/replacement-attorneys/1'])
            ->once();

        $result = $this->service->deleteReplacementAttorney($lpa, 1);

        $this->assertTrue($result);
        $this->assertCount(2, $lpa->document->replacementAttorneys);
        $this->assertSame([2, 3], array_values(array_map(fn($attorney) => $attorney->id, iterator_to_array($lpa->document->replacementAttorneys))));
    }

    public function testSetWhoAreYou(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);
        $whoAreYou = new WhoAreYou(['who' => 'donor']);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/who-are-you', $whoAreYou->toArray()])
            ->once()
            ->andReturn($whoAreYou->toArray());

        $result = $this->service->setWhoAreYou($lpa, $whoAreYou);

        $this->assertTrue($result);
        $this->assertTrue($lpa->whoAreYouAnswered);
    }

    public function testSetType(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/type', ['type' => 'health-and-welfare']])
            ->once()
            ->andReturn(['type' => 'health-and-welfare']);

        $result = $this->service->setType($lpa, 'health-and-welfare');

        $this->assertTrue($result);
        $this->assertSame('health-and-welfare', $lpa->document->type);
    }

    public function testSetDonor(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);
        $donor = new Donor(FixturesData::getPfLpa()->document->donor->toArray());

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/donor', $donor->toArray()])
            ->once()
            ->andReturn($donor->toArray());

        $result = $this->service->setDonor($lpa, $donor);

        $this->assertTrue($result);
        $this->assertInstanceOf(Donor::class, $lpa->document->donor);
        $this->assertSame('Ayden', $lpa->document->donor->name->first);
    }

    public function testSetCertificateProvider(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);
        $certificateProvider = new CertificateProvider(FixturesData::getPfLpa()->document->certificateProvider->toArray());

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/certificate-provider', $certificateProvider->toArray()])
            ->once()
            ->andReturn($certificateProvider->toArray());

        $result = $this->service->setCertificateProvider($lpa, $certificateProvider);

        $this->assertTrue($result);
        $this->assertInstanceOf(CertificateProvider::class, $lpa->document->certificateProvider);
        $this->assertSame('Certy', $lpa->document->certificateProvider->name->first);
    }

    public function testSetPreferences(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/preference', ['preference' => 'Preference text']])
            ->once()
            ->andReturn(['preference' => 'Preference text']);

        $result = $this->service->setPreferences($lpa, 'Preference text');

        $this->assertTrue($result);
        $this->assertSame('Preference text', $lpa->document->preference);
    }

    public function testSetInstructions(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/instruction', ['instruction' => 'Instruction text']])
            ->once()
            ->andReturn(['instruction' => 'Instruction text']);

        $result = $this->service->setInstructions($lpa, 'Instruction text');

        $this->assertTrue($result);
        $this->assertSame('Instruction text', $lpa->document->instruction);
    }

    public function testSetWhoIsRegisteringFailure(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(500);
        $mockResponse->method('getBody')->willReturn(Utils::streamFor('{}'));

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/who-is-registering', ['whoIsRegistering' => ['donor']]])
            ->once()
            ->andThrow(new ApiException($mockResponse));

        $this->assertFalse($this->service->setWhoIsRegistering($lpa, ['donor']));
        $this->assertNull($lpa->document->whoIsRegistering);
    }

    public function testSetCorrespondent(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);
        $correspondent = new Correspondence(FixturesData::getPfLpa()->document->correspondent->toArray());

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/correspondent', $correspondent->toArray()])
            ->once()
            ->andReturn($correspondent->toArray());

        $result = $this->service->setCorrespondent($lpa, $correspondent);

        $this->assertTrue($result);
        $this->assertInstanceOf(Correspondence::class, $lpa->document->correspondent);
        $this->assertSame('donor', $lpa->document->correspondent->who);
    }

    public function testSetRepeatCaseNumber(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/repeat-case-number', ['repeatCaseNumber' => 'A1234567B']])  // pragma: allowlist secret
            ->once()
            ->andReturn(['repeatCaseNumber' => 'A1234567B']);  // pragma: allowlist secret

        $result = $this->service->setRepeatCaseNumber($lpa, 'A1234567B');  // pragma: allowlist secret

        $this->assertTrue($result);
        $this->assertSame('A1234567B', $lpa->repeatCaseNumber);  // pragma: allowlist secret
    }

    public function testSetPayment(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => new Document()]);
        $payment = new Payment(FixturesData::getPfLpa()->payment->toArray());

        $this->apiClient->shouldReceive('httpPut')
            ->withArgs(['/v2/user/4321/applications/123/payment', $payment->toArray()])
            ->once()
            ->andReturn($payment->toArray());

        $result = $this->service->setPayment($lpa, $payment);

        $this->assertTrue($result);
        $this->assertInstanceOf(Payment::class, $lpa->payment);
        $this->assertSame('cheque', $lpa->payment->method);
    }
}
