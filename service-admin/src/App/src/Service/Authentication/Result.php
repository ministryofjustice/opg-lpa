<?php

namespace App\Service\Authentication;

use Laminas\Authentication\Result as ZendResult;

/**
 * Class Result
 * @package App\Service\Authentication
 */
class Result extends ZendResult
{
    public const FAILURE_ACCOUNT_LOCKED = -403;
}
