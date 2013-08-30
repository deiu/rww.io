<?php
/* rp_auth.php
 * Creates the URL to authenticate the user via IDP
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

// Reference: http://code.google.com/apis/identitytoolkit/v1/reference.html#method_identitytoolkit_relyingparty_createAuthUrl
$url = 'https://www.googleapis.com/identitytoolkit/v1/relyingparty/createAuthUrl?key='.GAPIKEY;
$q = array(
    'userIp' => $_SERVER['REMOTE_ADDR'],
    'continueUrl' => REQUEST_BASE.'/rp_callback',
    'identifier' => strtolower($i_provider).'.com',
);
$q = http('POST', $url, json_encode($q));
if ($q->status == 200) {
    $q = json_decode($q->body);
    if (isset($q->authUri))  {
        header('Location: '.$q->authUri);
        exit;
    }
} else {
    $q = json_decode($q->body);
    if (isset($q->error))
        echo $q->error->message;
}
