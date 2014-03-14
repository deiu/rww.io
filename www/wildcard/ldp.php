<?php

$slug = (isset($_SERVER['HTTP_SLUG']))?trim($_SERVER['HTTP_SLUG']):'';
$got_resource = true;

// check if we need to create a dir
if (isset($_SERVER['HTTP_LINK'])) {
    $link_header = http_parse_link_header($_SERVER['HTTP_LINK']);

    // look for an ldp:Container in the Link header
    if (in_array('http://www.w3.org/ns/ldp#Container', $link_header)) {
        if (strlen($slug) > 0) {
            $_dir = $slug;
        } else {
            // try to find a dedicated LDPC prefix first
            $p = LDP_get_prefix($_metafile, $_metabase.$_metaname, 'http://ns.rww.io/ldpx#ldpcPrefix');
            // else, try to find a generic prefix
            if (!$p)
                $p = LDP_get_prefix($_metafile, $_metabase.$_metaname, 'http://ns.rww.io/ldpx#ldprPrefix');
            $prefix = ($p)?$p:LDPC_PREFIX;
            $c = count(glob($_filename.$prefix.'*'));
            $c++;
            $_dir = $prefix.$c;
        }
        $d = $_filename.$_dir;
        // set the filename to the .meta file (we might need to post triples about the container there)
        $metafile = '.meta.'.$_dir;
        $_filename = $_filename.$metafile;
        $_dir = (strrpos($_dir, '/', -1))?$_dir:$_dir.'/'; // add trailing slash for dirs/containers        
        $ldp_location = $_base.$_dir;

        if (!file_exists($d))
            mkdir($d, 0777, true);

        $got_resource = false;
    }
}

if ($got_resource) {
    if (strlen($slug)> 0) {
        $metafile = $slug;
    } else {
        // generate and autoincrement file ID
        $p = LDP_get_prefix($_metafile, $_metabase.$_metaname, 'http://ns.rww.io/ldpx#ldprPrefix');
        $prefix = ($p)?$p:LDPR_PREFIX;
        $c = count(glob($_filename.$prefix.'*'));
        $c++;
        $metafile = $prefix.$c;
    }
    $_filename = $_filename.$metafile;
    $ldp_location = $_base.$metafile;
}