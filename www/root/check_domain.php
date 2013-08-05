<?php

require_once('runtime.php');

$acc = urldecode($_REQUEST['domain']);
$domain = $_ENV['CLOUD_DATA'].'/'.$acc;

$empty = is_dir_empty($domain);
if (($empty == null) || ($empty == true))
    httpStatusExit(404, 'Not Found');
else
    httpStatusExit(200, 'OK');
exit;
