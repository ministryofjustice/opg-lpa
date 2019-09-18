<?php
/**
 * Copyright 2010-2019 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * This file is licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License. A copy of
 * the License is located at
 *
 * http://aws.amazon.com/apache2.0/
 *
 * This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 *
 *
 *
 */

require '../../vendor/autoload.php';


use Aws\Sts\StsClient;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

/**
 * Assume Role
 *
 * This code expects that you have AWS credentials set up per:
 * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html
 */
$client = new StsClient([
    'region' => 'eu-west-1',
    'version' => 'latest'
]);

//AWS Info
//assumed current role - arn:aws:iam::050256574573:role/operator

//TO-DO
//check for env variable CI
//$roleToAssumeArn = 'arn:aws:iam::050256574573:role/ci';

$roleToAssumeArn = 'arn:aws:iam::050256574573:role/operator';

try {
    $result = $client->assumeRole([
        'RoleArn' => $roleToAssumeArn,
        'RoleSessionName' => 'session1'
    ]);
    // output AssumedRole credentials, you can use these credentials
    // to initiate a new AWS Service client with the IAM Role's permissions

    $s3Client = new S3Client([
        'version'     => 'latest',
        'region'      => 'eu-west-1',
        'credentials' =>  [
            'key'    => $result['Credentials']['AccessKeyId'],
            'secret' => $result['Credentials']['SecretAccessKey'],
            'token'  => $result['Credentials']['SessionToken']
        ]
    ]);

    $bucketName = 'opg-lpa-casper-mailbox';

    $iterator = $s3Client->getIterator('ListObjects', array('Bucket' => $bucketName));
    foreach ($iterator as $object) {
        $result = $s3Client->getObject([
            'Bucket' => $bucketName,
            'Key' => $object['Key']
        ]);

        echo $result["Body"] . "\n";
    }

} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}




//Connect to AWS

//Get Inbox - done
//Grab emails - done
    //search emails using subject
    //fetch body of email using regex
    //check activation link present in the email body for the user
    //file put contents

