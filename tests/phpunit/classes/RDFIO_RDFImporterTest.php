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
		$importData = $this->getTestImportData();
		$arc2rdfxmlparser->parseData( $importData );
		$this->assertGreaterThan(0, $arc2rdfxmlparser->countTriples(), 'No triples after parsing!');
	}
	
	public function testParseInvalidImportData() {
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$importData = $this->getInvalidTestImportData();
		$arc2rdfxmlparser->parseData( $importData );
		$this->assertEquals(0, $arc2rdfxmlparser->countTriples(), 'Triples found even for invalid RDF data!');
	}
	
	function getTestImportData() {
		$testImportData = '<rdf:RDF
				xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
				xmlns:cd="http://www.recshop.fake/cd#"
				xmlns:countries="http://www.countries.org/onto/"
				xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
				>
				
				<rdf:Description
				rdf:about="http://www.recshop.fake/cd/Empire Burlesque">
				<cd:artist>Bob Dylan</cd:artist>
				<cd:country rdf:resource="http://www.countries.org/onto/USA"/>
				<cd:company>Columbia</cd:company>
				<cd:price>10.90</cd:price>
				<cd:year>1985</cd:year>
				</rdf:Description>
				
				<rdf:Description
				rdf:about="http://www.recshop.fake/cd/Hide your heart">
				<cd:artist>Bonnie Tyler</cd:artist>
				<cd:country>UK</cd:country>
				<cd:company>CBS Records</cd:company>
				<cd:price>9.90</cd:price>
				<cd:year>1988</cd:year>
				</rdf:Description>
				
				<rdf:Description
				rdf:about="http://www.countries.org/onto/USA">
				<rdfs:label>USA</rdfs:label>
				</rdf:Description>
				
				<rdf:Description rdf:about="http://www.countries.org/onto/Albums">
				<rdfs:subClassOf rdf:resource="http://www.countries.org/onto/MediaCollections"/>
				</rdf:Description>
				</rdf:RDF>';
		return $testImportData;
	}

	function getInvalidTestImportData() {
		$testImportData = '< rdf:RDF
				xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
				xmlns:cd="http://www.recshop.fake/cd#"
				xmlns:countries="http://www.countries.org/onto/"
				xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
				>
				
				<rdf:Description
				rdf:about="http://www.recshop.fake/cd/Empire Burlesque">
				<cd:artist>Bob Dylan</cd:artist>
				<cd:country rdf:resource="http://www.countries.org/onto/USA"/>
				<cd:company>Columbia</cd:company>
				<cd:price>10.90</cd:price>
				<cd:year>1985</cd:year>
				</rdf:Description>
				
				<rdf:Description
				rdf:about="http://www.recshop.fake/cd/Hide your heart">
				<cd:artist>Bonnie Tyler</cd:artist>
				<cd:country>UK</cd:country>
				<cd:company>CBS Records</cd:company>
				<cd:price>9.90</cd:price>
				<cd:year>1988</cd:year>
				</rdf:Description>
				
				<rdf:Description
				rdf:about="http://www.countries.org/onto/USA">
				<rdfs:label>USA</rdfs:label>
				</rdf:Description>
				
				<rdf:Description rdf:about="http://www.countries.org/onto/Albums">
				<rdfs:subClassOf rdf:resource="http://www.countries.org/onto/MediaCollections"/>
				</rdf:Description>
				</rdf:RDF>';
		return $testImportData;
	}
	
}