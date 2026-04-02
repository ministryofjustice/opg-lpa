<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\CorrespondentHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Common\PhoneNumber;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CorrespondentHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private MockObject $form;
    private CorrespondentHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(\Application\Form\Lpa\CorrespondenceForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->urlHelper
            ->method('generate')
            ->willReturn('/lpa/123/correspondent');

        $this->handler = new CorrespondentHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(
        ?Correspondence $correspondent = null,
        string $whoIsRegistering = Correspondence::WHO_DONOR
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
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
        $correspondence->who = Correspondence::WHO_DONOR;
        $correspondence->name = new LongName(['title' => 'Mr', 'first' => 'John', 'last' => 'Doe']);
        $correspondence->address = new Address(['address1' => '1 Test Road', 'postcode' => 'AB1 2CD']);
        $correspondence->contactByPost = true;
        $correspondence->contactInWelsh = false;

        return $correspondence;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa($this->createCorrespondence());

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/date-check');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/correspondent');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersIndexTemplate(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/correspondent/index.twig', $this->anything())
            ->willReturn('<html></html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetBindsExistingCorrespondentData(): void
    {
        $correspondence = $this->createCorrespondence();
        $correspondence->email = new EmailAddress(['address' => 'test@example.com']);
        $correspondence->phone = new PhoneNumber(['number' => '01onal234']);

        $lpa = $this->createLpa($correspondence);

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->renderer->method('render')->willReturn('<html></html>');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testGetDefaultsToDonorWhenNoCorrespondentSet(): void
    {
        $lpa = $this->createLpa(null, Correspondence::WHO_DONOR);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/correspondent/index.twig',
                $this->callback(function (array $params) {
                    return isset($params['correspondentName'])
                        && str_contains($params['correspondentName'], 'Doe');
                })
            )
            ->willReturn('<html></html>');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testPostValidFormSavesAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'contactInWelsh' => '0',
            'correspondence' => [
                'contactByPost'  => '1',
                'contactByEmail' => '0',
                'contactByPhone' => '0',
                'email-address'  => '',
                'phone-number'   => '',
            ],
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setCorrespondent')
            ->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', ['some' => 'data']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostInvalidFormRendersFormAgain(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('<html></html>');

        $response = $this->handler->handle($this->createRequest('POST', ['some' => 'data']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostApiFailureThrowsException(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn([
            'contactInWelsh' => '0',
            'correspondence' => [
                'contactByPost'  => '1',
                'contactByEmail' => '0',
                'contactByPhone' => '0',
                'email-address'  => '',
                'phone-number'   => '',
            ],
        ]);

        $this->lpaApplicationService
            ->method('setCorrespondent')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set correspondent');

        $this->handler->handle($this->createRequest('POST', ['some' => 'data']));
    }

    public function testAllowEditButtonTrueForOtherCorrespondent(): void
    {
        $correspondence = $this->createCorrespondence();
        $correspondence->who = Correspondence::WHO_OTHER;

        $lpa = $this->createLpa($correspondence);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params) {
                    return $params['allowEditButton'] === true;
                })
            )
            ->willReturn('<html></html>');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testAllowEditButtonFalseForDonorCorrespondent(): void
    {
        $correspondence = $this->createCorrespondence();
        $correspondence->who = Correspondence::WHO_DONOR;
        $correspondence->company = null;

        $lpa = $this->createLpa($correspondence);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params) {
                    return $params['allowEditButton'] === false;
                })
            )
            ->willReturn('<html></html>');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }
}
