<?php

########################################################################
# Extension Manager/Repository config file for ext "mail2news".
#
# Auto generated 29-04-2010 17:17
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Mail to tt_news',
	'description' => 'Import mail messages to tt_news records',
	'category' => 'services',
	'author' => 'Loek Hilgersom',
	'author_email' => 'typo3extensions@netcoop.nl',
	'shy' => '',
	'dependencies' => 'tt_news',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'NetCoop.nl',
	'version' => '1.9.4',
	'constraints' => array(
		'depends' => array(
			'tt_news' => '2.0.0',
			'php' => '5.0.0-0.0.0',
			'typo3' => '4.1.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:9:"ChangeLog";s:4:"6cc1";s:10:"README.txt";s:4:"170f";s:30:"class.tx_mail2news_getmail.php";s:4:"a96b";s:27:"class.tx_mail2news_imap.php";s:4:"9058";s:29:"class.tx_mail2news_ttnews.php";s:4:"25ce";s:21:"ext_conf_template.txt";s:4:"a97c";s:12:"ext_icon.gif";s:4:"d436";s:17:"ext_localconf.php";s:4:"d56c";s:15:"ext_php_api.dat";s:4:"f4d2";s:15:"getmail_cli.php";s:4:"e505";s:8:"init.php";s:4:"69ad";s:14:"doc/manual.sxw";s:4:"2e6b";s:21:"doc/screenshot-em.png";s:4:"887e";s:19:"doc/wizard_form.dat";s:4:"3f4c";s:20:"doc/wizard_form.html";s:4:"3dad";}',
	'suggests' => array(
	),
);

?>