<?php
/* runtime.inc.php
 * application main runtime
 *
 * $Id$
 */

require_once('config.php');

// base dependencies
require_once('util.lib.php');
set_include_path(dirname(__FILE__).PATH_SEPARATOR.get_include_path());

spl_autoload_register(function ($class) {
    require_once(dirname(__FILE__).'/class/'.$class.'.php');
});

// base constants
if (!isset($_ENV['CLOUD_NAME'])) $_ENV['CLOUD_NAME'] = $_SERVER['SERVER_NAME'];
if (!isset($_ENV['CLOUD_HOME'])) $_ENV['CLOUD_HOME'] = realpath(dirname(__FILE__).'/../../');
if (!isset($_ENV['CLOUD_DATA'])) $_ENV['CLOUD_DATA'] = $_ENV['CLOUD_HOME'].'/data';
define('BASE_DOMAIN', $_ENV['CLOUD_NAME']);
define('X_AGENT', isset($_SERVER['X_AGENT']) ? $_SERVER['X_AGENT'] : 'Mozilla');
define('X_PAD', isset($_SERVER['X_PAD']) ? $_SERVER['X_PAD'] : '(null)');

define('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
if (isHTTPS()) {
    $BASE = 'https://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']!='443'?':'.$_SERVER['SERVER_PORT']:'');
} else {
    $BASE = 'http://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']!='80'?':'.$_SERVER['SERVER_PORT']:'');
}
//$URI = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
$URI = $_SERVER['REQUEST_URI'];
define('REQUEST_BASE', $BASE);
define('REQUEST_URL', $URI);
define('REQUEST_URI', $BASE.$URI);

// session startup
session_name('SID');
session_set_cookie_params(157680000, '/', '.'.preg_replace('/.+\.([^.]+\.[^.]+)$/', '\1', $_SERVER['SERVER_NAME']));
session_start();

// init RDF
if (function_exists('librdf_php_free_last_log'))
    librdf_php_free_last_log();
else {
    function librdf_php_last_log_level(){}
    function librdf_php_last_log_message(){}
}

date_default_timezone_set('GMT');
extract($_GET, EXTR_PREFIX_ALL, 'i');
extract($_POST, EXTR_PREFIX_ALL, 'i');

# init options
$_options = new stdClass();
$_options->base_url = '';
$_options->coderev = true;
$_options->debug = true;
$_options->editui = true;
$_options->glob = false;
$_options->open = true;
$_options->recursive = false;
$_options->sqlite = false;
$_options->linkmeta = true;
$_options->wiki = false;
if (file_exists(dirname(__FILE__).'/config.inc.php')) {
    require_once(dirname(__FILE__).'/config.inc.php');
}

# init user ID
$_user = $_user_name = '';
if (!isset($_SERVER['REMOTE_USER'])) $_SERVER['REMOTE_USER'] = '';
foreach (array($_SERVER['REMOTE_USER'], sess('u:id')) as $_user) {
    if (!is_null($_user) && strlen($_user))
        break;
}

if (!strlen($_user) && isset($_SERVER['SSL_CLIENT_CERT'])) {
    require_once('webid.lib.php');
    $_user = webid_verify();
    
    $_webid = webid_getinfo($_user);

    if (!isSess('u:name')) 
        sess('u:name', $_webid['name']);
    if (!isSess('u:pic'))
        sess('u:pic', $_webid['pic']);

    if (strlen($_user) && isset($_SERVER['SSL_CLIENT_S_DN_CN']))
        $_user_name = $_SERVER['SSL_CLIENT_S_DN_CN'];
}

# proper Emails
if (substr($_user, 0, 4) != 'http')
    if (substr($_user, 0, 7) != 'mailto:' && stristr($_user,'@'))
        $_user = "mailto:$_user";

# fallback to DNS
if (empty($_user))
    $_user = 'dns:'.$_SERVER['REMOTE_ADDR'];
elseif (sess('u:id') != $_user)
    sess('u:id', $_user);

foreach (array('HTTP_OPTIONS', 'HTTP_X_OPTIONS') as $k0)
if (isset($_SERVER[$k0]))
foreach (explode(',',$_SERVER[$k0]) as $elt) {
    $k = strtolower(trim($elt));
    $v = true;
    if ($k[0] == 'n' && $k[1] == 'o') {
        $k = substr($k, 2);
        $v = false;
    }
    if (in_array($k, array('open'))) continue;
    if (isset($_options->$k))
        $_options->$k = $v;
}

# ensure user props
if (sess('u:id')) {
    if (!isSess('u:link')) sess('u:link', $_user);
    if (!isSess('u:name')) {
        $_user_name = basename($_user);
        $c = strpos($_user_name, ':');
        if ($c > 0)
            $_user_name = substr($_user_name, $c+1);
        sess('u:name', $_user_name);
    }
}

TAG(__FILE__, __LINE__, '$Id$');
