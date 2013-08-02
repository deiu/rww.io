<?php
/* runtime.php
 * cloud common includes
 *
 * $Id$
 */

define('METHODS_S', 'GET, PUT, POST, OPTIONS, HEAD, MKCOL, DELETE, PATCH');

require_once(dirname(__FILE__).'/../inc/runtime.inc.php');

// cookie for displaying .meta files in the list
if (!isset($_COOKIE['showMetaFiles'])) {
    setcookie('showMetaFiles', true, time()+3600);
} 

$_showMetaFiles = (isset($_COOKIE['showMetaFiles']))?$_COOKIE['showMetaFiles']:false;
$_checkedShowMeta = ($_showMetaFiles == true)?'checked':'';

$_RAW_EXT = array(
    'css'=>'text',
    'htm'=>'text',
    'html'=>'text',
    'js'=>'text',
    'jpg'=>'image',
    'jpeg'=>'image',
    'png'=>'image',
    'gif'=>'image',
    'txt'=>'text');

header("User: $_user");

// Cloud
if (!isset($_SERVER['SCRIPT_URL']))
    $_SERVER['SCRIPT_URL'] = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
if (strpos($_SERVER['SCRIPT_URL'], '?'))
    $_SERVER['SCRIPT_URL'] = strstr($_SERVER['SCRIPT_URL'], '?', true);
if (!isset($_SERVER['SCRIPT_URI']))
    $_SERVER['SCRIPT_URI'] = REQUEST_BASE.$_SERVER['SCRIPT_URL'];
$_base = $_SERVER['SCRIPT_URI'];
$_domain = $_SERVER['SERVER_NAME'];

// Graph
$_filebase = $_ENV['CLOUD_DATA'].'/'.$_SERVER['SERVER_NAME'];
$_filename = $_SERVER['SCRIPT_URL'];
$_filename_ext = strrpos($_filename, '.');
$_filename_ext = $_filename_ext ? substr($_filename, 1+$_filename_ext) : '';
if (!strlen($_filename) || $_filename[0] != '/')
    $_filename = "/$_filename";
if (substr($_filename, 0, strlen($_filebase)) != $_filebase)
    $_filename = "$_filebase$_filename";
$_request_path = substr($_filename, strlen($_filebase));

if ($_options->debug) {
    header('Filename: '.$_filename);
}

// Web Access Control
$_wac = new WAC($_user, $_filename, $_filebase, $_base, $_options);

// WebDAV
header('MS-Author-Via: DAV, SPARQL');

// HTTP Access Control
if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header('Access-Control-Allow-Methods: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $t = explode('/', $_SERVER['HTTP_ORIGIN']);
    if (count($t) > 2) {
        $n = "{$t[0]}//{$t[2]}";
    } else {
        $n = '*';
    }
    header('Access-Control-Allow-Origin: '.$n);
    header('Access-Control-Allow-Credentials: true');
}


// HTTP Methods
$_method = '';
foreach (array('REQUEST_METHOD', 'REDIRECT_REQUEST_METHOD') as $k) {
    if (isset($_SERVER[$k])) {
        $_method = strtoupper($_SERVER[$k]);
        break;
    }
}

if ($_method == 'OPTIONS') {
    header('HTTP/1.1 200 OK');

    if (!isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header('Access-Control-Allow-Methods: '.METHODS_S);
    if (!isset($_SERVER['HTTP_ORIGIN']))
        header('Access-Control-Allow-Origin: *');

    header('Allow: '.METHODS_S);
    header('Accept-Patch: application/json');
    exit;
}

// HTTP Content Negotiation
require_once('input.php');
require_once('output.php');
if (isset($_RAW_EXT[$_filename_ext])) {
    $_input = 'raw';
    $_output = 'raw';

    if (substr(basename($_filename), 0, 5) == '.meta') {
        $_output = 'turtle';
        $_output_type = 'text/turtle';
    } else {
        $_output_type = $_RAW_EXT[$_filename_ext].'/'.($_filename_ext=='js'?'javascript':$_filename_ext);
    }
}
