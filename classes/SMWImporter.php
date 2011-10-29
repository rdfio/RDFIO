<?php

class RDFIOSMWImporter { // TODO: Think this class needs a better name
	protected $mDataFormat = null;
	protected $mTextToObjectsParser = null;
	const INPUT_TYPE_TRIPLES = 0;
	const INPUT_TYPE_RDFXML = 1;
	const INPUT_TYPE_TURTLE = 2;	

	public function __construct() {
		// ...
	}

	public function execute() {
		$this->mTextToObjectsParser = new RDFIOARC2Parser(); // TODO: Make the choice of parser more configureable / pluggable?
		
		$this->mTextToObjectsParser->setInput( $this->getInput() );
		$this->mTextToObjectsParser->execute();
		$results = $this->mTextToObjectsParser->getResults();
		
		// TODO: $results should be in SMW internals format ... 
		// ... and then be sent to RDFIOWikiWriter
	}

	
	# Getters and setters
	
	public function setInput( RDFIORawData $input ) {
		$this->mInput = $input;	
	}
	public function getInput() {
		return $this->mInput;
	}
}
