<?php
/* POST.php
 * service HTTP POST controller
 * (PATCH is a variant of POST)
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

require_once('runtime.php');

if (isset($i_query)) {
    require_once('GET.php');
    //exit;
}

// permissions
if (empty($_user)) 
    httpStatusExit(401, 'Unauthorized');

// Web Access Control
$can = false;
if ($_wac->can('Append') || $can = $_wac->can('Write'))
    $can = true;
if (DEBUG) {
    openlog('RWW.IO', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
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

// intercept requests for WebID generator
if (isset($_POST['SPKAC'])) {
    require_once 'webidgen.php';
    // exit required so it can successfully send the certificate
    exit;
}

// check quota
if (check_quota($_root, $_SERVER["CONTENT_LENGTH"]) == false)
    httpStatusExit(507, 'Insufficient Storage');

// action
$d = dirname($_filename);
if (!file_exists($d))
    mkdir($d, 0777, true);

// intercept requests for images
if (isset($_FILES["image"])) {
    // Check if the user uploaded a new picture
    if ((isset($_FILES['image'])) && ($_FILES['image']['error'] == 0)) {
        // Allow only pictures with a size smaller than 5MB
        if ($_FILES['image']['size'] <= IMAGE_SIZE) {
            // Using getimagesize() to avoid fake mime types 
            $image_info = exif_imagetype($_FILES['image']['tmp_name']);
            switch ($image_info) {
                case IMAGETYPE_GIF:
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $_filename.$_FILES['image']['name']))
                            echo 'Could not copy the picture to the user\'s dir. Please check permissions.';
                    break;
                case IMAGETYPE_JPEG:
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $_filename.$_FILES['image']['name']))
                            echo 'Could not copy the picture to the user\'s dir. Please check permissions.';
                    break;
                case IMAGETYPE_PNG:
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $_filename.'/'.$_FILES['image']['name']))
                            echo 'Could not copy the picture to the user\'s dir. Please check permissions.';
                    break;
                default:
                    echo 'The selected image format is not supported.';
                    break;
            }
        } else {
            echo 'The image size is too large. The maximum allowed size is 5MB.';
        }
    }
    // refresh and exit
    header('Location: '.$_SERVER["REDIRECT_SCRIPT_URI"]);
    exit;
}

$_data = file_get_contents('php://input');

if ($_input == 'raw') {
    require_once('if-match.php');
    file_put_contents($_filename, $_data, FILE_APPEND | LOCK_EX);
    exit;
}

$g = new Graph('', $_filename, '', $_base);
require_once('if-match.php');

if ($_method == 'PATCH') {
    if ($_input == 'json' && ($g->patch_json($_data) || 1)) {
        librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
        $g->save();
    }
} elseif (!empty($_input) && ($g->append($_input, $_data) || 1)) {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    $g->save();
} elseif ($_content_type == 'application/sparql-update') {
    require_once('SPARQL.php');
} else {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    header('Accept-Post: '.implode(',', $_content_types));
    httpStatusExit(406, 'Content-Type ('.$_content_type.') Not Acceptable');
}

@header('Triples: '.$g->size());
