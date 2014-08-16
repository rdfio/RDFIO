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
			// Get properties, categories, templates and related data from the page
			$newWikiContent = "";
			$mwTitleObj = Title::newFromText( $wikiTitle );
			
			// If page exists, get its data
			$titleIsObj = is_object($mwTitleObj);
			$titleExists = $mwTitleObj->exists();
			if ( $titleIsObj && $titleExists ) {

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
			foreach ( $templateMatches[1] as $index => $templateName ) {
				$mwTemplates[$templateName] = array();  // this will contain the template's properties later
				$mwTemplates[$templateName]['templateCallText'] = $templateMatches[0][$index];
			}

			if ( !empty($mwTemplates) ) {
						// Extract the wikitext from each template
				foreach ( $mwTemplates as $templatePageName => $array ) {
					$mwTemplatePageTitle = Title::newFromText( $templatePageName, $defaultNamespace=NS_TEMPLATE );
					$mwTemplateObj = WikiPage::factory( $mwTemplatePageTitle );
					$mwTemplateText = $mwTemplateObj->getText();
					$mwTemplates[$templateName]['wikitext'] = $mwTemplateText;
				
						// Get the properties and parameter names used in the templates	
					preg_match_all('/\[\[(.*)::\{\{\{(.*)\}\}\}\]\]/', $mwTemplateText, $templateParameterMatches);
					foreach( $templateParameterMatches[2] as $index => $templateParameter ) {
							// Store parameter-property pairings both ways round for easy lookup
						$mwTemplates[$templateName]['parameters'][$templateParameter]['property'] = $templateParameterMatches[1][$index];
						$mwTemplates[$templateName]['properties'][$templateParameterMatches[1][$index]] = $templateParameterMatches[2][$index];
					}
				

						// Get the parameter values used in the templates
					preg_match_all('/\{\{\s?.*\s?\|(.*)\|?.*\}\}/', $mwTemplates[$templateName]['templateCallText'], $internalText);
					$templateParameterValues = explode("|", $internalText[1][0]);
					foreach ( $templateParameterValues as $paramPair ) {
						$paramValueArray = explode("=", $paramPair);
						$mwTemplates[$templateName]['parameters'][$paramValueArray[0]]['value'] = $paramValueArray[1];
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

				$templatesWithProperty = array();

					// Find whether the property is in any template(s) on the page
				if ( !empty( $mwTemplates ) ) {
					foreach( $mwTemplates as $templateName => $array ) {
						$isInTemplate = array_key_exists( $predTitleWikified, $mwTemplates[$templateName]['properties'] );
						if ( $isInTemplate && !in_array( $templateName, $templatesWithProperty ) ) {
							$templatesWithProperty[] = $templateName;
						}
					}
				}
				$isInPage = array_key_exists( $predTitleWikified, $mwProperties );
			
					// Set new value - this will be used in different ways depending on whether property is inside or outside template	
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

					// Handle updating differently depending on whether property exists in/outside template

				if ( $hasLocalUrl && $isEquivURI ) { 
					// Don't update Equivalent URI if the URL is a local URL (thus containing
					// "Special:URIResolver").
				} else if ( $isInTemplate ) {
					// Code to update/add property to template call(s)
				} else if ( $isInPage  ) {
					// replace value with new one if different
					
					$oldPropertyText = $mwProperties[$predTitleWikified]['wikitext'];	
					// Store the old wiki text for the fact, in order to replace later
					
					$newPropertyText = '[[' . $predTitleWikified . '::' . $newValueText . ']]';
						
					// Replace the existing property with new value
					if ( $newPropertyText != $oldPropertyText ) {
						$newWikiContent = str_replace( $oldPropertyText, $newPropertyText, $newWikiContent );
					}
				} else if ( !$isInPage ) { // If property isn't in the page (outside of templates) ...
					$newPropertyAsWikiText = '[[' . $predTitleWikified . '::' . $obj . ']]';
					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
				}
			}			
			$newWikiContent .= $newPropertiesAsWikiText;
			
			// Add categories to the wiki text
			$newCategoriesAsWikiText = "\n";
			foreach( $wikiPage->getCategories() as $category ) {

				$categoryTitle = Title::newFromText( $category );
				$categoryTitleWikified = $categoryTitle->getText();
				
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
