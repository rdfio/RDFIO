<?php

class RDFIORDFImporterTest extends MediaWikiTestCase {

	protected function setUp() {
		$this->arc2rdfxmlparser = ARC2::getRDFXMLParser();
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	// Hmm, realizing that this is more a test of ARC2 than of RDFIO ...	

	public function testParseImportData() {
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();

		$testDataObj = new RDFIOTestData();
		$importData = $testDataObj->getTestImportData();

		$arc2rdfxmlparser->parseData( $importData );
		$this->assertGreaterThan( 0, $arc2rdfxmlparser->countTriples(), 'No triples after parsing!' );
	}

	public function testParseInvalidImportData() {
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();

		$testDataObj = new RDFIOTestData();
		$importData = $testDataObj->getInvalidTestImportData();

		$arc2rdfxmlparser->parseData( $importData );
		$this->assertEquals( 0, $arc2rdfxmlparser->countTriples(), 'Triples found even for invalid RDF data!' );
	}

}