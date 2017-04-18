<?php

class RDFIOSMWPageWriter {

	public function __construct() {
	}

	/**
	 * Main function, that takes an array of RDFIOWikiPage objects, and writes to
	 * MediaWiki.
	 * @param array $wikiPages
	 */
	public function import( $wikiPages ) {

		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			/* @var $wikiPage RDFIOWikiPage */

			$oldWikiCont = $this->getTextForPage( $wikiTitle );
			$newWikiCont = $oldWikiCont; // using new variable to separate extraction from editing

			$oldTemplates = array();
			if ( Title::newFromText( $wikiTitle )->exists() && $oldWikiCont !== '' ) {
				$oldTemplates = $this->extractTemplateCalls( $oldWikiCont );
			}

			$newCategories = $wikiPage->getCategories();

			$tplsForCats = $this->getTemplatesForCategories( $newCategories );
			$newTplCalls = '';
			foreach ( $tplsForCats as $tplName => $callText ) {
				$oldTemplates[$tplName]['templateCallText'] = $callText; // FIXME: Kind of silly
				$newTplCalls .= $callText . "\n";
			}
			$newWikiCont .= $newTplCalls;

			if ( !empty( $oldTemplates ) ) {
				// Extract the wikitext from each template
				foreach ( $oldTemplates as $tplName => $array ) {
					$mwTplText = $this->getTextForPage( $tplName, NS_TEMPLATE );
					$oldTemplates[$tplName]['wikitext'] = $mwTplText;

					// Get the properties and parameter names used in the templates
					preg_match_all( '/\[\[(.*)::\{\{\{(.*)\|?\}\}\}\]\]/', $mwTplText, $tplParamMatches );
					$propNameInTpl = $tplParamMatches[1];
					$paramNameInTpl = $tplParamMatches[2];
					foreach ( $paramNameInTpl as $idx => $tplParam ) {
						// Store parameter-property pairings both ways round for easy lookup
						$oldTemplates[$tplName]['parameters'][$tplParam]['property'] = $propNameInTpl[$idx];
						$oldTemplates[$tplName]['properties'][$propNameInTpl[$idx]] = $paramNameInTpl[$idx];
					}

					$hasTplParams = array_key_exists( 'templateParamsValues', $oldTemplates[$tplName] );
					// Get the parameter values used in the templates
					if ( $hasTplParams ) {
						$tplParamVals = explode( '|', $oldTemplates[$tplName]['templateParamsValues'] );
						foreach ( $tplParamVals as $paramPair ) {
							$paramValArray = explode( '=', $paramPair );
							$paramName = $paramValArray[0];
							$paramVal = $paramValArray[1];
							$oldTemplates[$tplName]['parameters'][$paramName]['value'] = $paramVal;
						}
					}
				}
			}

			// Put existing template calls into an array for updating more than one fact
			foreach ( $oldTemplates as $tplName => $array ) {
				$updatedTplCalls[$tplName] = $array['templateCallText'];
			}

			// Add Facts
			$newWikiCont = $this->addNewFactsToWikiText( $wikiPage->getFacts(), $updatedTplCalls, $oldTemplates, $newWikiCont );

			// Add Categories
			$newWikiCont = $this->addNewCategoriesToWikiText( $newCategories, $newWikiCont );

			// Write to wiki
			$this->writeToArticle( $wikiTitle, $newWikiCont, 'Update by RDFIO' );
		}
	}

	/**
	 * Add facts to wiki text
	 * @param array $facts
	 * @param array $updatedTplCalls
	 * @param array $oldTemplates
	 * @param string $wikiText
	 * @return string $wikiText
	 */
	private function addNewFactsToWikiText( $facts, $updatedTplCalls, $oldTemplates, $wikiText ) {
		$oldProperties = $this->extractProperties( $wikiText );

		$newPropsAsText = "\n";
		foreach ( $facts as $fact ) {
			$pred = $fact['p'];
			$obj = $fact['o'];

			$predTitleWikified = $this->getWikifiedTitle( $pred );

			// Find whether the property is in any template(s) on the page
			$tplsWithProp = array();
			$occursInATpl = false;
			if ( !empty( $oldTemplates ) ) {
				foreach ( $oldTemplates as $tplName => $tplInfo ) {
					if ( array_key_exists( 'properties', $oldTemplates[$tplName] ) ) {
						$occursInATpl = array_key_exists( $predTitleWikified, $oldTemplates[$tplName]['properties'] );
						if ( $occursInATpl && !in_array( $tplName, $tplsWithProp ) ) {
							$tplsWithProp[] = $tplName;
						}
					}
				}
			}

			$isInPage = array_key_exists( $predTitleWikified, $oldProperties );

			// Set new value - this will be used in different ways depending on whether property is inside or outside template
			$isEquivURI = strpos( $pred, "Equivalent URI" ) !== false;
			if ( $isEquivURI ) {
				// FIXME: Should be done for all "URL type" facts, not just
				//        Equivalent URI:s
				// Since this is a URL, it should not be made into a WikiTitle
				$newSMWVal = SMWDataValueFactory::newTypeIdValue( '_uri', $obj );
			} else {
				// Create an updated property
				$objTitle = Title::newFromText( RDFIOUtils::cleanWikiTitle( $obj ) );
				$newSMWVal = SMWWikiPageValue::makePageFromTitle( $objTitle );
			}
			$newValText = $newSMWVal->getWikiValue();

			// Handle updating differently depending on whether property exists in/outside template
			$hasLocalUrl = strpos( $obj, "Special:URIResolver" ) !== false;
			if ( $hasLocalUrl && $isEquivURI ) {
				// Don't update Equivalent URI if the URL is a local URL (thus containing
				// "Special:URIResolver").
			} else if ( $occursInATpl ) {
				// Code to update/add property to template call(s)
				foreach ( $tplsWithProp as $idx => $tplName ) {
					$oldTplCall = $updatedTplCalls[$tplName];  // use temp value as may be updated more than once
					$param = $oldTemplates[$tplName]['properties'][$predTitleWikified];
					$oldVal = null;
					$hasOldVal = array_key_exists( 'value', $oldTemplates[$tplName]['parameters'][$param] );
					if ( $hasOldVal ) {
						$oldVal = $oldTemplates[$tplName]['parameters'][$param]['value'];
					}
					$newParamValText = $param . '=' . $newValText;
					$newTplCall = $oldTplCall;

					if ( $hasOldVal ) {
						// if the parameter already had a value and there's a new value, replace this value in the template call
						if ( $newValText != $oldVal ) {
							$oldParamValText = $param . '=' . $oldVal;
							$newTplCall = str_replace( $oldParamValText, $newParamValText, $oldTplCall );
						}
					} else {
						// if the parameter wasn't previously populated, add it to the parameter list in the template call
						preg_match( '/(\{\{\s?.*\s?\|?.?)(\}\})/', $oldTplCall, $tplCallMatch );
						if ( !empty( $tplCallMatch ) ) {
							$tplCallStart = $tplCallMatch[1];
							$tplCallEnd = $tplCallMatch[2];
							$newTplCall = $tplCallStart . '|' . $newParamValText . $tplCallEnd;
						}
					}

				}
				if ( $newTplCall != $oldTplCall ) {
					//  if the template call has been updated, change it in the page wikitext and update the placeholder variable
					$newWikiCont = str_replace( $oldTplCall, $newTplCall, $wikiText );
					$updatedTplCalls[$tplName] = $newTplCall;
				}

			} else if ( $isInPage ) {
				// if it's a standard property in the page, replace value with new one if different

				$oldPropText = $oldProperties[$predTitleWikified]['wikitext'];
				// Store the old wiki text for the fact, in order to replace later

				$newPropText = '[[' . $predTitleWikified . '::' . $newValText . ']]';

				// Replace the existing property with new value
				if ( $newPropText != $oldPropText ) {
					$newWikiCont = str_replace( $oldPropText, $newPropText, $newWikiCont );
				}
			} else if ( !$isInPage ) { // If property isn't in the page (outside of templates) ...
				$newPropAsText = '[[' . $predTitleWikified . '::' . $obj . ']]';
				$newPropsAsText .= $newPropAsText . "\n";
			}
		}
		$wikiText .= $newPropsAsText;
		return $wikiText;
	}

	/**
	 * Add category statements to the wiki text
	 * @param array $newCategories
	 * @param string $wikiText
	 * @return string $wikiText
	 */
	private function addNewCategoriesToWikiText( $newCategories, $wikiText ) {
		$oldCategories = $this->extractCategories( $wikiText );

		$newCatText = '';
		foreach ( $newCategories as $cat ) {
			$catTitleWikified = $this->getWikifiedTitle( $cat, NS_CATEGORY );
			if ( !array_key_exists( $catTitleWikified, $oldCategories ) ) {
				$newCatText .= "\n" . '[[Category:' . $catTitleWikified . "]]"; // Is there an inbuilt class method to do this?  Can't find one in Category.
			}
		}
		$wikiText .= $newCatText;

		return $wikiText;
	}

	/**
	 * Extract an array of properties from wiki text
	 * FIXME: Refactor to be 'extractFacts' instead
	 * @param string $wikiContent
	 * @return array
	 */
	private function extractProperties( $wikiContent ) {
		$mwProperties = array();
		preg_match_all( '/\[\[(.*)::([^\|\]]+)(\|([^\]]*))?\]\]/', $wikiContent, $matches );
		$wikiText = $matches[0];
		$propName = $matches[1];
		$propVal = $matches[2];
		foreach ( $propName as $idx => $pName ) {
			$mwProperties[$pName] = array( 'value' => $propVal[$idx], 'wikitext' => $wikiText[$idx] );
		}
		return $mwProperties;
	}

	/**
	 * Extract an array of categories from wiki text
	 * @param string $wikiContent
	 * @return array $mwCategories
	 */
	private function extractCategories( $wikiContent ) {
		$mwCategories = array();
		preg_match_all( '/\[\[Category:([^\|]*)\|?[^\|]*\]\]/', $wikiContent, $matches );
		$wikiText = $matches[0];
		$catName = $matches[1];
		foreach ( $catName as $idx => $cName ) {
			$mwCategories[$cName] = array( 'wikitext' => $wikiText[$idx] );
		}
		return $mwCategories;
	}

	/**
	 * Extract an array of templates from wiki text
	 * @param string $wikiContent
	 * @return array $mwTemplates
	 */
	private function extractTemplateCalls( $wikiContent ) {
		$mwTemplates = array();
		preg_match_all( '/\{\{\s?([^#][A-Za-z0-9\ ]+)\s?(\|([^\}]*))?\s?\}\}/U', $wikiContent, $matches );
		$wikiText = $matches[0];
		$tplName = $matches[1];
		$tplParams = $matches[3];
		foreach ( $tplName as $idx => $tName ) {
			$mwTemplates[$tName] = array();
			$mwTemplates[$tName]['templateCallText'] = $wikiText[$idx];
			$mwTemplates[$tName]['templateParamsValues'] = $tplParams[$idx];
		}
		return $mwTemplates;
	}

	/**
	 * Get the wikified title
	 * @param string $title
	 * @param int $wikiNamespace
	 * @return string $wikifiedTitle
	 */
	private function getWikifiedTitle( $title, $wikiNamespace = NS_MAIN ) {
		$titleObj = Title::newFromText( $title, $wikiNamespace );
		$wikifiedTitle = $titleObj->getText();
		return $wikifiedTitle;
	}

	/**
	 * Retrieves wiki text from wiki database
	 * @param string $title
	 * @param int $wikiNamespace
	 * @return string $wikiText
	 */
	private function getTextForPage( $title, $wikiNamespace = NS_MAIN ) {
		$wikiText = '';
		$titleObj = Title::newFromText( $title, $wikiNamespace );
		$pageObj = WikiPage::factory( $titleObj );
		$content = $pageObj->getContent();
		if ( $content !== null ) {
			$wikiText = $content->getNativeData();
		}
		return $wikiText;
	}

	/**
	 * Takes the parsed and updated content as
	 * a string and writes to the wiki.
	 * @param string $wikiTitle
	 * @param string $content
	 * @param string $summary
	 */
	private function writeToArticle( $wikiTitle, $content, $summary ) {
		$mwTitleObj = Title::newFromText( $wikiTitle );
		$mwArticleObj = new Article( $mwTitleObj );
		$mwArticleObj->doEdit( $content, $summary );
	}

	/**
	 *
	 * @param RDFIOWikiPage $wikiPage
	 * @return array $templates
	 */
	private function getTemplatesForCategories( $categories ) {
		$templates = array();
		foreach ( $categories as $cat ) {
			$catPageText = $this->getTextForPage( $cat, NS_CATEGORY );
			preg_match( '/\[\[Has template::Template:(.*)\]\]/', $catPageText, $matches ); // get Has template property, if exists
			if ( !empty( $matches ) ) {
				$tplName = $matches[1];
				$tplCallText = '{{' . $tplName . '}}'; // Add template call to page wikitext - {{templatename}}
				$templates[$tplName] = $tplCallText;
			}
		}
		return $templates;
	}
}
