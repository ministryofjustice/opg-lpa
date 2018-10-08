<?php

$profiles = [];

$row = 0;
if (($handle = fopen("profiles-dump.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $row++;
        if ($row == 1) {
            continue;
        }

        $id = $data[0];

        $amended = [
            'name'      => (!empty($data[1])) ? json_decode($data[1], true) : null,
            'address'   => (!empty($data[2])) ? json_decode($data[2], true) : null,
            'dob'       => (!empty($data[3])) ? json_decode($data[3], true) : null,
            'email'     => (!empty($data[4])) ? json_decode($data[4], true) : null,
        ];

        // Remove Mongo's $date key
        if (isset($amended['dob']['date']['$date'])) {
            $amended['dob']['date'] = $amended['dob']['date']['$date'];
        }

        $profiles[$id] = $amended;
    }
}
fclose($handle);

//--------------

$output = fopen('php://output', 'wb');

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

$row = 0;
if (($handle = fopen("users-dump.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $row++;
        if ($row === 1) {
            continue;
        }

        if ($data[4] === 'Y') {
            $data[4] = 'true';
        }

        for ($i = 6; $i <= 11; $i++) {
            if (is_numeric($data[$i])) {
                $data[$i] = date('c', $data[$i]);
            }
        }

        if ($row != 1 && !empty($data[12])) {
            $json = json_decode($data[12], true);
            $amended = [];

            foreach ($json as $value) {
                $amended[$value] = true;
            }

            $data[12] = json_encode($amended);
        }

        // Map dates to correct format
        // Email update
        if (!empty($data[14])) {
            $data[14] = json_encode(array_map($map, json_decode($data[14], true)));
        } else {
            $data[14] = '';
        }

        // Password reset
        if (!empty($data[13])) {
            $data[15] = json_encode(array_map($map, json_decode($data[13], true)));
        } else {
            $data[15] = '';
        }

        // Clear these fields
        $data[13] = '';

        // Include profile data
        if (isset($profiles[$data[0]])) {
            $data[16] = json_encode($profiles[$data[0]]);
        } else {
            $data[16] = '';
        }


        fputcsv($output, $data);

        $row++;
    }
    fclose($handle);
}


fclose($output);
