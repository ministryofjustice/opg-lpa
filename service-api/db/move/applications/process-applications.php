<?php
include_once ('../../../vendor/autoload.php');

$output = fopen('php://output', 'wb');
$errors = fopen('errors.txt', 'w');

// Recursively replace $date array items with the actual date string.
$map = function ($v) use (&$map) {
    if (is_array($v) && isset($v['$date'])) {
        return $v['$date'];

    } elseif(is_array($v)){
        return array_map($map, $v);

    }

    return $v;
};

$row = 0;
if (($handle = fopen("applications-dump.csv", "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $row++;
        if ($row === 1) {
            continue;
        }

        //---

        if (!empty($data[1])) {

            if (!empty($data[11])) {
                $document   = json_decode($data[11], true);

                if (!is_array($document)) {
                    fwrite($errors, "{$data[0]} : ".var_export($data[11], true)."\n");
                    continue;
                }

                // Map the dates
                $document = array_map($map, $document);

                $data[11] = json_encode($document);
            }

            if (!empty($data[12])) {
                $payment   = json_decode($data[12], true);

                // Map the dates
                $payment = array_map($map, $payment);

                $data[12] = json_encode($payment);
            }

            if (!empty($data[13])) {
                $metadata   = json_decode($data[13], true);

                // Map the dates
                $metadata = array_map($map, $metadata);

                $data[13] = json_encode($metadata);
            }


        }

        //---

        fputcsv($output, $data);
    }

    fclose($handle);
}


fclose($output);
fclose($errors);
