<?php
# input.php
# HTTP input handler
#
# $Id$

require_once('runtime.php');

$_content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
$_content_type_lst = explode(';', $_content_type); // ; charset=utf8
$_content_type = $_content_type_lst[0];

// negotiation: setup type maps
$_content_type_map = array(
    '/turtle' => 'turtle',
    '/n3' => 'turtle',
    '/nt' => 'ntriples',
    '/rdf+n3' => 'turtle',
    '/rdf+nt' => 'ntriples',
    '/rdf+xml' => 'rdfxml',
    '/rdf' => 'rdfxml',
    '/html' => 'rdfa',
    '/xhtml' => 'rdfa',
    '/rss+xml' => 'rss-tag-soup',
    '/rss' => 'rss-tag-soup',
    '/json' => 'json',
    '/json-ld' => 'json-ld',
);

// negotiation: process HTTP Content-Type
$_input = '';
foreach ($_content_type_map as $needle=>$input) {
    if (strstr($_content_type, $needle) !== FALSE) {
        $_input = $input;
        break;
    }
}

