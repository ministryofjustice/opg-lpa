<?php
namespace MinistryOfJustice\PostcodeInfo\Response;


abstract class AbstractData extends \ArrayObject {

    public function __construct( Array $details ){

        parent::__construct( $details, self::ARRAY_AS_PROPS );

    }

}
