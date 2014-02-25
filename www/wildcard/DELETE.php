<?php
/* DELETE.php
 * service HTTP DELETE controller
 *
 */

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir")
                    rrmdir($dir."/".$object);
                else
                    unlink($dir."/".$object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

require_once('runtime.php');

// permissions
if (empty($_user))
    httpStatusExit(401, 'Unauthorized');


if (DEBUG) {
    openlog('RWW.IO', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
    syslog(LOG_INFO, "<---------DEL--------->");
    syslog(LOG_INFO, 'User: '.$_user.' -> '.$_filename);
    closelog();
}
// Web Access Control
if (!$_wac->can('Write'))
    httpStatusExit(403, 'Forbidden');

$any_s = null;
if (strrchr($_SERVER['REQUEST_URI'], '#'))
    $any_s = $_SERVER['REQUEST_URI'];
elseif (isset($i_any) && isset($i_any['s']))
    $any_s = $i_any['s'];
elseif (isset($i_s))
    $any_s = $i_s;

if (!is_null($any_s)) {
    $g = new Graph('', $_filename, '', $_SERVER['SCRIPT_URI']);
    $r = strlen($any_s) ? $g->remove_any($any_s) : 0;
    header('Triples: '.$r);
    if ($r)
        $g->save();
    exit;
}

if (is_dir($_filename)) {
    if ($_options->recursive)
        rrmdir($_filename);
    else
        rmdir($_filename);
} elseif (file_exists($_filename)) {
    unlink($_filename);
} else {
    $g = new Graph('', $_filename, '', '');
    if ($g->exists()) {
        $g->delete();
    } else {
        httpStatusExit(404, 'Not Found');
    }
}

if (file_exists($_filename))
    httpStatusExit(409, 'Conflict');
