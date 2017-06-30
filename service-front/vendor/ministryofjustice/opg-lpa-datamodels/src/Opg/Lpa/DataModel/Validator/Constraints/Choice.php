<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class Choice extends SymfonyConstraints\Choice
{
    use ValidatorPathTrait;

    //  Values are overwritten in the constructor
    public $message = 'invalid-value-selected';
    public $multipleMessage = 'invalid-values-selected';
    public $minMessage = 'minimum-number-of-values:{{ limit }}';
    public $maxMessage = 'maximum-number-of-values:{{ limit }}';


    public function __construct($options = null)
    {
        // Include the allowed values in the error message
        if (isset($options['choices'])) {
            $this->message = 'allowed-values:'.implode(',', $options['choices']);
            $this->multipleMessage = 'allowed-values:'.implode(',', $options['choices']);
        }

        parent::__construct($options);
    }
}
