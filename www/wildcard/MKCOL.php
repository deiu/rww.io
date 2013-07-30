<?php
/* MKCOL.php
 * service HTTP MKCOL controller
 *
 * $Id$
 */

require_once('runtime.php');

// permissions
if (empty($_user)) {
    httpStatusExit(401, 'Unauthorized');
    
// Web Access Control
$_wac = new WAC($_user, $_filename, $_filebase, $_base, $_options);
if ($_wac->can('Write') == false) {
    // debug
    openlog('data.fm', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
    syslog(LOG_INFO, $_wac->getReason());
    closelog();
    httpStatusExit(403, 'Forbidden');
} else {
    openlog('data.fm', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
    syslog(LOG_INFO, $_wac->getReason());
}

// action
@mkdir($_filename, 0777, true);
