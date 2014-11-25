<?php
/* if-match.php
 */

$if_match = isset($_SERVER["HTTP_IF_MATCH"]) ? trim(str_replace("\"", "", $_SERVER["HTTP_IF_MATCH"])) : '';
$if_none_match = isset($_SERVER["HTTP_IF_NONE_MATCH"]) ? trim(str_replace("\"", "", $_SERVER["HTTP_IF_NONE_MATCH"])) : '';

if (strlen($if_match) || strlen($if_none_match)) {
    if (isset($g)) {
        $exists = $g->exists();
        $etag = is_dir($_filename)?md5_dir($_filename):md5_file($_filename);
    } else {
        $exists = file_exists($_filename);
        if ($exists) {
            $etag = is_dir($_filename)?md5_dir($_filename):md5_file($_filename);
            if (strlen($etag))
                $etag = trim(array_shift(explode(' ', $etag)));
        } else {
            $etag = '';
        }
    }
    //echo "ETag: ".$etag;
    //echo "\nExists: ".$exists;
    //echo "\nIf-Match: ".$if_match;
    //echo "\nIf-None-Match: ".$if_none_match;

    if (strlen($if_match)) {
        $fail = (($if_match == '*' && !$exists) || ($if_match != '*' && $if_match != $etag));
    } else {
        $fail = (($if_none_match == '*' && $exists) || ($if_none_match != '*' && $if_none_match == $etag));
    }
    //echo "\nFail: ".$fail;
    //echo "\nMethod: ".$_method_type;
    if ($fail) {
        if ($_method_type == 'read') {
            httpStatusExit(304, 'Not Modified');
        } elseif ($_method_type == 'write') {
            header("Link: <".$_base.">; rel=describedby", false);
            httpStatusExit(412, 'Precondition Failed');
        }
    }
}
