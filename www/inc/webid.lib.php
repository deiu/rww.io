<?php

function webid_claim() {
    $r = array('uri'=>array());
    if (isset($_SERVER['SSL_CLIENT_CERT'])) {
        $pem = $_SERVER['SSL_CLIENT_CERT'];
        $x509 = openssl_x509_read($pem);
        $pubKey = openssl_pkey_get_public($x509);
        $keyData = openssl_pkey_get_details($pubKey);
        if (isset($keyData['rsa'])) {
            if (isset($keyData['rsa']['n']))
                $r['m'] = strtolower(array_pop(unpack("H*", $keyData['rsa']['n'])));
            if (isset($keyData['rsa']['e']))
                $r['e'] = hexdec(array_shift(unpack("H*", $keyData['rsa']['e'])));
        }

        $d = openssl_x509_parse($x509);
        if (isset($d['extensions']) && isset($d['extensions']['subjectAltName'])) {
            foreach (explode(', ', $d['extensions']['subjectAltName']) as $elt) {
                if (substr($elt, 0, 4) == 'URI:') {
                    $r['uri'][] = substr($elt, 4);
                }
            }
        }
    }
    return $r;
}

function webid_query($uri, $g=null) {
    $r = array();
    if (is_null($g))
        $g = new Graph('uri', $uri, '', $uri);
    $q = $g->SELECT(sprintf("PREFIX : <http://www.w3.org/ns/auth/cert#> SELECT ?m ?e WHERE { <%s> :key [ :modulus ?m; :exponent ?e; ] . }", $uri));
    if (isset($q['results']) && isset($q['results']['bindings']))
        $r = $q['results']['bindings'];
      
    return $r;
}

function webid_verify() {
    $q = webid_claim();
    if (isset($q['uri'])) {
        foreach ($q['uri'] as $uri) {
            foreach (webid_query($uri) as $elt) {
                if ($q['e'] == $elt['e']['value'] && $q['m'] == strtolower(preg_replace('/[^0-9a-fA-F]/', '', $elt['m']['value']))) {
                    return $uri;
                }
            }
        }
    }
    return '';
}

function webid_getinfo($uri) {
    $g = new Graph('uri', $uri, '', $uri);
    $q = $g->SELECT(sprintf("PREFIX : <http://xmlns.com/foaf/0.1/>
                     SELECT ?name ?pic ?depic FROM <%s> WHERE { 
                        ?s a :Person .
                        OPTIONAL { ?s :name ?name } . 
                        OPTIONAL { ?s :img ?pic } .
                        OPTIONAL { ?s :depiction ?depic } .
                    }", $uri));

    if (isset($q['results']) && isset($q['results']['bindings']))
        $r = $q['results']['bindings']; 

    if (is_array($r[0])) {
        $name = $r[0]['name']['value'];
        $pic = $r[0]['pic']['value'];
        $depic = $r[0]['depic']['value'];

        if (strlen($name) == 0)
            $name = 'Anonymous';
        
        if (strlen($pic) == 0)
            $pic = (strlen($depic) > 0)?$depic:'/common/images/nouser.png';          
    } else {
        $name = '';
        $pic = '/common/images/nouser.png';
    }

    return array('name' => $name, 'pic' => $pic);
}
