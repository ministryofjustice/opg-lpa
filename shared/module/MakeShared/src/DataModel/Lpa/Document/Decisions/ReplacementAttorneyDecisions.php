<?php

namespace MakeShared\DataModel\Lpa\Document\Decisions;

use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ReplacementAttorneyDecisions extends AbstractDecisions
{
    public const LPA_DECISION_WHEN_FIRST = 'first';
    public const LPA_DECISION_WHEN_LAST = 'last';
    public const LPA_DECISION_WHEN_DEPENDS = 'depends';

    /**
     * @var string Additional free text details relating to this 'when' decision.
     */
    protected $whenDetails;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('when', [
            new Assert\Type('string'),
            new Assert\Choice(
                choices: [
                    self::LPA_DECISION_WHEN_FIRST,
                    self::LPA_DECISION_WHEN_LAST,
                    self::LPA_DECISION_WHEN_DEPENDS
                ]
            ),
        ]);

        $metadata->addPropertyConstraints('whenDetails', [
            new Assert\Type('string'),
            new Assert\Length(
                min: 1,
                max: (1000 * 1024)
            ),
        ]);
    }

    /**
     * @return string
     */
    public function getWhenDetails()
    {
        return $this->whenDetails;
    }

    /**
     * @param string|null $whenDetails
     * @return $this
     */
    public function setWhenDetails($whenDetails): ReplacementAttorneyDecisions
    {
        $this->whenDetails = $whenDetails;

        return $this;
    }
}
