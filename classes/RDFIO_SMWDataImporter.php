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
		$sentence_obj = null;
		try{
			$oid = WOMProcessor::getObjIdByXPath($title, '//sentence[1]');
			// use page object functions
			$sentence_obj = $wom->getObject($oid[0]);
		} catch( Exception $e ) {
			return;
		}
		
		$text_obj = null;
		foreach( $sentence_obj->getObjects() as $sub_obj) {
			if($sub_obj->getTypeID() == WOM_TYPE_TEXT) {
				$text_obj = $sub_obj;
				break;
			}
		}
		
		$text_obj->setText('Hi, world.');
		$article = new Article($title);
		$content = $wom->getWikiText();
		$summary = "Edited by WOM...";
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
