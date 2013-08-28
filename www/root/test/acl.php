<?php


class testACL {

    private $_url;
    private $_data;
    private $_result;
    private $_error;
    private $_succeeded=0;
    private $_failed=0;
    private $_webid_crt = "acl_user.pem";
    private $_webid_key = "acl_user_key.pem";

    function __construct($data=null) {     
        if (!$data)
            $this->_data = "<a> <b> <c> .";
        else
            $this->_data = $data;
    }

    function get_succeeded() {
        return $this->_succeeded;
    }
    
    function get_failed() {
        return $this->_failed;
    } 

    function get($url) {
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);      
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->_webid_crt); 
        curl_setopt($ch, CURLOPT_SSLKEY, $this->_webid_key);
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/turtle'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
        
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->_webid_crt); 
        curl_setopt($ch, CURLOPT_SSLKEY, $this->_webid_key);
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
    
    function delete($url) {
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->_webid_crt); 
        curl_setopt($ch, CURLOPT_SSLKEY, $this->_webid_key);
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
    
    function mkcol($url) {
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "MKCOL");
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->_webid_crt); 
        curl_setopt($ch, CURLOPT_SSLKEY, $this->_webid_key);
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
    
    
    function success($method, $uri) {
        echo 'Expected: <font color="green">Success: 200</font> / Outcome: ';

        if ($method == "Read") {
            $code = $this->get($uri);            
        } else if ($method == "Write") {
            $code = $this->post($uri);
        } else if ($method == "Delete") {
            $code = $this->delete($uri);
        } else if ($method == "MKCOL") {
            $code = $this->mkcol($uri);
        }
         
        if ($code == 200) {
            echo '<font color="green">Success: '.$code.'</font>';
            $this->_succeeded++;
        } else {
            echo '<font color="red">Failed: '.$code.'</font>';
            $this->_failed++;
        }
    }
    
    function fail($method, $uri) {
        echo 'Expected: <font color="green">Failed: 403</font> / Outcome: ';

        if ($method == "Read") {
            $code = $this->get($uri);            
        } else if ($method == "Write") {
            $code = $this->post($uri);
        } else if ($method == "Delete") {
            $code = $this->delete($uri);
        } else if ($method == "MKCOL") {
            $code = $this->mkcol($uri);
        }
         
        if ($code != 200) {
            echo '<font color="green">Failed: '.$code.'</font>';
            $this->_succeeded++;
        } else {
            echo '<font color="red">Success: '.$code.'</font>';
            $this->_failed++;
        }
    }
}




$test = new testACL();

// For each method: uri => expected outcome
$methods = array('Read' => array (
                    'https://deiu.example.com/' => 'pass',
                    'https://deiu.example.com/.acl' => 'fail',
                    'https://deiu.example.com/test/owned/' => 'pass',
                    'https://deiu.example.com/test/private/' => 'fail',
                    'https://deiu.example.com/test/public/' => 'pass',
                    'https://deiu.example.com/test/public-read/' => 'pass',
                    'https://deiu.example.com/test/recursive-read/dir/file' => 'pass',
                    'https://deiu.example.com/test/recursive-write/dir/file' => 'fail',
                    ),
                'Write' => array(
                    'https://deiu.example.com/.acl' => 'fail',
                    'https://deiu.example.com/test.ttl' => 'fail',
                    'https://deiu.example.com/test/owned/test.ttl' => 'pass',
                    'https://deiu.example.com/test/public/test.ttl' => 'fail', // defaultForNew only applies for read 
                    'https://deiu.example.com/test/private/test.ttl' => 'fail',
                    'https://deiu.example.com/test/public-read/test.ttl' => 'fail',
                    'https://deiu.example.com/test/recursive-read/dir/test.ttl' => 'fail',
                    'https://deiu.example.com/test/recursive-write/dir/test.ttl' => 'pass',
                    ),
                'MKCOL' => array(
                    'https://deiu.example.com/testdir' => 'fail',
                    'https://deiu.example.com/test/private/testdir' => 'fail',
                    'https://deiu.example.com/test/owned/testdir' => 'pass',
                    'https://deiu.example.com/test/recursive-write/testdir' => 'pass',
                    ),
                'Delete' => array(
                    'https://deiu.example.com/test' => 'fail',
                    'https://deiu.example.com/test/private' => 'fail',
                    'https://deiu.example.com/test/owned/test.ttl' => 'pass',
                    'https://deiu.example.com/test/owned/testdir' => 'pass',
                    'https://deiu.example.com/test/recursive-write/dir/test.ttl' => 'pass',
                    'https://deiu.example.com/test/recursive-write/testdir' => 'pass',
                    ),
                );

foreach ($methods as $method => $uris) {
    $i=0;
    foreach ($uris as $uri => $expected) {
        $i++;
        echo "<h3>Test ".$i." for ".$method."</h3>";
        echo "<b>Testing ".$method." for: ".$uri."</b><br/>";
        if ($expected == 'pass')
            $test->success($method, $uri);
        else if ($expected == 'fail')
            $test->fail($method, $uri);
    }
}


$total = $test->get_succeeded() + $test->get_failed();
echo '<p>Total tests: <b>'.$total.
    '</b> | Successful tests: <font color="green"><b>'.$test->get_succeeded().
    '</b></font> | Failed tests: <font color="red"><b>'.$test->get_failed().
    '</b></font></p>';










