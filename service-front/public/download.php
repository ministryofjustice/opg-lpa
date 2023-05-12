<?php

$file = $_GET['file'];
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");

readfile('/tmp/' . $file);
exit();
