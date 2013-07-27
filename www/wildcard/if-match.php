<?php
/* if-match.php
 *
 * conditional requests via headers: If-Match, If-None-Match
 */

$if_match = isset($_SERVER["HTTP_IF_MATCH"]) ? trim($_SERVER["HTTP_IF_MATCH"]) : '';
$if_none_match = isset($_SERVER["HTTP_IF_NONE_MATCH"]) ? trim($_SERVER["HTTP_IF_NONE_MATCH"]) : '';

if (strlen($if_match) || strlen($if_none_match)) {
    if (isset($g)) {
        $exists = $g->exists();
        $etag = $g->etag();

    } else {
        $exists = file_exists($_filename);
        $etag = $exists ? `md5sum $_filename` : '';
        if (strlen($etag))
            $etag = trim(array_shift(explode(' ', $etag)));

    }

    if (strlen($if_match)) {
        $fail = (($if_match == '*' && !$exists) || ($if_match != '*' && $if_match != $etag));
    } else {
        $fail = (($if_none_match == '*' && $exists) || ($if_none_match != '*' && $if_none_match == $etag));
    }

    if ($fail) {
        if ($_method_type == 'read')
            httpStatusExit(304, 'Not Modified');
        elseif ($_method_type == 'write')
            httpStatusExit(412, 'Precondition Failed');
    }
}
