<?php

class RDFIOSMWPageWriterTest extends RDFIOTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}


	public function testBuildPropertyTemplateParamIndex() {
		$smwWriter = new RDFIOSMWPageWriter();

		$facts = array(
			array(
				'p' => 'Has capital',
				'o' => 'Stockholm',
			),
			array(
				'p' => 'Has population',
				'o' => '10000001',
			),
		);
		$tplFacts = array(
			'Country' => array(
				'Has capital' => 'Capital',
				'Has population' => 'Country population',
			),
		);

		$expectedPropTplParamIdx = array(
			'Has capital' => array(
				'Country' => 'Capital',
			),
			'Has population' => array(
				'Country' => 'Country population',
			),
		);

		$propTplParamIdx = $this->invokeMethod( $smwWriter, 'buildPropertyTemplateParamIndex', array( $facts, $tplFacts ) );

		$this->assertArrayEquals( $expectedPropTplParamIdx, $propTplParamIdx, true, true );
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

		$oldFacts = array(
			'Has capital' => array( 'property' => 'Has capital', 'value' => 'Stockholm', 'wikitext' => '[[Has capital::Stockholm]]' ),
			'Has population' => array( 'property' => 'Has population', 'value' => '10000000', 'wikitext' => '[[Has population::10000000|]]' ),
		);
		$newFact = array( 'p' => 'Has population', 'o' => '10000001' );

		$updatedWikiText = $this->invokeMethod( $smwWriter, 'updateExplicitFactsInText', array( $newFact, $oldFacts, $oldWikiText ) );

		$this->assertEquals( $expectedWikiText, $updatedWikiText );
	}

	public function testUpdateExplicitFactsInTextExistingEquivURI() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiText = <<<EOT
[[Has capital::Stockholm]]
[[Has population::10000000]]

[[Equivalent URI::http://example.org/onto/Sweden]]
[[Category:Country]]
EOT;

		$oldFacts = array(
			'Has capital' => array( 'property' => 'Has capital', 'value' => 'Stockholm', 'wikitext' => '[[Has capital::Stockholm]]' ),
			'Has population' => array( 'property' => 'Has population', 'value' => '10000000', 'wikitext' => '[[Has population::10000000]]' ),
			'Equivalent URI' => array( 'property' => 'Equivalent URI', 'value' => 'http://example.org/onto/Sweden', 'wikitext' => '[[Equivalent URI::http://example.org/onto/Sweden]]' ),
		);
		$newFact = array( 'p' => 'Equivalent URI', 'o' => 'http://example.org/onto/Sweden' );

		$updatedWikiText = $this->invokeMethod( $smwWriter, 'updateExplicitFactsInText', array( $newFact, $oldFacts, $oldWikiText ) );

		$this->assertEquals( $oldWikiText, $updatedWikiText );
	}

	/**
	 *
	 */
	public function testUpdateTemplateCalls() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiText = <<<EOT
The capital of Sweden is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
{{Country
|Capital=Stockholm
|Population=10000000
}}
{{Geographical region}}
EOT;

		$expectedWikiText = <<<EOT
The capital of Sweden is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
{{Country
|Capital=Stockholm
|Population=10000001
}}
{{Geographical region}}
EOT;

		$propTplIndex = array(
			'Has population' => array(
				'Country' => 'Population',
			),
		);

		$tplCallCountry = <<<EOT
{{Country
|Capital=Stockholm
|Population=10000000
}}
EOT;
		$tplCallGeoRegion = <<<EOT
{{Geographical region}}
EOT;


		$oldTemplateCalls = array(
			'Country' => array( 'calltext' => $tplCallCountry ),
			'Geographical region' => array( 'calltext' => $tplCallGeoRegion ),
		);

		$newFact = array( 'p' => 'Has population', 'o' => '10000001' );

		$updatedWikiText = $this->invokeMethod( $smwWriter, 'updateTemplateCalls', array( $newFact, $propTplIndex, $oldTemplateCalls, $oldWikiText ) );

		$this->assertEquals( $expectedWikiText, $updatedWikiText );
	}


	public function testUpdateTemplateCallsAddTemplateParam() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiText = <<<EOT
{{Country
|Capital=Stockholm
|Population=10000000
}}
{{Geographical region}}
EOT;

		$expectedWikiText = <<<EOT
{{Country
|Capital=Stockholm
|Population=10000000
|Second city=Gothenburg
}}
{{Geographical region}}
EOT;

		$propTplIndex = array(
			'Has population' => array(
				'Country' => 'Population',
			),
			'Has second city' => array(
				'Country' => 'Second city',
			)
		);

		$tplCallCountry = <<<EOT
{{Country
|Capital=Stockholm
|Population=10000000
}}
EOT;
		$tplCallGeoRegion = <<<EOT
{{Geographical region}}
EOT;


		$oldTemplateCalls = array(
			'Country' => array( 'calltext' => $tplCallCountry ),
			'Geographical region' => array( 'calltext' => $tplCallGeoRegion ),
		);

		$newFact = array( 'p' => 'Has second city', 'o' => 'Gothenburg' );

		$updatedWikiText = $this->invokeMethod( $smwWriter, 'updateTemplateCalls', array( $newFact, $propTplIndex, $oldTemplateCalls, $oldWikiText ) );

		$this->assertEquals( $expectedWikiText, $updatedWikiText );
	}


	public function testAddNewExplicitFact() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiText = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000|]].
