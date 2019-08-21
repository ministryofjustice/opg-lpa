<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomeHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Helper\UrlHelper;

class HomeHandlerTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    protected $container;

    /** @var RouterInterface|ObjectProphecy */
    protected $router;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->router    = $this->prophesize(RouterInterface::class);
    }

//    public function testReturnsJsonResponseWhenNoTemplateRendererProvided()
//    {
//        $homePage = new HomeHandler(
//            get_class($this->container->reveal()),
//            $this->router->reveal(),
//            null
//        );
//        $response = $homePage->handle(
//            $this->prophesize(ServerRequestInterface::class)->reveal()
//        );
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//    }

    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
//        $renderer = $this->prophesize(TemplateRendererInterface::class);
//        $renderer
//            ->render('app::home-page', Argument::type('array'))
//            ->willReturn('');
//
//        $homePage = new HomeHandler(
//            get_class($this->container->reveal()),
//            $this->router->reveal(),
//            $renderer->reveal()
//        );
//
//        $response = $homePage->handle(
//            $this->prophesize(ServerRequestInterface::class)->reveal()
//        );
//
//        $this->assertInstanceOf(HtmlResponse::class, $response);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::home-page')
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        //  Set up the handler
        $handler = new HomeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
