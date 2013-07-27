<?php

require_once('runtime.php');

if (!isset($i_callback))
    $i_callback = 'user';

header('Content-Type: text/javascript');
echo $i_callback, '("', $_user, '");';
