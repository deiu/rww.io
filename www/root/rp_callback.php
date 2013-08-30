<?php
/* rp_callback.php
 * Verifies assertions returned by the IDP
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

// Reference: http://code.google.com/apis/identitytoolkit/v1/reference.html#method_identitytoolkit_relyingparty_verifyAssertion
function verify($continueUrl, $response) {
    $q = array('userIp' => $_SERVER['REMOTE_ADDR']);
    $q['requestUri'] = $continueUrl;
    $q['postBody'] = $response;
    $q = http('POST', 'https://www.googleapis.com/identitytoolkit/v1/relyingparty/verifyAssertion?key='.GAPIKEY, json_encode($q));
    if ($q->status == 200)
        return json_decode($q->body, true);
    return array();
}

$result = verify(REQUEST_URI, @file_get_contents('php://input'));
$email = isset($result['verifiedEmail']) ? strtolower($result['verifiedEmail']) : '';
$name = isset($result['displayName']) ? $result['displayName'] : '';
$firstName = isset($result['firstName']) ? $result['firstName'] : '';
$lastName = isset($result['lastName']) ? $result['lastName'] : '';
if (strlen($email)) {
    sess('u:id', "mailto:$email");
    sess('u:link', "mailto:$email");
    sess('u:name', strlen($name) ? $name : $email);
}

$next = sess('next', null);
if (!is_string($next) || !strlen($next))
    $next = '/login';
?>
<script type="text/javascript">
var next = <?=json_encode($next)?>;
if (opener) {
    opener.location = next;
    window.close();
} else {
    window.location = next;
}
</script>
