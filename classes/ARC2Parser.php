<?php

class RDFIOARC2Parser extends RDFIOParser {
	
	protected $mInputType = null;

	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		switch ( $this->getInputType() ) {
			case ( RDFIOSMWImporter::INPUT_TYPE_RDFXML ): 
				$this->mExternalParser = ARC2::getRDFXMLParser();
				break;
			case ( RDFIOSMWImporter::INPUT_TYPE_RDFXML ):
				$this->mExternalParser = ARC2::getTurtleParser();
			default:
				// TODO: Add some error message!
		}
		
		# Execute the external parser
		$this->mExternalParser->parseData( $this->getInput() );
		$this->setResults( $this->mExternalParser->getTriples() ); 
	}
}
