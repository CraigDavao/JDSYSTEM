<?php
// config.php for Hostinger
define("SITE_URL", "https://jollydolly.shop/");

// Hostinger database details (from your control panel)
define("DB_HOST", "localhost"); // Try this instead of srv1319.hstgr.io
define("DB_USER", "u251504662_group6");
define("DB_PASS", "o7JOhio|T7"); 
define("DB_NAME", "u251504662_jollydolly");
define("DB_PORT", 3306);

// Error reporting for development
if ($_SERVER['HTTP_HOST'] == 'jollydolly.shop') {
    ini_set('display_errors', 0); // Hide errors on live site
} else {
    ini_set('display_errors', 1); // Show errors on local
}
?>