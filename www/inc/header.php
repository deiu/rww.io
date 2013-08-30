<?php
/* header.php
 * page header
 *
 * $Id$
 *
 *  Copyright (C) 2013 RWW.IO
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal 
 *  in the Software without restriction, including without limitation the rights 
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 *  copies of the Software, and to permit persons to whom the Software is furnished 
 *  to do so, subject to the following conditions:

 *  The above copyright notice and this permission notice shall be included in all 
 *  copies or substantial portions of the Software.

 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

define('HEADER', 1);
if (!isset($TITLE)) {
    $TITLE = 'data cloud';
}

$user_link = sess('u:link');
$user_pic = '/common/images/nouser.png';

if (substr($_user, 0, 4) == 'dns:') {
    $user_name = $_user;
} else if (is_null(sess('u:name'))) {
    if (is_null($user_name) || !strlen($user_name))
        $user_name = $_user;
} else {
    $user_name = sess('u:name');
    $user_pic = sess('u:pic');
}

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?=$_SERVER['SERVER_NAME']?>: <?=$TITLE?></title>
    <link rel="stylesheet" href="/common/css/blueprint.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="/common/css/common.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="/common/css/font-awesome.min.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="/common/css/buttons.css" type="text/css" media="screen, projection" />
    <script src="/common/js/rdflib.js" type="text/javascript"></script>
    <script src="/common/js/prototype.js" type="text/javascript"></script>
    <script src="/common/js/common.js" type="text/javascript"></script>
    <script type="text/javascript">
    cloud.init({request_base:'<?=REQUEST_BASE?>',request_url:'<?=REQUEST_URL?>',user:'<?=$_user?>'});
    </script>
</head>
<body>
    <div id="alert" style="display: none;">
        <div id="alertbody" style="display: inline;"></div>
    </div>
    <div id="title" style="display: none;"><?=$TITLE?></div>
    
    <div id="topnav" class="topnav center">
    <img src="/common/images/logo.svg" class="logo-icon left" /><span class="title" title="Home"><a href="http://<?=ROOT_DOMAIN?>">RWW.I/O</a></span>
    <?php
    if ($_SERVER['SERVER_NAME'] != ROOT_DOMAIN) {
        if ($user_link) { ?>
            <div class="login">
                <span class="login-links">
                    <a class="white" href="<?=$user_link?>" target="_blank"><?=$user_name?></a><br />
                    <a class="white" href="?logout">Logout</a>
                </span>
                <a class="white" href="<?=$user_link?>" target="_blank">
                    <img class="login-photo img-border r3" src="<?=$user_pic?>" title="View profile" /></a>
            </div>
        <?php } else { ?> 
            <div class="login"> 
                <span class="login-links"><a class="white" href="https://<?=BASE_DOMAIN?>">WebID Login</a>
                <br/><a class="white" href="#" onclick="showWebID()">Get a WebID</a></span>
                <img class="login-photo" src="/common/images/nouser.png" />
            </div>
    <?php
        }
    }
    ?>
</div>
<?php
TAG(__FILE__, __LINE__, '$Id$');

