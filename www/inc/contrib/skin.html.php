<!DOCTYPE html>
<html id="docHTML">
<head>
<?php
    if (substr($_SERVER['SERVER_NAME'],0,4) == 'dev.') {
        $base = '/common/lib/tabulator/';
    } else {
        $base = 'https://w3.scripts.mit.edu/tabulator/';
    }
    echo '<link type="text/css" rel="stylesheet" href="', $base, 'tabbedtab.css" />';
    echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>';
    echo '<script type="text/javascript" src="', $base, 'js/mashup/mashlib.js"></script>';
?>
<script>

/* http://api.jquery.com/extending-ajax/#Prefilters */
jQuery.ajaxPrefilter(function(options) {
    if (options.crossDomain) {
        options.url = "https://w3.scripts.mit.edu/proxy?uri=" + encodeURIComponent(options.url);
    }
});

jQuery(document).ready(function() {
    var uri = window.location.href;
    window.document.title = uri;
    var kb = tabulator.kb;
    var subject = kb.sym(uri);
    tabulator.outline.GotoSubject(subject, true, undefined, true, undefined);
});
</script>
</head>
<body>
<div class="TabulatorOutline" id="DummyUUID">
    <table id="outline"></table>
</div>
</body>
</html>
