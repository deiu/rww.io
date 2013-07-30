<?php
/* index.html.php
 * service HTML index page
 *
 * $Id$
 */

require_once('runtime.php');

$TITLE = 'Index of '.$_request_path;
defined('HEADER') || include_once('header.php');
if (!isset($_options->editui)) $_options->editui = true;
if ($_options->editui) {
?>

<div id="container">

<div id="editor" class="notice" style="z-index: 1000; position: fixed; top: 5%; left: 30%; display: none;">
    <img class="clear right" src="//<?=BASE_DOMAIN.$_options->base_url?>/common/images/cancel.gif" onclick="$(this).up().hide()" />
    <input class="cleft left" style="margin: 0;" type="text" id="editorpath" placeholder="loading..." />
    <select id="editorType" class="left" style="margin: 0;" onchange="cloud.edit($F('editorpath'))">
        <option disabled="disabled"></option>
        <option>text/turtle</option>
        <option>text/rdf+xml</option>
        <option>text/rdf+nt</option>
        <option>application/json</option>
        <option>application/json-ld</option>
        <option disabled="disabled">----</option>
        <option>text/css</option>
        <option>text/html</option>
        <option>text/javascript</option>
    </select>
    <textarea class="clear left" id="editorarea" style="width: 50em; bottom: 2em" disabled="disabled"></textarea>
    <input class="clear right" type="button" value="Save" onclick="cloud.save();" />
</div>

<div id="wac-editor" class="notice" style="z-index: 1000; position: fixed; top: 5%; left: 40%; display: none;">
    <h3>Permissions for <b><span id="wac-path" name="wac-path"></span></b><br/><small><span id="wac-reqpath" name="wac-reqpath"></span></small></h3>
    <input type="hidden" id="wac-exists" value="0" />
    <input type="hidden" id="wac-owner" value="<?=$_user?>" />
    <p>
        <input type="checkbox" id="wac-read" name="Read"> Read
        <input type="checkbox" id="wac-write" name="Write"> Write
        <input type="checkbox" id="wac-recursive" name="Default"> Default
    </p>
    Allow access for:
    <br/>
    <small>(comma separated mailto: or http:// addresses OR leave blank for everyone)</small>
    <br/>
    <textarea id="wac-users" name="users" cols="5" rows="5"></textarea>
    <br/>
    <input type="submit" name="wac-save" value="Save" onclick="wac.save()">    
    <input type="button" value="Cancel" onclick="wac.hide()">
</div>
<?php } ?>


<div class="center">

<div id="topnav" class="topnav center">
    <img src="/common/images/cloud.png" class="cloud-icon left" /><span class="title">RWW.IO</span>
    <?php
    if ($user_link) { ?>
        <div class="login">
            <span class="login-links">
                <a class="white" href="<?=$user_link?>" target="_blank"><?=$user_name?></a><br />
                <a class="white" href="/logout">Logout</a>
            </span>
            <a class="white" href="<?=$user_link?>" target="_blank">
                <img class="login-photo r3" src="<?=$user_pic?>" /></a>
        </div>
    <?php } else { ?> 
        <div class="login"> 
            <span class="login-links"><a class="white" href="https://<?=BASE_DOMAIN?>">WebID<br/>Login</a></span> 
            <img class="login-photo" src="common/images/nouser.png" />
        </div>
    <?php } ?>
</div>
<div>
<table id="index" class="files center">
<thead>
    <tr>        
        <th> Name</th>
        <th>Size</th>
        <th>Type</th>
        <th>Last Modified</th>
        <th colspan="3">Actions</th>
    </tr>
</thead>
<tbody>
<?php
$listing = array();
if (is_dir($_filename))
    $listing = scandir($_filename);
foreach($listing as $item) {
    $len = strlen($item);
    if (!$len) continue;
    if (($_request_path == '/' && $item == '..') ||
        ($item[0] == '.' && $item != '..' && substr($item, 0, 5) != '.meta'))
        continue;
    $is_dir = is_dir("$_filename/$item");
    $item_ext = strrpos($item, '.');
    $item_ext = $item_ext ? substr($item, 1+$item_ext) : '';
    $item_elt = $item;
    if (in_array($item_ext, array('sqlite')))
        $item_elt = substr($item_elt, 0, -strlen($item_ext)-1);
    if ($is_dir)
        $item_elt = "$item_elt/";
    elseif (isset($_ext) && (!$item_ext || $item_ext == 'sqlite'))
        $item_elt = "$item_elt$_ext";

    echo '<tr>';
    echo '<td><a href="', $item_elt, '">', $item_elt, '</a>';
    if ($item_ext == 'sqlite')
        echo ' (sqlite)';
    echo '</td>';
    echo '<td>'.(!$is_dir?filesize("$_filename/$item"):'-').'</td>';
    echo '<td>';
    if ($is_dir) {
        echo 'Directory';
    } elseif (isset($_RAW_EXT[$item_ext])) {
        echo 'text/', $item_ext=='js'?'javascript':$item_ext;
    } elseif (!strlen($item_ext)) {
        echo 'text/turtle';
    }
    echo '</td><td>'.strftime('%F %X %Z', filemtime("$_filename/$item")).'</td>';
    echo '</td>';
    echo '<td class="options">';
    if ($_options->editui && !$is_dir) {
        echo '<a href="javascript:cloud.edit(\''.$item_elt.'\');"><img src="//'.BASE_DOMAIN.$_options->base_url.'/common/images/pencil.gif" title="Edit contents" /></a>';
    }
    echo '</td>';
    echo '<td class="options">';
    echo '<a href="javascript:wac.edit(\''.$_request_path.'\', \''.$item_elt.'\');"><img src="//'.BASE_DOMAIN.$_options->base_url.'/common/images/wac.png" title="Edit access control rules" /></a> ';
    echo '</td>';
    echo '<td class="options">';
    if ($_options->editui)
        echo '<a href="javascript:cloud.rm(\''.$item_elt.'\');"><img src="//'.BASE_DOMAIN.$_options->base_url.'/common/images/cancel.gif" title="Delete" /></a>';
    echo '</td>';
    echo '</tr>';
}
?>
</tbody>
<?php if ($_options->editui) { ?>
<tfoot>
    <tr>
        <td colspan=7>
            <input id="create-name" name="create[name]" type="text" value="" placeholder="new..." />
            <input class="button-new" id="create-type-file" name="create[type]" type="button" value="File" onclick="cloud.append($F($(this.parentNode).down()));" />
            <input class="button-new" id="create-type-directory" name="create[type]" type="button" value="Dir" onclick="cloud.mkdir($F($(this.parentNode).down()));" />
        </td>
    </tr>
</tfoot>
<?php } ?>
</table>
</div>
<hr class="center" />
<div class="clear"></div>
</div>


<script type="text/javascript">
$(document).observe('keydown', function(e) {
    if (e.keyCode == 27) { // ESC
        $('editor').hide();
        $('webid-gen').hide();
        $('wac-editor').hide();
    }
});
</script>

<?php if ($_options->editui) { ?>
<!-- <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script> -->

<?php
}
TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
