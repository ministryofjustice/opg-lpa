<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use App\Form\AbstractForm;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Validator\ValidatorResponse;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\FormInterface;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
abstract class AbstractLpaForm extends AbstractForm
{
    /** @var Lpa|null */
    protected $lpa;

    /** @var array */
    protected $formElements = [];

    public function __construct($name = null, $options = [])
    {
        // Handle case where Laminas InvokableFactory passes options as the first argument
        if (is_array($name) && empty($options)) {
            $options = $name;
            $name = null;
        }

        if (array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }

        parent::__construct($name, $options);
    }

    /**
     * Override setData() to inject unchecked-checkbox defaults before the data
     * reaches either the Laminas input filter or our validateByModel() methods.
     * Browsers omit unchecked checkboxes from the POST body entirely, so without
     * this normalisation every downstream consumer needs individual `?? '0'` guards.
     *
     * {@inheritDoc}
     */
    public function setData(iterable $data): static
    {
        if (is_array($data)) {
            $data = $this->normalizeCheckboxDefaults($data, $this);
        }
        return parent::setData($data);
    }

    /**
     * Recursively inject the configured unchecked_value for any Checkbox element
     * whose key is absent from $data.  Radio buttons (which extend Checkbox in
     * Laminas) are skipped — they are submitted as a single value and are handled
     * by normal required-field validation.
     *
     * @param array<string, mixed>  $data
     */
    private function normalizeCheckboxDefaults(array $data, FieldsetInterface $fieldset): array
    {
        foreach ($fieldset->getElements() as $elementName => $element) {
            // Radio extends MultiCheckbox extends Checkbox — skip them.
            if ($element instanceof Radio) {
                continue;
            }

            if ($element instanceof Checkbox) {
                // Use the array key (short name) rather than $element->getName(), because
                // Fieldset::prepareElement() has already prefixed the element's name attribute
                // with the fieldset name (e.g. "correspondence[contactByEmail]") by the time
                // setData() is called.  The $this->elements array key is still the original
                // short name and is the correct key to look up in $data.
                if (!array_key_exists($elementName, $data)) {
                    $data[$elementName] = $element->getUncheckedValue();
                }
            }
        }

        foreach ($fieldset->getFieldsets() as $fieldsetName => $nested) {
            if (!array_key_exists($fieldsetName, $data) || !is_array($data[$fieldsetName])) {
                $data[$fieldsetName] = [];
            }
            $data[$fieldsetName] = $this->normalizeCheckboxDefaults($data[$fieldsetName], $nested);
        }

        return $data;
    }

    public function init()
    {
        foreach ($this->formElements as $name => $elm) {
            $this->add([
                'name'       => $name,
                'type'       => $elm['type'],
                'options'    => (array_key_exists('options', $elm) ? $elm['options'] : []),
                'attributes' => (array_key_exists('attributes', $elm) ? $elm['attributes'] : []),
            ]);

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

    public function isValid(): bool
    {
        $result = parent::isValid();

        if ($result) {
            $modelValidationResult = $this->validateByModel();
            $this->isValid = $result = (bool) ($result & $modelValidationResult['isValid']);
        }

        if (!$result) {
            $messages = $this->getInputFilter()->getMessages();

            if (isset($modelValidationResult) && isset($modelValidationResult['messages'])) {
                $messages = array_merge($messages, $modelValidationResult['messages']);
            }

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
     * @return (mixed|string[])[]
     *
     * @psalm-return array<array<string>|string, array<string>|mixed>
     */
    protected function modelValidationMessageConverter(ValidatorResponse $validationResponse, array|null $context = null)
    {
        $messages = [];

        foreach ($validationResponse as $validationErrorKey => $validationErrors) {
            if (strstr($validationErrorKey, '/')) {
                $errorKeyStubs = explode('.', $validationErrorKey);

                $fields = [0 => '', 1 => ''];

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
                $field = str_replace('.', '-', $validationErrorKey);
                $messages[$field] = $validationErrors['messages'];
            }
        }

        return $messages;
    }

    /**
     * @param array|mixed|null|object $formData
     *
     * @psalm-param T|array|null|object $formData
     */
    protected function convertFormDataForModel(array|null $formData)
    {
        $modelData = [];

        if ($formData === null) {
            return $modelData;
        }

        $formData = $this->stripFlatKeysCollidingWithHyphenated($formData);

        foreach ($formData as $key => $value) {
            $names = explode('-', $key);
            $m = &$modelData;

            foreach ($names as $name) {
                if (!array_key_exists($name, $m)) {
                    $m[$name] = [];
                }
                $m = &$m[$name];
            }

            $m = $value;

            if ($this->has($key) && ($this->get($key) instanceof Checkbox || $this->get($key) instanceof Radio)) {
                if ($value == '0' || $value === false || $value == '' || $value === null) {
                    $m = false;
                } elseif ($value == '1' || $value === true) {
                    $m = true;
                }
            }
        }

        return $modelData;
    }

    /**
     * @param array<array-key, mixed> $formData
     * @return array<array-key, mixed>
     */
    private function stripFlatKeysCollidingWithHyphenated(array $formData): array
    {
        $hyphenatedPrefixes = [];
        foreach (array_keys($formData) as $key) {
            if (!is_string($key)) {
                continue;
            }
            $dashPos = strpos($key, '-');
            if ($dashPos > 0) {
                $hyphenatedPrefixes[substr($key, 0, $dashPos)] = true;
            }
        }

        foreach (array_keys($formData) as $key) {
            if (!is_string($key)) {
                continue;
            }
            if (strpos($key, '-') === false && isset($hyphenatedPrefixes[$key])) {
                unset($formData[$key]);
            }
        }

        return $formData;
    }

    public function bind(array|object $object, int $flags = FormInterface::VALUES_NORMALIZED)
    {
        if (is_array($object)) {
            $object = new \ArrayObject($object);
        }
        return parent::bind($object, $flags);
    }

    public function getModelDataFromValidatedForm()
    {
        if ($this->data != null) {
            // Use VALUES_AS_ARRAY so that getData() returns the input-filter's plain
            // PHP array rather than the bound ArrayObject when bind() was used.
            return $this->convertFormDataForModel($this->getData(FormInterface::VALUES_AS_ARRAY));
        }
    }

    abstract protected function validateByModel();
}
