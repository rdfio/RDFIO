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
			
			$newPropertiesAsWikiText = "\n";

			foreach ( $subjectFacts as $subjectFact ) {
				$predicateAsText = $subjectFact->getPredicate()->getAsWikiPageName();
				$objectAsText = $subjectFact->getObject()->getAsText();
				$predicateAsText = $this->escapeCharsForWikiTitle( $predicateAsText );
				$objectAsText = $this->escapeCharsForWikiTitle( $objectAsText );

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

	# Convenience methods
	
	public function escapeCharsForWikiTitle( $wikiTitle ) {
		$wikiTitle = str_replace( '[', '', $wikiTitle );
		$wikiTitle = str_replace( ']', '', $wikiTitle );
		return $wikiTitle;
	}
	
	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way. It is used to encode URIs. 
	 */
	static public function encodeURI( $uri ) {
		$uri = str_replace( '-', '-2D', $uri );
		// $uri = str_replace( '_', '-5F', $uri); //not necessary
		$uri = str_replace( array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                    array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    $uri );
		return $uri;
	}

	/**
	 * This function unescapes URIs generated with SMWExporter::encodeURI. This
	 * allows services that receive a URI to extract e.g. the according wiki page.
	 */
	static public function decodeURI( $uri ) {
		$uri = str_replace( array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                   $uri );
		$uri = str_replace( '%2D', '-', $uri );
		return $uri;
	}
	
	# Getters and setters

	public function setImportData( RDFIODataAggregate $importData ) {
		$this->mImportData = $importData;
	}
	public function getImportData() {
		return $this->mImportData;
	}
}
