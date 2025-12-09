<?php

declare(strict_types=1);

namespace Application\View\Twig;

use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use Application\View\Helper\Traits\ConcatNamesTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppFunctionsExtension extends AbstractExtension
{
    use ConcatNamesTrait;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('applicant_names', [$this, 'applicantNames']),
            new TwigFunction('final_check_accessible', [$this, 'finalCheckAccessible']),
        ];
    }

    public function applicantNames(Lpa $lpa): ?string
    {
        if (!isset($lpa->document->whoIsRegistering)) {
            return null;
        }

        if ($lpa->document->whoIsRegistering === 'donor') {
            return 'the donor';
        }

        if (is_array($lpa->document->whoIsRegistering) && is_array($lpa->document->primaryAttorneys)) {
            $humans = [];

            foreach ($lpa->document->primaryAttorneys as $attorney) {
                if (in_array($attorney->id, $lpa->document->whoIsRegistering)) {
                    $humans[] = $attorney;
                }
            }

            return $this->concatNames($humans);
        }

        return null;
    }

    public function finalCheckAccessible(Lpa $lpa): bool
    {
        return FormFlowChecker::isFinalCheckAccessible($lpa);
    }
}
