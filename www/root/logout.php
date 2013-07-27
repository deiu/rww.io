<?php

require_once('runtime.php');

foreach ($_SESSION as $k=>$v) {
    sess($k, null);
}

if (isset($i_next)) {
    sess('next', $i_next);
} elseif (isMethod('GET') && isset($_SERVER['HTTP_REFERER'])) {
    sess('next', $_SERVER['HTTP_REFERER']);
}

if (isSess('next')) {
    $next = sess('next', null);
    $next = str_replace('https://', 'http://', $next);
    header('Location: '.$next);
} else {
    header('Location: /');
}
exit;
