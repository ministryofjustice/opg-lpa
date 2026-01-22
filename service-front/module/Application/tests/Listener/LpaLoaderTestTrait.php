<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\LpaLoaderListener;
use Application\Listener\LpaLoaderTrait;
use Application\Model\FormFlowChecker;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\View\Model\JsonModel;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LpaLoaderTestTrait extends TestCase
{
    private function createLpa(int $id): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = $id;
        return $lpa;
    }

    public function testGetLpaReturnsLpaFromEvent(): void
    {
        $lpa = $this->createLpa(123);

        $event = new MvcEvent();
        $event->setParam(LpaLoaderListener::ATTR_LPA, $lpa);

        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $result = $controller->testGetLpa();

        $this->assertSame($lpa, $result);
    }

    public function testGetFlowCheckerReturnsFlowCheckerFromEvent(): void
    {
        $lpa = $this->createLpa(123);
        $flowChecker = new FormFlowChecker($lpa);

        $event = new MvcEvent();
        $event->setParam(LpaLoaderListener::ATTR_FLOW_CHECKER, $flowChecker);

        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $result = $controller->testGetFlowChecker();

        $this->assertSame($flowChecker, $result);
    }

    public function testMoveToNextRouteThrowsExceptionWhenRouteMatchIsNull(): void
    {
        $event = new MvcEvent();

        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(false);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'RouteMatch must be an instance of Laminas\Router\Http\RouteMatch for moveToNextRoute()'
        );

        $controller->testMoveToNextRoute();
    }

    public function testMoveToNextRouteReturnsJsonModelForPopupRequest(): void
    {
        $event = new MvcEvent();

        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(true);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $result = $controller->testMoveToNextRoute();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['success' => true], $result->getVariables());
    }

    public function testMoveToNextRouteRedirectsToNextRoute(): void
    {
        $lpa = $this->createLpa(123);

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')
            ->with('lpa/donor')
            ->willReturn('lpa/when-lpa-starts');
        $flowChecker->method('getRouteOptions')
            ->with('lpa/when-lpa-starts')
            ->willReturn(['query' => ['foo' => 'bar']]);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->method('getMatchedRouteName')->willReturn('lpa/donor');

        $event = new MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setParam(LpaLoaderListener::ATTR_LPA, $lpa);
        $event->setParam(LpaLoaderListener::ATTR_FLOW_CHECKER, $flowChecker);

        $expectedResponse = $this->createMock(Response::class);

        $redirectPlugin = $this->createMock(\Laminas\Mvc\Controller\Plugin\Redirect::class);
        $redirectPlugin->expects($this->once())
            ->method('toRoute')
            ->with(
                'lpa/when-lpa-starts',
                ['lpa-id' => 123],
                ['query' => ['foo' => 'bar']]
            )
            ->willReturn($expectedResponse);

        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(false);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $result = $controller->testMoveToNextRoute();

        $this->assertSame($expectedResponse, $result);
    }

    public function testIsPopupReturnsTrueForXmlHttpRequest(): void
    {
        $event = new MvcEvent();

        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(true);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $this->assertTrue($controller->testIsPopup());
    }

    public function testIsPopupReturnsFalseForNormalRequest(): void
    {
        $event = new MvcEvent();

        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(false);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $this->assertFalse($controller->testIsPopup());
    }

    public function testFlattenDataFlattensNestedArrays(): void
    {
        $event = new MvcEvent();
        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $modelData = [
            'name' => [
                'title' => 'Mr',
                'first' => 'John',
                'last' => 'Smith',
            ],
            'email' => 'john@example.com',
        ];

        $result = $controller->testFlattenData($modelData);

        $this->assertEquals('Mr', $result['name-title']);
        $this->assertEquals('John', $result['name-first']);
        $this->assertEquals('Smith', $result['name-last']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function testFlattenDataHandlesDobSpecially(): void
    {
        $event = new MvcEvent();
        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $modelData = [
            'dob' => [
                'date' => '1990-05-15',
            ],
        ];

        $result = $controller->testFlattenData($modelData);

        $this->assertArrayHasKey('dob-date', $result);
        $this->assertEquals('15', $result['dob-date']['day']);
        $this->assertEquals('05', $result['dob-date']['month']);
        $this->assertEquals('1990', $result['dob-date']['year']);
    }

    public function testFlattenDataHandlesAddress(): void
    {
        $event = new MvcEvent();
        $redirectPlugin = $this->createMock(\stdClass::class);
        $request = $this->createMock(Request::class);

        $controller = new TestControllerUsingTrait($event, $redirectPlugin, $request);

        $modelData = [
            'address' => [
                'address1' => '123 Main St',
                'address2' => 'Apt 4',
                'postcode' => 'SW1A 1AA',
            ],
        ];

        $result = $controller->testFlattenData($modelData);

        $this->assertEquals('123 Main St', $result['address-address1']);
        $this->assertEquals('Apt 4', $result['address-address2']);
        $this->assertEquals('SW1A 1AA', $result['address-postcode']);
    }
}
