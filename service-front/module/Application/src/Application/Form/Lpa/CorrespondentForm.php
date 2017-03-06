<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Zend\Form\FormInterface;

class CorrespondentForm extends AbstractActorForm
{
    /**
     * Flag to indicate if the correspondent used is a trust
     *
     * @var bool
     */
    private $isTrust = false;

    protected $formElements = [
        'who' => [
            'type' => 'Hidden',
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
        'email-address' => [
            'type' => 'Email',
            'validators' => [
                [
                    'name' => 'EmailAddress',
                ]
            ],
        ],
        'phone-number' => [
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
            'type' => 'Zend\Form\Element\Submit',
        ],
    ];

    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct('form-correspondent', $options);
    }

    public function bind($data, $flags = FormInterface::VALUES_NORMALIZED)
    {
        //  If this data is for a trust set the boolean flag then continue to bind in the parent function
        if (isset($data['type'])) {
            $this->isTrust = ($data['type'] == 'trust');

            //  Move the name to the company field so the data binds correctly
            if (isset($data['name'])) {
                $data['company'] = $data['name'];
                unset($data['name']);
            }
        } elseif (isset($data['who']) && isset($data['company'])) {
            $this->isTrust = ($data['who'] == Correspondence::WHO_ATTORNEY && !is_null($data['company']));
        }

        return parent::bind($data, $flags);
    }

    public function isTrust()
    {
        return $this->isTrust;
    }

   /**
    * Validate form input data through model validators.
    *
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $this->actorModel = new Correspondence();

        return parent::validateByModel();
    }
}
