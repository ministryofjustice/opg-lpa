<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

class Collection extends Composite
{
    const MISSING_FIELD_ERROR = 1;
    const NO_SUCH_FIELD_ERROR = 2;

    protected static $errorNames = [
        self::MISSING_FIELD_ERROR => 'MISSING_FIELD_ERROR',
        self::NO_SUCH_FIELD_ERROR => 'NO_SUCH_FIELD_ERROR',
    ];

    public $fields = [];
    public $allowExtraFields = false;
    public $allowMissingFields = false;
    public $extraFieldsMessage = 'This field was not expected.';
    public $missingFieldsMessage = 'This field is missing.';

    public function getRequiredOptions()
    {
        return ['fields'];
    }

    protected function getCompositeOption()
    {
        return 'fields';
    }
}
