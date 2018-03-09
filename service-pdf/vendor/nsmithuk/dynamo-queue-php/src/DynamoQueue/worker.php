<?php

$files = array(
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
);

$found = false;
foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

//------------------------------------

cli\Colors::enable();

$strict = in_array('--strict', $_SERVER['argv']);
$arguments = new cli\Arguments(compact('strict'));

$arguments->addOption(array('endpoint'), [
        'description' => '(Optional) The AWS region to use'
    ]
);

$arguments->addOption(array('region'), [
        'description' => 'The AWS region to use'
    ]
);

$arguments->addOption(array('table'), [
        'description' => 'The DynamoDB table to use'
    ]
);

$arguments->addOption(array('key'), [
        'description' => 'The AWS key to use'
    ]
);

$arguments->addOption(array('secret'), [
        'description' => 'The AWS secret to use'
    ]
);

$arguments->addOption(array('ttl'), [
        'description' => "The number of milliseconds to leave a job before it's removed during a cleanup"
    ]
);

$arguments->addFlag(array('create'), 'Create the table in DynamoDB');

$arguments->addFlag(array('cleanup'), 'Cleanup the table in DynamoDB');

$arguments->addFlag(array('v'), 'Set the system log level to Debug');

$arguments->parse();


//------------------------------------
// Setup Logging

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;


if ($arguments['v']) {
    $stream = new StreamHandler('php://stdout', Logger::DEBUG);
} else {
    $stream = new StreamHandler('php://stdout', Logger::INFO);
}


$formatter = new LineFormatter();
$stream->setFormatter($formatter);

$logger = new Logger('DynamoQueue');
$logger->pushHandler($stream);


//------------------------------------
// Create the DynamoDbClient Client

$settings = [
    'version' => '2012-08-10',
];

if ($arguments['endpoint']) {
    $settings['endpoint'] = trim($arguments['endpoint']);
}

if ($arguments['region']) {
    $settings['region'] = trim($arguments['region']);
} else {
    cli\err( '%r--region is required%n' );
    cli\line( '%yA list of available public regions and endpoints can be found at http://docs.aws.amazon.com/general/latest/gr/rande.html#ddb_region%n' );
    exit(1);
}

if( $arguments['key'] && $arguments['secret'] ){
    $settings['credentials'] = [
        'key'    => trim($arguments['key']),
        'secret' => trim($arguments['secret']),
    ];
}

$dynamoDb = new \Aws\DynamoDb\DynamoDbClient( $settings );


//------------------------------------
// Create the Queue Client

$config = array();

if ($arguments['table']) {
    $config['table_name'] = trim($arguments['table']);
} else {
    cli\err( '%r--table is required%n' );
    cli\line( '%yThe name of the DynamoDB table to use%n' );
    exit(1);
}

$queue = new \DynamoQueue\Worker\Client( $dynamoDb, $config );

//------------------------------------
// If we're creating the table

if ($arguments['create']) {

    try {

        $queue->createTable( $config['table_name'] );
        cli\line( "%gTable '{$config['table_name']}' has been created in {$settings['region']}%n" );

    } catch( Exception $e ){
        cli\err( "%rUnable to create table in DynamoDB: ". $e->getMessage() ."%n" );
        exit(1);
    }

    exit(0);

}

//------------------------------------
// If we're performing a table cleanup

if ($arguments['cleanup']) {

    try {

        $ttl = ($arguments['ttl'] && is_numeric($arguments['ttl'])) ? (int)$arguments['ttl'] : 0;

        $deletedJobs = $queue->cleanupTable( $ttl );
        cli\line( "%g{$deletedJobs} jobs tidied up from '{$config['table_name']}'%n" );

    } catch( Exception $e ){
        cli\err( "%rUnable to cleanup table: ". $e->getMessage() ."%n" );
        exit(1);
    }

    exit(0);

}

//------------------------------------
// Validate the table (and connection)

try {

    $valid = $queue->isTableValid();

    if( is_array($valid) ){

        $logger->alert( "Errors were found when validating the table is setup correctly", $valid );
        exit(1);

    }

} catch ( Exception $e ){

    $logger->alert( "Unable to connect to DynamoDB (and validate the table)", [ 'exception'=>$e ] );
    exit(1);

}

//------------------------------------
// Create the process handler

#TODO - This will become a config option so custom Handlers can be used.

$handler = new \DynamoQueue\Worker\Handler\Autoloader();

//------------------------------------
// Create the (a) Worker

$worker = new \DynamoQueue\Worker\Worker( $queue, $handler, $logger );

//---

declare(ticks = 1);
pcntl_signal(SIGTERM, array($worker, 'stop'));
pcntl_signal(SIGINT, array($worker, 'stop'));
pcntl_signal(SIGQUIT, array($worker, 'stop'));

//---

try {

    $logger->notice( "Worker started" );

    $okay = $worker->run();

} catch ( Exception $e ){

    $logger->emergency( "An unknown exception has caused the queue to terminate", [ 'exception'=>$e ] );
    exit(1);

}

//---

$logger->notice( "Worker stopped" );

if( $okay ){
    exit( 0 );
} else {
    exit( 1 );
}
