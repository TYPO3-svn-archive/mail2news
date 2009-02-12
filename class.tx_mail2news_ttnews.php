<?php

class tx_mail2news_ttnews {

	public $extconf;

	/*
	*	Construct new object and store extconf in object property
	*/
	
	function __construct($extconf) {
		$this->extconf = $extconf;
	}

	function store_news($newsitem) {
	
		global $TYPO3_DB;

		// supply additional fields from configuration defaults
		$newsitem['pid'] = $this->extconf['pid'];
		$newsitem['hidden'] = $this->extconf['hide_by_default'];
		// Set category for this record?
		$addCat = isset($newsitem['category']);
		// tt_news field category in table tt_news contains no of categories
		if ($addCat) {
			$category_id = $newsitem['category'];
			$newsitem['category'] = 1;
		} else {
			$newsitem['category'] = 0;
		}
		$newsitem['cruser_id'] = $this->extconf['cruser_id'];
		$newsitem['tstamp'] = $newsitem['crdate'] = time();

		/*	
		// encode everything with UTF-8 if set in localconf.php
		if($TYPO3_CONF_VARS['BE']['forceCharset'] == 'utf-8'){
			foreach($newsitem as $field => $value){
				$newsitem[$field] = utf8_encode($value);
			}
		}
		*/
		
		/*
		foreach($newsitem as $key=>$item) {
			echo '$newsitem['. $key . '] = ' . $item . "\n";
		}
		echo "\n";
		*/
		
		$TYPO3_DB->exec_INSERTquery('tt_news',$newsitem);
		
		// Set category in table tt_news_cat_mm with UID of new record
		if ($addCat) {
			$catmm = array(
				'uid_local' => $TYPO3_DB->sql_insert_id(),
				'uid_foreign' => $category_id,
				'sorting' => 1,
				//'tablenames' => ''
			);
			$TYPO3_DB->exec_INSERTquery('tt_news_cat_mm',$catmm);
		}
		
	}
}
?>
