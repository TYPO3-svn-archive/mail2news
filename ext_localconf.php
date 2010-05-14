<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['getmail_cli'] = array('EXT:mail2news/getmail_cli.php','_CLI_mail2news');
?>
