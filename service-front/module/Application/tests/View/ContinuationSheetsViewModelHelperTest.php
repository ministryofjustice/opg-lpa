<?php

namespace ApplicationTest\View;

use Application\View\ContinuationSheetsViewModelHelper;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class ContinuationSheetsViewModelHelperTest extends MockeryTestCase
{
    public function testContinuationSheetsViewModelHelperBuild(): void
    {
        $lpa = Mockery::Mock(Lpa::class);
    }
}
