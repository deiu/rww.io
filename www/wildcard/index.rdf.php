<?php
/* index.rdf.php
 * service RDF index page
 *
 * $Id$
 */

require_once('runtime.php');

$g = new Graph('memory', '', '', $_base);

$listing = array();
if (is_dir($_filename))
    $listing = scandir($_filename);
foreach($listing as $item) {
    $len = strlen($item);
    if (!$len) continue;
//    if (($_request_path == '/' && $item == '..') ||
//        ($item[0] == '.' && $item != '..' && substr($item, 0, 5) != '.meta'))
//        continue;
    $is_dir = is_dir("$_filename/$item");
    $item_ext = strrpos($item, '.');
    $item_ext = $item_ext ? substr($item, 1+$item_ext) : '';
    $item_elt = $item;
    if (in_array($item_ext, array('sqlite')))
        $item_elt = substr($item_elt, 0, -strlen($item_ext)-1);
    if ($is_dir)
        $item_elt = "$item_elt/";
    /* Following breaks graph walking by index
     * We strongly prefer Accept-based conneg

    elseif (isset($_ext) && (!$item_ext || $item_ext == 'sqlite'))
        $item_elt = "$item_elt$_ext";

     */
    if ($is_dir)
        $item_type = 'p:Directory';
    elseif (in_array($item_ext, $_RAW_EXT))
        $item_type = 'p:File';
    else
        $item_type = '<http://www.w3.org/2000/01/rdf-schema#Resource>';
    $mtime = filemtime("$_filename/$item");
    $size = filesize("$_filename/$item");
    $g->append('turtle', "@prefix p: <http://www.w3.org/ns/posix/stat#> . <$item_elt> a $item_type ; p:mtime $mtime ; p:size $size .");
}
