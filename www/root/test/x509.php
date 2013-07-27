<?php
header('Content-Type: text/plain');

if (isset($_SERVER['SSL_CLIENT_CERT'])) {
    $pem = $_SERVER['SSL_CLIENT_CERT'];
    $r = openssl_x509_parse($pem);
    print_r($r);
}
