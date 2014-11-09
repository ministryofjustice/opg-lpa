<?php

namespace Opg\Lpa\Pdf\Service;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;

class Generator implements GeneratorInterface {

    const TYPE_FORM_XXX = 'xxx';

    public function __construct( Config $config, $type, Lpa $lpa, ResponseInterface $response ){

    }

    /**
     * Returns bool true iff the document was successfully generated and passed to $response->send().
     * Otherwise returns a string describing the error is returned.
     *
     * @return bool|string
     */
    public function generate(){

        return true;

    }

} // class
