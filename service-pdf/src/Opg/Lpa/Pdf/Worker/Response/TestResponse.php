<?php

namespace Opg\Lpa\Pdf\Worker\Response;

use SplFileInfo;

/**
 * Generates PDF files and stores them in a location on the file system
 */
class TestResponse extends AbstractResponse
{
    /**
     * Store the file on the passed path for retrieval by the API service.
     *
     * @param SplFileInfo $file
     */
    public function save(SplFileInfo $file)
    {
        $this->logToConsole('Response received: ' . $file->getRealPath());

        $filesPath = $this->config['worker']['testResponse']['path'];

        //  If the folder for the files is not present create it now
        if (!\file_exists($filesPath)) {
            mkdir($filesPath, 0777, true);
        }

        $targetFilePath = realpath($filesPath) . "/{$this->docId}.pdf";

        //  If the file can be found then copy it to the target location
        if (\file_exists($file->getPathname())) {
            copy($file->getPathname(), $targetFilePath);

            $this->logToConsole('File saved to ' . $targetFilePath);
        } else {
            $this->logToConsole('ERROR: Failed to save to ' . $targetFilePath);
        }
    }
}
