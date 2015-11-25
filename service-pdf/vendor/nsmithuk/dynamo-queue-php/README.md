# DynamoQueue - PHP

Overview
------------
DynamoQueue is a DynamoDB backed queue library. The PHP version provides both queueing and worker components.

DynamoQueue features:

* Each job will be received by one worker, once, and only once.
* The status of jobs in the queue can be tracked by their job ID.
* Jobs will be processed in the order\* in which they are added (\*see [Job Processing Order](#job-processing-order)).
* All the scalability benefits of Amazon’s DynamoDB.
* Jobs can be up to 400 KB in size (see [Messages](#messages)).


Installation
------------

Simplest is to add the following to `composer.json`:

```javascript
{
    "require": {
        "NSmithUK/dynamo-queue-php": "dev-master"
    }
}
```

And then run:

```bash
php composer.phar install
```

Initial Setup
------------
The first thing needed is to create a suitable table in DynamoDB. This can be done directly form the DynamoQueue Worker client.

```
bin/dynamo-queue --key <key> --secret <secret> --region <region> --table <name> --create
```

Where

* **key** is your AWS access key
* **secret** is your AWS access secret
* **region** is the AWS region you want the table to be in. e.g. eu-west-1, us-east-1, etc.
* **table** the name of the table to create

Alternatively to specifying the key and secret here, they can be specified as environment variables, or set via IAM roles for Amazon EC2 instances. [More details here](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html). 

A full list of supported regions can be found here: <http://docs.aws.amazon.com/general/latest/gr/rande.html#ddb_region>

_Note - This command only provisions 1 read and 1 write unit each for the Table and the Global Secondary Index. This is fine for testing but should be changed to a more suitable value for production._

Starting the worker
------------
Once a table has been created a worker can be started using the command
```
bin/dynamo-queue --key <key> --secret <secret> --region <region> --table <name>
```
Where

* **key** is your AWS access key
* **secret** is your AWS access secret
* **region** is the AWS region in which you created the table.
* **table** the name of the table you created

Alternatively to specifying the key and secret here, they can be specified as environment variables, or set via IAM roles for Amazon EC2 instances. [More details here](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html). 

Adding jobs to the queue
------------
Assuming you've installed DynamoQueue via Composer, the DynamoQueue client will be available via the autoloader.

```php
// First create an instance of the DynamoDbClient from Amazon’s SDK
$dynamoDb = new \Aws\DynamoDb\DynamoDbClient([
    'version' => '2012-08-10',
    'region' => '<region>',
    'credentials' => [
        'key'    => '<key>',
        'secret' => '<secret>',
    ]
]);

// Create an instance of the DynamoQueue client
$queue = new \DynamoQueue\Queue\Client( $dynamoDb, [ 'table_name' => '<table name>' ] );
```

You can then add jobs to the queue using 

```php
$queue->enqueue( $processor, $message )
```

Where

* **processor** is the Processor the Worker will use to execute the job. By default in PHP, this should be a full classname (including namespace) of a class that implements the `ProcessorInterface`, that is accessible via the autoloader within the Worker.
* **message** is a string containing the message/instruction to be passed to the Worker. For rich data we recommend this is a JSON string. Binary data is also supported for if you wish to compress and/or encrypt the data.

For example:

```php
$jobId = $queue->enqueue( 'DynamoQueueTests\Processors\EchoMessage', "Test Message" );
```

Alternatively you can specify your own unique jobId if you wish:
 
```php
$queue->enqueue( 'DynamoQueueTests\Processors\EchoMessage', "Test Message", "id-1" );
```


Messages
------------
As per DynamoDB limits, each individual job - including all metadata - cannot be greater than 400 KB.

Realistically however as AWS charge basically on a per KB basis for writes, it’s strongly recommended you make messages as small as possible; ideally less than 1 KB.

This can be aided by:

* Compressing messages before adding them to the queue; or
* Storing the main message elsewhere - in S3 or a database for example - then just passing a reference to the message through the queue.


Job Processing Order
------------
To aid scalability DynamoQueue supports distributing jobs across partitions. In DynamoQueue’s default configuration of a single partition, jobs are processed in exactly the order they were added to the queue. When more than one partition is used jobs are processed in approximately the order they were added. The greater the number of partitions used, the lower the correlation between when they were added and when they are processed.