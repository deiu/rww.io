<?php
require_once('../runtime.php');
require_once('webid.lib.php');

header('Content-type: text/plain');

$claim = webid_claim();
if (isset($i_uri))
    $claim['uri'][] = $i_uri;

$query = array();
foreach($claim['uri'] as $elt) {
    $g = new Graph('uri', $elt, '', $elt);
    $query[$elt] = array(
        'triples' => $g->size(),
        'bindings' => webid_query($elt, $g)
    );
}

$r = array(
    'claim' => $claim,
    'query' => $query,
    'verified' => webid_verify()
);
print_r($r);
