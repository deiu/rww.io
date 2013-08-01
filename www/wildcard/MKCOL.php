<?php
/* MKCOL.php
 * service HTTP MKCOL controller
 *
 * $Id$
 */

require_once('runtime.php');

// permissions
if (empty($_user))
    httpStatusExit(401, 'Unauthorized');

if ($_wac->can('Write') == false) {
    // debug
    if (DEBUG) {
        openlog('RWW.IO', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
        syslog(LOG_INFO, $_wac->getReason());
        closelog();
    }
    httpStatusExit(403, 'Forbidden');
}

// action
@mkdir($_filename, 0777, true);
