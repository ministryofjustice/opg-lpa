<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PeopleToNotifyHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private Metadata&MockObject $metadata;
    private PeopleToNotifyHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->metadata = $this->createMock(Metadata::class);

        $this->handler = new PeopleToNotifyHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->metadata,
        );
    }

    private function createLpa(int $numPeople = 0): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        for ($i = 0; $i < $numPeople; $i++) {
            $np = new NotifiedPerson();
            $np->id = $i + 1;
            $np->name = new Name(['title' => 'Mr', 'first' => 'Person' . $i, 'last' => 'Notify']);
            $np->address = new Address(['address1' => $i . ' Road', 'postcode' => 'AB1 2CD']);
            $lpa->document->peopleToNotify[] = $np;
        }

        return $lpa;
    }

    private function createRequest(string $method, Lpa $lpa, array $postData = []): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/instructions');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/people-to-notify');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersNoPeopleToNotify(): void
    {
        $lpa = $this->createLpa(0);

        $form = $this->createMock(\Laminas\Form\FormInterface::class);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify/add');
        $this->renderer->method('render')->willReturn('<html>index</html>');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetRendersWithMultiplePeople(): void
    {
        $lpa = $this->createLpa(3);

        $form = $this->createMock(\Laminas\Form\FormInterface::class);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('<html>index</html>');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidSetsMetadataAndRedirects(): void
    {
        $lpa = $this->createLpa(1);

        $form = $this->createMock(\Laminas\Form\FormInterface::class);
        $form->method('isValid')->willReturn(true);
        $this->formElementManager->method('get')->willReturn($form);
        $this->metadata->expects($this->once())->method('setPeopleToNotifyConfirmed');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/instructions');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, ['submit' => 'Save and continue']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $lpa = $this->createLpa(1);

        $form = $this->createMock(\Laminas\Form\FormInterface::class);
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('<html>index</html>');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
