#
# Table structure for table 'tx_mail2news_importer'
#
CREATE TABLE tx_mail2news_importer (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	override_sections int(11) DEFAULT '0' NOT NULL,
	allowed_senders tinytext,
	mail_server tinytext,
	mail_username tinytext,
	mail_password tinytext,
	imap int(11) DEFAULT '0' NOT NULL,
	use_ssl tinyint(3) DEFAULT '0' NOT NULL,
	self_signed_certificate tinyint(3) DEFAULT '0' NOT NULL,
	portno int(11) DEFAULT '0' NOT NULL,
	delete_after_download tinyint(3) DEFAULT '0' NOT NULL,
	delete_rejected_mail tinyint(3) DEFAULT '0' NOT NULL,
	concatenate_text_parts tinyint(3) DEFAULT '0' NOT NULL,
	max_image_size int(11) DEFAULT '0' NOT NULL,
	max_attachment_size int(11) DEFAULT '0' NOT NULL,
	imageextensions tinytext,
	attachmentextensions tinytext,
	record_type tinytext,
	default_category tinytext,
	default_t3blog_category tinytext,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	hide_by_default tinyint(3) DEFAULT '0' NOT NULL,
	clearcachecmd tinytext,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Update Table structure for table 'tt_news'
#
CREATE TABLE tt_news (
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	KEY phpunit_dummy (is_dummy_record)
);

#
# Update Table structure for table 'tt_news_cat'
#
CREATE TABLE tt_news_cat (
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	KEY phpunit_dummy (is_dummy_record)
);

#
# Update Table structure for table 'tx_t3blog_post'
#
CREATE TABLE tx_t3blog_post (
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	KEY phpunit_dummy (is_dummy_record)
);

#
# Update Table structure for table 'tx_t3blog_cat'
#
CREATE TABLE tx_t3blog_cat (
	is_dummy_record tinyint(1) unsigned DEFAULT '0' NOT NULL,
	KEY phpunit_dummy (is_dummy_record)
);
