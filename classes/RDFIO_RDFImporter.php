<?php

/**
 * Class for importing RDF data, either from RDF Import Special page, or
 * from SPARQL Update queries in the SPARQL Endpoint Special Page. 
 */
class RDFIORDFImporter {
	
	function __construct() {}

	/**
	 * Import RDF/XML, e.g. from the RDF Import Special Page.
	 * @param string $importData
	 */
	public function importRdfXml( $importData ) {
		// Parse RDF/XML to triples
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$arc2rdfxmlparser->parseData( $importData );

		// Receive the data
		$triples = $arc2rdfxmlparser->triples;
		$tripleIndex = $arc2rdfxmlparser->getSimpleIndex();
		$namespaces = $arc2rdfxmlparser->nsp;
		
		$this->importFromArc2Data( $triples, $wikiPages, $namespaces );
	}

	/**
	 * Import triples, e.g. from the SPARQL Endpoint Special Page.
	 * @param array $triples
	 */
	public function importTriples( $triples ) {
		$this->importFromArc2Data( $triples );
	}

	/**
	 * Do the actual import, after having parsed the data into ARC2 data structures
	 * @param array $triples
	 * @param array $tripleIndex
	 * @param array $namespaces
	 */
	private function importFromArc2Data( $triples, $tripleIndex="", $namespaces="" ) {
		global $wgOut;
		
        // Parse data from ARC2 triples to custom data structure holding wiki pages
        $arc2towikiconverter = new RDFIOARC2ToWikiConverter();
        $wikiPages = $arc2towikiconverter->convert( $triples, $tripleIndex, $namespaces );
        
        // Import pages into wiki
        $smwPageWriter = new RDFIOSMWPageWriter();
        $smwPageWriter->import( $wikiPages );
	}

}
