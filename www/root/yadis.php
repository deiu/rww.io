<?php
/* yadis.php
 * OpenID XRDS
 *
 * $Id$
 */

require_once('runtime.php');

header('Content-Type: application/xrds+xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<xrds:XRDS
  xmlns:xrds="xri://$xrds"
  xmlns:openid="http://openid.net/xmlns/1.0"
  xmlns="xri://$xrd*($v*2.0)">
  <XRD>
  <Service xmlns="xri://$xrd*($v*2.0)">
    <Type>http://specs.openid.net/auth/2.0/return_to</Type>
    <URI><?=REQUEST_BASE?>/rp_callback</URI>
  </Service>
  </XRD>
</xrds:XRDS>
