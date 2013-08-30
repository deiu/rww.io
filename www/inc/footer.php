<?php
/* footer.php
 * page footer
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

define('FOOTER', 1);
TAG(__FILE__, __LINE__, '$Id$');
$time = $TAGS[count($TAGS)-1]['time']-$TAGS[0]['time'];
$caller = $TAGS[count($TAGS)-2];
$sparql_n = 0;
$sparql_t = 0;
if (isset($timings)) {
    $sparql_n = count($timings);
    foreach ($timings as $t) {
        $sparql_t += $t['time'];
    }
}
?>
<hr class="center" />
<div class="center align-right width-1024">
<?php

    $user_link = sess('u:link');
    $user_name = sess('u:name');
    if (is_null($user_name) || !strlen($user_name))
        $user_name = $_user;
?>

<div onclick="$('codeID').toggle();">
<?php

if ($_options->coderev) {
$src = explode('/', __FILE__);
$src = array_slice($src, array_search('www', $src));
$src = implode('/', $src);
$src = "https://github.com/deiu/rww.io/tree/master/$src";
?>
<div class="footer">
<span class="left">
    <a href="https://github.com/deiu/rww.io" target="_blank">GitHub</a> | <a href="http://<?=ROOT_DOMAIN?>/help">Help?</a> | Show your support
</span> 
<span class="left">
    <a data-code="ed957952a941abf15d50696973fa4b92" href="https://coinbase.com/checkouts/ed957952a941abf15d50696973fa4b92" target="_blank"><img src="/common/images/bitcoin.png" alt="Bitcoin donation" title="Bitcoin donation" border="0" /></a>
</span>
<span class="left">
    <a href="http://flattr.com/thing/1748916/" target="_blank"><img src="/common/images/flattr.png" alt="Flattr this project" title="Flattr this project" border="0" /></a>
</span>
<span class="left">
    <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YCG7HFRPTVD4A" target="_blank"><img src="/common/images/paypal.png" alt="Paypal donation" title="Paypal donation" border="0" /></a>
</span>

<span id="codeID" class="align-right" style="display:none;">
 request completed in <?=substr($time, 0, 6)?>s
<?=$sparql_n<1?'':sprintf('with %d quer%s in %ss', $sparql_n, $sparql_n>1?'ies':'y', substr($sparql_t, 0, 6))?> 
 [ <?php echo implode(' / ', array(
    'librdf: '.array_shift(explode(' ',librdf_version_string_get())),
    'raptor: '.array_shift(explode(' ',raptor_version_string_get())),
    'rasqal: '.array_shift(explode(' ',rasqal_version_string_get()))
)); ?>
 ]
</span>
<span id="codeTime">Performance info...</span>
</div>

</div>
</div>
<?php
}

?>
<div class="clear"></div>
</div>


</body>
</html>
