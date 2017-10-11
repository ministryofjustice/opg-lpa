<?php

namespace OpgTest\Lpa\DataModel;

use PHPUnit\Framework\TestCase;

class TestHelper
{
    /**
     * @param $errors
     * @param TestCase $testCase
     */
    public static function assertNoDuplicateErrorMessages($errors, $testCase)
    {
        $duplicatesFound = false;
        $duplicates = array();
        $messages = array();
        foreach ($errors as $errorKey => $errorValue) {
            $error = $errors[$errorKey];
            foreach ($error['messages'] as $messageIndex => $messageValue) {
                $messageKey = "{$errorKey}-{$messageValue}";
                if (array_key_exists($messageKey, $messages)) {
                    $duplicatesFound = true;
                    $duplicate = "$messages[$messageKey]|[{$errorKey}][{$messageIndex}]={$messageValue}";
                    array_push($duplicates, $duplicate);
                } else {
                    $messages[$messageKey] = "[{$errorKey}][{$messageIndex}]={$messageValue}";
                }
            }
        }
        if ($duplicatesFound) {
            $testCase->fail('The following messages were duplicated: ' . implode(', ', $duplicates));
        }
    }
}
