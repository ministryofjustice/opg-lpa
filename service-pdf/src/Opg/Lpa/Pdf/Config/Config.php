<?php

namespace Opg\Lpa\Pdf\Config;

use ArrayAccess;

/**
 * @template-implements ArrayAccess<string,mixed>
 */
class Config implements ArrayAccess
{
    private static $instance = null;

    private $container = null;

    private function __construct(array $config = null)
    {
        $allConfig = include('global.php');

        if (!is_null($config)) {
            $allConfig = self::merge($allConfig, $config);
        }

        $this->container = $allConfig;
    }

    public static function getInstance(?array $config = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return (isset($this->container[$offset]) ? $this->container[$offset] : null);
    }

    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays and preserveNumericKeys is false, the value
     * from the second array will be appended to the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the one of the first array.
     *
     * @param  array $a
     * @param  array $b
     * @param  bool  $preserveNumericKeys
     * @return array
     */
    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key]) || array_key_exists($key, $a)) {
                if (!$preserveNumericKeys && is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }
        return $a;
    }
}
