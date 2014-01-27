<?php
require_once('../../inc/config.php');
require_once('../runtime.php');
header('Content-Type: application/json');
$_POST = array_merge($_POST, json_decode(file_get_contents('php://input'), true));
extract($_GET, EXTR_PREFIX_ALL, 'i');
extract($_POST, EXTR_PREFIX_ALL, 'i');

$method = explode('?',basename(REQUEST_URL));
$method = $method[0];
if (strlen($method) < 1 ) {
    $method = $i_method;
}

$w = array(
    'method' => $method,
);
$r = array();

if ($method == 'storageStatus') {
    $r['storageName'] = $i_storageName;
    $dir = $_ENV['CLOUD_DATA'].'/'.$r['storageName'].'.'.ROOT_DOMAIN;
    $files = scandir($dir);
    $r['available'] = sizeof($files)<=1;
}

if ($method == 'accountStatus') {
    $r['accountName'] = $i_accountName;
    $dir = $_ENV['CLOUD_DATA'].'/'.$r['accountName'].'.'.ROOT_DOMAIN;
    $files = scandir($dir);
    $r['available'] = sizeof($files)<=1;
}

$w['status'] = 'success';
$w['response'] = $r;
echo json_encode($w);
