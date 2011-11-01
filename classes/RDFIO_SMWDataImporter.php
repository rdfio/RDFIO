<?php

class RDFIOSMWDataImporter { 
	protected $mImportData = null;
	protected $mWikiWriter = null;

	public function __construct() {
		$this->mWikiWriter = new RDFIOWikiWriter();
	}

	public function execute() {
		$this->mWikiWriter->setInput( $results );
		$this->mWikiWriter->execute();
	}
	
	# Getters and setters
	
	public function setImportData( RDFIODataAggregate $input ) {
		$this->mImportData = $importData;	
	}
	public function getImportData() {
		return $this->mImportData;
	}
}
