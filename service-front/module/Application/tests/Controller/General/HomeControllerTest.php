<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\HomeController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use DateTimeImmutable;
use Laminas\View\Model\ViewModel;

final class HomeControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction(): void
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $feeEffectiveDate = new DateTimeImmutable(getenv('LPA_FEE_EFFECTIVE_DATE') ?: '2025-11-17T00:00:00');
        $timeNow = new DateTimeImmutable('now');
        $fee = ($timeNow >= $feeEffectiveDate) ? 92 : 82;
        $this->assertEquals($fee, $result->getVariable('lpaFee'));
        $this->assertEquals('1.2.3.4-test', $result->getVariable('dockerTag'));
    }

    public function testRedirectAction(): void
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        $this->redirect->shouldReceive('toUrl')
            ->withArgs(['https://www.gov.uk/power-of-attorney/make-lasting-power'])
            ->andReturn('https://www.gov.uk/power-of-attorney/make-lasting-power')->once();

        $result = $controller->redirectAction();

        $this->assertEquals('https://www.gov.uk/power-of-attorney/make-lasting-power', $result);
    }

    public function testCookieAction(): void
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->enableCookieAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testAccessibilityAction(): void
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->accessibilityAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testTermsAction(): void
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->termsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testContactAction(): void
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->contactAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }
}
