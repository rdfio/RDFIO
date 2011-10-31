<?php

class RDFIOSMWImporter { // TODO: Think this class needs a better name
	protected $mDataFormat = null;
	protected $mRawToSMWDataParser = null;
	protected $mWikiWriter;
	const INPUT_TYPE_TRIPLES = 0;
	const INPUT_TYPE_RDFXML = 1;
	const INPUT_TYPE_TURTLE = 2;	

	public function __construct() {
		$this->mRawToSMWDataParser = new RDFIOARC2Parser(); 
		$this->mWikiWriter = new RDFIOWikiWriter();
	}

	public function execute() {
		$this->mRawToSMWDataParser->setInput( $this->getInput() );
		$this->mRawToSMWDataParser->execute();
		$results = $this->mRawToSMWDataParser->getResults();
		
		$this->mWikiWriter->setInput( $results );
		$this->mWikiWriter->execute();
	}
	
	# Getters and setters
	
	public function setInput( RDFIOData $input ) {
		$this->mInput = $input;	
	}
	public function getInput() {
		return $this->mInput;
	}
}
