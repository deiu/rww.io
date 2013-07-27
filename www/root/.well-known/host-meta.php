<?php
require_once('../runtime.php');
header('Access-Control-Allow-Origin: *');
header('Content-Type: xrd+xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0" xmlns:hm="http://host-meta.net/xrd/1.0">
  <hm:Host xmlns="http://host-meta.net/xrd/1.0"><?=BASE_DOMAIN?></hm:Host>
  <Link rel="lrdd" template="http://<?=BASE_DOMAIN?>/.well-known/webfinger?q={uri}" />
</XRD>
