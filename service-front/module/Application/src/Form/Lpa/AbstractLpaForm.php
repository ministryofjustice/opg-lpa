<?php

namespace Application\Form\Lpa;

use Application\Form\AbstractCsrfForm;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Validator\ValidatorResponse;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\FormInterface;

abstract class AbstractLpaForm extends AbstractCsrfForm
{
    /**
     * LPA object if it was passed in via the constructor
     *
     * @var Lpa
     */
    protected $lpa;

    /**
     * Form elements configuration to be added to the form
     *
     * @var array
     */
    protected $formElements = [];

    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        //  If an LPA has been passed in the options then extract it and set as a variable now
        if (array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }

        parent::__construct($name, $options);
    }

    public function init()
    {
        foreach ($this->formElements as $name => $elm) {
            //  Add the element
            $this->add([
                'name'       => $name,
                'type'       => $elm['type'],
                'options'    => (array_key_exists('options', $elm) ? $elm['options'] : []),
                'attributes' => (array_key_exists('attributes', $elm) ? $elm['attributes'] : []),
            ]);

            //  Add data to the input filter
            $this->addToInputFilter([
                'name'          => $name,
                'required'      => (array_key_exists('required', $elm) ? $elm['required'] : false),
                'error_message' => (array_key_exists('error_message', $elm) ? $elm['error_message'] : null),
                'filters'       => (array_key_exists('filters', $elm) ? $elm['filters'] : []),
                'validators'    => (array_key_exists('validators', $elm) ? $elm['validators'] : []),
            ]);
        }

        parent::init();
    }

    /**
     * Validate form elements through model validation.
     *
     * @see \Laminas\Form\Form::isValid()
     */
    public function isValid(): bool
    {
        $result = parent::isValid();

        if ($result) {
            // do validation though model validators.
            $modelValidationResult = $this->validateByModel();

            // if Zend validation was successful, do validation through model.
            $this->isValid = $result = (bool) ($result & $modelValidationResult['isValid']);
        }

        // merge both zend and LPA model validation error messages.
        if (!$result) {
            $messages = $this->getInputFilter()->getMessages();

            // merge Zend and model validation errors.
            if (isset($modelValidationResult) && isset($modelValidationResult['messages'])) {
                $messages = array_merge($messages, $modelValidationResult['messages']);
            }

            // Process model validation messages so they relate to fields on the form,
            // e.g. "name/company-name" becomes "name" and "name/company-company" becomes company.
            $multiFieldMessages = [];

            foreach ($messages as $key => $message) {
                if (preg_match('|(.*/.*)-(.*)|', $key, $matches)) {
                    $field = $matches[2];
                    $multiFieldMessages[$field] = $message;
                }
            }

            $this->setMessages(array_merge($messages, $multiFieldMessages));
        }

        return $result;
    }

    /**
     * Convert model validation response to Zend Form validation messages format.
     *
     * Return value depends on $validationResponse, e.g.
     *
     * $validationResponse =
     *     {storage: ['name.first'=>['value'=>'', 'messages'=>[0=>'cannot-be-blank'],],]}
     * return value = ['name-first'=>'cannot-be-blank',]
     *
     * OR
     *
     * $validationResponse =
     *     {storage: ['address.address2/postcode'=>['value'=>'', 'messages'=>[0=>'cannot-be-null'],],]}
     * return value =
     *     ['address-address2'=>'linked-1-cannot-be-null','address-postcode'=>'linked-1-cannot-be-null',]
     *
     * @param ValidatorResponse $validationResponse
     * @return array|string
     */
    protected function modelValidationMessageConverter(ValidatorResponse $validationResponse, $context = null)
    {
        $messages = [];

        foreach ($validationResponse as $validationErrorKey => $validationErrors) {
            if (strstr($validationErrorKey, '/')) {
                //  The error relates to multiple fields
                $errorKeyStubs = explode('.', $validationErrorKey);

                $fields = [
                    0 => '',
                    1 => '',
                ];

                foreach ($errorKeyStubs as $stub) {
                    if (strstr($stub, '/')) {
                        $subFields = explode('/', $stub);
                        $fields[0] .= "{$subFields[0]}-";
                        $fields[1] .= "{$subFields[1]}-";
                    } else {
                        $fields[0] .= "{$stub}-";
                        $fields[1] .= "{$stub}-";
                    }
                }

                foreach ($fields as $field) {
                    $childFields = [rtrim($field, '-')];

                    //  A multiple field error containing a name property is a special case.
                    //  Name on its own means that the custom violation was built in the parent object context
                    //  Otherwise it would be prefixed with parentElementName.name and this statement would not match.
                    //  If it does it means we need to map name to the three name components, title, first, last
                    if ($childFields[0] === 'name') {
                        $childFields = ['name-title', 'name-first', 'name-last'];
                    }

                    foreach ($childFields as $childField) {
                        $messages[$childField] = array_map(function ($v) {
                            return 'linked-1-' . $v;
                        }, $validationErrors['messages']);
                    }
                }
            } else {
                //  If the error only relates to a single field swap any dots for dashes in the field name
                $field = str_replace('.', '-', $validationErrorKey);

                $messages[$field] = $validationErrors['messages'];
            }
        }

        return $messages;
    }

    /**
     * Convert form data to model-compatible input data format.
     *
     * @param array $formData. e.g. ['name-title'=>'Mr','name-first'=>'John',]
     *
     * @return array e.g. ['name'=>['title'=>'Mr','first'=>'John',],]
     */
    protected function convertFormDataForModel($formData)
    {
        $modelData = [];

        foreach ($formData as $key => $value) {
            $names = explode('-', $key);
            $m = &$modelData;

            for ($i = 0; $i < count($names); $i++) {
                if (!array_key_exists($names[$i], $m)) {
                    $m[$names[$i]] = [];
                }

                $m = &$m[$names[$i]];
            }

            $m = $value;

            if ($this->has($key) && ($this->get($key) instanceof Checkbox || $this->get($key) instanceof Radio)) {
                //  If possible convert the value to a boolean
                if ($value == "0" || $value === false || $value == "" || $value === null) {
                    $m = false;
                } elseif ($value == "1" || $value === true) {
                    $m = true;
                }
            }
        }

        return $modelData;
    }

    public function bind($object, $flags = FormInterface::VALUES_NORMALIZED)
    {
        return parent::bind(new \ArrayObject($object));
    }

    /**
     *  Get validated form data for creating model object
     *
     * @return array|null
     */
    public function getModelDataFromValidatedForm()
    {
        if ($this->data != null) {
            return $this->convertFormDataForModel($this->getData());
        }
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    abstract protected function validateByModel();
}
