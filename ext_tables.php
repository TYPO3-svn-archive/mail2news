<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::allowTableOnStandardPages('tx_mail2news_importer');

t3lib_extMgm::addPageTSConfig('
mod.tx_mail2news {
	fieldPresetValues {
		imageorient = 17
		imagecols = 1
		sectionindex = 1
	}
	fieldMarkers {
		short = #short#
		allow_comments = #comments#
		tagClouds = #tags#
		categories = #cats#
		header = #header#
		tx_newscalendar_state = #cal#
	}
}
');

$TCA['tx_mail2news_importer'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer',		
		'label'     => 'title',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'override_sections',	
		'sortby' => 'sorting',	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_mail2news_importer.gif',
		'dividers2tabs'     => true,
	),
);

// Add CSH support (Help icons) to extension tables
t3lib_extMgm::addLLrefForTCAdescr('tx_mail2news_importer','EXT:mail2news/locallang_csh_importer.xml');

?>