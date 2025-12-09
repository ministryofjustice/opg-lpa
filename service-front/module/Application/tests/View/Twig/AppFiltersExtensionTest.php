<?php

declare(strict_types=1);

namespace ApplicationTest\View\Twig;

use Application\View\Twig\AppFiltersExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

final class AppFiltersExtensionTest extends TestCase
{
    private AppFiltersExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = [];
        $this->config['version']['cache'] = 'v2';
        $this->extension = new AppFiltersExtension($this->config);
    }

    public function testRegistersOrdinalSuffixFilter(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertNotEmpty($filters);

        $names = array_map(
            static fn (TwigFilter $filter) => $filter->getName(),
            $filters
        );

        $this->assertContains('ordinal_suffix', $names);
    }

    public function testOrdinalSuffix(): void
    {
        $this->assertSame('1st', $this->extension->ordinalSuffix(1));
        $this->assertSame('2nd', $this->extension->ordinalSuffix(2));
        $this->assertSame('3rd', $this->extension->ordinalSuffix(3));
        $this->assertSame('4th', $this->extension->ordinalSuffix(4));
        $this->assertSame('121st', $this->extension->ordinalSuffix(121));
    }

    public function testAssetPath(): void
    {
        $this->assertSame('/assets/v2/testpath', $this->extension->assetPath('/assets/testpath'));
    }
}
