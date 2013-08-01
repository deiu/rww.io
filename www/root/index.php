<?php
/* index.php
 * application index
 *
 * $Id$
 */

require_once('runtime.php');

header('X-XRDS-Location: '.REQUEST_BASE.'/yadis');
defined('HEADER') || include_once('header.php');
?>

<div class="box welcome center width-1024">
 <strong> Welcome!</strong> This <a class="white link" target="_blank" href="http://www.w3.org/DesignIssues/ReadWriteLinkedData.html">Read/Write</a> <a class="white link" target="_blank" href="http://www.w3.org/DesignIssues/LinkedData.html">Linked Data</a> service is free (and open-source) for educational and personal use. <a class="white link" href="help.php">Click here</a> see what operations are supported.
</div>

<div class="getaccount center width-1024">
    <div class="padded-l-1">
        <h3>To get your own dedicated storage space, just follow these steps:</h3>
        <ol>
            <li>Check if your domain name is available.</li>
            <li>Either login with your WebID, or create a new one. Tip: you can even host it on your own domain!</li>
            <li>Click on this icon: <img class="actions" src="/common/images/22/acl.png" title="Access Control"> to start protecting your data.</li>
        </ol>
    </div>
    
    <div class="padded-l-1">
        <div class="left protocol">https://</div>
        <div class="left"><input id="account-name" type="text" class="account-input" name="account-name" /></div>
        <div class="left domain">.<?=$_SERVER['SERVER_NAME']?></div>
        <div class="left"><input id="account-submit" class="account-submit" type="submit" value="Check!" onclick="checkDomain()" /></div>
        <div class="left"><input id="account-go" class="account-go" type="submit" value="Take me there!" style="display:none;" /></div>
    </div>
</div>

<script type="text/javascript">
function checkDomain() {
    var text = document.getElementById("account-name").value;
    text = text+'.<?=$_SERVER['SERVER_NAME']?>';  
    
    new Ajax.Request('check_domain.php', {
        method: 'get',
        parameters: { 'domain': encodeURIComponent(text)},
        onSuccess: function(response){
            // red
            $('account-name').setStyle({ 'background': '#e7604a'});
            $('account-go').hide();
        },
        onFailure: function(){
            $('account-name').setStyle({ 'background': '#5EFB6E'});
            $('account-go').setAttribute('onclick', 'window.location.replace("https://'+text+'")');
            $('account-go').show();
        }
    });
}
</script>

<div class="spacer"></div>
<?php
TAG(__FILE__, __LINE__, '$Id$');
defined('FOOTER') || include_once('footer.php');
