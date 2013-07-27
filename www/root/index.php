<?php
/* index.php
 * application index
 *
 * $Id$
 */

require_once('runtime.php');

header('X-XRDS-Location: '.REQUEST_BASE.'/yadis');
defined('HEADER') || include_once('header.php');
?>
<?php if ($_options->open) { ?>
<div id="welcome" class="box" style="margin-right: 2em;">
<strong>Welcome!</strong> This <a target="_blank" href="http://www.w3.org/DesignIssues/ReadWriteLinkedData.html">Read/Write</a> <a target="_blank" href="http://www.w3.org/DesignIssues/LinkedData.html">Linked Data</a> service is free (and open-source) for educational and personal use.
</div>
<?php }
include('help.php');
TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
