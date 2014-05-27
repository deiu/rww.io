<?php
/* index.php
 * application index
 */

require_once('runtime.php');
header('X-XRDS-Location: '.REQUEST_BASE.'/yadis');
defined('HEADER') || include_once('header.php');

?>
<div class="box welcome center width-1024">
 <strong> Welcome!</strong> This <a class="white link" target="_blank" href="http://www.w3.org/DesignIssues/ReadWriteLinkedData.html">Read/Write</a> <a class="white link" target="_blank" href="http://www.w3.org/DesignIssues/LinkedData.html">Linked Data</a> service is free (and open-source) for educational and personal use.
</div>
<div id="onboarding"></div>
<div class="box center width-1024">
    <br/>
    <h3>&nbsp;You don't have an account yet? <button onclick="javascript: window.open('onboard/signup.html','','status=yes,toolbar=no,menubar=no,location=no,width=500,height=410,left=100,top=100');">Sign up!</button>
    </h3>
</div>
<?php
require('help.php');

TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
