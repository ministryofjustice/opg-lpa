<?php
include_once ('../../../vendor/autoload.php');

use MongoDB\BSON\ObjectID as MongoId;

$output = fopen('php://output', 'wb');

$row = 0;
if (($handle = fopen("whoareyou-dump.csv", "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $row++;
        if ($row === 1) {
            continue;
        }

        //---

        // Extract the MongoID
        $id = rtrim(ltrim($data[0], 'ObjectId('), ')');
        $mId = new MongoId($id);

        // Extract the time from the ID, and add it as a new field.
        $data[3] = date('c', $mId->getTimestamp());

        // Drop the MongoID
        unset($data[0]);

        fputcsv($output, $data);
    }

    fclose($handle);
}


fclose($output);
