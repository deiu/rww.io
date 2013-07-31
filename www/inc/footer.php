<?php
/* footer.php
 * page footer
 *
 * $Id$
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
<div class="center align-right width-1024">
<?php

    $user_link = sess('u:link');
    $user_name = sess('u:name');
    if (is_null($user_name) || !strlen($user_name))
        $user_name = $_user;
?>
<span style="float: left; clear: left">



</span>
<div onclick="$('codeID').toggle();">
<?php

if ($_options->coderev) {
$src = explode('/', __FILE__);
$src = array_slice($src, array_search('www', $src));
$src = implode('/', $src);
$src = "https://github.com/linkeddata/data.fm/tree/master/$src";
?>
<span id="codeID" class="align-right" style="display:none;">
/ <?php echo implode(' / ', array(
    'librdf: '.array_shift(explode(' ',librdf_version_string_get())),
    'raptor: '.array_shift(explode(' ',raptor_version_string_get())),
    'rasqal: '.array_shift(explode(' ',rasqal_version_string_get()))
)); ?>
</span>
<span id="codeTime">Completed in <?=substr($time, 0, 6)?>s
<?=$sparql_n<1?'':sprintf('with %d quer%s in %ss', $sparql_n, $sparql_n>1?'ies':'y', substr($sparql_t, 0, 6))?> </span>
</div>
</div>
<?php
}

?>
<div class="clear"></div>
</div>


</body>
</html>
