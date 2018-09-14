<?php

$output = fopen('php://output', 'wb');

$row = 1;
if (($handle = fopen("users-dump.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {

        if ($row != 1 && !empty($data[12])) {
            $json = json_decode($data[12], true);
            $amended = [];

            foreach ($json as $value) {
                $amended[$value] = true;
            }

            $data[12] = json_encode($amended);
        }

        $data[13] = '';
        $data[14] = '';
        $data[15] = '';
        $data[16] = '';

        fputcsv($output, $data);

        $row++;
    }
    fclose($handle);
}


fclose($output);
