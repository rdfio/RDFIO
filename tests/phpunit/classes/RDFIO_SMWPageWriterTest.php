<?php

class RDFIOSMWPageWriterTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testExtractPropertiesSimple() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000]].
EOT;

		$expectedOutput = array(
			'Has capital' => array( 'value' => 'Stockholm', 'wikitext' => '[[Has capital::Stockholm]]'),
			'Has population' => array( 'value' => '10000000', 'wikitext' => '[[Has population::10000000]]')
		);

		$extractedProperties = $this->invokeMethod( $smwWriter, 'extractProperties', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedProperties, true, true );
	}

	public function testExtractPropertiesDifferentDisplayValue() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
EOT;

		$expectedOutput = array(
			'Has capital' => array( 'value' => 'Stockholm', 'wikitext' => '[[Has capital::Stockholm|Sthlm]]' ),
			'Has population' => array( 'value' => '10000000', 'wikitext' => '[[Has population::10000000|ten million]]' )
		);

		$extractedProperties = $this->invokeMethod( $smwWriter, 'extractProperties', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedProperties, true, true );
	}


		/**
	 * Call protected/private method of a class.
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	public function invokeMethod(&$object, $methodName, array $parameters = array())
	{
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}

}
