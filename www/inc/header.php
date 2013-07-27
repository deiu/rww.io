<?php
/* header.php
 * page header
 *
 * $Id$
 */

define('HEADER', 1);
if (!isset($TITLE)) {
    $TITLE = 'data cloud';
}
?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?=$_SERVER['SERVER_NAME']?>: <?=$TITLE?></title>
    <link rel="stylesheet" href="//<?=BASE_DOMAIN.$_options->base_url?>/common/css/blueprint.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="//<?=BASE_DOMAIN.$_options->base_url?>/common/css/common.css" type="text/css" media="screen, projection" />
    <script src="//w3.scripts.mit.edu/rdflib.js/dist/rdflib.js" type="text/javascript"></script>
    <script src="//<?=BASE_DOMAIN.$_options->base_url?>/common/js/prototype.js" type="text/javascript"></script>
    <script src="//<?=BASE_DOMAIN.$_options->base_url?>/common/js/common.js" type="text/javascript"></script>
    <script type="text/javascript">
    cloud.init({request_base:'<?=REQUEST_BASE?>',request_url:'<?=REQUEST_URL?>',user:'<?=$_user?>'});
    </script>
</head>
<body style="padding: 1.5em;">
    <div id="alert" style="position: absolute; top: 0; left: 0; width: 100%; padding-top: 5px; text-align: center; z-index: 1000; display: none;">
        <div id="alertbody" style="display: inline;"></div>
    </div>
    <div id="title" style="display: none;"><?=$TITLE?></div>
    <div id="container" style="position: relative;">
<?php
TAG(__FILE__, __LINE__, '$Id$');
