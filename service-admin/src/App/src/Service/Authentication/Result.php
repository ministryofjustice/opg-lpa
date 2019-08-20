<?php

namespace App\Service\Authentication;

use Zend\Authentication\Result as ZendResult;

/**
 * Class Result
 * @package App\Service\Authentication
 */
class Result extends ZendResult
{
    const FAILURE_ACCOUNT_LOCKED = -403;
}
