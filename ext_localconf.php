<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
//$GLOBALS['TYPO3_CONF_VARS']
$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']['getmail_cli'] = array(
	'EXT:mail2news/getmail_cli.php',
	'_CLI_mail2news'
);
//$GLOBALS['TYPO3_CONF_VARS']
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_mail2news_scheduler_start'] = array(
	'extension' => 'mail2news',
	'title' => 'Mail-to-news Importer',
	'description' => 'Imports email messages to news articles using the mail2news extension'
);
?>