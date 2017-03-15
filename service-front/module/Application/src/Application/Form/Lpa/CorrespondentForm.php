<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Zend\Form\FormInterface;

class CorrespondentForm extends AbstractActorForm
{
    /**
     * Flag to indicate if the data in this form can be edited - by default it is
     *
     * @var bool
     */
    private $isEditable = true;

    /**
     * Flag to indicate if a trust has been selected during the data bind
     *
     * @var bool
     */
    private $trustSelected = false;

    protected $formElements = [
        'who' => [
            'type'       => 'Hidden',
            'attributes' => [
                //  By default set the value to other for a blank form
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
        //  If the data being bound represents a donor or a human attorney then the form is not editable
        //  In all other circumstances some or all of the data can be edited
        $who = (isset($data['who']) ? $data['who'] : null);
        $type = (isset($data['type']) ? $data['type'] : null);

        //  Check to see if the data should be editable
        if ($who == Correspondence::WHO_DONOR || ($who == Correspondence::WHO_ATTORNEY && $type == 'human')) {
            $this->isEditable = false;
        }

        //  Check to see if the data represents a trust and set any required data
        if ($type == 'trust') {
            //  Replace the who value for a trust attorney
            $who = $data['who'] = Correspondence::WHO_ATTORNEY;

            //  Move the name to the company field so the data binds correctly
            $data['company'] = $data['name'];
            unset($data['name']);
        }

        $this->trustSelected = ($who == Correspondence::WHO_ATTORNEY && !empty($data['company']));

        return parent::bind($data, $flags);
    }

    public function trustSelected()
    {
        return $this->trustSelected;
    }

    public function isEditable()
    {
        return $this->isEditable;
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
