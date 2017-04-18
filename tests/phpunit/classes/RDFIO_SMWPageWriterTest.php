<?php

class RDFIOSMWPageWriterTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testExtractPropertyParameterIndex() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
## Some info
* [[Has capital::{{{Capital|}}}]]
* [[Has population::{{{Country population}}}]]
EOT;

		$expectedOutput = array(
			'Has capital' => 'Capital',
			'Has population' => 'Country population',
		);

		$extractedFacts = $this->invokeMethod( $smwWriter, 'extractPropertyParameterIndex', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedFacts, true, true );
	}

	public function testUpdateExplicitFactsInText() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiText = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000|]].
EOT;

		$expectedWikiText = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000001|]].
EOT;

		$newFact = array( 'p' => 'Has population', 'o' => '10000001' );

		$updatedWikiText = $this->invokeMethod( $smwWriter, 'updateExplicitFactsInText', array( $newFact, $oldWikiText ) );

		$this->assertEquals( $expectedWikiText, $updatedWikiText );
	}

	public function testExtractFacts() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000|]].
EOT;

		$expectedOutput = array(
			'Has capital' => array( 'property' => 'Has capital', 'value' => 'Stockholm', 'wikitext' => '[[Has capital::Stockholm]]' ),
			'Has population' => array( 'property' => 'Has population', 'value' => '10000000', 'wikitext' => '[[Has population::10000000|]]' ),
		);

		$extractedFacts = $this->invokeMethod( $smwWriter, 'extractFacts', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedFacts, true, true );
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

		$expectedcalltext = <<<EOT
{{Country
|Capital=Stockholm
|Population=10000000
}}
EOT;

		$expectedOutput = array(
			'Country' => array(
				'calltext' => $expectedcalltext,
				'paramvals' => [
					[ 'name' => 'Capital', 'val' => 'Stockholm' ],
					[ 'name' => 'Population', 'val' => '10000000' ],
				],
			),
			'Geographical region' => array(
				'calltext' => '{{Geographical region}}',
				'paramvals' => [] ),
		);

		$extractedTemplates = $this->invokeMethod( $smwWriter, 'extractTemplateCalls', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedTemplates, true, true );
	}

	public function testAddNewCategoriesToWikiText() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
EOT;

		$newCategories = array( 'Geographical region', 'Geographical region in Europe' );

		$expectedWikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
[[Category:Geographical region]]
[[Category:Geographical region in Europe]]
EOT;

		$newWikiContent = $this->invokeMethod( $smwWriter, 'addNewCategoriesToWikiText', array( $newCategories, $wikiContent ) );

		$this->assertEquals( $expectedWikiContent, $newWikiContent );
	}

	public function testExtractTplNameFromHasTemplateFact() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
Some text here.
[[Has template::Template:Country|]]
[[Has template::Template:Geographical region]]
[[Has template::Template:European country|Country in Europe]]
EOT;

		$expectedTplNames = array(
			'Country',
			'Geographical region',
			'European country',
		);

		$tplNames = $this->invokeMethod( $smwWriter, 'extractTplNameFromHasTemplateFact', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedTplNames, $tplNames, true, true );
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
