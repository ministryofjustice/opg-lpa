<?php

namespace MakeShared\DataModel\Lpa\Document\Attorneys;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Base Represents of an Attorney. This can be extended with one of two types, either Human or TrustCorporation.
 *
 * Class AbstractAttorney
 * @package MakeShared\DataModel\Lpa\Document\Attorneys
 */
abstract class AbstractAttorney extends AbstractData
{
    /**
     * @var int The attorney's internal ID.
     */
    protected $id;

    /**
     * @var Address Their postal address.
     */
    protected $address;

    /**
     * @var EmailAddress Their email address.
     */
    protected $email;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank([
                'groups' => [
                    'required-at-api'
                ]
            ]),
            new Assert\Type([
                'type' => 'int'
            ]),
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank(),
            new Assert\Type([
                'type' => '\MakeShared\DataModel\Common\Address'
            ]),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([
                'type' => '\MakeShared\DataModel\Common\EmailAddress'
            ]),
            new ValidConstraintSymfony(),
        ]);
    }

    /**
     * Instantiates a concrete instance of either Human or TrustCorporation
     * depending on the data passed to it.
     *
     * @param string|array $data An array or JSON representing an Attorney
     * @return Human|TrustCorporation
     */
    public static function factory($data)
    {
        // If it's a string...
        if (is_string($data)) {
            // Assume it's JSON.
            $data = json_decode($data, true);

            // Throw an exception if it turns out to not be JSON...
            if (is_null($data)) {
                throw new \InvalidArgumentException('Invalid JSON passed to constructor');
            }
        }

        // Based on type...
        switch ($data['type']) {
            case 'trust':
                return new TrustCorporation($data);
            case 'human':
                return new Human($data);
        }

        // Otherwise check if there was a number passed...
        if (isset($data['number'])) {
            return new TrustCorporation($data);
        }

        // else assume it's a human...
        return new Human($data);
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
            case 'address':
                return ($value instanceof Address ? $value : new Address($value));
            case 'email':
                return ($value instanceof EmailAddress ? $value : new EmailAddress($value));
        }

        return parent::map($property, $value);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): AbstractAttorney
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     * @return $this
     */
    public function setAddress(Address $address): AbstractAttorney
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return EmailAddress
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param EmailAddress $email
     * @return $this
     */
    public function setEmail($email): AbstractAttorney
    {
        $this->email = $email;

        return $this;
    }
}
