<?php

namespace ZfcTwigTest\View;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader;
use Twig\Template;
use ZfcTwig\View\TwigResolver;

class TwigResolverTest extends TestCase
{
    /** @var  TwigResolver */
    protected $resolver;

    protected function setUp()
    {
        parent::setUp();

        $chain = new Loader\ChainLoader();
        $chain->addLoader(new Loader\ArrayLoader(['key1' => 'var1']));
        $environment = new Environment($chain);
        $this->resolver = new TwigResolver($environment);
    }

    public function testResolve()
    {
        $this->assertInstanceOf(Template::class, $this->resolver->resolve('key1'));
    }

    /**
     * @expectedException \Twig_Error_Loader
     * @expectedExceptionMessage Template "key2" is not defined.
     */
    public function testResolveError()
    {
        $this->assertInstanceOf(Template::class, $this->resolver->resolve('key2'));
    }
}