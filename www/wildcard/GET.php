<?php
/* GET.php
 * service HTTP GET/HEAD controller
 *
 */

require_once('runtime.php');

// serve the favicon
if (basename($_filename) == 'favicon.ico') {
    $length = filesize($_SERVER["DOCUMENT_ROOT"].'/favicon.ico');
    header('Accept-Ranges: bytes');
	header('Content-Length: ' . $length);
	header('Content-Type: image/x-icon');
    readfile($_SERVER["DOCUMENT_ROOT"].'/favicon.ico');

    exit;
} 

if ((basename($_filename) == 'logout') || (isset($_GET['logout']))) {
    foreach ($_SESSION as $k=>$v) {
        sess($k, null);
    }

    if (isset($i_next)) {
        sess('next', $i_next);
    } elseif (isMethod('GET') && isset($_SERVER['HTTP_REFERER'])) {
        sess('next', $_SERVER['HTTP_REFERER']);
    }

    if (isSess('next')) {
        $next = sess('next', null);
        $next = str_replace('https://', 'http://', $next);
        header('Location: '.$next);
    } else {
        header('Location: /');
    }
    exit;
}

if (!in_array($_method, array('GET', 'HEAD')) && !isset($i_query))
    httpStatusExit(501, 'Not Implemented');

// on the fly output format conversions ?
if (!file_exists($_filename) && in_array($_filename_ext, array('turtle','n3','json','rdf','nt','jsonld'))) {
    $_filename = substr($_filename, 0, -strlen($_filename_ext)-1);
    $_base = substr($_base, 0, -strlen($_filename_ext)-1);
    if ($_filename_ext == 'turtle' || $_filename_ext == 'n3') {
        $_output = 'turtle';
        $_output_type = 'text/turtle';
    } elseif ($_filename_ext == 'json') {
        $_output = 'json';
        $_output_type = 'application/json';
    } elseif ($_filename_ext == 'rdf') {
        $_output = 'rdfxml-abbrev';
        $_output_type = 'application/rdf+xml';
    } elseif ($_filename_ext == 'nt') {
        $_output = 'ntriples';
        $_output_type = 'text/plain';
    } elseif ($_filename_ext == 'jsonld') {
        $_output = 'json-ld';
        $_output_type = 'application/ld+json';
    }
}

// permissions
if (empty($_user)) {
    httpStatusExit(401, 'Unauthorized', '401.php');
}

// WebACL
$can = false;
$can = $_wac->can('Read');
if (DEBUG) {
    openlog('ldphp', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
    foreach($_wac->getDebug() as $line)
        syslog(LOG_INFO, $line);
    syslog(LOG_INFO, 'Verdict: '.$can.' / '.$_wac->getReason());
    closelog();
}
if ($can == false)  {
    if ($_output == 'html')
        httpStatusExit(403, 'Forbidden', '403-404.php');
    else
        httpStatusExit(403, 'Forbidden');
} 

// directory indexing
if (is_dir($_filename) || substr($_filename,-1) == '/') {
    // add meta relation
    if ($_options->linkmeta)
        header("Link: <".$_metabase.$_metaname.">; rel=meta", false);
    
    if (substr($_filename, -1) != '/') {
        header("Location: $_base/");
        exit;
    } elseif (!isset($_output) || empty($_output) || $_output == 'html') {
        include_once('index.html.php');
        exit;
    } else {
        $dirindex = true;
        foreach (array('index') as $index) {
            if (file_exists("$_filename/$index")) {
                $_filename = "$_filename/$index";
                $dirindex = false;
                break;
            }
        }
        if ($dirindex) {
            include_once('index.rdf.php');
  		}
    }
} 

// set default output
if (empty($_output)) {
    $_output = 'turtle';
    $_output_type = 'text/turtle';
}

// output raw
if ($_output == 'raw') {
    if ($_output_type)
        header("Content-Type: $_output_type");
    if (!file_exists($_filename))
        httpStatusExit(404, 'Not Found', '403-404.php');
    
    // add meta relation
    if ($_options->linkmeta)
        header("Link: <".$_metabase.$_metaname.">; rel=meta", false);

    // caching for files
    $expires = 60*60; // 1 hour
    header("Pragma: public");
    header("Cache-Control: max-age=".$expires, true);
    header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

    if ($_method == 'GET')
        readfile($_filename);
    exit;
} else {
    // always revalidate cache for RDF documents
    header("Cache-Control: max-age=0", true);
}
header("Vary: Accept, Origin, If-Modified-Since, If-None-Match, ETag");

// *: glob
if ($_options->glob && (strpos($_filename, '*') !== false || strpos($_filename, '{') !== false)) {
    $last_mtime = 0;
    $glob_md5 = '';
    foreach(glob($_filename, GLOB_BRACE|GLOB_NOSORT) as $item) {
        if (!substr($item, 0, strlen($_filebase)) == $_filebase) continue;
        $item_ext = strrchr($item, '.');
        if ($item_ext == '.sqlite' || ($item_ext && in_array(substr($item_ext, 1), $_RAW_EXT))) continue;  

        // get file mtime and md5 
        $mtime = filemtime($item);
        if ($mtime > $last_mtime)
            $last_mtime = $mtime;
        $glob_md5 .= md5_file($item);
    }
    $etag = md5($glob_md5);
    $last_modified = $last_mtime;
} else if (is_dir($_filename)) {
    $_f = $_filename.'.';
    $etag = md5_dir($_f);
    $last_modified = filemtime($_f);
} else {
    $_f = $_filename;
    $etag = md5_file($_f);
    $last_modified = filemtime($_f);
}

// add ETag and Last-Modified headers
if (strlen($etag)) {
    $etag = trim(array_shift(explode(' ', $etag)));
    header('ETag: "'.$etag.'"');
}
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified).' GMT', true, 200);
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || 
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (strlen($_SERVER['HTTP_IF_NONE_MATCH']) > 0))) {
    if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified || 
        trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) { 
        header("HTTP/1.1 304 Not Modified"); 
        exit; 
    }
}

