<?php

namespace ZfcTwigTest\View;

use PHPUnit\Framework\TestCase;
use Twig_Environment;
use Twig_Loader_Array;
use Twig_Loader_Chain;
use Twig_Template;
use ZfcTwig\View\TwigResolver;

class TwigResolverTest extends TestCase
{
    /** @var  TwigResolver */
    protected $resolver;

    protected function setUp()
    {
        parent::setUp();

        $chain = new Twig_Loader_Chain();
        $chain->addLoader(new Twig_Loader_Array(['key1' => 'var1']));
        $environment = new Twig_Environment($chain);
        $this->resolver = new TwigResolver($environment);
    }

    public function testResolve()
    {
        $this->assertInstanceOf(Twig_Template::class, $this->resolver->resolve('key1'));
    }

    /**
     * @expectedException \Twig_Error_Loader
     * @expectedExceptionMessage Template "key2" is not defined.
     */
    public function testResolveError()
    {
        $this->assertInstanceOf(Twig_Template::class, $this->resolver->resolve('key2'));
    }
}