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


	public function testExtractCategories() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
EOT;

		$expectedOutput = array(
			'Country' => array( 'wikitext' => '[[Category:Country|a country]]' ),
			'Country in Europe' => array( 'wikitext' => '[[Category:Country in Europe|]]' ),
		);

		$extractedCategories = $this->invokeMethod( $smwWriter, 'extractCategories', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedCategories, true, true );
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

	public function testExtractTemplates() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
{{Country
|Capital=Stockholm
|Population=10000000
}}
{{Geographical region}}
EOT;

		$expectedTplCallText = <<<EOT
{{Country
|Capital=Stockholm
|Population=10000000
}}
EOT;

		$expectedTplParams = <<<EOT
Capital=Stockholm
|Population=10000000
EOT;

		$expectedOutput = array(
			'Country' => array(
				'templateCallText' => $expectedTplCallText,
				'templateParamsValues' => $expectedTplParams ),
			'Geographical region' => array(
				'templateCallText' => '{{Geographical region}}',
				'templateParamsValues' => '' ),
		);

		$extractedTemplates = $this->invokeMethod( $smwWriter, 'extractTemplateCalls', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedTemplates, true, true );
	}

	public function testAddNewCategoriesToWikiText() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
EOT;

		$oldCategories = array(
			'Country' => array( 'wikitext' => '[[Category:Country|a country]]' ),
			'Country in Europe' => array( 'wikitext' => '[[Category:Country in Europe|]]' ),
		);

		$newCategories = array( 'Geographical region', 'Geographical region in Europe' );

		$expectedWikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
[[Category:Geographical region]]
[[Category:Geographical region in Europe]]
EOT;

		$newWikiContent = $this->invokeMethod( $smwWriter, 'addNewCategoriesToWikiText', array( $newCategories, $oldCategories, $oldWikiContent ) );

		$this->assertEquals( $expectedWikiContent, $newWikiContent );
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
