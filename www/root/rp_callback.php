<?php
/* rp_callback.php
 * Verifies assertions returned by the IDP
 *
 * $Id$
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
