<?php


class testACL {

    private $_url;
    private $_data;
    private $_result;
    private $_error;

    function __construct($data=null) {
       
        if (!$data)
            $this->_data = "<a> <b> <c> .";
        else
            $this->_data = $data;
    }

    function get($url) {
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/turtle'));
        //curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
        
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERT, "acl_user.pem"); 
        curl_setopt($ch, CURLOPT_SSLKEY, "acl_user_key.pem");
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);

        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch);

        if ($error)
            echo '<br/>Curl error: ' .$error ;

        return $httpCode;
    }

    function post($url) {
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_GET, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/turtle'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
        
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERT, "acl_user.pem"); 
        curl_setopt($ch, CURLOPT_SSLKEY, "acl_user_key.pem");
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);

        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch);

        if ($error)
            echo '<br/>Curl error: ' .$error ;

        return $httpCode;
    }
}

$success = 0;
$failed = 0;

// URIs
$get_pub_uri_root = 'https://deiu.example.com/';
$get_pub_uri = 'https://deiu.example.com/test/public/';
$get_prv_uri = 'https://deiu.example.com/test/private/';
$get_own_uri = 'https://deiu.example.com/test/acl-owned/';

$post_pub_uri = 'https://deiu.example.com/test/public/test.ttl';
$post_prv_uri = 'https://deiu.example.com/test/private/test.ttl';
$post_own_uri = 'https://deiu.example.com/test/acl-owned/test.ttl';

$test = new testACL();

/*

// ----- public GET (root) ----- 
echo "<h3>Testing GET for PUBLIC ROOT at: ".$get_pub_uri_root."</h3>";
echo '<p>Expected: <font color="green">Success: 200</font> / Outcome: ';
$code = $test->get($get_pub_uri_root);
if ($code == 200) {
    echo '<font color="green">Success: '.$code.'</font>';
    $success++;
} else {
    echo '<font color="red">Failed: '.$code.'</font>';
    $failed++;
}
echo "</p>";

// ----- public GET ----- 
echo "<h3>Testing GET for PUBLIC at: ".$get_pub_uri."</h3>";
echo '<p>Expected: <font color="green">Success: 200</font> / Outcome: ';
$code = $test->get($get_pub_uri);
if ($code == 200) {
    echo '<font color="green">Success: '.$code.'</font>';
    $success++;
} else {
    echo '<font color="red">Failed: '.$code.'</font>';
    $failed++;
}
echo "</p>";

// ----- protected GET ----- 
echo "<h3>Testing GET for PRIVATE at: ".$get_prv_uri."</h3>";
echo '<p>Expected: <font color="green">Failed: 403</font> / Outcome: ';
$code = $test->get($get_prv_uri);
if ($code != 200) {
    echo '<font color="green">Failed: 403</font>';
    $success++;
} else {
    echo '<font color="red">Success - '.$code.'</font>';
    $failed++;
}
echo "</p>";

// ----- owned GET ----- 
echo "<h3>Testing GET for OWNED at: ".$get_own_uri."</h3>";
echo '<p>Expected: <font color="green">Success: 200</font> / Outcome: ';
$code = $test->get($get_own_uri);
if ($code == 200) {
    echo '<font color="green">Success: 200</font>';
    $success++;
} else {
    echo '<font color="red">Failed - '.$code.'</font>';
    $failed++;
}
echo "</p>";

// ----- public POST ----- 
echo "<h3>Testing POST for PUBLIC at: ".$post_pub_uri."</h3>";
echo '<p>Expected: <font color="green">Success: 200</font> / Outcome: ';
$code = $test->post($post_pub_uri);
if ($code == 200) {
    echo '<font color="green">Success: '.$code.'</font>';
    $success++;
} else {
    echo '<font color="red">Failed: '.$code.'</font>';
    $failed++;
}
echo "</p>";

// ----- protected POST ----- 
echo "<h3>Testing POST for PRIVATE at: ".$post_prv_uri."</h3>";
echo '<p>Expected: <font color="green">Failed: 403</font> / Outcome: ';
$code = $test->post($post_prv_uri);
if ($code != 200) {
    echo '<font color="green">Failed: 403</font>';
    $success++;
} else {
    echo '<font color="red">Success: '.$code.'</font>';
    $failed++;
}
echo "</p>";

*/
// ----- owned POST ----- 
echo "<h3>Testing POST for OWNED at: ".$post_own_uri."</h3>";
echo '<p>Expected: <font color="green">Success: 200</font> / Outcome: ';
$code = $test->post($post_own_uri);
if ($code == 200) {
    echo '<font color="green">Success: 200</font>';
    $success++;
} else {
    echo '<font color="red">Failed: '.$code.'</font>';
    $failed++;
}
echo "</p>";

$total = $success + $failed;
echo '<p>Total tests: <b>'.$total.'</b> | Failed tests: <font color="red"><b>'.$failed.'</b></font></p>';










