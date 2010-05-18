<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ('class.tx_mail2news_getmail.php');

/**
 * Description of class.tx_mail2news_scheduler_start
 *
 * @author Loek Hilgersom
 *
 */
class tx_mail2news_scheduler_start extends tx_scheduler_Task {

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return    bool	true if task was completed successfully
	 */
	public function execute() {
		// Call the actual mail2news importer, still the cli script for backwards compatibility

		//include_once ('init.php');
		define("PATH_uploads_pics", PATH_site."uploads/pics/");
		define("PATH_uploads_media", PATH_site."uploads/media/");

		$class = t3lib_div::makeInstanceClassName('tx_mail2news_getmail');
		$main = new $class();
		$main->process_all_importers(unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mail2news']));

		// state if the execution went well
		return true;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_scheduler_start.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_scheduler_start.php']);
}
?>