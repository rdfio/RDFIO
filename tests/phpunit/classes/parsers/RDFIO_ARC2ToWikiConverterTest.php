<?php

class RDFIOARC2ToWikiConverterTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * @covers RDFIOARC2ToWikiConverter::convert
	 */
	public function testConvert() {
		$testData = new RDFIOTestData();

		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$arc2rdfxmlparser->parseData( $testData->getTestImportData() );

		$triples = $arc2rdfxmlparser->triples;
		$tripleIndex = $arc2rdfxmlparser->getSimpleIndex();
		$namespaces = $arc2rdfxmlparser->nsp;

		$arc2towikiconverter = new RDFIOARC2ToWikiConverter();
		$wikiPages = $arc2towikiconverter->convert( $triples, $tripleIndex, $namespaces );

		$this->assertEquals( 12, count( $wikiPages ), "No wiki pages converted from triples!" );
	}

}