<?php
/* util.lib.php
 * PHP utilities
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

// check if the LDPC offers default prefixes
function LDP_get_prefix($path, $uri, $type='http://ns.rww.io/ldpx#LDPRprefix') {
    if ($path && $uri) {
        $g = new Graph('', $path, '',$uri);
        if ($g->size() > 0) {
            // specific authorization
            $q = 'SELECT ?s, ?prefix WHERE { ?s <'.$type.'> ?prefix }';
            $s = $g->SELECT($q);
            $res = $s['results']['bindings'];

            if (isset($res) && count($res) > 0)
                return $res[0]['prefix']['value'];
        }
    }
    return null;
}

// parse a given http header
function http_parse_link_header( $header ) {
    $retVal = array();
    $elems = explode(';', $header);
    foreach ($elems as $v) {
        $v = str_replace('<', '', $v);
        $v = str_replace('>', '', $v);
        array_push($retVal , trim($v));
    }

    return $retVal;
}

// check if a dir is empty
function is_dir_empty($dir) {
    $files = scandir($dir);
    if (sizeof($files) > 2)
        return false;
    else
        return true;
}

// display the file sizes in a human readable format
function human_filesize($bytes, $decimals=2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}


// get quota for a dir
function get_quota($dir) {
    $get_size = '/usr/bin/du -sk '.$dir."|awk '{ print $1; }'";
    $used = trim(shell_exec($get_size));
    $total = DISK_QUOTA * 1000000; // mega to bytes
    $used = $used * 1000; // kilo to bytes
    
    return array('used'=>$used, 'total'=>$total);
}

// check if the size fits inside the quota
function check_quota($dir, $size) {
    $q = get_quota($dir);
    $a = $q['total'] - $q['used'];

    return ($size <= $a)?true:false;       
}

// display the quota bar
function display_quota($dir) {
    $q = get_quota($dir);
    $used = $q['used'] / 1000000; // bytes to mega
    $total = $q['total'] / 1000000; // bytes to mega;

    $medium = $total * (70/100);
    // at 90% we start to get worried
    $high = $total * (90/100);

    if ($used <= $medium) 
        $bg = 'green';
    else if ($used > $medium && $used <= $high)
        $bg = 'yellow';
    else if ($used > $high)
        $bg = 'red';

    $width = ($used *100) / $total;
    $width = number_format($width, 2);

    $ret = '<div class="quota" title="Quota: used '.
            number_format($used, 2).' MB / '.
            number_format($total).' MB">';
    $ret .= '   <div class="meter '.$bg.'" style="width:'.$width.'%;"></div>';
    $ret .= '   <div class="meter-inner">'.number_format($used, 2).'MB /'.number_format($total).'MB</div>';
    $ret .= '</div>';
    
    return $ret;
}


function isMethod($method) { return ($_SERVER['REQUEST_METHOD'] == $method); }
function isPost() { return isMethod('POST'); }
function isPostData() {
  return isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER['HTTP_CONTENT_LENGTH'])
    && $_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['HTTP_CONTENT_LENGTH'] > 0;
} 
function isSess($id) { return isset($_SESSION[$id]); }
function sess($id,$val=NULL) {
  if (func_num_args()==1) {
    return (isSess($id)?$_SESSION[$id]:NULL);
  } elseif (is_null($val)) {
    $r = isset($_SESSION[$id]) ? $_SESSION[$id] : null;
    unset($_SESSION[$id]);
    return $r;
  } else {
    $prev = sess($id);
    $_SESSION[$id] = $val;
    return $prev;
  }
}

function newQS($key, $val=null) { return newQSA(array($key=>$val)); }
function newQSA($array=array()) {
  parse_str($_SERVER['QUERY_STRING'], $arr);
  $s = count($arr);
  foreach($array as $key=>$val) {
    $arr[$key] = $val;
    if (is_null($val))
      unset($arr[$key]);
  }
  return (count($arr)||$s)?'?'.http_build_query($arr):'';
} 

function isHTTPS() { return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'); }

function timings($query=null) {
  global $timingc;
  global $timings;

  if(!isset($timings)) {
    $timings = array();
  }

  if (!isset($timingc) || empty($timingc)) {
    $timingc = 1;
  } elseif (!is_null($query)) {
    $timingc++;
  }
  $key = $timingc;

  if (is_null($query)) {
    $timings[$key]['time'] = microtime(true)-$timings[$key]['time'];
    if (function_exists('mysql_error') && mysql_error())
        $timings[$key]['error'] = mysql_error();
    return true;
  } else {
    $timings[$key] = array();
    $timings[$key]['time'] = microtime(true);
    $timings[$key]['query'] = $query;
    return false;
  }
}

function httpStatusExit($status, $message, $require=null, $body=null) {
    global $_options, $TAGS;
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']  == 'XMLHttpRequest') {
        $x_json = json_encode(array('status'=>$status,'message'=>$message));
    }
    $status = (string)$status;
    header("HTTP/1.1 $status $message");
    if (isset($x_json))
        header('X-JSON: '.$x_json);
    if ($require)
        require_once($require);
    else
        echo "<h1>$status $message</h1>\n";
    if ($body)
        echo "<p>$body</p>\n";
    exit;
}

$TAGS = array(array(
    'file' => __FILE__,
    'line' => __LINE__,
    'id' => null,
    'time'=>microtime(true)
));
function TAG($file, $line, $id) {
    global $TAGS;
    $TAGS[] = array(
        'file' => $file,
        'line' => $line,
        'id' => $id,
        'time' => microtime(true)
    );
}

function http($method, $uri, $content=null) {
    $c = stream_context_create(array('http'=>array(
        'method' => $method,
        'header' =>"Connection: close\r\nContent-Type: application/json\r\nAccept: application/json\r\nReferer: https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}\r\n",
        'content' => $content,
        'ignore_errors' => true,
        'max_redirects' => 0,
    )));
    $f = fopen($uri, 'r', false, $c);
    $h = stream_get_meta_data($f);
    $r = stream_get_contents($f);
    fclose($f);
    $status = 0;
    $header = array();
    if (isset($h['wrapper_data'])) {
        $status = array_shift($h['wrapper_data']);
        $header['Status'] = $status;
        $lst = explode(' ', $status);
        if (count($lst) > 1)
            $status = (int)$lst[1];
        foreach ($h['wrapper_data'] as $elt) {
            $i = strpos($elt, ': ');
            $k = substr($elt, 0, $i);
            if (!isset($header[$k]))
                $header[$k] = array(substr($elt, $i+2));
            //elseif (!is_array($header[$k]))
            //    $header[$k] = array($header[$k], substr($elt, $i+2));
            else
                $header[$k][] = substr($elt, $i+2);
        }
    }
    return (object)array('uri'=>$uri, 'status'=>$status, 'header'=>$header, 'body'=>$r);
}
