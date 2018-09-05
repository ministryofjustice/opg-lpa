<?php
namespace Application\Model\DataAccess\Postgres;

use PDO;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;

class AbstractBase {

    const TIME_FORMAT = 'Y-m-d\TH:i:s.uO'; // ISO8601 including microseconds

    private $pdo;

    public final function __construct()
    {

        $dbconf = [
            'adapter' => 'pgsql',
            'host'      => getenv('OPG_LPA_POSTGRES_HOSTNAME') ?: null,
            'port'      => getenv('OPG_LPA_POSTGRES_PORT') ?: null,
            'dbname'    => getenv('OPG_LPA_POSTGRES_NAME') ?: null,
            'username'  => getenv('OPG_LPA_POSTGRES_USERNAME') ?: null,
            'password'  => getenv('OPG_LPA_POSTGRES_PASSWORD') ?: null,
            'options' => [
                // Warning: RDS and ATTR_PERSISTENT are not friends.
                PDO::ATTR_PERSISTENT => false
            ]
        ];

        $dsn = "{$dbconf['adapter']}:host={$dbconf['host']};port={$dbconf['port']};dbname={$dbconf['dbname']}";

        $this->pdo = new PDO($dsn, $dbconf['username'], $dbconf['password'], $dbconf['options']);

        // Set PDO to throw exceptions on error
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    }

    protected function getPdo() : PDO
    {
        return $this->pdo;
    }

    /*
    protected function getZendDbXXX() : ZendDbAdapter
    {
        return new ZendDbAdapter([
            'driver'   => 'Pgsql',
            'hostname' => getenv('OPG_LPA_POSTGRES_HOSTNAME'),
            'port'     => getenv('OPG_LPA_POSTGRES_PORT'),
            'database' => getenv('OPG_LPA_POSTGRES_NAME'),
            'username' => getenv('OPG_LPA_POSTGRES_USERNAME'),
            'password' => getenv('OPG_LPA_POSTGRES_PASSWORD'),
        ]);
    }
    */

    protected function getZendDb() : ZendDbAdapter
    {
        $dbconf = [
            'adapter' => 'pgsql',
            'host'      => getenv('OPG_LPA_POSTGRES_HOSTNAME') ?: null,
            'port'      => getenv('OPG_LPA_POSTGRES_PORT') ?: null,
            'dbname'    => getenv('OPG_LPA_POSTGRES_NAME') ?: null,
            'username'  => getenv('OPG_LPA_POSTGRES_USERNAME') ?: null,
            'password'  => getenv('OPG_LPA_POSTGRES_PASSWORD') ?: null,
        ];

        $dsn = "{$dbconf['adapter']}:host={$dbconf['host']};port={$dbconf['port']};dbname={$dbconf['dbname']}";

        return new ZendDbAdapter([
            'dsn' => $dsn,
            'driver' => 'pdo',
            'username' => $dbconf['username'],
            'password' => $dbconf['password'],
            'driver_options' => [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        ]);
    }

}
