<?php
/* index.rdf.php
 * service RDF index page
 *
 */

// Returns contents of a directory as RDF

require_once('runtime.php');

$g = new Graph('memory', '', '', $_base);

// page length (number of items on a page)
$pl = 10;

$listing = array();
if (is_dir($_filename))
    $listing = scandir($_filename);

$contents = array();

foreach($listing as $item) {
    $len = strlen($item);
    if (!$len) continue;
    // don't report .. for the root
    if (($item == '..') || ($item == '.'))
        continue;
    $is_dir = is_dir("$_filename/$item");
    $item_ext = strrpos($item, '.');
    $item_ext = $item_ext ? substr($item, 1+$item_ext) : '';
    $item_elt = $item;
    if (in_array($item_ext, array('sqlite')))
        $item_elt = substr($item_elt, 0, -strlen($item_ext)-1);
    if ($is_dir) 
        $item_elt = "$item_elt/";
    if ($is_dir)
        $item_type = 'p:Directory';
    elseif (in_array($item_ext, $_RAW_EXT))
        $item_type = 'p:File';
    else
        $item_type = '<http://www.w3.org/2000/01/rdf-schema#Resource>';
    $mtime = filemtime("$_filename/$item");
    $size = filesize("$_filename/$item");    
    $uri = ($is_dir)?$_base.basename($item).'/':$_base.basename($item);
    $properties = array( 'resource' => $item_elt,
                         'uri' => $uri,
    					 'type' => $item_type,
    					 'mtime' => $mtime,
    					 'size' => $size);
    $contents[] = $properties;
}

// serve LDP by default and beging with the first page
$p = 1;
$complement = $_base.'?p=1';
header("Link: <".$complement.">; rel='first'", false);

if (isset($_GET['p'])) {
	$p = (int) $_GET['p'];
	$complement = '?p='. (string) $p;
}

// prepare list of LDPRs
$ldprs = array();
foreach ($contents as $item) {
    if ($item['resource'] != './')
        $ldprs[] = '<'.$item['resource'].'>';
}
// split members into pages
$ldprs_chunks = array_chunk($ldprs, $pl);
$ldprs_page = $ldprs_chunks[$p-1];

// default -> show all
$show_members = true;
$show_containment = true;
$show_empty = false;

// parse headers to retrieve preferred representation
if (isset($_SERVER['HTTP_PREFER'])) {
    $h = array();
    $opts = explode(';', $_SERVER['HTTP_PREFER']);
    foreach ($opts as $opt) {
        $o = explode('=', trim($opt));
        $v = explode(' ', trim($o[1], '"'));
        
        $h[$o[0]] = $v;
    }

    if (isset($h['omit'])) {
        foreach ($h['omit'] as $opt) {
            if ($opt == 'http://www.w3.org/ns/ldp#PreferContainment')
                $show_containment = false;
            else if ($opt == 'http://www.w3.org/ns/ldp#PreferMembership')
                $show_members = false;
            else if ($opt == 'http://www.w3.org/ns/ldp#PreferEmptyContainer')
                $show_empty = true;
        }
    }
    // include takes precedence whatever the case
    if (isset($h['include'])) {
        $show_members = false;
        $show_containment = false;
        $show_empty = false;
        foreach ($h['include'] as $opt) {
            if ($opt == 'http://www.w3.org/ns/ldp#PreferContainment')
                $show_containment = true;
            else if ($opt == 'http://www.w3.org/ns/ldp#PreferMembership')
                $show_members = true;
            else if ($opt == 'http://www.w3.org/ns/ldp#PreferEmptyContainer')
                $show_empty = true;
        }
    
    }
    // return the ack header
    header('Preference-Applied: return=representation', false);
}

// split members into pages
$contents_chunks = array_chunk($contents, $pl);
$contents = $contents_chunks[$p-1];
$pages = count($contents_chunks);
// add paging headers
if (!$show_empty && $p > 0) {
    // set last page
    header("Link: <".$_base."?p=".(string)($pages).">; rel='last'", false);

    if ($p > 1)
        header("Link: <".$_base."?p=".(string)($p-1).">; rel='prev'", false);
    if($p < $pages) {
        header("Link: <".$_base."?p=".(string)($p+1).">; rel='next'", false);
        header("HTTP/1.1 333 Returning Related", false, 333);
    }
}

