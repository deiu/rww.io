<?php
require_once('../inc/runtime.php');
$_options->linkmeta = false;

if (isset($_REQUEST['uri'])) {
    $url = $_REQUEST['uri'];
    $ctype = $_SERVER["HTTP_ACCEPT"];
    $origin = $_SERVER["HTTP_ORIGIN"];

    // 
    $cert_file = '../certs/agent.pem';
    $cert_password = '123';
     
    $ch = curl_init();
    $options = array(
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array('On-Behalf-Of: '.$_user, 'Accept: '.$ctype, 'Origin: '.$origin),
        CURLOPT_USERAGENT => 'RWW Agent',
        CURLOPT_URL => $url,
        CURLOPT_SSLCERT => $cert_file,
        CURLOPT_SSLCERTPASSWD => $cert_password,
        //CURLINFO_HEADER_OUT => true,
    );
    curl_setopt_array($ch , $options);
     
    $output = curl_exec($ch);
    if (!$output) {
        echo "Curl Error : " . curl_error($ch);
    } else if (DEBUG) {
        //echo "<pre>".htmlentities($output)."</pre>";
        //echo "<pre>".print_r(curl_getinfo($ch), true)."</pre>";
        //var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT));
    }

    $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $clen = curl_getinfo($ch , CURLINFO_CONTENT_LENGTH_DOWNLOAD);

    // prepare response
    header("Content-Type: ".$ctype);
    header("Content-Length: ".$clen);
    header('Access-Control-Allow-Origin: '.$origin);
    header('Access-Control-Allow-Credentials: true');
    header('User: '.$_user);
    echo $output;
    exit;
} else {
    echo "<p>To use this WebID-TLS delegated authentication proxy, you need to add the following triple to your WebID profile:</p>\n";
    echo "<pre>".htmlentities("<#me> <http://www.w3.org/ns/auth/acl#delegatee> <https://agent.rww.io/profile/card#me> ;")."</pre>";
    echo "<p>*replace <i>#me</i> with your own fragment idenfier or the graph in which you described yourself.</p>";
}

