<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class Choice extends SymfonyConstraints\Choice
{
    use ValidatorPathTrait;

    public $minMessage = 'minimum-number-of-values:{{ limit }}';
    public $maxMessage = 'maximum-number-of-values:{{ limit }}';

    // Values are overridden in the constructor
    public $message = 'invalid-value-selected';
    public $multipleMessage = 'invalid-values-selected';

    public function __construct($options = null){

        // Include the allowed values in the error message
        if( isset($options['choices']) ){
            $this->message = 'allowed-values:'.implode(',', $options['choices']);
            $this->multipleMessage = 'allowed-values:'.implode(',', $options['choices']);
        }

        parent::__construct( $options );
    }


}
