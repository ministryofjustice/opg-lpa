<?php

namespace ZfcTwigTest\View;

use Twig_Environment;
use Twig_Loader_Array;
use Twig_Loader_Chain;
use Zend\View\Model\ModelInterface;
use Zend\View\View;
use ZfcTwig\View\TwigRenderer;
use PHPUnit\Framework\TestCase;
use ZfcTwig\View\TwigResolver;

class TwigRendererTest extends TestCase
{
    /** @var  TwigRenderer */
    protected $renderer;

    public function setUp()
    {
        parent::setUp();

        $chain = new Twig_Loader_Chain();
        $chain->addLoader(new Twig_Loader_Array(['key1' => 'var1 {{ foobar }}']));
        $environment = new Twig_Environment($chain);
        $this->renderer = new TwigRenderer(new View, $chain, $environment, new TwigResolver($environment));
    }

    public function testRenderWithName()
    {
        $content = $this->renderer->render('key1');

        $this->assertInternalType('string', $content);
        $this->assertSame('var1 ', $content);
    }

    public function testRenderWithNameAndValues()
    {
        $content = $this->renderer->render('key1', ['foobar' => 'baz']);

        $this->assertInternalType('string', $content);
        $this->assertSame('var1 baz', $content);
    }

    public function testRenderWithModelAndValues()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelInterface $model */
        $model = $this->getMockBuilder(ModelInterface::class)->getMock();
        $model->expects($this->exactly(1))
            ->method('getTemplate')
            ->willReturn('key1');
        $model->expects($this->exactly(1))
            ->method('getVariables')
            ->willReturn(['foobar' => 'baz']);

        $content = $this->renderer->render($model);

        $this->assertInternalType('string', $content);
        $this->assertSame('var1 baz', $content);
    }

}
