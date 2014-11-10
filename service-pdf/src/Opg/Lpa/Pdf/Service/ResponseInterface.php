<?php

namespace Opg\Lpa\Pdf\Service;

use SplFileInfo, Exception;

interface ResponseInterface {

    /**
     * Saves the generated file to a central store.
     * Once this has returned successfully, it's safe to delete $file.
     *
     * @param SplFileInfo $pathToFile - The generated PDF file.
     * @throws Exception If for any reason the file cannot be saved.
     */
    public function save( SplFileInfo $file );

} // interface
