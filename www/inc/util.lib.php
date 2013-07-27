<?php
/* util.lib.php
 * PHP utilities
 *
 * $Id$
 */

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
