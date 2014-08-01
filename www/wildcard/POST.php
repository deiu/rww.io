<?php
/* POST.php
 * service HTTP POST controller
 * (PATCH is a variant of POST)
 *
 */

require_once('runtime.php');

if (isset($i_query)) {
    require_once('GET.php');
    //exit;
}

$_method_type = "write";

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
    require_once '../inc/webidgen.php';
    // exit required so it can successfully send the certificate
    exit;
}

// check quota
if (check_quota($_root, $_SERVER["CONTENT_LENGTH"]) == false)
    httpStatusExit(507, 'Insufficient Storage');

// create dir structure if it doesn't exist
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
            httpStatusExit(513, 'Request Entity Too Large');
        }
    }
    // refresh and exit
    header('Location: '.$_SERVER["REDIRECT_SCRIPT_URI"]);
    exit;
}

// check if we post using LDP (by posting to a dir)
if (is_dir($_filename) && $_content_type == 'text/turtle') {
    include('ldp.php');
} else {
    $metafile = '';
    $ldp_location = $_base;
}

$_data = file_get_contents('php://input');

if ($_input == 'raw') {
    require_once('if-match.php');
    file_put_contents($_filename, $_data, FILE_APPEND | LOCK_EX);
    httpStatusExit(201, 'Created');
}

$g = new Graph('', $_filename, '', $_base);
require_once('if-match.php');

if ($_method == 'PATCH') {
    if ($_input == 'json' && ($g->patch_json($_data) || 1)) {
        librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
        $g->save();
        header('Triples: '.$g->size());
        header("Link: <".dirname($_base).'/'.$metafile.">; rel=meta", false);
        header('Location: '.$ldp_location);
        httpStatusExit(201, 'Created');
    }
} else if (!empty($_input) && ($g->append($_input, $_data) || 1)) {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    $g->save();
    header("Triples: ".$g->size(), false);
    header("Link: <".$_base.$metafile.">; rel=meta", false);
    header('Location: '.$ldp_location);
    header('ETag: "'.md5_file($_filename).'"');
    httpStatusExit(201, 'Created');
} else if ($_content_type == 'multipart/form-data') {
    if(isset($_FILES)){
        $errors= array();
        var_dump($_FILES);
        foreach($_FILES as $file){
            $file_name = $file['name'];
            $file_size = $file['size'];
            $file_tmp  = $file['tmp_name'];
            $file_type = $file['type'];
            if($file_size > 10000000){
                $errors[]='File size must be less than 10 MB. Size of '.$file_name.' is: '.$file_size;
                httpStatusExit(513, 'Request Entity Too Large');
            }

            echo "Moved to ".$_filename.$file_name."\n";
            if(is_dir($_filename)==false){
                mkdir($_filename, 0700);        // Create directory if it does not exist
            }
            if(is_dir($_filename.$file_name)==false){
                if (!move_uploaded_file($file_tmp,$_filename.$file_name)) {
                    $errors[]='Cannot move file from '.$file_tmp.' to '.$_filename.$file_name;
                }
            }
        }
        if(empty($errors)){
            if (sizeof($_FILES) == 1) {
                $file_name = str_replace("+", "%20", urlencode($file_name));
                header("Link: <".$_base.".meta.".$file_name.">; rel=meta", true);
                header("Link: <".$_base.".acl.".$file_name.">; rel=acl", false);
                header('Location: '.$_base.$file_name);
                header('ETag: "'.md5_file($_filename.$file_name).'"');
            }
            httpStatusExit(201, 'Created');
        } else {
            httpStatusExit(501, print_r($errors));
        }
    } else {
        echo "Empty FILES var.";
        var_dump($_FILES);
    }
} else if ($_content_type == 'application/sparql-update') {
    require_once('SPARQL.php');
} else {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    header('Accept-Post: '.implode(',', $_content_types));
    httpStatusExit(406, 'Content-Type ('.$_content_type.') Not Acceptable');
}
