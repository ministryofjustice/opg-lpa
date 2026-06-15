<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Correspondence;
use Laminas\Form\FormInterface;

/**
 * @template T
 * @template-extends AbstractActorForm<T>
 */
class CorrespondentForm extends AbstractActorForm
{
    protected $formElements = [
        'who' => [
            'type'       => 'Hidden',
            'attributes' => [
                'value' => Correspondence::WHO_OTHER,
            ],
        ],
        'name-title' => [
            'type' => 'Text',
        ],
        'name-first' => [
            'type' => 'Text',
        ],
        'name-last' => [
            'type' => 'Text',
        ],
        'company' => [
            'type' => 'Text',
        ],
        'address-address1' => [
            'type' => 'Text',
        ],
        'address-address2' => [
            'type' => 'Text',
        ],
        'address-address3' => [
            'type' => 'Text',
        ],
        'address-postcode' => [
            'type' => 'Text',
        ],
        'submit' => [
            'type' => 'Submit',
        ],
        'email-address' => [
            'type' => 'Email',
        ],
        'phone-number' => [
            'type' => 'Text',
        ],
    ];

    private bool $isEditable = true;
    private bool $trustSelected = false;

    public function init()
    {
        $this->setName('form-correspondent');
        $this->setAttribute('data-cy', 'form-correspondent');
        $this->actorModel = new Correspondence();
        parent::init();
    }

    public function bind(array|object $object, int $flags = FormInterface::VALUES_NORMALIZED)
    {
        if ($object instanceof \ArrayAccess || is_array($object)) {
            $who  = (isset($object['who']) ? $object['who'] : null);
            $type = (isset($object['type']) ? $object['type'] : null);

            if (
                $who == Correspondence::WHO_DONOR ||
                $who == Correspondence::WHO_CERTIFICATE_PROVIDER ||
                ($who == Correspondence::WHO_ATTORNEY && $type == 'human')
            ) {
                $this->isEditable = false;
            }

            if ($type == 'trust') {
                $who = $object['who'] = Correspondence::WHO_ATTORNEY;
                $object['company'] = $object['name'];
                unset($object['name']);
            }

            $this->trustSelected = ($who == Correspondence::WHO_ATTORNEY && !empty($object['company']));
        }

        return parent::bind($object, $flags);
    }

    public function trustSelected(): bool
    {
        return $this->trustSelected;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }
}
