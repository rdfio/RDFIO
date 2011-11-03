<?php

class RDFIOSMWDataImporter { 
	protected $mImportData = null;
	protected $mWikiWriter = null;

	public function __construct() {
		$this->mWikiWriter = new RDFIOWikiWriter();
	}

	public function execute() {

		// FIXME: Hard-coded now, while testing
		$subjWikiTitle = 'Test2';
		
		$titleObj = Title::newFromText( $subjWikiTitle );
		
		$wom = WOMProcessor::getPageObject( $titleObj );
		$womPropObjs = array();
		try{
			$objIds = WOMProcessor::getObjIdByXPath( $titleObj, '//property' );
			// use page object functions
			foreach ( $objIds as $objId ) {
				$womPropObj = $wom->getObject( $objId );
				$womPropObjs[] = $womPropObj;
			}
		} catch( Exception $e ) {
			return;
		}
		
		$propsAndValuesForPage = array();

		foreach ( $womPropObjs as $womPropObj ) {
			$propName = $womPropObj->getPropertyName();
			$propValue = $womPropObj->getPropertyValue();
			// TODO: Figure out what happens if a key already exists
			$propsAndValuesForPage[$propName] = $propValue; 
		}
		
		/*
		 	FIXME: Later remove this test code
		 	
		 	$newTitle = Title::newFromText( 'Test3' ); 
			$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $newTitle );
			$property_obj->setSMWDataValue( $newSMWPageValue );
			
			$article = new Article($titleObj);
			$content = $wom->getWikiText();
			$summary = "Updated fact ... ?";
			$article->doEdit( $content, $summary );
 
		 */
		
	}
	
	# Getters and setters
	
	public function setImportData( RDFIODataAggregate $importData ) {
		$this->mImportData = $importData;	
	}
	public function getImportData() {
		return $this->mImportData;
	}
}
