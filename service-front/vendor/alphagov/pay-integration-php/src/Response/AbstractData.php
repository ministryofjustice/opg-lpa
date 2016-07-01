<?php
namespace Alphagov\Pay\Response;

use Alphagov\Pay\Exception;

abstract class AbstractData extends \ArrayObject {

    public function __construct( Array $details ){

        parent::__construct( $details, self::ARRAY_AS_PROPS );

    }

}
