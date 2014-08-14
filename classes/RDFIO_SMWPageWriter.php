<?php

class RDFIOSMWPageWriter {

	public function __construct() {}

	/**
	 * Main function, that takes an array of RDFIOWikiPage objects, and writes to
	 * MediaWiki using the WikiObjectModel extension.
	 * @param array $wikiPages
	 */
	public function import( $wikiPages ) {
		global $wgOut;

		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			// Get property objects from WOM
			$womPropertyObjs = array();
			$womCategoryObjs = array();
			$newWikiContent = "";
			$mwTitleObj = Title::newFromText( $wikiTitle );
			
			// If page exists, get it's data from WOM
			$titleIsObj = is_object($mwTitleObj);
			$titleExists = $mwTitleObj->exists();
			if ( $titleIsObj && $titleExists ) {
/*				$womWikiPage = WOMProcessor::getPageObject( $mwTitleObj );
				
				// Get wiki text
				$wikiContent = $womWikiPage->getWikiText();
				
				// Get properties
				try{
					$propertyObjIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//property' );
					// use page object functions
					foreach ( $propertyObjIds as $propertyObjId ) {
						$womPropertyObj = $womWikiPage->getObject( $propertyObjId );
						$womPropertyName = $womPropertyObj->getPropertyName();
						$womPropertyObjs[$womPropertyName] = $womPropertyObj;
					}
				} catch( MWException $e ) {
					// WOM is sending unspecific exceptions that are not really errors ...
				    //$wgOut->addHTML( '<pre>Exception when talking to WOM: ' . $e->getMessage() . '</pre>' );
				}
				
				// Get categories
				try {
					$categoryObjIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//category' );
					foreach ( $categoryObjIds as $categoryObjId ) {
						$womCategoryObj = $womWikiPage->getObject( $categoryObjId );
						$womCategoryName = $womCategoryObj->getName();
						$womCategoryObjs[$womCategoryName] = $womCategoryObj; 
					}
				} catch( MWException $e ) {
					// WOM is sending unspecific exceptions that are not really errors ...
					//$wgOut->addHTML( '<pre>Exception when talking to WOM: ' . $e->getMessage() . '</pre>' );
				}
*/			
	/*
	** Below here is experimental stuff 
	*/	

			$mwPageObj = WikiPage::factory( $mwTitleObj );			
			$oldWikiContent = $mwPageObj->getText();
			$mwProperties = array();
			$mwCategories= array();
					// Find all the properties stored in the conventional way within the page	
			preg_match_all('/\[\[(.*)::(.*)\]\]/', $oldWikiContent, $propertyMatches);
			foreach ( $propertyMatches[1] as $index => $propertyName ) {
				$mwProperties[$propertyName] = array( 'value' => $propertyMatches[2][$index], 'wikitext' => $propertyMatches[0][$index] );
			}

					// Find all the categories, in the same way	
			preg_match_all('/\[\[Category:(.*)\]\]/', $oldWikiContent, $categoryMatches);
			foreach ( $categoryMatches[1] as $index => $categoryName ) {
				$mwCategories[$categoryName] = array( 'wikitext' => $categoryMatches[0][$index] );
			}


					// Find all the templates
			preg_match_all('/\{\{\s?(.*)\s?\|.*\}\}/', $oldWikiContent, $templateMatches);
			foreach ( $templateMatches[1] as $templateName ) {
				$mwTemplates[] = $templateName ;  // this will contain the template's properties later
			}

			if ( !empty($mwTemplates) ) {
						// Extract the wikitext from each template
				foreach ( $mwTemplates as $templatePageName ) {
					$mwTemplatePageTitle = Title::newFromText( $templatePageName, $defaultNamespace=NS_TEMPLATE );
					$mwTemplateObj = WikiPage::factory( $mwTemplatePageTitle );
					$mwTemplateText = $mwTemplateObj->getText();
					$templateWikiText[$templatePageName] = $mwTemplateText;
				}
						// Get the properties and parameter names used in the templates	
				foreach ( $templateWikiText as $templateName => $mwTemplateText ) {
					preg_match_all('/\[\[(.*)::\{\{\{(.*)\}\}\}\]\]/', $mwTemplateText, $templateParameterMatches);
					foreach( $templateParameterMatches[2] as $index => $templateParameter ) {
						$templateProperties[$templateName][$templateParameter] = $templateParameterMatches[1][$index];
					}
				}

						// Get the parameter values used in the templates
				foreach ( $templateMatches[0] as $index => $templateContents ) {
					preg_match_all('/\{\{\s?.*\s?\|(.*)\|?.*\}\}/', $templateContents, $internalText);
					$templateParameterValues = explode("|", $internalText[1][0]);
					foreach ( $templateParameterValues as $paramPair ) {
						$paramValueArray = explode("=", $paramPair);
						$templateParamValuePairs[$templateMatches[1][$index]][] = array( $paramValueArray[0] => $paramValueArray[1] );
					}
				}
			}
			

			}
			$newWikiContent = $oldWikiContent; // using new variable to separate extraction from editing

			// Add facts (properties) to the wiki text
			$newPropertiesAsWikiText = "\n";
			foreach ( $wikiPage->getFacts() as $fact ) {
				$pred = $fact['p'];
				$obj = $fact['o'];
				
				$predTitle = Title::newFromText( $pred );
				$predTitleWikified = $predTitle->getText();
				
				$isEquivURI = strpos( $pred, "Equivalent URI" ) !== false;
				$hasLocalUrl = strpos( $obj, "Special:URIResolver" ) !== false;
				/*if ( $hasLocalUrl && $isEquivURI ) { 
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
					
					if ( $isEquivURI ) {
					    // FIXME: Should be done for all "URL type" facts, not just
					    //        Equivalent URI:s
					    // Since this is a URL, it should not be made into a WikiTitle
					    $newSMWValue = SMWDataValueFactory::newTypeIdValue( '_uri', $obj );
					} else {
					    // Create an updated property
					    $objTitle = Title::newFromText( $obj );					    	
					    $newSMWValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
					}
					
					$womPropertyObj->setSMWDataValue( $newSMWValue );
					$newPropertyText = $womPropertyObj->getWikiText();
						
					// Replace the existing property with new value
					$wikiContent = str_replace( $oldPropertyText, $newPropertyText, $wikiContent );
				}*/
				// Non-WOM version of the above

				
				if ( $hasLocalUrl && $isEquivURI ) { 
					// Don't update Equivalent URI if the URL is a local URL (thus containing
					// "Special:URIResolver").
				} else if ( !array_key_exists( $predTitleWikified, $mwProperties ) ) { // If property already exists ...
					$newPropertyAsWikiText = '[[' . $predTitleWikified . '::' . $obj . ']]';
					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
				} else { 
				
					$oldPropertyText = $mwProperties[$predTitleWikified]['wikitext'];	
					// Store the old wiki text for the fact, in order to replace later
					
					if ( $isEquivURI ) {
					    // FIXME: Should be done for all "URL type" facts, not just
					    //        Equivalent URI:s
					    // Since this is a URL, it should not be made into a WikiTitle
					    $newSMWValue = SMWDataValueFactory::newTypeIdValue( '_uri', $obj );
					} else {
					    // Create an updated property
					    $objTitle = Title::newFromText( $obj );					    	
					    $newSMWValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
					}
					$newValueText = $newSMWValue->getWikiValue();	
					$newPropertyText = '[[' . $predTitleWikified . '::' . $newValueText . ']]';
						
					// Replace the existing property with new value
					$newWikiContent = str_replace( $oldPropertyText, $newPropertyText, $newWikiContent );
				}
			}			
			$newWikiContent .= $newPropertiesAsWikiText;
			
			// Add categories to the wiki text
			$newCategoriesAsWikiText = "\n";
			foreach( $wikiPage->getCategories() as $category ) {

				$categoryTitle = Title::newFromText( $category );
				$categoryTitleWikified = $categoryTitle->getText();
			/* Old WOM method - temporariy disabled
	
				if ( !array_key_exists( $categoryTitleWikified, $womCategoryObjs ) ) { // If property already exists ...
					$newWomCategoryObj = new WOMCategoryModel( $categoryTitleWikified );
					$newCategoryAsWikiText = $newWomCategoryObj->getWikiText();
					$newCategoriesAsWikiText .= $newCategoryAsWikiText . "\n";
				}  */

			// New non-WOM version!
				if ( !array_key_exists( $categoryTitleWikified, $mwCategories ) ) {
					$newCategoriesAsWikiText .= '[[Category:' . $categoryTitleWikified . "]]\n"; // Is there an inbuilt class method to do this?  Can't find one in Category.
				}
			}
			$newWikiContent .= $newCategoriesAsWikiText;
				
			// Write to wiki
			$this->writeToArticle($wikiTitle, $newWikiContent, 'Update by RDFIO');
		}
	}
	
	/**
	 * The actual write function, that takes the parsed and updated content as 
	 * a string and writes to the wiki.
	 * @param string $wikiTitle
	 * @param string $content
	 * @param string $summary
	 */
	protected function writeToArticle( $wikiTitle, $content, $summary ) {
		$mwTitleObj = Title::newFromText( $wikiTitle );
		$mwArticleObj = new Article( $mwTitleObj );
		$mwArticleObj->doEdit( $content, $summary );
	}

}
