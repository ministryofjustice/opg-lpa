<?php
namespace Opg\Lpa\DataModel\Validator;

use Respect\Validation\Exceptions;
use Respect\Validation\Validator as RespectValidator;

class Validator extends RespectValidator {

    private static $messagesInitialised = false;

    public function __construct(){

        parent::__construct();

        if( false === self::$messagesInitialised ){
            self::initMessages();
            self::$messagesInitialised = true;
        }

    }

    protected static function initMessages(){

        Exceptions\AllOfException::$defaultTemplates = array(
            Exceptions\AllOfException::MODE_DEFAULT => array(
                Exceptions\AllOfException::NONE => 'all-must-pass',
                Exceptions\AllOfException::SOME => 'all-must-pass',
            ),
            Exceptions\AllOfException::MODE_NEGATIVE => array(
                Exceptions\AllOfException::NONE => 'none-must-pass',
                Exceptions\AllOfException::SOME => 'none-must-pass',
            )
        );

        //-------

        Exceptions\BetweenException::$defaultTemplates = array(
            Exceptions\BetweenException::MODE_DEFAULT => array(
                Exceptions\BetweenException::BOTH => 'not-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\BetweenException::LOWER => 'not-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\BetweenException::GREATER => 'not-in-range/{{minValue}}-{{maxValue}}',
            ),
            Exceptions\BetweenException::MODE_NEGATIVE => array(
                Exceptions\BetweenException::BOTH => 'is-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\BetweenException::LOWER => 'is-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\BetweenException::GREATER => 'is-in-range/{{minValue}}-{{maxValue}}',
            )
        );

        //-------

        Exceptions\BoolException::$defaultTemplates = array(
            Exceptions\BoolException::MODE_DEFAULT => array(
                Exceptions\BoolException::STANDARD => 'not-bool',
            ),
            Exceptions\BoolException::MODE_NEGATIVE => array(
                Exceptions\BoolException::STANDARD => 'is-bool',
            )
        );

        //-------

        Exceptions\EmailException::$defaultTemplates = array(
            Exceptions\EmailException::MODE_DEFAULT => array(
                Exceptions\EmailException::STANDARD => 'not-email-address',
            ),
            Exceptions\EmailException::MODE_NEGATIVE => array(
                Exceptions\EmailException::STANDARD => 'is-email-address',
            )
        );

        //-------

        Exceptions\InstanceException::$defaultTemplates = array(
            Exceptions\InstanceException::MODE_DEFAULT => array(
                Exceptions\InstanceException::STANDARD => 'not-instance-type/{{instanceName}}',
            ),
            Exceptions\InstanceException::MODE_NEGATIVE => array(
                Exceptions\InstanceException::STANDARD => 'is-instance-type/{{instanceName}}',
            )
        );

        //-------

        Exceptions\IntException::$defaultTemplates = array(
            Exceptions\IntException::MODE_DEFAULT => array(
                Exceptions\IntException::STANDARD => 'not-int',
            ),
            Exceptions\IntException::MODE_NEGATIVE => array(
                Exceptions\IntException::STANDARD => 'is-int',
            )
        );

        //-------

        Exceptions\LengthException::$defaultTemplates = array(
            Exceptions\LengthException::MODE_DEFAULT => array(
                Exceptions\LengthException::BOTH => 'not-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\LengthException::LOWER => 'not-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\LengthException::GREATER => 'not-in-range/{{minValue}}-{{maxValue}}',
            ),
            Exceptions\LengthException::MODE_NEGATIVE => array(
                Exceptions\LengthException::BOTH => 'is-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\LengthException::LOWER => 'is-in-range/{{minValue}}-{{maxValue}}',
                Exceptions\LengthException::GREATER => 'is-in-range/{{minValue}}-{{maxValue}}',
            )
        );

        //-------

        Exceptions\MinException::$defaultTemplates = array(
            Exceptions\MinException::MODE_DEFAULT => array(
                Exceptions\MinException::STANDARD => 'not-greater-than/{{minValue}}',
                Exceptions\MinException::INCLUSIVE => 'not-greater-equal-than/{{minValue}}',
            ),
            Exceptions\MinException::MODE_NEGATIVE => array(
                Exceptions\MinException::STANDARD => 'is-greater-than/{{minValue}}',
                Exceptions\MinException::INCLUSIVE => 'is-greater-equal-than/{{minValue}}',
            )
        );

        //-------

        Exceptions\NotEmptyException::$defaultTemplates = array(
            Exceptions\NotEmptyException::MODE_DEFAULT => array(
                Exceptions\NotEmptyException::STANDARD => 'is-empty',
                Exceptions\NotEmptyException::NAMED => 'is-empty',
            ),
            Exceptions\NotEmptyException::MODE_NEGATIVE => array(
                Exceptions\NotEmptyException::STANDARD => 'not-empty',
                Exceptions\NotEmptyException::NAMED => 'not-empty',
            )
        );

        //-------

        Exceptions\NullValueException::$defaultTemplates = array(
            Exceptions\NullValueException::MODE_DEFAULT => array(
                Exceptions\NullValueException::STANDARD => 'not-null',
            ),
            Exceptions\NullValueException::MODE_NEGATIVE => array(
                Exceptions\NullValueException::STANDARD => 'is-null',
            )
        );

        //-------

        Exceptions\PhoneException::$defaultTemplates = array(
            Exceptions\PhoneException::MODE_DEFAULT => array(
                Exceptions\PhoneException::STANDARD => 'not-phone-number',
            ),
            Exceptions\PhoneException::MODE_NEGATIVE => array(
                Exceptions\PhoneException::STANDARD => 'is-phone-number',
            )
        );

        //-------

        Exceptions\StringException::$defaultTemplates = array(
            Exceptions\StringException::MODE_DEFAULT => array(
                Exceptions\StringException::STANDARD => 'not-string',
            ),
            Exceptions\StringException::MODE_NEGATIVE => array(
                Exceptions\StringException::STANDARD => 'is-string',
            )
        );

        //-------

        Exceptions\XdigitException::$defaultTemplates = array(
            Exceptions\XdigitException::MODE_DEFAULT => array(
                Exceptions\XdigitException::STANDARD => 'not-hex',
                Exceptions\XdigitException::EXTRA => 'not-hex-plus-{{additionalChars}}'
            ),
            Exceptions\XdigitException::MODE_NEGATIVE => array(
                Exceptions\XdigitException::STANDARD => 'is-hex',
                Exceptions\XdigitException::EXTRA => 'is-hex-plus-{{additionalChars}}'
            )
        );

        //-------

    } // function

} // class
