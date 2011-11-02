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
		
		$title = Title::newFromText('Test2');
		
		$wom = WOMProcessor::getPageObject($title);
		$property_obj = null;
		try{
			$oid = WOMProcessor::getObjIdByXPath($title, '//property[1]');
			// use page object functions
			$property_obj = $wom->getObject($oid[0]);
		} catch( Exception $e ) {
			return;
		}
		
		// FIXME: Remove debug code
		$p_as_wikitext = $property_obj->getWikiText();
		
		$newTitle = Title::newFromText( 'Test3' ); 
		$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $newTitle );
		
		$property_obj->setSMWDataValue( $newSMWPageValue );
		
		$article = new Article($title);
		$content = $wom->getWikiText();
		$summary = "Updated fact ... ?";
		$article->doEdit( $content, $summary );
		
		// $article = new Article($title);
		// $summary = "A Bot edit ...";
		// $content = $article->fetchContent();
		// $content_new = $content . ' ... some more content';
		// $article->doEdit($content_new, $summary);
		
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
