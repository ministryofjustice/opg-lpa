<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\TypeController;
use Application\Form\Lpa\TypeForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Document\Document;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;

final class TypeControllerTest extends AbstractControllerTestCase
{
    private MockInterface|TypeForm $form;
    private array $postData = [
        'type' => Document::LPA_TYPE_HW
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(TypeForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\TypeForm'])->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->flatten()])->once();
        $this->setMatchedRouteName($controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/donor?lpa-id=' . $this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' . $this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' . $this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals('', $result->getVariable('isChangeAllowed'));
    }

    public function testIndexActionGetNoType(): void
    {
        $this->lpa->document = new Document();

        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->flatten()])->once();
        $this->setMatchedRouteName($controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/donor?lpa-id=' . $this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' . $this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' . $this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setPostInvalid($this->form);
        $this->setMatchedRouteName($controller, 'lpa/form-type');
        $this->url->shouldReceive('fromRoute')->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/donor?lpa-id=' . $this->lpa->id)->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['user/dashboard/create-lpa', ['lpa-id' => $this->lpa->id]])
            ->andReturn('user/dashboard/create-lpa?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('user/dashboard/create-lpa?lpa-id=' . $this->lpa->id, $result->getVariable('cloneUrl'));
        $this->assertEquals('lpa/donor?lpa-id=' . $this->lpa->id, $result->getVariable('nextUrl'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
    }

    public function testIndexActionPostFailed(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->withArgs([$this->lpa, $this->postData['type']])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA type for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess(): void
    {
        /** @var TypeController $controller */
        $controller = $this->getController(TypeController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')
            ->withArgs([$this->lpa, $this->postData['type']])->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/form-type');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/donor', $result->getHeaders()->get('Location')->getUri());
    }
}
