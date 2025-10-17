<?php

use Laminas\Db\Adapter\Adapter as ZendDbAdapter;

class Helpers
{
    // JSON configuration loaded from config.json file
    private static $config = null;

    // database adapter
    private static $db = null;

    /**
     * @param $scope Only return specified subset of config
     * @return array
     */
    public static function getConfig(string $scope = null)
    {
        if (self::$config == null) {
            // build config from JSON file
            $json_str = file_get_contents(__DIR__ . '/config.json');
            self::$config = json_decode($json_str, true);
        }

        if ($scope !== null) {
            // return subset
            return self::$config[$scope];
        }

        return self::$config;
    }

    /**
     * Get a database adapter
     * @return ZendDbAdapter
     */
    public static function getDbAdapter()
    {
        // return adapter if already instantiated
        if (self::$db !== null) {
            return self::$db;
        }

        $dbconf = self::getConfig('db');

        $dsn = "{$dbconf['adapter']}:host={$dbconf['host']};port={$dbconf['port']};dbname={$dbconf['dbname']}";

        self::$db = new ZendDbAdapter([
            'dsn' => $dsn,
            'driver' => 'pdo',
            'username' => $dbconf['username'],
            'password' => $dbconf['password'],
            'driver_options' => [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        ]);

        return self::$db;
    }
}
