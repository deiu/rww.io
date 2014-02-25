<?php
/* MKCOL.php
 * service HTTP MKCOL controller
 */

require_once('runtime.php');

// permissions
if (empty($_user))
    httpStatusExit(401, 'Unauthorized');

if ($_wac->can('Write') == false)
    httpStatusExit(403, 'Forbidden');

// check quota (avoids making lots of dirs if out of space)
if (check_quota($_root, 10) == false)
    httpStatusExit(507, 'Insufficient Storage');

// action
@mkdir($_filename, 0777, true);
httpStatusExit(201, 'Created');
