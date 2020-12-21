<?php
namespace Application\Logging;

use Laminas\Log\Processor\ProcessorInterface;

/**
 * Process any headers array in the $extra field, stripping sensitive
 * fields and moving any X-Trace-Id field up to the top-level of the logged
 * event as a trace_id property.
 *
 * For example, if we make a call like:
 *
 *    $headers = [
 *        'X-Trace-Id' => 'foo',
 *        'Cookie' => '...',
 *        'Content-Type' => 'application/json'
 *    ];
 *
 *    $this->getLogger()->err('an error', ['headers' => $headers]);
 *
 * This sets $extra to ['headers' => $headers], and this processor will receive
 * a $logEvent like:
 *
 *     ['extra' => ['headers' => $headers]]
 *
 * Given that structure, this processor will output a log event like this:
 *
 *    [
 *        'trace_id' => 'foo',
 *        'extra' => [
 *            'headers' => [
 *                'Content-Type' => 'application/json'
 *            ]
 *        ]
 *    ]
 *
 * i.e.
 * - strip out 'cookie' header
 * - promote X-Trace-Id to the top level of the log event and change key to trace_id
 * - retain other headers as-is on the event
 */
class HeadersProcessor implements ProcessorInterface
{
    /**
     * Name of the field in the $extra array passed to the logger.
     * If the $extra array contains this key, the value for the key is
     * retrieved and processed.
     */
    public const HEADERS_FIELD_NAME = 'headers';

    public const HEADERS_TO_STRIP = ['cookie', 'authorization', '_ga', '_gid', 'token'];

    public function process(array $logEvent): array
    {
        // early return if there's no "headers" in $extra
        if (!isset($logEvent['extra'][self::HEADERS_FIELD_NAME])) {
            return $logEvent;
        }

        $headers = $logEvent['extra'][self::HEADERS_FIELD_NAME];

        // headers; filter out any which potentially contain private data
        // and promote X-Trace-Id to top level property in $extra
        $headersArray = [];

        foreach ($headers as $name => $value) {
            $lcaseName = strtolower($name);

            if ($lcaseName === 'x-trace-id' && !is_null($value)) {
                $logEvent['trace_id'] = $value;
            }

            if (!(in_array($lcaseName, self::HEADERS_TO_STRIP))) {
                $headersArray[$name] = $value;
            }
        }

        // set fixed headers on $extra
        $logEvent['extra'][self::HEADERS_FIELD_NAME] = $headersArray;

        return $logEvent;
    }
}
