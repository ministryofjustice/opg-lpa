<?php

declare(strict_types=1);

namespace ApplicationTest\View\Helper;

use Application\View\Helper\FinalCheckAccessible;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\DataModel\Lpa\Lpa;

final class FinalCheckAccessibleTest extends MockeryTestCase
{
    public function testInvoke(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));


        $finalCheckAccessible = new FinalCheckAccessible();
        $result = $finalCheckAccessible($lpa);

        $this->assertFalse($result);
    }
}
