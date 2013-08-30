<?php
/* index.html.php
 * service HTML index page
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


$TITLE = 'Index of '.$_request_path;
defined('HEADER') || include_once('header.php');
if (!isset($_options->editui)) $_options->editui = true;
if ($_options->editui) {

$quota = display_quota($_root);

?>

<div id="container">

<table id="webid-gen" style="display:none;">
    <form method="POST" action="">
        <tr><td><input type="text" name="name" size="40" class="required" placeholder="full name..."></td></tr>
        <tr><td><input type="text" name="path" size="40" value="/profile/card#me" class="required"></td></tr>
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
        <option>text/txt</option>
        <option>text/css</option>
        <option>text/html</option>
        <option>text/javascript</option>
    </select>
    <textarea class="editor-content clear left" id="editorarea" disabled="disabled"></textarea><br/>
    <div class="right actions"><a href="#" class="button button-rounded button-flat-caution" onclick="$('editor').hide();"><i class="icon-remove"></i> Cancel</a></div>
    <div class="right"><a href="#" class="button button-rounded button-flat-primary" onclick="cloud.save();"><i class="icon-save"></i> Save</a></div>
</div>

<div id="wac-editor" class="wac-editor" style="display: none;">
    <span id="wac-reqpath" name="wac-reqpath" style="display: none;"></span>
    <h3>Resource name: <b><span id="wac-path" name="wac-path"></span></b></h3>
    <input type="hidden" id="wac-exists" value="0" />
    <input type="hidden" id="wac-owner" value="<?=$_user?>" />
    <div>
        <div class="left"><input type="checkbox" id="wac-read" name="Read"> Read </div>
        <div class="left"><input type="checkbox" id="wac-write" name="Write" onclick="toggleCheck('wac-write','wac-append')"> Write </div>
        <div class="left"><input type="checkbox" id="wac-append" name="Append" onclick="toggleCheck('wac-append','wac-write')"> Append </div>
        <br/>
        <p>
        <div id="recursive" class="left" style="display: none;"><input type="checkbox" id="wac-recursive" name="Recursive"> Default for all new files in this directory?</div>
        </p>
    </div>
    <br />
    <br />
    <div>
        Allow access for:<br />
        <small>(comma separated WebID addresses OR leave blank for everyone)</small>
    </div>
    <textarea id="wac-users" name="users" cols="5" rows="5"></textarea>
    <br/>
    <div class="right actions"><a href="#" class="button button-rounded button-flat-caution" onclick="wac.hide()"><i class="icon-remove"></i> Cancel</a></div>
    <div class="right"><a href="#" class="button button-rounded button-flat-primary" onclick="wac.save()"><i class="icon-save"></i> Save</a></div>
</div>
<?php } ?>


<div class="center">


<div>

<div class="cloudactions center width-1024">
    <div>
        <div class="newitems">
        <div class="left cell inline-block sep-right"><img class="pointer newitem" src="/common/images/refresh.png" title="Refresh list" onclick="window.location.reload(true);" /></div>
        <div class="left cell inline-block sep-right"><img class="pointer newitem" src="/common/images/home.png" title="Go to top level" onclick="window.location.replace('/');" /></div>
        <div class="left cell inline-block actions"><img class="pointer newitem" src="/common/images/images.png" title="Upload an image" onclick="showImage();" /></div>
        <div class="left cell inline-block"><img class="pointer newitem" src="/common/images/add_folder.png" title="Create a new directory" onclick="showCloudNew('dir');" /></div>
        <div class="left cell inline-block"><img class="pointer newitem" src="/common/images/add_file.png" title="Create a new file" onclick="showCloudNew('file');" /></div>
        <div class="left cell inline-block"><input id="create-item" class="item" type="text" name="" style="display:none;" onkeypress="cloudListen(event)" /></div>
        <div class="left cell inline-block"><img id="submit-item" class="pointer newitem" src="/common/images/ok.png" title="Create" style="display:none;" onclick="createItem();" /></div>
        <div class="left cell inline-block"><img id="cancel-item" class="pointer newitem" src="/common/images/cancel.png" title="Cancel" style="display:none;" onclick="hideCloud();" /></div>
        <div class="left cell inline-block"><form id="imageform" name="imageform" method="post" enctype="multipart/form-data"><input type="file" id="addimage" name="image" style="display:none;" /></form></div>
        <div class="left cell inline-block"><img id="submit-image" class="pointer newitem" src="/common/images/upload.png" title="Upload" style="display:none;" onclick="submitImage();" /></div>
        <div class="left cell inline-block"><img id="cancel-image" class="pointer newitem" src="/common/images/cancel.png" title="Cancel" style="display:none;" onclick="hideImage();" /></div>
        </div>
    </div>

    <div class="meta right top-5">
        <?php if ($_showMetaFiles == true) { ?>
        <a class="pointer" onclick="setCookie('showMetaFiles', '0', '1');">Hide</a>
        <?php } else { ?>
        <a class="pointer" onclick="setCookie('showMetaFiles', '1', '1');">Show</a>
        <?php } ?>
        <span> hidden files?</span>
    </div>
    <?php if ($user_link) { ?>
        <div class="right top-5"><?=$quota?></div>
    <?php } ?>

</div>

<table id="index" class="files center box-shadow">
<thead>
    <tr>
        <th> Name</th>
        <th>Size</th>
        <th>Type</th>
        <th>Last Modified</th>
        <th colspan="3">Actions</th>
    </tr>
</thead>
<tbody class="lines">

<?php
// check if we have a real file structure
$listing = array();
$fake = false;
if (is_dir($_filename)) {
    $listing = scandir($_filename);
} else {
    // set fake dir
    $listing[] = '.';
    $fake = true;
}
foreach($listing as $item) {
    $len = strlen($item);
    
    if (!$len)
        continue;
    if (($_request_path != '/' && $item == '.'))
        continue;
    if (($_request_path == '/' && $item == '..'))
        continue;
    if (($_showMetaFiles == false) && 
        ((substr($item, 0, 5) == '.meta') || (substr($item, 0, 4) == '.acl')))
        continue;   

    // fake a valid file structure
    if ($fake)
        $is_dir = true;
    else
        $is_dir = is_dir("$_filename/$item");
    $item_ext = strrpos($item, '.');
    $item_ext = $item_ext ? substr($item, 1+$item_ext) : '';
    $item_elt = $item;
    if (in_array($item_ext, array('sqlite')))
        $item_elt = substr($item_elt, 0, -strlen($item_ext)-1);
    
    if ($is_dir)
        $item_elt = ($item_elt != '.')?"$item_elt/":'/';
    elseif (isset($_ext) && (!$item_ext || $item_ext == 'sqlite'))
        $item_elt = "$item_elt$_ext";

    echo '<tr>';
    echo '<td class="filename"><a href="', $item_elt, '">', $item_elt, '</a>';
    if ($item_ext == 'sqlite')
        echo ' (sqlite)';
    echo '</td>';
    echo '<td>';
        if (!$is_dir)
            echo human_filesize(filesize("$_filename/$item"));
        else
            echo '-';
    echo '</td>';
    echo '<td>';
    if ($is_dir) {
        echo ($item_elt != '/')?'Directory':'Root';
    } elseif ((substr($item_elt, 0, 5) == '.meta') || (substr($item_elt, 0, 4) == '.acl')) {
        echo 'text/turtle';
    } elseif (isset($_RAW_EXT[$item_ext])) {
        if ($_RAW_EXT[$item_ext] != 'text')
            $is_dir = true; // fake a dir to disable the edit button
        echo $_RAW_EXT[$item_ext].'/', $item_ext=='js'?'javascript':$item_ext;
    } elseif (!strlen($item_ext)) {
        echo 'text/turtle';
    }
    if ($fake)
        echo '</td><td>'.strftime('%F %X %Z', time()).'</td>';
    else
        echo '</td><td>'.strftime('%F %X %Z', filemtime("$_filename/$item")).'</td>';
    echo '</td>';
    echo '<td class="options">';
    if ($_options->editui && !$is_dir) {
        echo '<a href="#" onclick="cloud.edit(\''.$item_elt.'\');"><img class="actions" src="/common/images/22/edit.png" title="Edit contents" /></a>';
    }
    echo '</td>';
    echo '<td class="options">';
    echo '<a href="#" onclick="wac.edit(\''.$_request_path.'\', \''.$item_elt.'\');"><img class="actions" src="/common/images/22/acl.png" title="Access Control" /></a> ';
    echo '</td>';
    echo '<td class="options">';
    if ($_options->editui)
        echo '<a href="#" onclick="cloud.rm(\''.$item_elt.'\');"><img class="actions" src="/common/images/22/delete.png" title="Delete" /></a>';
    echo '</td>';
    echo '</tr>';
}
?>
</tbody>
</table>

</div>

<div class="clear spacer"></div>
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

function showImage() {
    hideCloud();
    $('addimage').show();
    $('submit-image').show();
    $('cancel-image').show();
}

function hideImage() {
    document.imageform.reset();
    $('addimage').hide();
    $('submit-image').hide();
    $('cancel-image').hide();
}

function submitImage() {
    document.imageform.submit();
    $('addimage').hide();
    $('submit-image').hide();
    $('cancel-image').hide();
}

function createItem() {
    var res = document.getElementById("create-item");
    console.log(res.name+' / val='+res.value);
    if (res.name == 'file')
        cloud.append(res.value);
    else if (res.name == 'dir')
        cloud.mkdir(res.value);
}

function cloudListen(e) {
    // 13 = the Enter key
    if (e.which == 13 || e.keyCode == 13) {
        createItem();
    }
}

function showCloudNew(type) {
    if (type == 'file')
        var text = 'file name...';   
    else
        var text = 'directory name...';

    hideImage();
    $('create-item').setAttribute('name', type);
    $('create-item').setAttribute('placeholder', text);    
    $('create-item').show();
    $('create-item').focus();
    $('submit-item').show();
    $('cancel-item').show();
}

function hideCloud() {
    $('create-item').hide();
    $('create-item').clear();
    $('submit-item').hide();
    $('cancel-item').hide();
}

function toggleCheck(e1,e2) {
    if ($(e1).checked == true)
        $(e2).checked = false;
    else
        $(e2).checked = true;
}

$(document).observe('keydown', function(e) {
    if (e.keyCode == 27) { // ESC
        $('editor').hide();
        $('wac-editor').hide();
        hideWebID();
        hideCloud();
        hideImage();
    }
});
</script>

<?php

TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
