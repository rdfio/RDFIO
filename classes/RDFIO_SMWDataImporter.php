<?php

class RDFIOSMWDataImporter {

	public function __construct() {
		// ...
	}

	public function import( $wikiPages ) {

		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			
			// Sanitize the title a bit
			$wikiTitle = str_replace('[','',$wikiTitle);
			$wikiTitle = str_replace(']','',$wikiTitle);
			
			$facts = $wikiPage['facts'];
			$equivuris = $wikiPage['equivuris'];
			
			# Populate the facts array also with the equivalent URI "facts"
			foreach ( $equivuris as $equivuri ) {
				$facts[] = array( 'p' => "Equivalent URI", 'o' => $equivuri );
			}
			
			$mwTitleObj = Title::newFromText( $wikiTitle );
			
			if ( !$mwTitleObj->exists() ) {
				// Create stub article 
				$this->writeToArticle( $wikiTitle, '', 'Article Created by RDFIO' );
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
			} catch( Exception $e ) {
				// @TODO Take better care of this?
				// echo( '<pre>Exception when talking to WOM: ' . $e->getMessage() . '</pre>' ); 
			}
			
			$newPropertiesAsWikiText = "\n";

			foreach ( $facts as $fact ) {
				$pred = $fact['p'];
				$obj = $fact['o'];

				if ( !array_key_exists( $pred, $womPropertyObjs ) ) {
					$newWomPropertyObj = new WOMPropertyModel( $pred, $obj, '' );
					$newPropertyAsWikiText = $newWomPropertyObj->getWikiText();
					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
					
					$wikiContent = $womWikiPage->getWikiText() . $newPropertiesAsWikiText;
				} else {
					$womPropertyObj = $womPropertyObjs[$pred];
					
					// Store the old wiki text for the fact, in order to replace later
					$oldPropertyText = $womPropertyObj->getWikiText();
					
					// Create an updated property
					$objTitle = Title::newFromText( $obj );
					$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
					$womPropertyObj->setSMWDataValue( $newSMWPageValue );
					$newPropertyText = $womPropertyObj->getWikiText();
						
					// Replace the existing property with new value
					$wikiContent = $womWikiPage->getWikiText();
					$wikiContent = str_replace( $oldPropertyText, $newPropertyText, $wikiContent );
				}
			}			
			// Write changes (or additions) to article
			$this->writeToArticle($wikiTitle, $wikiContent, 'Update by RDFIO');
		}
	}
	
	protected function writeToArticle( $wikiTitle, $content, $summary ) {
		$mwTitleObj = Title::newFromText( $wikiTitle );
		$mwArticleObj = new Article( $mwTitleObj );
		$mwArticleObj->doEdit( $content, $summary );
	}


}
