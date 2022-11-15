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
    const LPA_TYPE_PF = 'property-and-financial';
    const LPA_TYPE_HW = 'health-and-welfare';

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

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     *
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Donor
     */
    public function getDonor()
    {
        return $this->donor;
    }

    /**
     * @return array|string
     */
    public function getWhoIsRegistering()
    {
        return $this->whoIsRegistering;
    }

    /**
     * @return Decisions\PrimaryAttorneyDecisions
     */
    public function getPrimaryAttorneyDecisions()
    {
        return $this->primaryAttorneyDecisions;
    }

    /**
     * @return Decisions\ReplacementAttorneyDecisions
     */
    public function getReplacementAttorneyDecisions()
    {
        return $this->replacementAttorneyDecisions;
    }

    /**
     * @return Correspondence
     */
    public function getCorrespondent()
    {
        return $this->correspondent;
    }

    /**
     * @return string
     */
    public function getInstruction()
    {
        return $this->instruction;
    }

    /**
     * @return string
     */
    public function getPreference()
    {
        return $this->preference;
    }

    /**
     * @return CertificateProvider
     */
    public function getCertificateProvider()
    {
        return $this->certificateProvider;
    }

    /**
     * @return array
     */
    public function getPrimaryAttorneys(): array
    {
        return $this->primaryAttorneys;
    }

    /**
     * @return array
     */
    public function getReplacementAttorneys(): array
    {
        return $this->replacementAttorneys;
    }

    /**
     * @return array
     */
    public function getPeopleToNotify(): array
    {
        return $this->peopleToNotify;
    }
}
