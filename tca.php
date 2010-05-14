<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_mail2news_importer'] = array (
	'ctrl' => $TCA['tx_mail2news_importer']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,override_sections,allowed_senders,mail_server,mail_username,mail_password,imap,use_ssl,self_signed_certificate,portno,delete_after_download,delete_rejected_mail,concatenate_text_parts,max_image_size,max_attachment_size,imageextensions,attachmentextensions,category_identifier,subheader_identifier,default_category,news_cruser_id,hide_by_default,clearcachecmd'
	),
	'feInterface' => $TCA['tx_mail2news_importer']['feInterface'],
	'columns' => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.title',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'override_sections' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections',		
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.0', '0'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.1', '6'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.2', '5'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.3', '3'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.4', '4'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.5', '2'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.6', '1'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.override_sections.I.7', '7'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'allowed_senders' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.allowed_senders',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'mail_server' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.mail_server',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'nospace',
			)
		),
		'mail_username' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.mail_username',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'nospace',
			)
		),
		'mail_password' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.mail_password',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'nospace,password',
			)
		),
		'imap' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.imap',		
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.imap.I.0', '0'),
					array('LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.imap.I.1', '1'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'use_ssl' => array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.use_ssl',
			'config' => array (
				'type' => 'check',
			)
		),
		'self_signed_certificate' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.self_signed_certificate',		
			'config' => array (
				'type' => 'check',
			)
		),
		'portno' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.portno',		
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'delete_after_download' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.delete_after_download',		
			'config' => array (
				'type' => 'check',
			)
		),
		'delete_rejected_mail' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.delete_rejected_mail',		
			'config' => array (
				'type' => 'check',
			)
		),
		'concatenate_text_parts' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.concatenate_text_parts',		
			'config' => array (
				'type' => 'check',
			)
		),
		'max_image_size' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.max_image_size',		
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'max_attachment_size' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.max_attachment_size',		
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'imageextensions' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.imageextensions',		
			'config' => array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'checkbox' => '',	
				'eval' => 'nospace',
			)
		),
		'attachmentextensions' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.attachmentextensions',		
			'config' => array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'checkbox' => '',	
				'eval' => 'nospace',
			)
		),
		'category_identifier' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.category_identifier',		
			'config' => array (
				'type' => 'input',	
				'size' => '12',
			)
		),
		'subheader_identifier' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.subheader_identifier',		
			'config' => array (
				'type' => 'input',	
				'size' => '12',
			)
		),
		'default_category' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.default_category',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'news_cruser_id' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.news_cruser_id',		
			'config' => array (
				'type' => 'select',	
				'foreign_table' => 'be_users',	
				'foreign_table_where' => 'ORDER BY be_users.uid',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'hide_by_default' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.hide_by_default',		
			'config' => array (
				'type' => 'check',
			)
		),
		'clearcachecmd' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.clearcachecmd',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'nospace',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.mailbox, mail_server, mail_username, mail_password, imap, use_ssl, self_signed_certificate, portno, delete_after_download, delete_rejected_mail,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.processing, concatenate_text_parts, max_image_size, max_attachment_size, imageextensions, attachmentextensions,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.newsrecord, category_identifier, subheader_identifier, default_category, news_cruser_id, hide_by_default, clearcachecmd,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'6' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.mailbox, mail_server, mail_username, mail_password, imap, use_ssl, self_signed_certificate, portno, delete_after_download, delete_rejected_mail,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'5' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.processing, concatenate_text_parts, max_image_size, max_attachment_size, imageextensions, attachmentextensions,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'3' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.newsrecord, category_identifier, subheader_identifier, default_category, news_cruser_id, hide_by_default, clearcachecmd,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'4' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.mailbox, mail_server, mail_username, mail_password, imap, use_ssl, self_signed_certificate, portno, delete_after_download, delete_rejected_mail,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.processing, concatenate_text_parts, max_image_size, max_attachment_size, imageextensions, attachmentextensions,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'2' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.mailbox, mail_server, mail_username, mail_password, imap, use_ssl, self_signed_certificate, portno, delete_after_download, delete_rejected_mail,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.newsrecord, category_identifier, subheader_identifier, default_category, news_cruser_id, hide_by_default, clearcachecmd,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'1' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.processing, concatenate_text_parts, max_image_size, max_attachment_size, imageextensions, attachmentextensions,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.newsrecord, category_identifier, subheader_identifier, default_category, news_cruser_id, hide_by_default, clearcachecmd,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
		'7' => array('showitem' =>
			'--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.general, hidden;;1;;1-1-1, title;;;;2-2-2, allowed_senders, override_sections;;;;3-3-3,
			--div--;LLL:EXT:mail2news/locallang_db.xml:tx_mail2news_importer.tabs.extended,'
		),
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>