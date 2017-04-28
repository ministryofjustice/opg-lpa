<?php

namespace Opg\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a full LPA document, plus associated metadata.
 *
 * Class Lpa
 * @package Opg\Lpa\DataModel\Lpa
 */
class Lpa extends AbstractData
{
    /**
     * @var int The LPA identifier.
     */
    protected $id;

    /**
     * @var \DateTime the LPA was started.
     */
    protected $startedAt;

    /**
     * This means 'created' in the business sense, which means when the LPA instrument is finished.
     *
     * @var \DateTime the LPA was created.
     */
    protected $createdAt;

    /**
     * @var \DateTime the LPA was last updated.
     */
    protected $updatedAt;

    /**
     * @var \DateTime|null When the LPA was last updated AND it's status (at the moment in time) was complete.
     */
    protected $completedAt;

    /**
     * @var \DateTime|null DateTime the LPA was locked.
     */
    protected $lockedAt;

    /**
     * @var string LPA's owner User identifier.
     */
    protected $user;

    /**
     * @var Payment status.
     */
    protected $payment;

    /**
     * @var bool Flag to record whether the 'Who Are You' question has been answered with regards to this LPA.
     */
    protected $whoAreYouAnswered;

    /**
     * @var bool Is this LPA locked. i.e. read-only.
     */
    protected $locked;

    /**
     * @var int Reference to another LPA on which this LPA is based.
     */
    protected $seed;

    /**
     * @var int|null If this is a repeat LPA application, the relevant case number.
     */
    protected $repeatCaseNumber;

    /**
     * @var Document All the details making up the LPA document.
     */
    protected $document;

    /**
     * @var array Metadata relating to the LPA. Clients can use this value however they wish.
     */
    protected $metadata = [];

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'int'
            ]),
            new Assert\Range([
                'min' => 0,
                'max' => 99999999999
            ]),
        ]);

        $metadata->addPropertyConstraints('startedAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('createdAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('updatedAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('completedAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('lockedAt', [
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('user', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'xdigit'
            ]),
            new Assert\Length([
                'min' => 32,
                'max' => 32
            ]),
        ]);

        $metadata->addPropertyConstraints('payment', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Payment\Payment'
            ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('whoAreYouAnswered', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('locked', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'bool'
            ]),
        ]);

        $metadata->addPropertyConstraints('seed', [
            new Assert\Type([
                'type' => 'int'
            ]),
            new Assert\Range([
                'min' => 0,
                'max' => 99999999999
            ]),
        ]);

        $metadata->addPropertyConstraints('repeatCaseNumber', [
            new Assert\Type([
                'type' => 'int'
            ]),
        ]);

        $metadata->addPropertyConstraints('document', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Document\Document'
            ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('metadata', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'array'
            ]),
            new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                // Max allowed size when JSON encoded in bytes.
                $bytes = 1024 * 1024 * 1;

                // Put a $bytes limit (when JSON encoded) on the metadata array.
                if (is_array($value) && strlen(json_encode($value)) >  $bytes) {
                    $context->buildViolation('must-be-less-than-or-equal:' . $bytes . '-bytes')->addViolation();
                }
            })
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $v)
    {
        switch ($property) {
            case 'startedAt':
            case 'updatedAt':
            case 'createdAt':
            case 'completedAt':
            case 'lockedAt':
                return (($v instanceof \DateTime || is_null($v)) ? $v : new \DateTime($v));
            case 'payment':
                return (($v instanceof Payment || is_null($v)) ? $v : new Payment($v));
            case 'document':
                return (($v instanceof Document || is_null($v)) ? $v : new Document($v));
        }

        return parent::map($property, $v);
    }

    /**
     * Returns $this as an array suitable for inserting into MongoDB.
     *
     * @return array
     */
    public function toMongoArray()
    {
        $data = parent::toMongoArray();

        // Rename 'id' to '_id' (keeping it at the beginning of the array)
        $data = ['_id' => $data['id']] + $data;

        unset($data['id']);

        return $data;
    }

    /**
     * Return an abbreviated (summary) version of the LPA.
     *
     * @return array
     */
    public function abbreviatedToArray()
    {
        $data = $this->toArray();

        // Include these top level fields...
        $data = array_intersect_key($data, array_flip([
            'id', 'lockedAt', 'startedAt', 'updatedAt', 'createdAt', 'completedAt', 'user', 'locked', 'document', 'metadata'
        ]));

        // Include these document level fields...
        $data['document'] = array_intersect_key($data['document'], array_flip([
            'donor', 'type'
        ]));

        return $data;
    }
}
