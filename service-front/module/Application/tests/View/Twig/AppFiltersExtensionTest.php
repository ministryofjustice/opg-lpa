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
        $this->config['version']['cache'] = '12345678';
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
        $this->assertSame('/assets/12345678/testpath', $this->extension->assetPath('/assets/testpath'));
    }

    public function testConcatListOfNamesReturnsNullForEmptyList(): void
    {
        $result = $this->extension->concatListOfNames([]);

        $this->assertNull($result);
    }

    public function testConcatListOfNamesReturnsSingleName(): void
    {
        $actor = (object) ['name' => 'Alice Smith'];

        $result = $this->extension->concatListOfNames([$actor]);

        $this->assertSame('Alice Smith', $result);
    }

    public function testConcatListOfNamesReturnsCommaSeparatedListWithAnd(): void
    {
        $actors = [
            (object) ['name' => 'Alice Smith'],
            (object) ['name' => 'Bob Jones'],
            (object) ['name' => 'Charlie Brown'],
        ];

        $result = $this->extension->concatListOfNames($actors);

        $this->assertSame('Alice Smith, Bob Jones and Charlie Brown', $result);
    }
}
