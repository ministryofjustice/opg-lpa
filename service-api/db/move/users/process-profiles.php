<?php

$row = 0;
if (($handle = fopen("profiles-dump.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {

        $row++;
        if ($row == 1) {
            continue;
        }

        //---

        $id = $data[0];

        $amended = [
            'name'      => json_decode($data[1], true),
            'address'   => json_decode($data[2], true),
            'dob'       => json_decode($data[3], true),
            'email'     => json_decode($data[4], true),
        ];

        // Remove Mongo's $date key
        $amended['dob']['date'] = $amended['dob']['date']['$date'];

        $str = json_encode($amended);

        addProfileToLine($id, $str);

    }

    fclose($handle);
}

//---

/**
 * Not efficient but is safe for large files.
 *
 * @param $id
 * @param $text
 */
function addProfileToLine($id, $text){

    $reading = fopen('users-converted.csv', 'r');
    $writing = fopen('users-converted.csv.tmp', 'w');

    $replaced = false;

    while (($data = fgetcsv($reading, 0, ",")) !== FALSE) {

        // Also check the identity isn't null, ensuring profiles of deleted users aren't copied over.

        if ($data[0] == $id && !empty($data[1])) {
            $data[16] = $text;
            $replaced = true;
        }

        fputcsv($writing, $data);
    }

    fclose($reading); fclose($writing);

    // Don't amend if nothing changed.
    if ($replaced) {
        rename('users-converted.csv.tmp', 'users-converted.csv');
    } else {
        unlink('users-converted.csv.tmp');
    }

}
