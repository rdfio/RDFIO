<?php

class RDFIOSMWDataImporter {
	protected $mImportData = null;
	protected $mWikiWriter = null;

	public function __construct() {
		$this->mWikiWriter = new RDFIOWikiWriter();
	}

	public function execute() {

		$dataAggregate = $this->getImportData();
		$subjectDatas = $dataAggregate->getSubjectDatas();

		foreach ( $subjectDatas as $subjectData ) {
			$subject = $subjectData->getSubject();
			$subjectWikiTitle = $subject->getAsWikiPageName();

			$subjectFacts = $subjectData->getFacts();
			
			$mwTitleObj = Title::newFromText( $subjectWikiTitle );

			if ( !$mwTitleObj->exists() ) {
				$mwArticleObj = new Article( $mwTitleObj );
				$content = "";
				$summary = 'Page created by RDFIO';
				$mwArticleObj->doEdit( $content, $summary );
			} 
			
			$womWikiPage = WOMProcessor::getPageObject( $mwTitleObj );
			$womPropertyObjs = array();
			
			try{
				$objIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//property' );
				// use page object functions
				foreach ( $objIds as $objId ) {
					$womPropertyObj = $womWikiPage->getObject( $objId );
					$womPropertyName = $womPropertyObj->getPropertyName();
					$womPropertyObjs[$womPropertyName] = $womPropertyObj;
				}
				$foundExistnigProperties = TRUE; 
			} catch( Exception $e ) {
				echo( "Exception: " . $e->getMessage() );
				$foundExistnigProperties = FALSE; 
			}

			foreach ( $subjectFacts as $subjectFact ) {
				$propertyName = $subjectFact->getPredicate()->getAsWikiPageName();
				if ( $foundExistnigProperties && in_array( $propertyName, $womPropertyObjs ) ) {
					$womPropertyObj = $womPropertyObjs[$propertyName];
					
					$objectAsText = $subjectFact->getObject()->getAsText();
					
					$newTitle = Title::newFromText( $objectAsText );
					$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $newTitle );
					$womPropertyObj->setSMWDataValue( $newSMWPageValue );
				} else {
					// Create new property objects
				}
			}			
			
			$mwArticleObj = new Article( $mwTitleObj );
			$content = $womWikiPage->getWikiText();
			$summary = 'Update by RDFIO';
			$mwArticleObj->doEdit( $content, $summary );
		}
	}

	# Getters and setters

	public function setImportData( RDFIODataAggregate $importData ) {
		$this->mImportData = $importData;
	}
	public function getImportData() {
		return $this->mImportData;
	}
}
