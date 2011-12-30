<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (t3lib_extMgm::extPath('mail2news').'class.tx_mail2news_ttnews.php');
require_once (t3lib_extMgm::extPath('mail2news').'Tests/Unit/Fixtures/class.tx_mail2news_phpunittypo3db.php');

/**
 * Description of class
 *
 * @author loek
 */
class tx_mail2news_ttnewsTest extends Tx_Phpunit_TestCase {

	/**
	 * @var tx_mail2news_getmail
	 */
	private $fixture = NULL;
	/**
	 * @var t3lib_DB
	 */
	private $databaseBackup = NULL;
	/**
	 * @var t3lib_beUserAuth 
	 */
	private $backEndUserBackup = NULL;

	/**
	 * @var Tx_Phpunit_Framework
	 */
	private $tf;
	
	private $testDataCreated = FALSE;
	
	private $flippedTestData = array();
	
	public function setUp() {
		$this->databaseBackup = $GLOBALS['TYPO3_DB'];
		$this->backEndUserBackup = $GLOBALS['BE_USER'];
		
		$this->tf = new Tx_Phpunit_Framework('tt_news');
		$this->fixture = new tx_mail2news_ttnews();
	}

	public function tearDown() {
		$this->tf->cleanUp();
		unset($this->fixture, $this->tf);

		t3lib_div::purgeInstances();

		$GLOBALS['TYPO3_DB'] = $this->databaseBackup;
		$GLOBALS['BE_USER'] = $this->backEndUserBackup;
	}

	public function categoryIdsDataProvider() {

		if ($this->testDataCreated) {
			$testCategories = array('Blabla and such', 'Linux', 'Windows', 'Mac', 'OS2');
			$testData = array();
			$this->tf = new Tx_Phpunit_Framework('tt_news');
			foreach ($testCategories as $testCategory) {
				$uid = $this->tf->createRecord('tt_news_cat', array('title' => $testCategory));
				$testData[$uid] = $testCategory;
			}
			$this->flippedTestData = array_flip($testData);
			$this->testDataCreated = TRUE;
		}
		
		return array(
			'Empty argument returns FALSE' => array('', FALSE),
			'Existing Catname returns uid' => array('Mac', strval($this->flippedTestData['Mac'])),
			'2 existing catnames returns 2 uids' => array('Linux, OS2', strval($this->flippedTestData['Linux']) . ',' . strval($this->flippedTestData['OS2'])),
			'2 existing catnames reversed returns 2 uids reversed' => array('OS2, Linux', strval($this->flippedTestData['OS2']) . ',' . strval($this->flippedTestData['Linux'])),
		);

	}

//		$databaseMock = $this->getMock('t3lib_DB');
//		$GLOBALS['TYPO3_DB'] = $databaseMock;
	/**
	 * @test
	 * @dataProvider categoryIdsDataProvider
	 */
	public function categoryIdsReturnsExpectedValues($input, $expectedResult) {
		$GLOBALS['TYPO3_DB'] = new tx_mail2news_phpunittypo3db($GLOBALS['TYPO3_DB']);
		
		$categoriesResult = $this->fixture->category_ids($input);
		
		$this->assertSame($expectedResult, $categoriesResult);
	}
	
	/**
	 * @test
	 */
	public function category_idsWithEmptyInputReturnsFalse() {
		$categories = $this->fixture->category_ids('');
		$this->assertSame(FALSE, $categories);
	}

	/**
	 * @test
	 */
	public function storeRecordInsertsDataIntoDatabase() {
		$backEndUserMock = $this->getMock('t3lib_beUserAuth');
		$GLOBALS['BE_USER'] = $backEndUserMock;

		$databaseMock = $this->getMock('t3lib_DB');
		$GLOBALS['TYPO3_DB'] = $databaseMock;

		$referenceIndexMock = $this->getMock('t3lib_refindex');
		t3lib_div::addInstance('t3lib_refindex', $referenceIndexMock);

		
		// Prepare item array as input for the method to test
		$item = array(
		    'bodytext' => 'Bodytext',
		    'image' => 'imagefilenames.jpg',
		    'author' => 'fromname',
		    'title' => 'subject',
		    // newsitem fields below do not need to be encoded
		    'author_email' => 'fromname@email.com',
		    'datetime' => time(),
		    // supply additional fields from configuration defaults
		    'pid' => 10,
		    'hidden' => 0,
		    'cruser_id' => 1
		);

		// Create the expected array that should go into the database
		$expectedItem = array(
		    'crdate' => time(),
		    'tstamp' => time(),
		    'category' => 0
		);

		$expectedItem = array_merge($expectedItem, $item);

		// Create mock object for INSERTquery
		$databaseMock->expects($this->once())
			->method('exec_INSERTquery')
			->with('tt_news', $expectedItem)
			->will($this->returnValue(TRUE));
			
		// Expected message to go into simplelog
		$expectedlogmsg = 'News item created: "subject", created on page (pid 10)';

		$backEndUserMock->expects($this->once())
			->method('simplelog')
			->with($expectedlogmsg, 'mail2news');
			
		// Check if reference index gets updated
		$databaseMock->expects($this->once())
			->method('sql_insert_id')
			->will($this->returnValue('42'));
		
		$referenceIndexMock->expects($this->once())
			->method('updateRefIndexTable')
			->with('tt_news', 42);
		
		$this->fixture->store_record($item);
	}

}

?>
