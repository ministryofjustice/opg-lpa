<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Response;

abstract class AbstractData extends \ArrayObject
{
    /**
     * @param array<mixed> $details
     */
    public function __construct(array $details)
    {
        parent::__construct($details, self::ARRAY_AS_PROPS);
    }
}
