<?php

class RDFIOSMWPageWriter {

	public function __construct() {
	}

	/**
	 * Main function, that takes an array of RDFIOWikiPage objects, and writes to
	 * MediaWiki using the WikiObjectModel extension.
	 * @param array $wikiPages
	 */
	public function import( $wikiPages ) {
		global $wgOut;

		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			$newWikiContent = '';
			$newTemplateCalls = null;

			$mwProperties = array();
			$mwCategories = array();
			$mwTemplates = array();

			$mwTitleObj = Title::newFromText( $wikiTitle );
			if ( is_object( $mwTitleObj ) && $mwTitleObj->exists() ) {

				$mwPageObj = WikiPage::factory( $mwTitleObj );
				$oldWikiContent = $mwPageObj->getContent()->getNativeData(); // FIXME: Check if getContent returns null

				preg_match( '/^\s?$/', $oldWikiContent, $isBlank );

				// Find all the properties stored in the conventional way within the page
				preg_match_all( '/\[\[(.*)::(.*)\]\]/', $oldWikiContent, $propertyMatches );
				$propertyWikitextInPage = $propertyMatches[0];
				$propertyNameInPage = $propertyMatches[1];
				$propertyValueInPage = $propertyMatches[2];
				foreach ( $propertyNameInPage as $index => $propertyName ) {
					$mwProperties[$propertyName] = array( 'value' => $propertyValueInPage[$index], 'wikitext' => $propertyWikitextInPage[$index] );
				}

				// Find all the categories, in the same way
				preg_match_all( '/\[\[Category:(.*)\]\]/', $oldWikiContent, $categoryMatches );
				$categoryWikitextInPage = $categoryMatches[0];
				$categoryNameInPage = $categoryMatches[1];
				foreach ( $categoryNameInPage as $index => $categoryName ) {
					$mwCategories[$categoryName] = array( 'wikitext' => $categoryWikitextInPage[$index] );
				}


				// Find all the templates
				preg_match_all( '/\{\{\s?([^#][a-zA-Z0-9]+)\s?\|(.*)\}\}/U', $oldWikiContent, $templateMatches );
				$templateCallInPage = $templateMatches[0];
				$templateNameInPage = $templateMatches[1];
				$templateParamsInPage = $templateMatches[2];
				foreach ( $templateNameInPage as $index => $templateName ) {
					$mwTemplates[$templateName]['templateCallText'] = $templateCallInPage[$index];
					$mwTemplates[$templateName]['templateParamsValues'] = $templateParamsInPage[$index];
				}

				if ( !empty( $isBlank ) ) {
					$newTemplates = $this->getTemplatesForCategories( $wikiPage );
					foreach ( $newTemplates as $name => $callText ) {
						$mwTemplates[$name]['templateCallText'] = $callText;
						$newTemplateCalls .= $callText . "\n";
					}
				}

				if ( !empty( $mwTemplates ) ) {
					// Extract the wikitext from each template
					foreach ( $mwTemplates as $templateName => $array ) {
						$mwTemplatePageTitle = Title::newFromText( $templateName, $defaultNamespace = NS_TEMPLATE );
						$mwTemplateObj = WikiPage::factory( $mwTemplatePageTitle );
						$mwTemplateText = $mwTemplateObj->getContent()->getNativeData(); // FIXME: Check if getContent returns null
						$mwTemplates[$templateName]['wikitext'] = $mwTemplateText;

						// Get the properties and parameter names used in the templates
						preg_match_all( '/\[\[(.*)::\{\{\{(.*)\|?\}\}\}\]\]/', $mwTemplateText, $templateParameterMatches );
						$propertyNameInTemplate = $templateParameterMatches[1];
						$parameterNameInTemplate = $templateParameterMatches[2];
						foreach ( $parameterNameInTemplate as $index => $templateParameter ) {
							// Store parameter-property pairings both ways round for easy lookup
							$mwTemplates[$templateName]['parameters'][$templateParameter]['property'] = $propertyNameInTemplate[$index];
							$mwTemplates[$templateName]['properties'][$propertyNameInTemplate[$index]] = $parameterNameInTemplate[$index];
						}

						$hasTemplateParams = array_key_exists( 'templateParamsValues', $mwTemplates[$templateName] );
						// Get the parameter values used in the templates
						if ( $hasTemplateParams ) {
							$templateParameterValues = explode( '|', $mwTemplates[$templateName]['templateParamsValues'] );
							foreach ( $templateParameterValues as $paramPair ) {
								$paramValueArray = explode( '=', $paramPair );
								$paramName = $paramValueArray[0];
								$paramValue = $paramValueArray[1];
								$mwTemplates[$templateName]['parameters'][$paramName]['value'] = $paramValue;
							}
						}
					}
				}


				// Put existing template calls into an array for updating more than one fact
				foreach ( $mwTemplates as $name => $array ) {
					$updatedTemplateCalls[$name] = $array['templateCallText'];
				}

				$newWikiContent = $oldWikiContent; // using new variable to separate extraction from editing
			}

			if ( !$mwTitleObj->exists() ) {
				// if page doesn't exist, check for categories in the wikipage data, and add an empty template call to the page wikitext	
				$newTemplates = $this->getTemplatesForCategories( $wikiPage );
				foreach ( $newTemplates as $name => $callText ) {
					$mwTemplates[$name]['templateCallText'] = $callText;
					$newTemplateCalls .= $callText . "\n";
				}
			}

			if ( $newTemplateCalls ) {
				$newWikiContent .= $newTemplateCalls;
			}

			// Add categories to the wiki text 
			// The new wikitext is actually added to the page at the end.
			// This allows us to add a template call associated with the category and then populate it with parameters in the facts section
			$newCategoriesAsWikiText = "\n";
			foreach ( $wikiPage->getCategories() as $category ) {

				$categoryTitle = Title::newFromText( $category, $defaultNamespace = NS_CATEGORY );
				$categoryTitleWikified = $categoryTitle->getText();

				if ( !array_key_exists( $categoryTitleWikified, $mwCategories ) ) {
					$newCategoriesAsWikiText .= '[[Category:' . $categoryTitleWikified . "]]\n"; // Is there an inbuilt class method to do this?  Can't find one in Category.
				}
			}

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
				$isInTemplate = null;

				// Find whether the property is in any template(s) on the page
				if ( !empty( $mwTemplates ) ) {
					foreach ( $mwTemplates as $templateName => $array ) {
						if ( array_key_exists( 'properties', $mwTemplates[$templateName] ) ) {
							$isInTemplate = array_key_exists( $predTitleWikified, $mwTemplates[$templateName]['properties'] );
							if ( $isInTemplate && !in_array( $templateName, $templatesWithProperty ) ) {
								$templatesWithProperty[] = $templateName;
							}
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
					$objTitle = Title::newFromText( RDFIOUtils::cleanWikiTitle( $obj ) );
					$newSMWValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
				}
				$newValueText = $newSMWValue->getWikiValue();

				// Handle updating differently depending on whether property exists in/outside template

				if ( $hasLocalUrl && $isEquivURI ) {
					// Don't update Equivalent URI if the URL is a local URL (thus containing
					// "Special:URIResolver").
				} else if ( $isInTemplate ) {
					// Code to update/add property to template call(s)
					foreach ( $templatesWithProperty as $index => $templateName ) {
						$oldTemplateCall = $updatedTemplateCalls[$templateName];  // use temp value as may be updated more than once
						$parameter = $mwTemplates[$templateName]['properties'][$predTitleWikified];
						$oldValue = null;
						$hasOldValue = array_key_exists( 'value', $mwTemplates[$templateName]['parameters'][$parameter] );
						if ( $hasOldValue ) {
							$oldValue = $mwTemplates[$templateName]['parameters'][$parameter]['value'];
						}
						$newParamValueText = $parameter . '=' . $newValueText;
						$newTemplateCall = $oldTemplateCall;

						if ( $hasOldValue ) {
							// if the parameter already had a value and there's a new value, replace this value in the template call
							if ( $newValueText != $oldValue ) {
								$oldParamValueText = $parameter . '=' . $oldValue;
								$newTemplateCall = str_replace( $oldParamValueText, $newParamValueText, $oldTemplateCall );
							}
						} else {
							// if the parameter wasn't previously populated, add it to the parameter list in the template call
							preg_match( '/(\{\{\s?.*\s?\|?.?)(\}\})/', $oldTemplateCall, $templateCallMatch );
							if ( !empty( $templateCallMatch ) ) {
								$templateCallBeginning = $templateCallMatch[1];
								$templateCallEnd = $templateCallMatch[2];
								$newTemplateCall = $templateCallBeginning . '|' . $newParamValueText . $templateCallEnd;
							}
						}

					}
					if ( $newTemplateCall != $oldTemplateCall ) {
						//  if the template call has been updated, change it in the page wikitext and update the placeholder variable
						$newWikiContent = str_replace( $oldTemplateCall, $newTemplateCall, $newWikiContent );
						$updatedTemplateCalls[$templateName] = $newTemplateCall;
					}

				} else if ( $isInPage ) {
					// if it's a standard property in the page, replace value with new one if different

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

			// Write to wiki
			$this->writeToArticle( $wikiTitle, $newWikiContent, 'Update by RDFIO' );
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

	function getTemplatesForCategories( $wikiPage ) {
		// if page doesn't exist, check for categories in the wikipage data, and add an empty template call to the page wikitext
		$output = array();
		foreach ( $wikiPage->getCategories() as $cat ) {
			$categoryTitle = Title::newFromText( $cat, $defaultNamespace = NS_CATEGORY );
			$categoryPage = WikiPage::factory( $categoryTitle );  // get Category page, if exists
			$categoryPageContent = $categoryPage->getContent();
			if ($categoryPageContent != null) {
				$categoryPageWikitext = $categoryPageContent->getNativeData();
				preg_match( '/\[\[Has template::Template:(.*)\]\]/', $categoryPageWikitext, $categoryTemplateMatches );// get Has template property, if exists
				if ( count( $categoryTemplateMatches ) > 0 ) {
					$templateName = $categoryTemplateMatches[1];
					$templateCallText = '{{' . $templateName . '}}';  // Add template call to page wikitext - {{templatename}}
					$output[$templateName] = $templateCallText;
					// This will then be populated with included paramters in the next section
				}
			}
		}
		return $output;
	}
}