// List LDPC info
$ldpc = "@prefix ldp: <http://www.w3.org/ns/ldp#> . @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . @prefix p: <http://www.w3.org/ns/posix/stat#> .".
        "<".$_base."> a ldp:Container, p:Directory ; ".
        "p:mtime ".filemtime($_filename)." ;".
        "p:size ".filesize($_filename)." ;";
$g->append('turtle', $ldpc);

// add extra LDPC metadata from .meta.<LDPC>
$meta_uri = dirname($_base).'/.meta.'.basename($_base);
$meta_file = dirname($_filename).'/.meta.'.basename($_filename);
$mg = new Graph('', $meta_file, '',$meta_uri);
if ($mg->size() > 0) {
    // specific authorization
    $q = 'SELECT * WHERE { <'.$_base.'> ?p ?o }';
    $s = $mg->SELECT($q);
    $res = $s['results']['bindings'];

    if (isset($res) && count($res) > 0) {
        foreach ($res as $t) {
	    $g->append_objects($_base, $t['p']['value'], array($t['o']));
        }
    }
}

// list each member
foreach($contents as $properties) {
/*
    // check ACL for each member resource
    $meta_uri = $properties['uri'];
    $meta_file = $_filename.basename($properties['resource']);

    // WebACL
    $wac = new WAC($_user, $meta_file, $meta_uri);
    $can = false;
    $can = $wac->can('Read');
    if (DEBUG) {
        openlog('RWW.IO', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
        foreach($wac->getDebug() as $line)
            syslog(LOG_INFO, $line);
        syslog(LOG_INFO, 'Verdict: '.$can.' / '.$wac->getReason());
        closelog();
    }
    if (!$can) {    
        //$g->append('turtle', '<'.$properties['resource'].'> <http://www.w3.org/ns/auth/acl#verdict> <http://www.w3.org/ns/auth/acl#denied>.');
        continue;
    }
*/
    // LDPRs
    // add metadata info for each member
    if (!$show_empty) {
        $g->append('turtle', "@prefix p: <http://www.w3.org/ns/posix/stat#> . <".
            $properties['resource']."> a ".
            $properties['type'] ." ; p:mtime ".
            $properties['mtime'] ." ; p:size ".
            $properties['size'] ." .");
    }

    // add ldp:contains triple to the LDPC
    if ($show_containment) 
        $g->append('turtle', "<".$_base."> <http://www.w3.org/ns/ldp#contains> <".$properties['resource']."> . ");

    // add resource type from resources containing metadata
    if ($properties['type'] != 'p:File') {
        if ($properties['type'] == 'p:Directory') {
            $meta_uri = dirname($properties['uri']).'/.meta.'.basename($properties['uri']);
            $meta_file = $_filename.'.meta.'.basename($properties['resource']);
        } else {
            $meta_uri = $properties['uri'];
            $meta_file = $_filename.basename($properties['resource']);
        }
        $dg = new Graph('', $meta_file, '',$meta_uri);
        if ($dg->size() > 0) {
            $q = 'SELECT * WHERE { <'.$properties['resource'].'> ?p ?o }';
            $s = $dg->SELECT($q);
            $res = $s['results']['bindings'];

            // add the resource type
            if (isset($res) && count($res) > 0) {
                foreach ($res as $t) {
                    if ($t['p']['value'] == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type')
                        $g->append_objects($properties['uri'], $t['p']['value'], array($t['o']));
                }
            }
        }
    }
}
/*
// TODO: add a list of resources with a given predicate (membership triples vs contained items)
if ($show_members) {
    foreach ($ldprs as $ldpr) {
        $q = 'SELECT * WHERE { <'.$_base.'> <http://www.w3.org/ns/ldp#hasMemberRelation> ?r } ';
        $s = $mg->SELECT($q);
        $res = $s['results']['bindings'];

        if (isset($res) && count($res) > 0) {
            foreach ($res as $t) {
                $nt = '<'.$_base.'> <'.$t['p']['value'].'> ';
                $nt .= ($t['o']['type']=='uri')?'<'.$t['o']['value'].'> .':'"'.$t['o']['value'].'" .';
                $g->append('turtle', $nt);
            }
        }

    }
}
*/

