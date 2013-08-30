<?php
/* output.php
 * HTTP output handler
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

// negotiation: parse HTTP Accept
$_accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
$_accept_list = array();
$_accept_data = array();
foreach (explode(',', $_accept) as $elt) {
    $elt = explode(';', $elt);
    if (count($elt) == 1) {
        $_accept_list[] = trim($elt[0]);
    } elseif (count($elt) == 2) {
        $_accept_data[trim($elt[0])] = (float)substr($elt[1], 2);
    }
}
asort($_accept_data, SORT_NUMERIC);
$_accept_data = array_reverse($_accept_data);

$_accept_type_map = array(
    '/json-ld' => 'json-ld',
    '/json' => 'json',
    '/turtle' => 'turtle',
    '/n3' => 'turtle',
    '/nt' => 'ntriples',
    '/rdf+n3' => 'turtle',
    '/rdf+nt' => 'ntriples',
    '/rdf+xml' => 'rdfxml-abbrev',
    '/rdf' => 'rdfxml-abbrev',
    '/atom+xml' => 'atom',
    '/rss+xml' => 'rss-1.0',
    '/rss' => 'rss-1.0',
    '/dot' => 'dot',
    '/csv' => 'csv',
    '/tsv' => 'tsv',
    '/tab-separated-values' => 'tsv',
    '/html' => 'html'
);

$_output = '';
$_output_type = null;
foreach ($_accept_list as $haystack) {
    foreach ($_accept_type_map as $needle=>$output) {
        if (strstr($haystack, $needle) !==FALSE) {
            $_output = $output;
            $_output_type = $haystack;
            break;
        }
    }
    if (!empty($_output)) break;
}
if (empty($_output))
foreach (array_keys($_accept_data) as $haystack) {
    foreach ($_accept_type_map as $needle=>$output) {
        if (strstr($haystack, $needle) !==FALSE) {
            $_output = $output;
            $_output_type = $haystack;
            break;
        }
    }
    if (!empty($_output)) break;
}

