<?php

########################################################################
# Extension Manager/Repository config file for ext: "mail2news"
#
# Auto generated 01-10-2008 17:46
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'NetCoop.nl',
	'version' => '1.9.0',
	'constraints' => array(
		'depends' => array(
			'tt_news' => '2.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:10:{s:9:"ChangeLog";s:4:"ae9c";s:10:"README.txt";s:4:"9fa9";s:21:"ext_conf_template.txt";s:4:"fca7";s:12:"ext_icon.gif";s:4:"1bdc";s:11:"getmail.php";s:4:"d901";s:8:"init.php";s:4:"1966";s:14:"doc/manual.sxw";s:4:"43d0";s:21:"doc/screenshot-em.png";s:4:"887e";s:19:"doc/wizard_form.dat";s:4:"3f4c";s:20:"doc/wizard_form.html";s:4:"c3ce";}',
	'suggests' => array(
	),
);

?>