EOT;

		$fact = array(
			'p' => 'Has second city',
			'o' => 'Gothenburg',
		);

		$expectedOutput = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000|]].
[[Has second city::Gothenburg]]
EOT;
		$newWikiText = $this->invokeMethod( $smwWriter, 'addNewExplicitFact', array( $fact, $oldWikiText ) );

		$this->assertEquals( $expectedOutput, $newWikiText );
	}



	public function testAddNewExplicitFactDontDuplicate() {
		$smwWriter = new RDFIOSMWPageWriter();

		$oldWikiText = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000|]].
EOT;

		$fact = array(
			'p' => 'Has population',
			'o' => '10000000',
		);

		$newWikiText = $this->invokeMethod( $smwWriter, 'addNewExplicitFact', array( $fact, $oldWikiText ) );

		$this->assertEquals( $oldWikiText, $newWikiText );
	}


	public function testExtractFacts() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden is [[Has capital::Stockholm]], which has
a population of [[Has population::10000000|]]. It's second city is [[Has second city::Gothenburg|Göteborg]].
EOT;

		$expectedOutput = array(
			'Has capital' => array( 'property' => 'Has capital', 'value' => 'Stockholm', 'wikitext' => '[[Has capital::Stockholm]]' ),
			'Has population' => array( 'property' => 'Has population', 'value' => '10000000', 'wikitext' => '[[Has population::10000000|]]' ),
			'Has second city' => array( 'property' => 'Has second city', 'value' => 'Gothenburg', 'wikitext' => '[[Has second city::Gothenburg|Göteborg]]' ),
		);

		$extractedFacts = $this->invokeMethod( $smwWriter, 'extractFacts', array( $wikiContent ) );

		$this->assertArrayEquals( $expectedOutput, $extractedFacts, true, true );
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

	public function testAddNewCategory() {
		$smwWriter = new RDFIOSMWPageWriter();

		$wikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
EOT;

		$category = 'Geographical region';

		$expectedWikiContent = <<<EOT
The capital of Sweden (which is [[Category:Country|a country]]) is [[Has capital::Stockholm|Sthlm]], which has
a population of [[Has population::10000000|ten million]].
[[Category:Country in Europe|]]
[[Category:Geographical region]]
EOT;

		$newWikiContent = $this->invokeMethod( $smwWriter, 'addNewCategory', array( $category, $wikiContent ) );

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
}
