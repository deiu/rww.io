<?php
/*
 * Global configuration options
 */

// default TLD name
define('ROOT_DOMAIN', 'rww.io');

// display debug info in syslog
define('DEBUG', true);

// respect caching - return 304 header and exit
define('CACHING', false);

// max allowed image size for uploads (default=3MB)
define('IMAGE_SIZE', 3000000);

// disk quota for each data store (default=10MB)
define('DISK_QUOTA', 100);

// default filename for new resources created through LDPCs
define('LDPR_PREFIX', 'resource_');
define('LDPC_PREFIX', 'dir_');
