<?php

namespace Opg\Lpa\DataModel\WhoAreYou;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a response to the 'Who are you?' question.
 *
 * Class WhoAreYou
 * @package Opg\Lpa\DataModel\Lpa
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('who', [
            new Assert\NotBlank,
            new Assert\Choice([
                'choices' => array_keys(self::options())
            ]),
        ]);

        $metadata->addPropertyConstraint(
            'qualifier',
            new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
                $object = $context->getObject();
                $options = $object::options();

                // Don't validate if 'who' isn't set...
                if (!is_string($object->who)) {
                    return;
                }

                // Check this, but don't validate. It's validated above.
                if (!isset($options[$object->who])) {
                    return;
                }

                // A qualifier is optional, so only invalid if a qualifier is not allowed, but one is set.
                if ($options[$object->who]['qualifier'] == false && !is_null($value)) {
                    $context->buildViolation((new Assert\IsNull)->message)->addViolation();
                }
            })
        );
    }

    /**
     * @return array An array representing the valid option.
     */
    public static function options()
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

    /**
     * @return string
     */
    public function getWho(): string
    {
        return $this->who;
    }

    /**
     * @param string $who
     * @return $this
     */
    public function setWho(string $who): WhoAreYou
    {
        $this->who = $who;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getQualifier()
    {
        return $this->qualifier;
    }

    /**
     * @param null|string $qualifier
     * @return $this
     */
    public function setQualifier($qualifier)
    {
        $this->qualifier = $qualifier;

        return $this;
    }
}
