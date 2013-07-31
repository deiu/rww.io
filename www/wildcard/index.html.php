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

<table id="webid-gen" style="display:none;">
    <form method="POST" action="">
        <tr><td><input type="text" name="name" size="40" class="required" placeholder="full name..."></td></tr>
        <tr><td><input type="text" name="path" size="40" value="card#me" class="required"></td></tr>
        <tr><td><input type="email" name="email" size="40" placeholder="recovery email..."></td></tr>
        <tr class="keygen" hidden><td>Key size<br /><keygen name="SPKAC" challenge="randomchars" keytype="rsa"></td></tr>
        <tr><td><button type="submit" onclick="hideWebID()">Create WebID</button>
                <button type="button" onclick="hideWebID()">Cancel</button>
        </td></tr>
    </form>
</table>

<div id="editor" class="editor" style="display:none">
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
    <textarea class="editor-content clear left" id="editorarea" disabled="disabled"></textarea>
    <button class="right" onclick="$(this).up().hide();">Cancel</button>
    <button class="right" onclick="cloud.save();">Save</button>
</div>

<div id="wac-editor" class="wac-editor" style="display: none;">
    <h3>Resource name: <b><span id="wac-path" name="wac-path"></span></b>
    <br/><small>Path: <b><span id="wac-reqpath" name="wac-reqpath"></span></b></small></h3>
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
    <button class="right" onclick="wac.hide()">Cancel</button>
    <button class="right" onclick="wac.save()">Save</button> 
</div>
<?php } ?>


<div class="center">

<div id="topnav" class="topnav center">
    <img src="/common/images/cloud.png" class="cloud-icon left" /><span class="title">RWW.I/O</span>
    <?php
    if ($user_link) { ?>
        <div class="login">
            <span class="login-links">
                <a class="white" href="<?=$user_link?>" target="_blank"><?=$user_name?></a><br />
                <a class="white" href="/logout">Logout</a>
            </span>
            <a class="white" href="<?=$user_link?>" target="_blank">
                <img class="login-photo img-border r3" src="<?=$user_pic?>" title="View profile" /></a>
        </div>
    <?php } else { ?> 
        <div class="login"> 
            <span class="login-links"><a class="white" href="https://<?=BASE_DOMAIN?>">WebID Login</a>
            <br/><a class="white" href="#" onclick="showWebID()">Get a WebID</a></span>
            <img class="login-photo" src="/common/images/nouser.png" />
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
    if (($_showMetaFiles == false) && (substr($item, 0, 5) == '.meta'))
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
    echo '<td class="filename"><a href="', $item_elt, '">', $item_elt, '</a>';
    if ($item_ext == 'sqlite')
        echo ' (sqlite)';
    echo '</td>';
    echo '<td>'.(!$is_dir?filesize("$_filename/$item"):'-').'</td>';
    echo '<td>';
    if ($is_dir) {
        echo 'Directory';
    } elseif (substr($item_elt, 0, 5) == '.meta') {
        echo 'text/turtle';
    } elseif (isset($_RAW_EXT[$item_ext])) {
        echo 'text/', $item_ext=='js'?'javascript':$item_ext;
    } elseif (!strlen($item_ext)) {
        echo 'text/turtle';
    }
    echo '</td><td>'.strftime('%F %X %Z', filemtime("$_filename/$item")).'</td>';
    echo '</td>';
    echo '<td class="options">';
    if ($_options->editui && !$is_dir) {
        echo '<a href="javascript:cloud.edit(\''.$item_elt.'\');"><img class="actions" src="/common/images/22/edit.png" title="Editor" /></a>';
    }
    echo '</td>';
    echo '<td class="options">';
    echo '<a href="javascript:wac.edit(\''.$_request_path.'\', \''.$item_elt.'\');"><img class="actions" src="/common/images/22/acl.png" title="Access Control" /></a> ';
    echo '</td>';
    echo '<td class="options">';
    if ($_options->editui)
        echo '<a href="javascript:cloud.rm(\''.$item_elt.'\');"><img class="actions" src="/common/images/22/delete.png" title="Delete" /></a>';
    echo '</td>';
    echo '</tr>';
}
?>
</tbody>
<?php if ($_options->editui) { ?>
<tfoot class="cloudactions">
    <tr>
        <td colspan="3">
            <div class="newitems">
            <div class="left cell inline-block"><img class="pointer newitem" src="/common/images/refresh.png" title="Refresh current location" onclick="window.location.reload(true);" /></div>
            <div class="left cell inline-block"><img class="pointer newitem" src="/common/images/add_file.png" title="Add new file" onclick="showCloudNew('file');" /></div>
            <div class="left cell inline-block"><img class="pointer newitem" src="/common/images/add_folder.png" title="Add new dir" onclick="showCloudNew('dir');" /></div>
            <div class="left cell inline-block"><input id="create-item" class="item" type="text" name="" style="display:none;" onkeypress="cloudSumit(event)" /></div>
            </div>
        </td>
        <td colspan="4" class="meta align-right">
            <?php if ($_showMetaFiles == true) { ?>
            <a class="pointer" onclick="setCookie('showMetaFiles', '0', '1');">Hide</a>
            <?php } else { ?>
            <a class="pointer" onclick="setCookie('showMetaFiles', '1', '1');">Show</a>
            <?php } ?>
            <span> .meta files?</span>
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
function showWebID() {
    // get the mouse position
    var e = window.event;
    var topVal = e.clientY;
    var leftVal = e.clientX-300;
    // set the coords
    $('webid-gen').setStyle({
        top: topVal+'px',
        left: leftVal+'px'
    });
    $('webid-gen').show();
}

function hideWebID() {
    $('webid-gen').hide();
}

function cloudSumit(e) {
    if (event.which == 13 || event.keyCode == 13) {
        var res = document.getElementById("create-item");
        console.log(res.name+' / val='+res.value);
        if (res.name == 'file')
            cloud.append(res.value);
        else if (res.name == 'dir')
            cloud.mkdir(res.value);
    }
}

function showCloudNew(type) {
    if (type == 'file')
        var text = 'file name...';   
    else
        var text = 'directory name...';

    $('create-item').setAttribute('name', type);
    $('create-item').setAttribute('placeholder', text);    
    $('create-item').show();
}

$(document).observe('keydown', function(e) {
    if (e.keyCode == 27) { // ESC
        $('editor').hide();
        $('webid-gen').hide();
        $('wac-editor').hide();
        $('create-item').hide();
    }
});
</script>

<?php if ($_options->editui) { ?>
<!-- <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script> -->

<?php
}
TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
