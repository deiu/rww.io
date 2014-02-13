<?php
/* runtime.php
 * cloud common includes
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

define('METHODS_S', 'GET, PUT, POST, OPTIONS, HEAD, MKCOL, DELETE, PATCH');

require_once(dirname(__FILE__).'/../inc/runtime.inc.php');

// cookie for displaying hidden files in the list (one month cookie)
if (!isset($_COOKIE['showMetaFiles'])) {
    setcookie('showMetaFiles', false, time()+2592000);
} 

$_showMetaFiles = (isset($_COOKIE['showMetaFiles']))?$_COOKIE['showMetaFiles']:false;
$_checkedShowMeta = ($_showMetaFiles == true)?'checked':'';

$_RAW_EXT = array(
    'otf'=>array('short'=>'font', 'type'=>'font/otf'),
    'css'=>array('short'=>'text', 'type'=>'text/css'),
    'htm'=>array('short'=>'text', 'type'=>'text/htm'),
    'html'=>array('short'=>'text', 'type'=>'text/html'),
    'js'=>array('short'=>'text', 'type'=>'text/javascript'),
    'jpg'=>array('short'=>'image', 'type'=>'image/jpg'),
    'jpeg'=>array('short'=>'image', 'type'=>'image/jpg'),
    'png'=>array('short'=>'image', 'type'=>'image/png'),
    'gif'=>array('short'=>'image',  'type'=>'image/gif'),
    'txt'=>array('short'=>'text', 'type'=>'text/txt'),
    'ttl'=>array('short'=>'turtle', 'type'=>'text/turtle'),
    'n3'=>array('short'=>'n3', 'type'=>'text/n3'),
    'nt'=>array('short'=>'nt', 'type'=>'text/nt'),
    );

$_content_types = array(
    'text/turtle;charset=utf-8',
    'text/n3;charset=utf-8',
    'text/nt;charset=utf-8',
    'text/css;charset=utf-8',
    'text/html;charset=utf-8',
    'text/javascript;charset=utf-8',
    'text/plain;charset=utf-8',
    'application/rdf+xml;charset=utf-8',
    'application/json;charset=utf-8',
    'image/jpeg',
    'image/jpeg',
    'image/png',
    'image/gif',
    'font/otf'
    );  

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
$_root = $_ENV['CLOUD_DATA'].'/'.$_SERVER['SERVER_NAME'];

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

// meta
$_metabase = ($_SERVER['SCRIPT_URL'] != '/')?dirname($_base):$_base;
$_metaname = ($_SERVER['SCRIPT_URL'] != '/')?'/.meta.'.basename($_SERVER['SCRIPT_URL']):'.meta';

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
    header('Access-Control-Expose-Headers: User');
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
    header('Access-Control-Expose-Headers: User');
    header('Accept-Post: '.implode(',', $_content_types));
    exit;
}

// HTTP Content Negotiation
require_once('input.php');
require_once('output.php');
if (isset($_RAW_EXT[$_filename_ext])) {
    if ((substr(basename($_filename), 0, 5) == '.meta') ||
         (substr(basename($_filename), 0, 4) == '.acl')) {
        $_output = 'turtle';
        $_output_type = 'text/turtle';
    } else {   
        $_input = 'raw';
        $_output = 'raw';
        $_output_type = $_RAW_EXT[$_filename_ext]['type'];
    }
}
