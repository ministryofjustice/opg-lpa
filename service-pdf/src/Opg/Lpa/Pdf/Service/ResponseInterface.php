<?php

namespace Opg\Lpa\Pdf\Service;

use SplFileInfo;

interface ResponseInterface {

    public function send( SplFileInfo $pathToFile );

} // interface
