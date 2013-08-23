<?php

require_once('runtime.php');

$acc = urldecode($_REQUEST['domain']);
$domain = $_ENV['CLOUD_DATA'].'/'.$acc;


if (!is_dir($domain))
    httpStatusExit(404, 'Not Found');
else
    httpStatusExit(200, 'OK');
exit;
