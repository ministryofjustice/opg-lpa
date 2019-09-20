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
        if(in_array($object['Key'], $seenKeys)){
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

        print_r($seenKeys);
        echo "-------------------------------------------------------------" . "\n";
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
    //echo "Body Content is : -> " .$bodyContent . "\n";
    //echo "Subject to be searched  is : -> " .$subject . "\n";
    //echo "Type is : -> " .$type . "\n";

    $regex = '|(https:\/\/\S+' . $linkRegex . '\/[a-zA-Z0-9]+)|sim';
    //echo "Regex is........." .$regex . "\n";

    if (preg_match($regex, $bodyContent, $matches) > 0) {
        $activationLink = $matches[1];
        echo "Activation link is ......: ->" . $activationLink . "\n";


        if (!is_null($activationLink)) {
            //caspertests+" + userNumber + "@lpa.opg.service.justice.gov.uk";
            $toEmail = 'caspertests+1234@lpa.opg.service.justice.gov.uk'; //to change and add regex
            echo "toEmail-------------------" . $toEmail . "\n";

            if (preg_match($toEmail, $bodyContent, $matches) > 0) {
                $toEmail = $matches[1];
                echo "To Email is........." . $toEmail . "\n";
            }

            $userId = getPlusPartFromEmailAddress($toEmail);
            echo "User ID .............." .$userId . "\n";

            $contents = $toEmail . ',' . $activationLink;
            echo "Contents is.............." .$contents . "\n";

            //TO-DO : CORRECT PATH HERE
            // file_put_contents('/mnt/test/activation_emails/' . $userId . '.' . $type, $contents);
            file_put_contents('/Users/seemamenon/OPG/opg-lpa/lpa-online/opg-lpa/tests/functional/activation_emails/' . $userId . '.' . $type, $contents);

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

//**************************************************************************
//Connect to AWS [based on role]

// Iterate through objects in bucket passing bucket name
//Get list of each object within the bucket using key
//Get body of each object
    //Parse through body of each object to find activation link
    //fetch body of email using regex
    //check activation link + activation token present in the email body for the user
    //file put contents [write string to file]

//store key in array
//iterate ech time based on value in array
//**************************************************************************




