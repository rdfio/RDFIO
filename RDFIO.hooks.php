<?php

class RDFIOHooks {

	public static function onUnitTestsList( &$files ) {
		$testDir = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'phpunit';
		$files = array_merge( $files, glob( $testDir . DIRECTORY_SEPARATOR . '*Test.php' ) );
		return true;
	}

}