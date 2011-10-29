<?php

class RDFIOARC2Parser extends RDFIOParser {
	
	protected $mInputType = null;

	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		switch ( $this->getInput()->getDataType() ) {
			case ( 'rdfxml' ): 
				$this->mExternalParser = ARC2::getRDFXMLParser();
				break;
			case ( 'turtle' ):
				$this->mExternalParser = ARC2::getTurtleParser();
			default:
				// TODO: Add some error message!
		}
		
		# Execute the external parser
		$this->mExternalParser->parseData( $this->getInput()->getData() );
		
		# Collect results
		$arc2TriplesData = new RDFIORawData();
		$arc2TriplesData->setData( $this->mExternalParser->getTriples() );
		$arc2TriplesData->setDataType( 'arc2triples' );
		
		# Convert ARC2 data structure to SMW (1.6) data structure
		$arc2ToSMWParser = new RDFIOARC2ToSMWParser();
		$arc2ToSMWParser->setInput( $arc2TriplesData );
		$arc2ToSMWParser->execute();
		
		# Store as results
		$smwData = $arc2ToSMWParser->getResults();
		$this->setResults( $smwData );
	}
}
