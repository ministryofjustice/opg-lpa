<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\SummaryHandler;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SummaryHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SummaryHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);

        $this->handler = new SummaryHandler(
            $this->renderer,
        );
    }

    private function createLpa(?string $repeatCaseNumber = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->repeatCaseNumber = $repeatCaseNumber;

        return $lpa;
    }

    private function createRequest(
        ?Lpa $lpa = null,
        array $queryParams = [],
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();
        $user = new User();

        return (new ServerRequest())
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withAttribute('secondsUntilSessionExpires', 3600)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/summary')
            ->withQueryParams($queryParams);
    }

    public function testHandleRendersTemplateWithDefaultReturnRoute(): void
    {
        $lpa = $this->createLpa();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/summary/index.twig',
                $this->callback(function (array $params): bool {
                    return $params['returnRoute'] === 'lpa/applicant'
                        && array_key_exists('fullFee', $params)
                        && array_key_exists('lowIncomeFee', $params);
                })
            )
            ->willReturn('summary page');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('summary page', (string) $response->getBody());
    }

    public function testHandleUsesReturnRouteFromQueryParam(): void
    {
        $lpa = $this->createLpa();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/summary/index.twig',
                $this->callback(function (array $params): bool {
                    return $params['returnRoute'] === 'lpa/donor';
                })
            )
            ->willReturn('summary page');

        $response = $this->handler->handle(
            $this->createRequest($lpa, ['return-route' => 'lpa/donor'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * @dataProvider feeDataProvider
     */
    public function testHandleCalculatesFeesCorrectly(?string $repeatCaseNumber, bool $isRepeat): void
    {
        $lpa = $this->createLpa($repeatCaseNumber);

        $expectedFullFee = Calculator::getFullFee($isRepeat);
        $expectedLowIncomeFee = Calculator::getLowIncomeFee($isRepeat);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/summary/index.twig',
                $this->callback(
                    function (array $params) use ($expectedFullFee, $expectedLowIncomeFee): bool {
                        return $params['fullFee'] === $expectedFullFee
                            && $params['lowIncomeFee'] === $expectedLowIncomeFee;
                    }
                )
            )
            ->willReturn('summary page');

        $this->handler->handle($this->createRequest($lpa));
    }

    /**
     * @return array<string, array{?string, bool}>
     */
    public static function feeDataProvider(): array
    {
        return [
            'new application' => [null, false],
            'repeat application' => ['12345678', true],
        ];
    }

    public function testHandleIncludesCommonTemplateVariables(): void
    {
        $lpa = $this->createLpa();
        $user = new User();

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withAttribute('secondsUntilSessionExpires', 1800)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/summary')
            ->withQueryParams([]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/summary/index.twig',
                $this->callback(function (array $params) use ($lpa, $user): bool {
                    return $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 1800
                        && $params['lpa'] === $lpa
                        && $params['currentRouteName'] === 'lpa/summary';
                })
            )
            ->willReturn('summary page');

        $this->handler->handle($request);
    }
}
