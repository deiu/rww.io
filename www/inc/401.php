<?php
/* 401.php
 * application HTTP 401 page
 *
 * $Id$
 */

defined('HEADER') || include_once('header.php');
?>
<div class="cleft left error">
You must login to access this URL
</div>

<div class="cleft left info">
If you just installed a new SSL certificate, try <a target="_blank" href="http://code.google.com/p/chromium/issues/detail?id=29784">restarting your browser</a> to trigger its selection
</div>

<div class="cleft">
<a target="_blank" href="http://getfirefox.org/">Firefox</a> 3.5+, currently shipping Safari, IE, and Chrome builds are known to work
</div>

<?php
TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
