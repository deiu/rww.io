<?php

require_once('runtime.php');

$acc = urldecode($_REQUEST['domain']);
$domain = $_ENV['CLOUD_DATA'].'/'.$acc;

if (is_dir_empty($domain))
    httpStatusExit(200, 'OK');
else
    httpStatusExit(404, 'Not Found');
exit;
