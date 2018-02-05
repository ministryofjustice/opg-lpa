<?php

namespace Opg\Lpa\Logger;

use Zend\Log\Logger as ZendLogger;
use Zend\Log\Writer\Stream;

/**
 * class Logger
 *
 * A simple logstash file logger
 */
class Logger extends ZendLogger
{
    /**
     * @var Logger
     */
    private static $instance = null;

    /**
     * @var Formatter\Logstash
     */
    private $formatter;

    /**
     * Logger constructor
     *
     * @param string|null $fileLogPath
     * @param string|null $sentryUri
     */
    public function __construct(string $fileLogPath = null, string $sentryUri = null)
    {
        parent::__construct();

        $this->formatter = new Formatter\Logstash();

        //  If contractor values have not been provider then try to get values from the application config
        if (empty($fileLogPath)) {
            $fileLogPath = getenv('OPG_LPA_COMMON_APPLICATION_LOG_PATH') ?: '/var/log/application.log';
        }

        $this->setFileLogPath($fileLogPath);

        if (empty($sentryUri)) {
            $sentryUri = getenv('OPG_LPA_COMMON_SENTRY_API_URI') ?: null;
        }

        if (empty(!$sentryUri)) {
            $this->setSentryUri($sentryUri);
        }
    }

    /**
     * Singleton provider for logger
     * Required so logger can be loaded in all services including none ZF2
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Destroy the logger
     */
    public static function destroy()
    {
        self::$instance = null;
    }

    /**
     * @param $logFilename
     * @return ZendLogger
     */
    public function setFileLogPath($logFilename)
    {
        $streamWriter = new Stream($logFilename);

        return $this->addWriter($streamWriter);
    }

    /**
     * @param string $sentryUri
     * @return ZendLogger
     */
    public function setSentryUri(string $sentryUri)
    {
        $sentryWriter = new Writer\Sentry($sentryUri);

        return $this->addWriter($sentryWriter);
    }

    /**
     * @param $clientConfig
     * @param $endpoints
     * @return ZendLogger
     */
    public function setSnsCredentials($clientConfig, $endpoints)
    {
        $snsWriter = new Writer\Sns($clientConfig, $endpoints);

        return $this->addWriter($snsWriter);
    }

    /**
     * @param string|\Zend\Log\Writer\WriterInterface $logWriter
     * @param int $priority
     * @param array|null $options
     * @return ZendLogger
     */
    public function addWriter($logWriter, $priority = 1, array $options = null)
    {
        $logWriter->setFormatter($this->formatter);

        return parent::addWriter($logWriter);
    }
}
