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
		$namespaces = $dataAggregate->getNamespacePrefixesFromParser();

		foreach ( $subjectDatas as $subjectData ) {
			$subject = $subjectData->getSubject();
			$subjectWikiTitle = $subject->getAsWikiPageName();

			$subjectFacts = $subjectData->getFacts();
			
			$mwTitleObj = Title::newFromText( $subjectWikiTitle );

			if ( !$mwTitleObj->exists() ) {
				$mwArticleObj = new Article( $mwTitleObj );
				$content = '';
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
			
			$newPropertiesAsWikiText = "\n";

			foreach ( $subjectFacts as $subjectFact ) {
				$predicateAsText = $subjectFact->getPredicate()->getAsWikiPageName();
				$objectAsText = $subjectFact->getObject()->getAsText();

				if ( array_key_exists( $predicateAsText, $womPropertyObjs ) ) {
					$womPropertyObj = $womPropertyObjs[$predicateAsText];
					$newTitle = Title::newFromText( $objectAsText );
					$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $newTitle );
					$womPropertyObj->setSMWDataValue( $newSMWPageValue );
				} else {
					$newWomPropertyObj = new WOMPropertyModel( $predicateAsText, $objectAsText, ' ' );

					$newPropertyAsWikiText = $newWomPropertyObj->getWikiText();
					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
				}
			}			
			
			$mwArticleObj = new Article( $mwTitleObj );
			$content = $womWikiPage->getWikiText() . $newPropertiesAsWikiText;
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
