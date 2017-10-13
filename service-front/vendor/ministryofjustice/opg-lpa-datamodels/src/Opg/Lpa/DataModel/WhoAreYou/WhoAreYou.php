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
     * @var string|null Answer to the second level question.
     */
    protected $subquestion;

    /**
     * @var string|null Extra details explaining their sub-question answer.
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

        $metadata->addPropertyConstraint('subquestion', new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
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

            // Ensure the value is in the subquestion array...
            if (!in_array($value, $options[$object->who]['subquestion'], true)) {
                $context->buildViolation('allowed-values:' . implode(',', $options[$object->who]['subquestion']))->addViolation();
            }
        }));

        $metadata->addPropertyConstraint('qualifier', new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
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
        }));
    }

    /**
     * @return array An array representing the valid option.
     */
    public static function options()
    {
        return [
            'professional' => [
                'subquestion' => [
                    'solicitor',
                    'will-writer',
                    'other'
                ],
                'qualifier' => true,
            ],
            'digitalPartner' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
            'organisation' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => true,
            ],
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
            'notSaid' => [
                'subquestion' => [
                    null
                ],
                'qualifier' => false,
            ],
        ];
    }
}
