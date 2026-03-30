<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\MoreInfoRequiredHandler;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MoreInfoRequiredHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MoreInfoRequiredHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);

        $this->handler = new MoreInfoRequiredHandler(
            $this->renderer,
        );
    }

    private function createLpa(int $id = 91333263035): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = $id;

        return $lpa;
    }

    private function createRequest(?Lpa $lpa = null): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();

        $user = new User();

        return (new ServerRequest())
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withAttribute('secondsUntilSessionExpires', 3600)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/more-info-required');
    }

    public function testHandleRendersTemplateWithLpaId(): void
    {
        $lpa = $this->createLpa(12345678901);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/more-info-required/index.twig',
                $this->callback(function (array $params) use ($lpa): bool {
                    return $params['lpaId'] === $lpa->id
                        && array_key_exists('signedInUser', $params)
                        && array_key_exists('secondsUntilSessionExpires', $params)
                        && array_key_exists('lpa', $params)
                        && array_key_exists('currentRouteName', $params);
                })
            )
            ->willReturn('more info required page');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('more info required page', (string) $response->getBody());
    }

    public function testHandleIncludesCommonTemplateVariables(): void
    {
        $lpa = $this->createLpa();
        $user = new User();

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withAttribute('secondsUntilSessionExpires', 1800)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/more-info-required');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/more-info-required/index.twig',
                $this->callback(function (array $params) use ($lpa, $user): bool {
                    return $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 1800
                        && $params['lpa'] === $lpa
                        && $params['currentRouteName'] === 'lpa/more-info-required'
                        && $params['lpaId'] === $lpa->id;
                })
            )
            ->willReturn('rendered');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
