<?php
require_once('../runtime.php');
header('Access-Control-Allow-Origin: *');
header('Content-Type: xrd+xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
$i_q = isset($_GET['q']) ? $_GET['q'] : exit();
if (false !== ($x = strpos($i_q, ':')))
    $i_q = substr($i_q, $x+1);
?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
    <Subject><?=$i_q?></Subject>
    <Link rel="remoteStorage" template="https://<?=strstr($i_q,'@',true)?>.<?=BASE_DOMAIN?>/{category}" api="simple" />
</XRD>
