<?php

	// Stop script if someone tries to access it through HTTP
	if(isset($_SERVER)) {
		$GLOBALS['__SERVER']	=&$_SERVER;
	} elseif(isset($HTTP_SERVER_VARS)) {
		$GLOBALS['__SERVER']	=&$HTTP_SERVER_VARS;
	}
	if (isset($GLOBALS['__SERVER']['HTTP_HOST'])) die(date("Y-m-d H:i:s ") . 'Unallowed script access (HTTP request)');

	define("PATH_typo3conf", dirname(dirname(dirname(__FILE__)))."/");
	define("PATH_site", dirname(PATH_typo3conf)."/");
	define("PATH_typo3", PATH_site."typo3/");       // Typo-configuraton path
	define("PATH_t3lib", PATH_site."t3lib/");
	define("PATH_uploads_pics", PATH_site."uploads/pics/");
	define('TYPO3_MODE','BE');
	ini_set('error_reporting', E_ALL ^ E_NOTICE);
	//ini_set('max_execution_time',0);
	define('TYPO3_cliMode', TRUE);

	// Read TYPO3 configuration
	require_once (PATH_typo3conf.'localconf.php');

	require_once (PATH_t3lib.'class.t3lib_tcemain.php');
	require_once (PATH_t3lib.'class.t3lib_div.php');
	require_once (PATH_t3lib.'class.t3lib_extmgm.php');
	require_once (PATH_t3lib.'config_default.php');

	// Connect to TYPO3 database
	if (!defined ("TYPO3_db")) die ("The configuration file was not included.");
	require_once(PATH_t3lib.'class.t3lib_db.php');          // The database library
	$TYPO3_DB = t3lib_div::makeInstance('t3lib_db');
	$TYPO3_DB->sql_pconnect (TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);
	$TYPO3_DB->sql_select_db (TYPO3_db);
	
	// Store configuration settings from EM in array $extConf
	$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['mail2news']);

?>