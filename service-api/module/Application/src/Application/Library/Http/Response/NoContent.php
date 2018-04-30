<?php

namespace Application\Library\Http\Response;

/**
 * Class NoContent
 * @package Application\Library\Http\Response
 */
class NoContent extends Json
{
    /**
     * NoContent constructor
     */
    public function __construct()
    {
        parent::__construct([], self::STATUS_CODE_204);
    }
}
