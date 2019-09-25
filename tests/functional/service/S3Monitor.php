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

require '../vendor/autoload.php';

use Aws\Sts\StsClient;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

$seenKeys = [];

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

    //check for env variable CI
     $envVar = getenv('CI');
     echo "The environmanet variable is ......". $envVar . "\n";

    if (true === (getenv('CI'))) {
        echo " Inside IF ......" . "\n";
        $roleToAssumeArn = 'arn:aws:iam::050256574573:role/ci';
    }
    else{
        $roleToAssumeArn = 'arn:aws:iam::050256574573:role/operator';
    }
    echo "the role is .................. " . $roleToAssumeArn . "\n";

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

    while(true) {
        $iterator = $s3Client->getIterator('ListObjects', array('Bucket' => $bucketName));

        echo "Checking objects in S3.......". "\n";

        foreach ($iterator as $object) {
            if (in_array($object['Key'], $seenKeys)) {
                continue;
            }
            $result = $s3Client->getObject([
                'Bucket' => $bucketName,
                'Key' => $object['Key'],
            ]);

            //The content of email is in Quoted-Printable encoding and uses = as an escape character. Hence decoding to match regex
            $bodyContent = quoted_printable_decode($result["Body"]);

            parseBody(
                $bodyContent,
                'Activate your lasting power of attorney account',
                'activation',
                'signup\/confirm'
            );

            parseBody(
                $bodyContent,
                'Password reset request',
                'passwordreset',
                'forgot-password\/reset'
            );

            $seenKeys[] = $object['Key'];
        }
        sleep(5);
    }


} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}

/**
 * @param inbox
 * @param subject
 * @param directory
 * @param overview
 * @param message
 * @param activationLink
 * @param toEmail
 * @param userId
 * @param contents
 */

function parseBody($bodyContent, $subject, $type, $linkRegex)
{
    $regex = '|(https:\/\/\S+' . $linkRegex . '\/[a-zA-Z0-9]+)|sim';

    if (preg_match($regex, $bodyContent, $matches) > 0) {
        $activationLink = $matches[1];

        if (!is_null($activationLink)) {
            $emailRegex = '|To: (.+)|m'; //to change and add regex

            if (preg_match($emailRegex, $bodyContent, $emailMatches) > 0) {
                $toEmail = $emailMatches[1];
            }

            $userId = getPlusPartFromEmailAddress($toEmail);
            $contents = $toEmail . ',' . $activationLink;

            if (!is_null($contents)) {
                //TO-DO : CORRECT PATH HERE
                file_put_contents('/mnt/test/functional/activation_emails/' . $userId . '.' . $type, $contents);
                //file_put_contents('/Users/seemamenon/OPG/opg-lpa/lpa-online/opg-lpa/tests/functional/activation_emails/' . $userId . '.' . $type, $contents);
            }
            echo 'Found email for user ' . $userId . PHP_EOL;

        } else {
            echo 'Message: "' . $subject . '" does not match regex ' . $regex . PHP_EOL;
            echo '----------------------------------------------------------------------------------';
            //echo $bodyContent . PHP_EOL;
            echo '----------------------------------------------------------------------------------';
        }
    }
}
/**
 * Extract the plus part from emails of the form:
 * basename+pluspart@example.com
 *
 * @param string $email
 */
function getPlusPartFromEmailAddress($email)
{
    $plusPos = strpos($email, '+');
    $atPos = strpos($email, '@');
    $userIdLength = $atPos - $plusPos - 1;
    $userId = substr($email, $plusPos + 1, $userIdLength);

    return $userId;
}