// tabulator data skin
if ($_output == 'html') {
    include_once('contrib/skin.html.php');
    exit;
}

// allocate RDF
if (!isset($g))
    $g = new Graph('', $_filename, '', $_base);

// *: glob
if ($_options->glob && (strpos($_filename, '*') !== false || strpos($_filename, '{') !== false)) {
    $last_mtime = 0;
    $glob_md5 = '';
    foreach(glob($_filename, GLOB_BRACE|GLOB_NOSORT) as $item) {
        if (!substr($item, 0, strlen($_filebase)) == $_filebase) continue;
        $item_ext = strrchr($item, '.');
        if ($item_ext == '.sqlite' || ($item_ext && in_array(substr($item_ext, 1), $_RAW_EXT))) continue;
        $item_uri = REQUEST_BASE.substr($item, strlen($_filebase));

        // get file mtime and md5 
        $mtime = filemtime($item);
        if ($mtime > $last_mtime)
            $last_mtime = $mtime;
        $glob_md5 .= md5_file($item);

        // WebACL
        $wac = new WAC($_user, $item, $item_uri);
        if ($wac->can('Read'))
            $g->append_file('turtle', "file://$item", $item_uri);        
    }

} elseif (!empty($_filename) && !$g->exists() && !$g->size())
    if (!$_options->wiki)
        header('HTTP/1.1 404 Not Found');

// offer ?wait updates (polling)
if (isset($i_wait)) {
    $etag = (is_array($i_wait) && isset($i_wait['etag'])) ? $i_wait['etag'] : $g->etag();
    while ($etag == $g->etag()) {
        sleep(1);
        clearstatcache();
    }
    $g->reload();
}

// ETag
/*
$etag = $g->etag();
if ($etag)
    header('ETag: "'.$etag.'"');
*/

// LDP type
if (is_dir($_filename))
    header("Link: <http://www.w3.org/ns/ldp#Container>; rel=\"type\"", false);
else
    header("Link: <http://www.w3.org/ns/ldp#Resource>; rel=\"type\"", false);

// offer WebSocket updates
$updatesVia = isHTTPS() ? 'wss:' : 'ws:';
$updatesVia .= '//'.$_domain.':'. (1+$_SERVER['SERVER_PORT']);
header('Updates-Via: '.$updatesVia);

// RDF details
header('Triples: '.$g->size());
if (isset($i_query))
    header('Query: '.str_replace(array("\r","\n"), '', $i_query));

// support JSON-P
if (isset($i_callback)) {
    header('Content-Type: text/javascript');
    if ($_method == 'GET') {
        if ($_output == 'json' || isset($i_query)) {
            echo $i_callback, '(';
            register_shutdown_function(function() { echo ');'; });
        } else {
            echo $i_callback, '("';
            register_shutdown_function(function() { echo '");'; });
        }
    }
} elseif (isset($i_query) || isset($i_any)) {
    header('Content-Type: application/json');
} else {
    header("Content-Type: $_output_type");
}

// eg. method != OPTIONS
if (in_array($_method, array('GET', 'POST')))
    if (isset($i_any)) {
        echo json_encode($g->any(
            isset($i_any['s']) ? $i_any['s'] : null,
            isset($i_any['p']) ? $i_any['p'] : null
        ));
    } elseif (isset($i_query)) {
        echo $g->query_to_string($i_query, $_output, $_base);
    } else {
        echo $g->to_string($_output);
    }
