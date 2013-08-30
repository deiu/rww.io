<?php
/* if-match.php
 *
 * conditional requests via headers: If-Match, If-None-Match
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
