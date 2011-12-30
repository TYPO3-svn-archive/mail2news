<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author loek
 */
class tx_mail2news_phpunittypo3db extends t3lib_DB {

	/**
	 * The constructor.
	 * 
	 * @param t3lib_DB $oldDatabase  the old database for getting the current settings
	 */
	function __construct(t3lib_DB $oldDatabase) {
		$this->link = $oldDatabase->link;
		$this->default_charset = $oldDatabase->default_charset;
	}
	
	/**
	 * Creates and executes a SELECT SQL-statement AND traverse result set and returns array with records in.
	 *
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		If set, the result array will carry this field names value as index. Requires that field to be selected of course!
	 * @return	array		Array of rows.
	 */

	function exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
		$where_clause = 'is_dummy_record=1 AND (' . $where_clause . ')';
		t3lib_utility_Debug::debug($where_clause ,'where_clause');
		return parent::exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit, $uidIndexField);
	}

}
 
?>