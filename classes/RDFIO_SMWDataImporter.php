<?php

class RDFIOSMWDataImporter { 
	protected $mImportData = null;
	protected $mWikiWriter = null;

	public function __construct() {
		$this->mWikiWriter = new RDFIOWikiWriter();
	}

	public function execute() {
		
		# TODO: Decide what the WikiWriter should do ...
		# ... maybe single page edits?
		
		# TODO: Ahh .. need to decide whether to store 
		# facts as triples or "resource indexes" ...
		# the latter of course more closely maps to 
		# SMW, since there is then one resource index
		# per wiki page, and that corresponds to one
		# SemanticData object ...
		
		// $this->mWikiWriter->setInput( $results );
		// $this->mWikiWriter->execute();
	}
	
	# Getters and setters
	
	public function setImportData( RDFIODataAggregate $importData ) {
		$this->mImportData = $importData;	
	}
	public function getImportData() {
		return $this->mImportData;
	}
}
