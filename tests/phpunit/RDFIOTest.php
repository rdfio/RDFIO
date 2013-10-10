<?php

// Register hooks
$wgHooks['UnitTestsList'][] = 'RDFIOTest::testPhpUnitSetup';

class RDFIOTest extends MediaWikiTestCase { 

	function setUp() {
		parent::setUp();		
	}
	function tearDown() {}

	/**
	 * Simple test to see that the PHPUnit test framework
	 * (And the MakeGood Eclipse plugin) is correctly set up.
	 */
	public function testPhpUnitSetup() {
		$this->assertTrue(true);
	}
	
}