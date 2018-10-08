<?php
include_once ('../../../vendor/autoload.php');

use League\Csv\Reader;
use League\Csv\Writer;

$validUserIds = [];

$users = file('users.csv');
foreach($users as $user){
    $validUserIds[trim($user)] = true;
}

//---

$output = fopen('php://output', 'wb');
$errors = fopen('errors.txt', 'w');

// Recursively replace $date array items with the actual date string.
$map = function ($v) use (&$map) {
    if (is_array($v) && isset($v['$date'])) {
        return $v['$date'];

    } elseif (is_array($v) && isset($v['$numberLong'])) {
        return (int)$v['$numberLong'];

    } elseif(is_array($v)){
        return array_map($map, $v);

    }

    return $v;
};

$writer = Writer::createFromPath('php://output', 'w+');
$writer->setEnclosure("'");

$reader = Reader::createFromPath('applications-dump.csv', 'r');
$reader->setEscape("\\");
$records = $reader->getRecords();

$removed = 0;

$handle = fopen("applications-dump.json", "r");
while (($line = fgets($handle)) !== false) {
    //if ($offset === 0) {
    //    continue;
    //}

    $data = json_decode($line, true);
    $data = array_map($map, $data);

    //---

    if (!empty($data['user'])) {

        if (!isset($validUserIds[$data['user']])) {

            // If the user has been deleted, also remove this LPA.
            foreach($data as $k => &$v){
                if ($k != '_id') {
                    $v = '';
                }
            }

            $data['updatedAt'] = date('c');
            $removed++;
        }

    }

    $data = array_map(function ($v){
        if (is_array($v)) {
            return json_encode($v);
        } else {
            return $v;
        }
    }, $data);

    //---

    /*
        id bigint PRIMARY KEY,
        user text,
        "updatedAt" timestamp with time zone NOT NULL,
        "startedAt" timestamp with time zone,
        "createdAt" timestamp with time zone,
        "completedAt" timestamp with time zone,
        "lockedAt" timestamp with time zone,
        locked boolean,
        "whoAreYouAnswered" boolean,
        seed bigint,
        "repeatCaseNumber" bigint,
        document jsonb,
        payment jsonb,
        metadata jsonb,
        search text
     */

    $data = [
        (isset($data['_id'])) ? $data['_id'] : '',
        (isset($data['user'])) ? $data['user'] : '',
        (isset($data['updatedAt'])) ? $data['updatedAt'] : '',
        (isset($data['startedAt'])) ? $data['startedAt'] : '',
        (isset($data['createdAt'])) ? $data['createdAt'] : '',
        (isset($data['completedAt'])) ? $data['completedAt'] : '',
        (isset($data['lockedAt'])) ? $data['lockedAt'] : '',
        (isset($data['locked'])) ? $data['locked'] : '',
        (isset($data['whoAreYouAnswered'])) ? $data['whoAreYouAnswered'] : '',
        (isset($data['seed'])) ? $data['seed'] : '',
        (isset($data['repeatCaseNumber'])) ? $data['repeatCaseNumber'] : '',
        (isset($data['document'])) ? $data['document'] : '',
        (isset($data['payment'])) ? $data['payment'] : '',
        (isset($data['metadata'])) ? $data['metadata'] : '',
        (isset($data['search'])) ? $data['search'] : '',
    ];

    //---

    $writer->insertOne($data);
}


fclose($handle);
fclose($output);
fclose($errors);

file_put_contents('removed.txt', "{$removed} removed LPAs\n");

