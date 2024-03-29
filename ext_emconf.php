<?php

########################################################################
# Extension Manager/Repository config file for ext "mail2news".
#
# Auto generated 29-06-2012 11:51
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Mail to tt_news',
	'description' => 'Import messages from an email account into tt_news or t3blog records. The easiest way to publish content on your site, start "moblogging" with TYPO3!',
	'category' => 'services',
	'author' => 'Loek Hilgersom',
	'author_email' => 'typo3extensions@netcoop.nl',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'NetCoop.nl',
	'version' => '2.0.3',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-5.3.99',
			'typo3' => '4.1.0-4.7.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:27:{s:9:"ChangeLog";s:4:"140e";s:10:"README.txt";s:4:"170f";s:30:"class.tx_mail2news_getmail.php";s:4:"7e6a";s:27:"class.tx_mail2news_imap.php";s:4:"d5e8";s:38:"class.tx_mail2news_scheduler_start.php";s:4:"4fcf";s:29:"class.tx_mail2news_t3blog.php";s:4:"542c";s:29:"class.tx_mail2news_ttnews.php";s:4:"9bb6";s:16:"ext_autoload.php";s:4:"690f";s:21:"ext_conf_template.txt";s:4:"72d2";s:12:"ext_icon.gif";s:4:"d436";s:17:"ext_localconf.php";s:4:"7eec";s:15:"ext_php_api.dat";s:4:"095e";s:14:"ext_tables.php";s:4:"3a9b";s:14:"ext_tables.sql";s:4:"7594";s:15:"getmail_cli.php";s:4:"d563";s:30:"icon_tx_mail2news_importer.gif";s:4:"d436";s:8:"init.php";s:4:"69ad";s:26:"locallang_csh_importer.xml";s:4:"92b3";s:16:"locallang_db.xml";s:4:"2b02";s:7:"tca.php";s:4:"a384";s:45:"Tests/Unit/class.tx_mail2news_getmailTest.php";s:4:"0c12";s:44:"Tests/Unit/class.tx_mail2news_ttnewsTest.php";s:4:"17e4";s:57:"Tests/Unit/Fixtures/class.tx_mail2news_phpunittypo3db.php";s:4:"ea61";s:14:"doc/manual.sxw";s:4:"b05d";s:21:"doc/screenshot-em.png";s:4:"887e";s:19:"doc/wizard_form.dat";s:4:"b8c3";s:20:"doc/wizard_form.html";s:4:"c6a8";}',
	'suggests' => array(
	),
);

?>