<?php

namespace Opg\Lpa\Pdf\Worker\Response;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;
use SplFileInfo;

/**
 * Abstract response class for generated PDF files
 */
abstract class AbstractResponse
{
    /**
     * Document ID value
     *
     * @var
     */
    protected $docId;

    /**
     * Config to use with the response
     *
     * @var
     */
    protected $config;

    /**
     * Logger utility
     *
     * @var Logger
     */
    protected $logger;

    /**
     * AbstractResponse constructor
     *
     * @param $docId
     */
    public function __construct($docId)
    {
        $this->docId = $docId;
        $this->config = Config::getInstance();
        $this->logger = Logger::getInstance();
    }

    /**
     * Abstract function to be implemented in the child class to save the file in an appropriate way
     *
     * @param SplFileInfo $file
     */
    abstract public function save(SplFileInfo $file);

    /**
     * Echo a string message in the console using the document ID prefix
     *
     * @param $message
     */
    protected function logToConsole($message)
    {
        echo $this->docId . ': ' . $message . "\n";
    }
}
