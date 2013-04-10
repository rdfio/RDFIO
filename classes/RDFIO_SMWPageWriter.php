<?php

class RDFIOSMWPageWriter {

	public function __construct() {
		// ...
	}

	public function import( $wikiPages ) {
		global $wgOut;

		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			
			# Sanitize the title a bit
			$wikiTitle = str_replace('[','',$wikiTitle);
			$wikiTitle = str_replace(']','',$wikiTitle);
			
			# Get data from Wiki Page object
			$facts = $wikiPage->getFacts();
			$equivuris = $wikiPage->getEquivalentUris();
			$categories = $wikiPage->getCategories();
				
			# Populate the facts array also with the equivalent URI "facts"
			foreach ( $equivuris as $equivuri ) {
				$facts[] = array( 'p' => "Equivalent URI", 'o' => $equivuri );
			}
			
			/*
			 * Get property objects from WOM
			 */
			$womPropertyObjs = array();
			$wikiContent = "";
			$mwTitleObj = Title::newFromText( $wikiTitle );
			
			
			/*
			 * If page exists, get it's data from WOM
			 */
			if ( $mwTitleObj->exists() ) {
				$womWikiPage = WOMProcessor::getPageObject( $mwTitleObj );

				try{
					$propertyObjIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//property' );
					// use page object functions
					foreach ( $propertyObjIds as $propertyObjId ) {
						$womPropertyObj = $womWikiPage->getObject( $propertyObjId );
						$womPropertyName = $womPropertyObj->getPropertyName();
						$womPropertyObjs[$womPropertyName] = $womPropertyObj;
					}
				} catch( Exception $e ) {
					$wgOut->addHTML( '<pre>Exception when talking to WOM: ' . $e->getMessage() . '</pre>' );
					throw $e; // TODO: Really?
				}
				
				$wikiContent = $womWikiPage->getWikiText();
			}
			

			/*
			 * Add facts (properties) to the wiki text
			 */			

			$newPropertiesAsWikiText = "\n";
			foreach ( $facts as $fact ) {
				$pred = $fact['p'];
				$obj = $fact['o'];
				
				$predTitle = Title::newFromText( $pred );
				$predTitleWikified = $predTitle->getText();
				
				$isEquivURI = strpos( $pred, "Equivalent URI" ) !== false;
				$hasLocalUrl = strpos( $obj, "Special:URIResolver" ) !== false;
				if ( $hasLocalUrl && $isEquivURI ) {
					// Don't update Equivalent URI if the URL is a local URL (thus containing
					// "Special:URIResolver").
				} else if ( !array_key_exists( $predTitleWikified, $womPropertyObjs ) ) { // If property already exists ...
					$newWomPropertyObj = new WOMPropertyModel( $pred, $obj, '' ); // FIXME: "Property" should not be included in title
					$newPropertyAsWikiText = $newWomPropertyObj->getWikiText();
					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
				} else { 
					$womPropertyObj = $womPropertyObjs[$predTitleWikified];
					
					// Store the old wiki text for the fact, in order to replace later
					$oldPropertyText = $womPropertyObj->getWikiText();
					
					// Create an updated property
					$objTitle = Title::newFromText( $obj );
					$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
					$womPropertyObj->setSMWDataValue( $newSMWPageValue );
					$newPropertyText = $womPropertyObj->getWikiText();
						
					// Replace the existing property with new value
					$wikiContent = str_replace( $oldPropertyText, $newPropertyText, $wikiContent );
				}
			}			
			$wikiContent .= $newPropertiesAsWikiText;
			
			/*
			 * Add categories to the wiki text
			 */
			
// 			foreach( $categories as $category ) {

// 				$categoryTitle = Title::newFromText( $category );
// 				$categoryTitleWikified = $categoryTitle->getText();
				
// 				if ( !array_key_exists( $predTitleWikified, $womPropertyObjs ) ) { // If property already exists ...
// 					$newWomPropertyObj = new WOMPropertyModel( $pred, $obj, '' ); // FIXME: "Property" should not be included in title
// 					$newPropertyAsWikiText = $newWomPropertyObj->getWikiText();
// 					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
// 				} else {
// 					$womPropertyObj = $womPropertyObjs[$predTitleWikified];
						
// 					// Store the old wiki text for the fact, in order to replace later
// 					$oldPropertyText = $womPropertyObj->getWikiText();
						
// 					// Create an updated property
// 					$objTitle = Title::newFromText( $obj );
// 					$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
// 					$womPropertyObj->setSMWDataValue( $newSMWPageValue );
// 					$newPropertyText = $womPropertyObj->getWikiText();
				
// 					// Replace the existing property with new value
// 					$wikiContent = str_replace( $oldPropertyText, $newPropertyText, $wikiContent );
// 				}
// 			}
			
			$this->writeToArticle($wikiTitle, $wikiContent, 'Update by RDFIO');
		}
	}
	
	protected function writeToArticle( $wikiTitle, $content, $summary ) {
		$mwTitleObj = Title::newFromText( $wikiTitle );
		$mwArticleObj = new Article( $mwTitleObj );
		$mwArticleObj->doEdit( $content, $summary );
	}


}
