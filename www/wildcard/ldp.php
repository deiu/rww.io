<?php

$slug = (isset($_SERVER['HTTP_SLUG']))?trim($_SERVER['HTTP_SLUG']):'';

// check if we need to create a dir
if (isset($_SERVER['HTTP_LINK'])) {
    // look for an ldp:Container in the Link header
    if (in_array('http://www.w3.org/ns/ldp#Container', http_parse_link_header($_SERVER['HTTP_LINK']))) {
        if (strlen($slug) > 0) {
            $_dir = $_filename.$slug;
        } else {
            $c = count(glob($_filename.LDPC_SUFFIX.'*'));
            $c++;
            $_dir = LDPC_SUFFIX.$c;
        }
        $d = $_filename.$_dir;
        // set the filename to the .meta file (we might need to post triples about the container there)
        $metafile = '.meta.'.$_dir;
        $_filename = $_filename.$metafile;

        if (!file_exists($d))
            mkdir($d, 0777, true);

    } else if (in_array('http://www.w3.org/ns/ldp#Resource', http_parse_link_header($_SERVER['HTTP_LINK']))) {
        if (strlen($slug)> 0) {
            $_filename = $_filename.$slug;
            $metafile = $slug;
        } else {
            // generate and autoincrement file ID
            $c = count(glob($_filename.LDPR_SUFFIX.'*'));
            $c++;
            $metafile = LDPR_SUFFIX.$c;
            $_filename = $_filename.$metafile;
        }
    }
} else {
    if (strlen($slug)> 0) {
        $_filename = $_filename.$slug;
        $metafile = $slug;
    } else {
        // generate and autoincrement file ID
        $c = count(glob($_filename.LDPR_SUFFIX.'*'));
        $c++;
        $metafile = LDPR_SUFFIX.$c;
        $_filename = $_filename.$metafile;
    }
}