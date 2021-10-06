<?php

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
            $emailRegex = '|To: (.+\+.+)|m'; //to change and add regex

            if (preg_match($emailRegex, $bodyContent, $emailMatches) > 0) {
                $toEmail = $emailMatches[1];
            }

            $userId = getPlusPartFromEmailAddress($toEmail);
            $contents = $toEmail . ',' . $activationLink;

            if (!is_null($contents)) {
                file_put_contents(
                    '/mnt/test/functional/activation_emails/' . $userId . '.' . $type,
                    $contents
                );
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
