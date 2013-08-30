<?php
/* s.php
 * PHP session server controller, debugger
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

if (isset($i_reset)) {
    sess($i_reset, null);
}
if (!isset($i_debug)) {
    $r = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (stristr($r, 'data.fm/')) {
        header('Location: '.$r);
    } else {
        header('Location: /');
    }
    exit;
}

header('Content-type: text/plain');
print_r($_SESSION);
function request_k(&$item, $key) {
    if (substr($key, 0, 5) == 'HTTP_') return;
    if (substr($key, 0, 7) == 'REMOTE_') return;
    if (substr($key, 0, 8) == 'REQUEST_') return;
    if (substr($key, 0, 7) == 'SCRIPT_') return;
    if (substr($key, 0, 4) == 'SSL_') return;
    $item = '';
}
array_walk($_SERVER, 'request_k');
ksort($_SERVER);
print_r(array_filter($_SERVER));
