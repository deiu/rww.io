<?php
/* rp_auth.php
 * Creates the URL to authenticate the user via IDP
 *
 * $Id$
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
