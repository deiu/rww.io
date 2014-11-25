<?php
/* PUT.php
 * service HTTP PUT controller
 *
 */

require_once('runtime.php');

$_method_type = "write";

// permissions
if (empty($_user))
    httpStatusExit(401, 'Unauthorized');

// Web Access Control
// - allow Append for PUT if the resource doesn't exist.
if (!file_exists($_filename)) {
    if (($_wac->can('Append') == false) && ($_wac->can('Write') == false))
        httpStatusExit(403, 'Forbidden');
} else if ($_wac->can('Write') == false) {
    httpStatusExit(403, 'Forbidden');
}

// check quota
if (check_quota($_root, $_SERVER["CONTENT_LENGTH"]) == false)
    httpStatusExit(507, 'Insufficient Storage');

// check if we need to create a dir
if (isset($_SERVER['HTTP_LINK'])) {
    $link_header = http_parse_link_header($_SERVER['HTTP_LINK']);
    // look for an ldp:Container in the Link header
    if (in_array('http://www.w3.org/ns/ldp#Container', $link_header) || in_array('http://www.w3.org/ns/ldp#BasicContainer', $link_header)) {
        @mkdir($_filename, 0777, true);
        header('Location: '.$_base);
        header("Link: <".dirname($_base)."/.meta.".basename($_filename).">; rel=meta", false);
        httpStatusExit(201, 'Created');
        
    }
}

// action
$d = dirname($_filename);
if (!file_exists($d))
    mkdir($d, 0777, true);

$_data = file_get_contents('php://input');

if ($_input == 'raw') {
    require_once('if-match.php');
    file_put_contents($_filename, $_data);
    header("Link: <".dirname($_base)."/.meta.".basename($_filename).">; rel=meta", false);
    httpStatusExit(201, 'Created');
}

$g = new Graph('', $_filename, '', $_base);
require_once('if-match.php');

$g->truncate();
if (!empty($_input) && $g->append($_input, $_data)) {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    $g->save();
    header('ETag: "'.md5_file($_filename).'"');
    header('Location: '.$_base);
    header("Link: <".dirname($_base)."/".basename($_filename).">; rel=meta", false);
    httpStatusExit(201, 'Created');
} else {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    header('Accept-Post: '.implode(',', $_content_types));
    header("Link: <".$_base.">; rel=meta", false);
    httpStatusExit(406, 'Content-Type ('.$_content_type.') Not Acceptable');
}
