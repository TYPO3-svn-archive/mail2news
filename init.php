<?php

ini_set('error_reporting', E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
//ini_set('error_reporting', E_ALL ^ E_NOTICE);

// Stop script if someone tries to access it through HTTP
if(isset($_SERVER)) {
	$GLOBALS['__SERVER'] = &$_SERVER;
} elseif(isset($HTTP_SERVER_VARS)) {
	$GLOBALS['__SERVER'] = &$HTTP_SERVER_VARS;
}
if (isset($GLOBALS['__SERVER']['HTTP_HOST'])) die(date("Y-m-d H:i:s ") . 'Unallowed script access (HTTP request)');

if (!defined('TYPO3_cliMode')) die('You cannot run this script directly!');
require_once(PATH_t3lib.'class.t3lib_cli.php');

define("PATH_uploads_pics", PATH_site."uploads/pics/");
define("PATH_uploads_media", PATH_site."uploads/media/");

?>