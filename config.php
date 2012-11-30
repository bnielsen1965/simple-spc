<?php
/**
 * IMPORTANT INSTALL NOTES
 *
 * The database settings and the SITE_URL must be configured when installing
 * the application.
 */

// demo mode to disable some features, i.e. changing passwords, creating users, etc.
define('DEMO_MODE', 1);

// database settings
define('DATABASE_HOST', 'localhost');
define('DATABASE_USERNAME', 'dev');
define('DATABASE_PASSWORD', 'dev');
define('DATABASE_NAME', 'spc');


// site URLs
define('SITE_URL', 'http://192.168.122.243/sspc/');
define('LOGIN_URL', SITE_URL . 'index.php');


date_default_timezone_set(@ date_default_timezone_get());


// site paths
define('ROOT_PATH', dirname(__FILE__) . '/');
define('CLASS_PATH', ROOT_PATH . 'classes/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('HEADERS', ROOT_PATH . 'headers/');


define('METRIC_LIST_LIMIT', 3); // maximum number of metrics to show in the list
define('USER_LIST_LIMIT', 3); // maximum number of users to show in the list

// class auto loader
function __autoload($class_name) {
  include CLASS_PATH . $class_name . '.php';
}


// user ACL flags 
DEFINE('ACL_ADMIN_MASK', 1);	// bit 0, defines an account as administrator
DEFINE('ACL_ENABLED_MASK', 2);	// bit 1, must be set to enable an account
DEFINE('ACL_OPERATOR_MASK', 4);
DEFINE('ACL_TECHNICIAN_MASK', 8);
DEFINE('ACL_ENGINEER_MASK', 16);

// start session
session_name('sspc');
session_start();

?>