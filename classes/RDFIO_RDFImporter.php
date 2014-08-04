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
		global $triples;
		// Parse RDF/XML to triples
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$arc2rdfxmlparser->parseData( $importData );

		// Receive the data
		$triples = $arc2rdfxmlparser->triples;
		$tripleIndex = $arc2rdfxmlparser->getSimpleIndex();
		$namespaces = $arc2rdfxmlparser->nsp;

		/* DEBUGGING
		echo "*** Triples ***";
		echo( print_r( $triples, true ));
		echo "*** Triple index ***";
		echo( print_r( $tripleIndex, true ));
		echo "*** Namespaces ***";
		echo( print_r( $namespaces, true ));
		*/
		
		$this->importFromArc2Data( $triples, $tripleIndex, $namespaces );
	}
	
	/**
	 * Import RDF/XML, e.g. from the RDF Import Special Page.
	 * @param string $importData
	 */
	public function importTurtle( $importData ) {
	    global $triples;
	// Parse RDF/XML to triples
	    $arc2turtleparser = ARC2::getTurtleParser($importData);
	    $arc2turtleparser->parseData( $importData );
	
	    // Receive the data
	    $triples = $arc2turtleparser->triples;
	    $tripleIndex = $arc2turtleparser->getSimpleIndex();
	    $namespaces = $arc2turtleparser->nsp;
	    
	    /* DEBUGGING
	    echo "*** Triples ***";
	    echo( print_r( $triples, true ));
	    echo "*** Triple index ***";
	    echo( print_r( $tripleIndex, true ));
	    echo "*** Namespaces ***";
	    echo( print_r( $namespaces, true ));
	     */
	     
	    $this->importFromArc2Data( $triples, $tripleIndex, $namespaces );
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
		global $wgOut, $triples;
		
        // Parse data from ARC2 triples to custom data structure holding wiki pages
        $arc2towikiconverter = new RDFIOARC2ToWikiConverter();
        $wikiPages = $arc2towikiconverter->convert( $triples, $tripleIndex, $namespaces );
        
        // Import pages into wiki
        $smwPageWriter = new RDFIOSMWPageWriter();
        $smwPageWriter->import( $wikiPages );
	}
	
	function addDataSource( $dataSourceUrl, $importType ) { 
		global $dataSourcePage; 
		$dataSourcePage = new RDFIOWikiPage($dataSourceUrl); 
		$dataSourcePage->addEquivalentURI($dataSourceUrl); 
		$dataSourcePage->addFact(array('p' => 'RDFIO Import Type', 'o' => $importType)); 
		$dataSourcePage->addCategory('RDFIO Data Source');
		$smwPageWriter = new RDFIOSMWPageWriter();
		$smwPageWriter->import(array( $dataSourceUrl => $dataSourcePage ));
		}

	function showImportedTriples( $importedTriples ) {
		global $rdfioUtils, $wgOut;
		
	        $style_css = <<<EOD
        	    table .rdfio- th {
        	        font-weight: bold;
        	        padding: 2px 4px;
        	    }
        	    table.rdfio-table td,
        	    table.rdfio-table th {
        	        font-size: 11px;
        	    }
EOD;
	        $wgOut->addInlineStyle($style_css);
	        $rdfioUtils->showSuccessMessage("Success!", "Successfully imported the triples shown below!");
	        $wgOut->addHTML("<table class=\"wikitable sortable rdfio-table\"><tbody><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>");
	        
	        foreach( $importedTriples as $triple ) {
	            $s = $triple['s'];
	            $p = $triple['p'];
	            $o = $triple['o'];
	            if ( RDFIOUtils::isURI( $s )) {
	                $s = "<a href=\"$s\">$s</a>";
	            }
	            if ( RDFIOUtils::isURI( $p )) {
	                $p = "<a href=\"$p\">$p</a>";
	            }
	            if ( RDFIOUtils::isURI( $o )) {
	                $o = "<a href=\"$o\">$o</a>";
	            }
	            $wgOut->addHTML("<tr><td>$s</td><td>$p</td><td>$o</td></tr>");
	        }
	        
	        $wgOut->addHTML("</tbody></table>");
		}
}
