<?php

namespace MakeShared\DataModel\Lpa\Document;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Lpa\Document\Attorneys;
use MakeShared\DataModel\Lpa\Document\Decisions;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\All as AllConstraintSymfony;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Document extends AbstractData
{
    public const LPA_TYPE_PF = 'property-and-financial';
    public const LPA_TYPE_HW = 'health-and-welfare';

    /**
     * The LPA type. One of the constants under Document::LPA_TYPE_*
     *
     * @var string
     */
    protected $type;

    /**
     * @var Donor The donor.
     */
    protected $donor;

    /**
     * If string, it's the donor who is registering.
     * If array, it contains a reference to one or more primary attorneys.
     *
     * @var string|array
     */
    protected $whoIsRegistering;

    /**
     * How the decisions are made with primary attorney.
     *
     * @var Decisions\PrimaryAttorneyDecisions
     */
    protected $primaryAttorneyDecisions;

    /**
     * How the decisions are made with replacement attorney.
     *
     * @var Decisions\ReplacementAttorneyDecisions
     */
    protected $replacementAttorneyDecisions;

    /**
     * The entity who should receive correspondence about the LPA.
     *
     * @var Correspondence
     */
    protected $correspondent;

    /**
     * Additional instructions to be included on the form.
     *
     * @var string
     */
    protected $instruction;

    /**
     * The Donor's preferences.
     *
     * @var string
     */
    protected $preference;

    /**
     * The Certificate Provider.
     *
     * @var CertificateProvider
     */
    protected $certificateProvider;

    /**
     * All of the primary Attorneys.
     *
     * @var array containing instances of Attorney.
     */
    protected $primaryAttorneys = [];

    /**
     * All of the replacement Attorneys.
     *
     * @var array containing instances of Attorney.
     */
    protected $replacementAttorneys = [];

    /**
     * All of the people to notify.
     *
     * @var array containing instances of NotifiedPerson.
     */
    protected $peopleToNotify = [];

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('type', [
            new Assert\Type('string'),
            new Assert\Choice(
                choices: [
                    self::LPA_TYPE_PF,
                    self::LPA_TYPE_HW
                ]
            ),
        ]);

        $metadata->addPropertyConstraints('donor', [
            new Assert\Type('\MakeShared\DataModel\Lpa\Document\Donor'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraint(
            'whoIsRegistering',
            new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
                if (is_null($value) || $value == 'donor') {
                    return;
                }

                $validAttorneyIds = array_map(function ($v) {
                    return $v->id;
                }, $context->getObject()->primaryAttorneys);

                // Strip out any rogue empty array elements.
                $value = array_filter($value, function ($val) {
                    return !empty($val);
                });

                // If it's an array, ensure the IDs are valid primary attorney IDs.
                if (!empty($value)) {
                    foreach ($value as $attorneyId) {
                        if (!in_array($attorneyId, $validAttorneyIds)) {
                            $context->buildViolation('allowed-values:' . implode(',', $validAttorneyIds))
                                ->setInvalidValue(implode(',', $value))
                                ->addViolation();

                            return;
                        }
                    }

                    return;
                }

                $context->buildViolation('allowed-values:donor,Array')->addViolation();
            })
        );

        $metadata->addPropertyConstraints('primaryAttorneyDecisions', [
            new Assert\Type('\MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('replacementAttorneyDecisions', [
            new Assert\Type('\MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('correspondent', [
            new Assert\Type('\MakeShared\DataModel\Lpa\Document\Correspondence'),
            new ValidConstraintSymfony(),
        ]);

        // instruction should be string, null or boolean false.
        $metadata->addPropertyConstraint(
            'instruction',
            new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
                if (is_string($value) && strlen($value) > 10000) {
                    $context->buildViolation('must-be-less-than-or-equal:10000')->addViolation();
                }

                if (is_null($value) || is_string($value) || $value === false) {
                    return;
                }

                $context->buildViolation('expected-type:string-or-bool=false')->addViolation();
            })
        );

        // preference should be string, null or boolean false.
        $metadata->addPropertyConstraint(
            'preference',
            new CallbackConstraintSymfony(function ($value, ExecutionContextInterface $context) {
                if (is_string($value) && strlen($value) > 10000) {
                    $context->buildViolation('must-be-less-than-or-equal:10000')->addViolation();
                }

                if (is_null($value) || is_string($value) || $value === false) {
                    return;
                }

                $context->buildViolation('expected-type:string-or-bool=false')->addViolation();
            })
        );

        $metadata->addPropertyConstraints('certificateProvider', [
            new Assert\Type('\MakeShared\DataModel\Lpa\Document\CertificateProvider'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('primaryAttorneys', [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new AllConstraintSymfony([
                new Assert\Type('\MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney'),
            ]),
            new Assert\Custom\UniqueIdInArray(),
        ]);

        $metadata->addPropertyConstraints('replacementAttorneys', [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new AllConstraintSymfony([
                new Assert\Type('\MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney'),
            ]),
            new Assert\Custom\UniqueIdInArray(),
        ]);

        // Allow only N trust corporation(s) across primaryAttorneys and replacementAttorneys.
        $metadata->addConstraint(new CallbackConstraintSymfony(function ($object, ExecutionContextInterface $context) {
            $max = 1;
            $attorneys = array_merge($object->primaryAttorneys, $object->replacementAttorneys);

            $attorneys = array_filter($attorneys, function ($attorney) {
                return $attorney instanceof Attorneys\TrustCorporation;
            });

            if (count($attorneys) > $max) {
                $context->buildViolation("must-be-less-than-or-equal:{$max}")
                    ->setInvalidValue(count($attorneys) . " found")
                    ->atPath('primaryAttorneys/replacementAttorneys')
                    ->addViolation();
            }
        }));

        $metadata->addPropertyConstraints('peopleToNotify', [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\Count(max: 5),
            new AllConstraintSymfony([
                new Assert\Type('\MakeShared\DataModel\Lpa\Document\NotifiedPerson'),
            ]),
            new Assert\Custom\UniqueIdInArray(),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $value)
    {
        switch ($property) {
            case 'donor':
                return (($value instanceof Donor || is_null($value)) ? $value : new Donor($value));
            case 'primaryAttorneyDecisions':
                return (($value instanceof Decisions\PrimaryAttorneyDecisions || is_null($value)) ?
                    $value : new Decisions\PrimaryAttorneyDecisions($value));
            case 'replacementAttorneyDecisions':
                return (($value instanceof Decisions\ReplacementAttorneyDecisions || is_null($value)) ?
                    $value : new Decisions\ReplacementAttorneyDecisions($value));
            case 'correspondent':
                return (($value instanceof Correspondence || is_null($value)) ? $value : new Correspondence($value));
            case 'certificateProvider':
                return (($value instanceof CertificateProvider || is_null($value)) ? $value : new CertificateProvider($value));
            case 'primaryAttorneys':
            case 'replacementAttorneys':
                return array_map(function ($value) {
                    if ($value instanceof Attorneys\AbstractAttorney) {
                        return $value;
                    } else {
                        return Attorneys\AbstractAttorney::factory($value);
                    }
                }, $value);
            case 'peopleToNotify':
                return array_map(function ($value) {
                    return ($value instanceof NotifiedPerson ? $value : new NotifiedPerson($value));
                }, $value);
        }

        return parent::map($property, $value);
    }

    /**
     * Get primary attorney object by attorney id.
     *
     * @param int $id
     * @return NULL|Attorneys\AbstractAttorney
     */
    public function getPrimaryAttorneyById($id)
    {
        if ($this->primaryAttorneys == null) {
            return null;
        }

        foreach ($this->primaryAttorneys as $attorney) {
            if ($attorney->id == $id) {
                return $attorney;
            }
        }

        return null;
    }

    /**
     * Get replacement attorney object by attorney id.
     *
     * @param int $id
     * @return NULL|Attorneys\AbstractAttorney
     */
    public function getReplacementAttorneyById($id)
    {
        if ($this->replacementAttorneys == null) {
            return null;
        }

        foreach ($this->replacementAttorneys as $attorney) {
            if ($attorney->id == $id) {
                return $attorney;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type): Document
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Donor
     */
    public function getDonor()
    {
        return $this->donor;
    }

    /**
     * @param Donor $donor
     * @return $this
     */
    public function setDonor($donor): Document
    {
        $this->donor = $donor;

        return $this;
    }

    /**
     * @return array|string
     */
    public function getWhoIsRegistering()
    {
        return $this->whoIsRegistering;
    }

    /**
     * @param array|string $whoIsRegistering
     * @return $this
     */
    public function setWhoIsRegistering($whoIsRegistering)
    {
        $this->whoIsRegistering = $whoIsRegistering;

        return $this;
    }

    /**
     * @return Decisions\PrimaryAttorneyDecisions
     */
    public function getPrimaryAttorneyDecisions()
    {
        return $this->primaryAttorneyDecisions;
    }

    /**
     * @param Decisions\PrimaryAttorneyDecisions $primaryAttorneyDecisions
     * @return $this
     */
    public function setPrimaryAttorneyDecisions($primaryAttorneyDecisions): Document
    {
        $this->primaryAttorneyDecisions = $primaryAttorneyDecisions;

        return $this;
    }

    /**
     * @return Decisions\ReplacementAttorneyDecisions
     */
    public function getReplacementAttorneyDecisions()
    {
        return $this->replacementAttorneyDecisions;
    }

    /**
     * @param Decisions\ReplacementAttorneyDecisions $replacementAttorneyDecisions
     * @return $this
     */
    public function setReplacementAttorneyDecisions($replacementAttorneyDecisions): Document
    {
        $this->replacementAttorneyDecisions = $replacementAttorneyDecisions;

        return $this;
    }

    /**
     * @return Correspondence
     */
    public function getCorrespondent()
    {
        return $this->correspondent;
    }

    /**
     * @param Correspondence $correspondent
     * @return $this
     */
    public function setCorrespondent($correspondent): Document
    {
        $this->correspondent = $correspondent;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstruction()
    {
        return $this->instruction;
    }

    /**
     * @param string $instruction
     * @return $this
     */
    public function setInstruction($instruction): Document
    {
        $this->instruction = $instruction;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreference()
    {
        return $this->preference;
    }

    /**
     * @param string $preference
     * @return $this
     */
    public function setPreference($preference): Document
    {
        $this->preference = $preference;

        return $this;
    }

    /**
     * @return CertificateProvider
     */
    public function getCertificateProvider()
    {
        return $this->certificateProvider;
    }

    /**
     * @param CertificateProvider $certificateProvider
     * @return $this
     */
    public function setCertificateProvider($certificateProvider): Document
    {
        $this->certificateProvider = $certificateProvider;

        return $this;
    }

    /**
     * @return array
     */
    public function getPrimaryAttorneys(): array
    {
        return $this->primaryAttorneys;
    }

    /**
     * @param array $primaryAttorneys
     * @return $this
     */
    public function setPrimaryAttorneys(array $primaryAttorneys): Document
    {
        $this->primaryAttorneys = $primaryAttorneys;

        return $this;
    }

    /**
     * @return array
     */
    public function getReplacementAttorneys(): array
    {
        return $this->replacementAttorneys;
    }

    /**
     * @param array $replacementAttorneys
     * @return $this
     */
    public function setReplacementAttorneys(array $replacementAttorneys): Document
    {
        $this->replacementAttorneys = $replacementAttorneys;

        return $this;
    }

    /**
     * @return array
     */
    public function getPeopleToNotify(): array
    {
        return $this->peopleToNotify;
    }

    /**
     * @param array $peopleToNotify
     * @return $this
     */
    public function setPeopleToNotify(array $peopleToNotify): Document
    {
        $this->peopleToNotify = $peopleToNotify;

        return $this;
    }
}
