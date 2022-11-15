<?php

namespace MakeShared\DataModel\WhoAreYou;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a response to the 'Who are you?' question.
 *
 * Class WhoAreYou
 * @package MakeShared\DataModel\Lpa
 */
class WhoAreYou extends AbstractData
{
    /**
     * @var string Answer to the top level of options.
     */
    protected $who;

    /**
     * @var string|null Extra details explaining their answer.
     */
    protected $qualifier;

    /**
     * @return (bool|null[])[][] An array representing the valid option.
     *
     * @psalm-return array{donor: array{subquestion: array{0: null}, qualifier: false}, friendOrFamily: array{subquestion: array{0: null}, qualifier: false}, financeProfessional: array{subquestion: array{0: null}, qualifier: false}, legalProfessional: array{subquestion: array{0: null}, qualifier: false}, estatePlanningProfessional: array{subquestion: array{0: null}, qualifier: false}, digitalPartner: array{subquestion: array{0: null}, qualifier: false}, charity: array{subquestion: array{0: null}, qualifier: false}, organisation: array{subquestion: array{0: null}, qualifier: false}, other: array{subquestion: array{0: null}, qualifier: true}, notSaid: array{subquestion: array{0: null}, qualifier: false}}
     */
    public static function options(): array
    {
        return [
            'donor' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'friendOrFamily' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'financeProfessional' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'legalProfessional' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'estatePlanningProfessional' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'digitalPartner' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'charity' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'organisation' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'other' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => true,
            ],
            'notSaid' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
        ];
    }
}
