<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\CorrespondenceSetService;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CorrespondenceSetServiceTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private UrlHelper&MockObject $urlHelper;
    private FormFlowChecker $flowChecker;
    private CorrespondenceSetService $service;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->flowChecker = $this->createMock(FormFlowChecker::class);
        $this->flowChecker->method('nextRoute')->willReturn('lpa/date-check');
        $this->flowChecker->method('getRouteOptions')->willReturn([]);

        $this->urlHelper
            ->method('generate')
            ->willReturn('/lpa/123/correspondent/edit');

        $this->service = new CorrespondenceSetService(
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->name = new \MakeShared\DataModel\Common\Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'User']);

        return $user;
    }

    private function createLpa(
        ?Correspondence $correspondent = null,
        string $whoIsRegistering = Correspondence::WHO_DONOR
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->version = 5;
        $lpa->document = new Document();
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        $donor = new Donor();
        $donor->name = new LongName(['title' => 'Mr', 'first' => 'John', 'last' => 'Doe']);
        $donor->address = new Address(['address1' => '1 Test Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->donor = $donor;

        $lpa->document->correspondent = $correspondent;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        return $lpa;
    }

    private function createCorrespondence(): Correspondence
    {
        $correspondence = new Correspondence();
        $correspondence->who = Correspondence::WHO_OTHER;
        $correspondence->name = new LongName(['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith']);
        $correspondence->address = new Address(['address1' => '2 Test Road', 'postcode' => 'EF3 4GH']);
        $correspondence->contactByPost = true;
        $correspondence->contactInWelsh = false;

        return $correspondence;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        array $queryParams = []
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa($this->createCorrespondence());


        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $this->createUser())
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/correspondent/edit')
            ->withQueryParams($queryParams);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetReturningFromReuseDetailsWithNonEditableDataProcessesDirectly(): void
    {
        $lpa = $this->createLpa();

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setCorrespondent')
            ->willReturn(true);

        $response = $this->service->setCorrespondent($lpa, [
            'who' => 'donor',
            'name' => ['title' => 'Mr', 'first' => 'John', 'last' => 'Doe'],
        ], $this->flowChecker, false, 5);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostApiFailureThrowsException(): void
    {
        $lpa = $this->createLpa();

        $this->lpaApplicationService
            ->method('setCorrespondent')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update correspondent');

        $this->service->setCorrespondent($lpa, [
            'who' => 'other',
            'name' => ['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith'],
        ], $this->flowChecker, false, 5);
    }

    public function testPostPopupReturnsJsonOnSuccess(): void
    {
        $this->lpaApplicationService
            ->method('setCorrespondent')
            ->willReturn(true);

        $lpa = $this->createLpa($this->createCorrespondence());

        $response = $this->service->setCorrespondent($lpa, [
            'who' => 'other',
            'name' => ['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith'],
        ], $this->flowChecker, true, 5);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
