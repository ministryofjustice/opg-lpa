<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\SharedSpace\SharedSpaceMemberForm;
use App\Handler\ManageSharedSpaceMemberHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\FormElementManager;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManageSharedSpaceMemberHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private SharedSpaceMemberForm&MockObject $form;
    private ManageSharedSpaceMemberHandler $handler;

    private const MEMBER = [
        'id'      => 'member-1',
        'name'    => ['first' => 'Member', 'last' => 'One'],
        'email'   => ['address' => 'member1@example.com'],
        'isAdmin' => true,
        'isActive' => true,
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->form = $this->createMock(SharedSpaceMemberForm::class);

        $this->formElementManager
            ->method('get')
            ->with(SharedSpaceMemberForm::class)
            ->willReturn($this->form);

        $this->handler = new ManageSharedSpaceMemberHandler(
            $this->renderer,
            $this->formElementManager,
            $this->sharedSpaceService,
        );
    }

    private function createRequest(string $memberId = 'member-1'): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['member-id' => $memberId]);

        return (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');
    }

    public function testGetRequestDisplaysFormForExistingMember(): void
    {
        $this->sharedSpaceService->method('getMember')->with('member-1')->willReturn(self::MEMBER);

        $permissionsElement = new Checkbox('permissions');
        $statusElement = new Radio('status')->setValueOptions([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]);

        $this->form
            ->method('get')
            ->willReturnMap([
                ['permissions', $permissionsElement],
                ['status', $statusElement],
            ]);

        $this->renderer->method('render')->willReturn('<html>member</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertTrue($permissionsElement->isChecked());
        $this->assertTrue($statusElement->getValue() === 'active');
    }

    public function testGetRequestRedirectsWhenMemberNotFound(): void
    {
        $this->sharedSpaceService->method('getMember')->with('unknown-member')->willReturn(null);

        $response = $this->handler->handle($this->createRequest('unknown-member'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space/manage', $response->getHeaderLine('Location'));
    }

    public function testGetRequestRedirectsWhenSignedInUserIsNotAdmin(): void
    {
        // The API only returns member details to admins of the shared
        // space, so a non-admin's request results in a null response here.
        $this->sharedSpaceService->method('getMember')->with('member-1')->willReturn(null);

        $this->sharedSpaceService->expects($this->never())->method('updateMember');

        $response = $this->handler->handle($this->createRequest('member-1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space/manage', $response->getHeaderLine('Location'));
    }

    public function testPostValidDataUpdatesMemberAndRedirects(): void
    {
        $this->sharedSpaceService->method('getMember')->with('member-1')->willReturn(self::MEMBER);

        $permissionsElement = new Checkbox('permissions');
        $permissionsElement->setChecked(false);

        $statusElement = new Radio('status')->setValueOptions([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]);
        $statusElement->setValue('active');

        $this->form->method('isValid')->willReturn(true);
        $this->form
            ->method('get')
            ->willReturnMap([
                ['permissions', $permissionsElement],
                ['status', $statusElement],
            ]);

        $this->sharedSpaceService
            ->expects($this->once())
            ->method('updateMember')
            ->with('member-1', false, true)
            ->willReturn(true);

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody(['permissions' => '0']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space/manage', $response->getHeaderLine('Location'));
    }

    public function testPostWithoutPermissionsKeyTreatsMemberAsNotAdmin(): void
    {
        $this->sharedSpaceService->method('getMember')->with('member-1')->willReturn(self::MEMBER);

        $permissionsElement = new Checkbox('permissions');

        $statusElement = new Radio('status')->setValueOptions([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]);
        $statusElement->setValue('inactive');

        $this->form->method('isValid')->willReturn(true);
        $this->form
            ->method('get')
            ->willReturnMap([
                ['permissions', $permissionsElement],
                ['status', $statusElement],
            ]);

        $this->sharedSpaceService
            ->expects($this->once())
            ->method('updateMember')
            ->with('member-1', false, false)
            ->willReturn(true);

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody([]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space/manage', $response->getHeaderLine('Location'));
    }

    public function testPostWhenUpdateFailsShowsError(): void
    {
        $this->sharedSpaceService->method('getMember')->with('member-1')->willReturn(self::MEMBER);

        $permissionsElement = new Checkbox('permissions');
        $permissionsElement->setChecked(false);

        $statusElement = new Radio('status')->setValueOptions([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]);
        $statusElement->setValue('inactive');

        $this->form->method('isValid')->willReturn(true);
        $this->form
            ->method('get')
            ->willReturnMap([
                ['permissions', $permissionsElement],
                ['status', $statusElement],
            ]);

        $this->sharedSpaceService->method('updateMember')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/manage-member.twig',
                $this->callback(function ($params) {
                    return $params['error'] === 'Failed to update member. Please try again.';
                })
            )
            ->willReturn('<html>error</html>');

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody(['permissions' => '0']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidDataRedisplaysFormWithoutUpdating(): void
    {
        $this->sharedSpaceService->method('getMember')->with('member-1')->willReturn(self::MEMBER);

        $this->form->method('isValid')->willReturn(false);

        $this->sharedSpaceService->expects($this->never())->method('updateMember');

        $this->renderer->method('render')->willReturn('<html>invalid</html>');

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody([]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
