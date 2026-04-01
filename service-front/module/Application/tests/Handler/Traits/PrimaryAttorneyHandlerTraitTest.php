<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Traits;

use Application\Handler\Traits\PrimaryAttorneyHandlerTrait;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PrimaryAttorneyHandlerTraitTest extends TestCase
{
    private object $traitUser;
    private LpaApplicationService&MockObject $lpaApplicationService;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $lpaAppService = $this->lpaApplicationService;

        $this->traitUser = new class ($lpaAppService) {
            use PrimaryAttorneyHandlerTrait;

            private LpaApplicationService $lpaApplicationService;

            public function __construct(LpaApplicationService $lpaApplicationService)
            {
                $this->lpaApplicationService = $lpaApplicationService;
            }

            public function callGetActorsList(Lpa $lpa, ?int $excludeIdx = null): array
            {
                return $this->getActorsList($lpa, $excludeIdx);
            }

            public function callGetAllActorsList(Lpa $lpa): array
            {
                return $this->getAllActorsList($lpa);
            }

            public function callAllowTrust(Lpa $lpa): bool
            {
                return $this->allowTrust($lpa);
            }

            public function callAttorneyIsCorrespondent(Lpa $lpa, \MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney $attorney): bool
            {
                return $this->attorneyIsCorrespondent($lpa, $attorney);
            }

            public function callUpdateCorrespondentData(Lpa $lpa, \MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney $actor, bool $isDelete = false): void
            {
                $this->updateCorrespondentData($lpa, $actor, $isDelete);
            }

            public function callIsXmlHttpRequest(\Psr\Http\Message\ServerRequestInterface $request): bool
            {
                return $this->isXmlHttpRequest($request);
            }
        };
    }

    private function createNameArray(string $first, string $last): array
    {
        return ['title' => 'Mr', 'first' => $first, 'last' => $last];
    }

    private function createAddressArray(): array
    {
        return ['address1' => '1 Test Street', 'postcode' => 'AB1 2CD'];
    }

    private function createHumanAttorney(string $first, string $last, ?int $id = null): Human
    {
        $data = [
            'name' => $this->createNameArray($first, $last),
            'address' => $this->createAddressArray(),
        ];
        if ($id !== null) {
            $data['id'] = $id;
        }
        return new Human($data);
    }

    private function createTrustCorporation(string $companyName, ?int $id = null): TrustCorporation
    {
        $data = [
            'name' => $companyName,
            'number' => '12345678',
            'address' => $this->createAddressArray(),
        ];
        if ($id !== null) {
            $data['id'] = $id;
        }
        return new TrustCorporation($data);
    }

    private function createBasicLpa(string $type = Document::LPA_TYPE_PF): Lpa
    {
        $lpa = new Lpa([
            'id' => 12345,
            'document' => [
                'type' => $type,
                'donor' => [
                    'name' => $this->createNameArray('Donor', 'Person'),
                    'address' => $this->createAddressArray(),
                ],
                'certificateProvider' => [
                    'name' => $this->createNameArray('Cert', 'Provider'),
                    'address' => $this->createAddressArray(),
                ],
                'primaryAttorneys' => [
                    [
                        'type' => 'human',
                        'id' => 1,
                        'name' => $this->createNameArray('Primary', 'One'),
                        'address' => $this->createAddressArray(),
                    ],
                    [
                        'type' => 'human',
                        'id' => 2,
                        'name' => $this->createNameArray('Primary', 'Two'),
                        'address' => $this->createAddressArray(),
                    ],
                ],
                'replacementAttorneys' => [
                    [
                        'type' => 'human',
                        'id' => 3,
                        'name' => $this->createNameArray('Replacement', 'One'),
                        'address' => $this->createAddressArray(),
                    ],
                ],
                'peopleToNotify' => [
                    [
                        'name' => $this->createNameArray('Notified', 'Person'),
                        'address' => $this->createAddressArray(),
                    ],
                ],
            ],
        ]);

        $lpa->document->correspondent = null;

        return $lpa;
    }

    public function testGetActorsListIncludesDonorCpAttorneysAndPeopleToNotify(): void
    {
        $lpa = $this->createBasicLpa();

        $result = $this->traitUser->callGetActorsList($lpa);

        $this->assertCount(5, $result);
        $this->assertEquals('Donor', $result[0]['firstname']);
        $this->assertEquals('donor', $result[0]['type']);
        $this->assertEquals('Cert', $result[1]['firstname']);
        $this->assertEquals('certificate provider', $result[1]['type']);
        $this->assertEquals('Primary', $result[2]['firstname']);
        $this->assertEquals('attorney', $result[2]['type']);
        $this->assertEquals('Primary', $result[3]['firstname']);
        $this->assertEquals('Notified', $result[4]['firstname']);
        $this->assertEquals('person to notify', $result[4]['type']);
    }

    public function testGetActorsListExcludesReplacementAttorneys(): void
    {
        $lpa = $this->createBasicLpa();

        $result = $this->traitUser->callGetActorsList($lpa);

        $names = array_column($result, 'firstname');
        $this->assertNotContains('Replacement', $names);
    }

    public function testGetActorsListExcludesSpecificAttorneyByIndex(): void
    {
        $lpa = $this->createBasicLpa();

        $result = $this->traitUser->callGetActorsList($lpa, 0);

        $attorneys = array_filter($result, fn($a) => $a['type'] === 'attorney');
        $this->assertCount(1, $attorneys);
        $attorney = array_values($attorneys)[0];
        $this->assertEquals('Two', $attorney['lastname']);
    }

    public function testGetActorsListSkipsTrustCorporations(): void
    {
        $lpa = $this->createBasicLpa();
        $lpa->document->primaryAttorneys[] = $this->createTrustCorporation('Trust Corp', 4);

        $result = $this->traitUser->callGetActorsList($lpa);

        $names = array_column($result, 'firstname');
        $types = array_column($result, 'type');
        $this->assertNotContains('Trust Corp', $names);
    }

    public function testGetAllActorsListIncludesReplacementAttorneys(): void
    {
        $lpa = $this->createBasicLpa();

        $result = $this->traitUser->callGetAllActorsList($lpa);

        $names = array_column($result, 'firstname');
        $this->assertContains('Replacement', $names);
    }

    public function testGetAllActorsListIncludesAllActors(): void
    {
        $lpa = $this->createBasicLpa();

        $result = $this->traitUser->callGetAllActorsList($lpa);

        $this->assertCount(6, $result);
    }

    public function testAllowTrustReturnsFalseForHwLpa(): void
    {
        $lpa = $this->createBasicLpa(Document::LPA_TYPE_HW);

        $this->assertFalse($this->traitUser->callAllowTrust($lpa));
    }

    public function testAllowTrustReturnsTrueForPfLpaWithNoTrust(): void
    {
        $lpa = $this->createBasicLpa(Document::LPA_TYPE_PF);

        $this->assertTrue($this->traitUser->callAllowTrust($lpa));
    }

    public function testAllowTrustReturnsFalseWhenTrustAlreadyExists(): void
    {
        $lpa = $this->createBasicLpa(Document::LPA_TYPE_PF);
        $lpa->document->primaryAttorneys[] = $this->createTrustCorporation('Existing Trust', 5);

        $this->assertFalse($this->traitUser->callAllowTrust($lpa));
    }

    public function testAllowTrustReturnsFalseWhenTrustInReplacementAttorneys(): void
    {
        $lpa = $this->createBasicLpa(Document::LPA_TYPE_PF);
        $lpa->document->replacementAttorneys[] = $this->createTrustCorporation('Replacement Trust', 6);

        $this->assertFalse($this->traitUser->callAllowTrust($lpa));
    }

    // ========== attorneyIsCorrespondent ==========

    public function testAttorneyIsCorrespondentReturnsFalseWhenNoCorrespondent(): void
    {
        $lpa = $this->createBasicLpa();
        $lpa->document->correspondent = null;

        $attorney = $lpa->document->primaryAttorneys[0];

        $this->assertFalse($this->traitUser->callAttorneyIsCorrespondent($lpa, $attorney));
    }

    public function testAttorneyIsCorrespondentReturnsFalseWhenCorrespondentIsDonor(): void
    {
        $lpa = $this->createBasicLpa();

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_DONOR,
            'name' => $this->createNameArray('Donor', 'Person'),
        ]);
        $lpa->document->correspondent = $correspondent;

        $attorney = $lpa->document->primaryAttorneys[0];

        $this->assertFalse($this->traitUser->callAttorneyIsCorrespondent($lpa, $attorney));
    }

    public function testAttorneyIsCorrespondentReturnsTrueWhenMatching(): void
    {
        $lpa = $this->createBasicLpa();
        $attorney = $lpa->document->primaryAttorneys[0];

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_ATTORNEY,
            'name' => $this->createNameArray('Primary', 'One'),
        ]);
        $correspondent->address = $attorney->address;
        $lpa->document->correspondent = $correspondent;

        $this->assertTrue($this->traitUser->callAttorneyIsCorrespondent($lpa, $attorney));
    }

    public function testAttorneyIsCorrespondentReturnsFalseWhenNameDiffers(): void
    {
        $lpa = $this->createBasicLpa();
        $attorney = $lpa->document->primaryAttorneys[0];

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_ATTORNEY,
            'name' => $this->createNameArray('Different', 'Name'),
        ]);
        $correspondent->address = $attorney->address;
        $lpa->document->correspondent = $correspondent;

        $this->assertFalse($this->traitUser->callAttorneyIsCorrespondent($lpa, $attorney));
    }

    public function testUpdateCorrespondentDataDoesNothingWhenNoCorrespondent(): void
    {
        $lpa = $this->createBasicLpa();
        $lpa->document->correspondent = null;

        $this->lpaApplicationService->expects($this->never())->method('deleteCorrespondent');
        $this->lpaApplicationService->expects($this->never())->method('setCorrespondent');

        $this->traitUser->callUpdateCorrespondentData($lpa, $lpa->document->primaryAttorneys[0]);
    }

    public function testUpdateCorrespondentDataDoesNothingWhenCorrespondentIsNotAttorney(): void
    {
        $lpa = $this->createBasicLpa();

        $correspondent = new Correspondence();
        $correspondent->who = Correspondence::WHO_DONOR;
        $lpa->document->correspondent = $correspondent;

        $this->lpaApplicationService->expects($this->never())->method('deleteCorrespondent');
        $this->lpaApplicationService->expects($this->never())->method('setCorrespondent');

        $this->traitUser->callUpdateCorrespondentData($lpa, $lpa->document->primaryAttorneys[0]);
    }

    public function testUpdateCorrespondentDataDeletesWhenIsDeleteTrue(): void
    {
        $lpa = $this->createBasicLpa();
        $attorney = $lpa->document->primaryAttorneys[0];

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_ATTORNEY,
            'name' => $this->createNameArray('Primary', 'One'),
        ]);
        $correspondent->address = $attorney->address;
        $lpa->document->correspondent = $correspondent;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('deleteCorrespondent')
            ->with($lpa)
            ->willReturn(true);

        $this->traitUser->callUpdateCorrespondentData($lpa, $attorney, true);
    }

    public function testUpdateCorrespondentDataDeleteThrowsOnFailure(): void
    {
        $lpa = $this->createBasicLpa();
        $attorney = $lpa->document->primaryAttorneys[0];

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_ATTORNEY,
            'name' => $this->createNameArray('Primary', 'One'),
        ]);
        $correspondent->address = $attorney->address;
        $lpa->document->correspondent = $correspondent;

        $this->lpaApplicationService
            ->method('deleteCorrespondent')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete correspondent for id: 12345');

        $this->traitUser->callUpdateCorrespondentData($lpa, $attorney, true);
    }

    public function testUpdateCorrespondentDataUpdatesWhenNameChanged(): void
    {
        $lpa = $this->createBasicLpa();

        // Attorney with different name than correspondent
        $attorney = $this->createHumanAttorney('Updated', 'Name', 1);

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_ATTORNEY,
            'name' => $this->createNameArray('Primary', 'One'),
            'address' => $this->createAddressArray(),
        ]);
        $lpa->document->correspondent = $correspondent;

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setCorrespondent')
            ->willReturn(true);

        $this->traitUser->callUpdateCorrespondentData($lpa, $attorney);
    }

    public function testUpdateCorrespondentDataSetThrowsOnFailure(): void
    {
        $lpa = $this->createBasicLpa();

        $attorney = $this->createHumanAttorney('Updated', 'Name', 1);

        $correspondent = new Correspondence([
            'who' => Correspondence::WHO_ATTORNEY,
            'name' => $this->createNameArray('Primary', 'One'),
            'address' => $this->createAddressArray(),
        ]);
        $lpa->document->correspondent = $correspondent;

        $this->lpaApplicationService
            ->method('setCorrespondent')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update correspondent for id: 12345');

        $this->traitUser->callUpdateCorrespondentData($lpa, $attorney);
    }


    public function testIsXmlHttpRequestReturnsTrueForXhr(): void
    {
        $request = (new ServerRequest())->withHeader('X-Requested-With', 'XMLHttpRequest');

        $this->assertTrue($this->traitUser->callIsXmlHttpRequest($request));
    }

    public function testIsXmlHttpRequestReturnsFalseForNormalRequest(): void
    {
        $request = new ServerRequest();

        $this->assertFalse($this->traitUser->callIsXmlHttpRequest($request));
    }
}
