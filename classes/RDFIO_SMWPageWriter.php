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

		// ---------------------------------------------------------------------------------------------
		// Overview of intended process
		// ---------------------------------------------------------------------------------------------
		//  1. Loop over wiki pages
		//      2. Get the old wiki text for current page
		//      3. Find all existing fact statements in page (to be updated)
		//      4. Find all existing template statements in page (to be updated)
		//      5. Find all templates that might be used (in current page, and via all its categories)
		//      6. Build an index: Property -> Template(s) -> Parameter name(s)
		//      7. Loop over each fact and:
		//          8. Update all existing fact statements on page
		//          9. Update in all existing templates, based on index
		//         10. Add to any relevant templates as new template calls
		//         11. If neither of 8-10 was done, add as new fact statements
		//         12. Update any URI-type objects with an Equivalent URI fact.
		//     13. Add category tags (if template with category has not been added yet).
		//     14. Write updated article
		// ---------------------------------------------------------------------------------------------

		// ----------------------------------------------------------------------
		//  1. Loop over wiki pages
		// ----------------------------------------------------------------------
		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			/* @var $wikiPage RDFIOWikiPage */

			// ----------------------------------------------------------------------
			//  2. Get the old wiki text for current page
			// ----------------------------------------------------------------------
			$oldWikiText = $this->getTextForPage( $wikiTitle );
			$newWikiText = $oldWikiText; // using new variable to separate extraction from editing

			// ----------------------------------------------------------------------
			//  3. Find all existing fact statements in page (to be updated)
			// ----------------------------------------------------------------------
			$oldFacts = $this->extractFacts( $oldWikiText );

			// ----------------------------------------------------------------------
			//  4. Find all existing template statements in page (to be updated)
			// ----------------------------------------------------------------------
			$oldTemplateCalls = $this->extractTemplateCalls( $oldWikiText );

			// ----------------------------------------------------------------------
			//  5. Find all templates that might be used (in current page, and via all its categories)
			// ----------------------------------------------------------------------
			$newCategories = $wikiPage->getCategories();
			$tplsForNewCats = $this->getTemplatesForCategories( $newCategories );

			// Collect old and new template names in one array
			$allTemplateNames = [];
			foreach ( $oldTemplateCalls as $tplName => $tplInfo ) {
				$allTemplateNames[] = $tplName;
			}
			$allTemplateNames = array_merge( $allTemplateNames, $tplsForNewCats );

			// ----------------------------------------------------------------------
			//  6. Build an index: Property -> Template(s) -> Parameter name(s)
			// ----------------------------------------------------------------------
			// Collect template wiki texts for building property/template index
			$allTemplateTexts = array();
			foreach ( $allTemplateNames as $tplName ) {
				$tplPageText = $this->getTextForPage( $tplName, NS_TEMPLATE );
				$allTemplateTexts[$tplName] = $tplPageText;
			}

			// Extract facts from template wiki texts (for building property/template index)
			$allTemplateFacts = array();
			foreach ( $allTemplateTexts as $tplName => $tplPageText ) {
				$allTemplateFacts[$tplName] = $this->extractPropertyParameterIndex( $tplPageText );
			}

			$propTplIndex = $this->buildPropertyTemplateParamIndex( $wikiPage->getFacts(), $allTemplateFacts );

			// ----------------------------------------------------------------------
			//  7. Loop over each fact and:
			// ----------------------------------------------------------------------
			foreach ( $wikiPage->getFacts() as $fact ) {
				$wikiTextUpdatedWithFact = $newWikiText;
				// ----------------------------------------------------------------------
				//  8. Update all existing fact statements on page
				// ----------------------------------------------------------------------
				$wikiTextUpdatedWithFact = $this->updateExplicitFactsInText( $fact, $wikiTextUpdatedWithFact );

				// ----------------------------------------------------------------------
				//  9. Update in all existing template calls, based on index
				// ----------------------------------------------------------------------
				$wikiTextUpdatedWithFact = $this->updateTemplateCalls( $fact, $propTplIndex, $oldTemplateCalls, $wikiTextUpdatedWithFact );

				// ----------------------------------------------------------------------
				// 10. If the fact is not updated yet, write via any relevant templates as new template calls
				// ----------------------------------------------------------------------
				//if ( $wikiTextUpdatedWithFact === $newWikiText ) {
				//	$wikiTextUpdatedWithFact = $this->addViaNewTemplateCalls( $wikiTextUpdatedWithFact );
				//}

				// ----------------------------------------------------------------------
				// 11. If neither of 8-10 was done, add as new fact statements
				// ----------------------------------------------------------------------
				if ( $wikiTextUpdatedWithFact === $newWikiText ) {
					$wikiTextUpdatedWithFact = $this->addNewExplicitFact( $fact, $wikiTextUpdatedWithFact );
				}

				// ----------------------------------------------------------------------
				// 12. Update any URI-type objects with an Equivalent URI fact.
				// ----------------------------------------------------------------------

				// Update main wiki text variable with changes for fact
				$newWikiText = $wikiTextUpdatedWithFact;
			}



			// if ( !empty( $oldTemplates ) ) {
			//
			// $hasTplParams = array_key_exists( 'paramvals', $oldTemplates[$tplName] );
			// // Get the parameter values used in the templates
			// if ( $hasTplParams ) {
			// 	$paramvals = explode( '|', $oldTemplates[$tplName]['paramvals'] );
			// 	foreach ( $paramvals as $paramPair ) {
			// 		$paramValArray = explode( '=', $paramPair );
			// 		$paramName = $paramValArray[0];
			// 		$paramVal = $paramValArray[1];
			// 		$oldTemplates[$tplName]['parameters'][$paramName]['value'] = $paramVal;
			// 	}
			// }
			// }

			// Add Facts
			// $newWikiText = $this->addNewFactsToWikiText( $wikiPage->getFacts(), $oldTemplates, $newWikiText );

			// Add Categories
			// $newWikiText = $this->addNewCategoriesToWikiText( $newCategories, $newWikiText );

			// ----------------------------------------------------------------------
			// 13. Add category tags (if template with category has not been added yet).
			// ----------------------------------------------------------------------

			// ----------------------------------------------------------------------
			// 14. Write updated article
			// ----------------------------------------------------------------------
			$this->writeToArticle( $wikiTitle, $newWikiText, 'Page updated by RDFIO' );
		}
	}

	/**
	 * @param array $newFacts
	 * @param array $allTemplateFacts
	 * @return array
	 */
	private function buildPropertyTemplateParamIndex( $newFacts, $allTemplateFacts ) {
		// Build the index
		$propTplIndex = array();
		foreach ( $newFacts  as $fact ) {
			$prop = $fact['p'];
			$propTplIndex[$prop] = array();
			foreach ( $allTemplateFacts as $tplName => $tplFacts ) {
				if ( array_key_exists( $prop, $tplFacts  ) ) {
					$paramName = $tplFacts[$prop];
					$propTplIndex[$prop][$tplName] = $paramName;
				}
			}
		}
		return $propTplIndex;
	}

	/**
	 * @param array $fact
	 * @param string $wikiText
	 * @return string $wikiText
	 */
	private function updateExplicitFactsInText( $fact, $wikiText ) {
		$prop = $fact['p'];
		$newVal = $fact['o'];

		$oldFacts = $this->extractFacts( $wikiText );
		if ( array_key_exists( $prop, $oldFacts ) ) {
			$oldVal = $oldFacts[$prop]['value'];
			$wikiText = str_replace( $oldVal, $newVal, $wikiText );
		}
		return $wikiText;
	}


	/**
	 * @param $fact
	 * @param $propTplIndex
	 * @param $wikiText
	 * @return string $wikiText
	 */
	private function updateTemplateCalls( $fact, $propTplIndex, $oldTemplateCalls, $wikiText ) {
		$prop = $fact['p'];
		$newVal = $fact['o'];

		if ( array_key_exists($prop, $propTplIndex) ) {
			foreach ( $propTplIndex[$prop] as $tplName => $paramName ) {
				$oldTplCallText = $oldTemplateCalls[$tplName]['calltext'];

				preg_match( '/\|' . $paramName . '\=([^\=\|\}\n]+)/', $oldTplCallText, $matches );
				if ( !empty( $matches ) ) {
					$oldVal = $matches[1];
					$newTplCallText = str_replace( $oldVal, $newVal, $oldTplCallText );
					$wikiText = str_replace( $oldTplCallText, $newTplCallText, $wikiText );
				}
			}
		}

		return $wikiText;
	}

	/**
	 * @param array $fact
	 * @param string $wikiText
	 * @return string $wikiText
	 */
	private function addNewExplicitFact( $fact, $wikiText ) {
		$p = $fact['p'];
		$o = $fact['o'];

		$newFactText = "\n" . '[[' . $p . '::' . $o . ']]';
		$wikiText .= $newFactText;

		return $wikiText;
	}

	/**
	 * Add facts to wiki text
	 * @param array $facts
	 * @param array $updatedTplCalls
	 * @param array $oldTemplates
	 * @param string $wikiText
	 * @return string $wikiText
	 */
	private function addNewFactsToWikiText( $facts, $oldTemplates, $wikiText ) {
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
					$oldTplCall = $oldTemplates[$tplName]['calltext'];  // use temp value as may be updated more than once
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
					$wikiText = str_replace( $oldTplCall, $newTplCall, $wikiText );
					$updatedTplCalls[$tplName] = $newTplCall;
				}

			} else if ( $isInPage ) {
				// if it's a standard property in the page, replace value with new one if different

				$oldPropText = $oldProperties[$predTitleWikified]['wikitext'];
				// Store the old wiki text for the fact, in order to replace later

				$newPropText = '[[' . $predTitleWikified . '::' . $newValText . ']]';

				// Replace the existing property with new value
				if ( $newPropText != $oldPropText ) {
					$wikiText = str_replace( $oldPropText, $newPropText, $wikiText );
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
	 * @param $tplPageText
	 * @return array $propParamIndex
	 */
	private function extractPropertyParameterIndex( $tplPageText ) {
		$propParamIndex = array();
		// Get the properties and parameter names used in the templates
		preg_match_all( '/\[\[([^\:]+)::\{\{\{([^\|\}]+)\|?\}\}\}\]\]/', $tplPageText, $tplParamMatches );
		$propNames = $tplParamMatches[1];
		$paramNames = $tplParamMatches[2];
		foreach ( $propNames as $idx => $propName ) {
			$propParamIndex[$propName] = $paramNames[$idx];
		}
		return $propParamIndex;
	}

	/**
	 * Extract an array of facts from wiki text
	 * @param string $wikiContent
	 * @return array $facts
	 */
	private function extractFacts( $wikiContent ) {
		$facts = array();
		preg_match_all( '/\[\[(.*)::([^\|\]]+)(\|([^\]]*))?\]\]/', $wikiContent, $matches );
		$wikiText = $matches[0];
		$propName = $matches[1];
		$propVal = $matches[2];
		foreach ( $propName as $idx => $pName ) {
			$facts[$pName] = array( 'property' => $pName, 'value' => $propVal[$idx], 'wikitext' => $wikiText[$idx] );
		}
		return $facts;
	}

	/**
	 * Extract an array of properties from wiki text
	 * @param string $wikiContent
	 * @return array $mwProperties
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
	 * @return array $templates
	 */
	private function extractTemplateCalls( $wikiContent ) {
		$templates = array();
		preg_match_all( '/\{\{\s?([^#][A-Za-z0-9\ ]+)\s?(\|([^\}]*))?\s?\}\}/U', $wikiContent, $matches );
		$wikiText = $matches[0];
		$tplName = $matches[1];
		$tplParamsText = $matches[2];
		foreach ( $tplName as $idx => $tName ) {
			$templates[$tName] = array();
			$templates[$tName]['calltext'] = $wikiText[$idx];

			$paramVals = array();
			preg_match_all( '/\|([^\|\n\=]+)\=([^\|\=\n]+)/', $tplParamsText[$idx], $paramMatches );
			$names = $paramMatches[1];
			$vals = $paramMatches[2];
			foreach ( $names as $idx => $name ) {
				$paramVals[] = array( 'name' => $names[$idx], 'val' => $vals[$idx] );
			}
			$templates[$tName]['paramvals'] = $paramVals;
		}
		return $templates;
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
	 * Get templates referred to via "Has template", by a set of categories
	 * @param RDFIOWikiPage $wikiPage
	 * @return array $templateNames
	 */
	private function getTemplatesForCategories( $categories ) {
		$templateNames = array();
		foreach ( $categories as $cat ) {
			$catPageText = $this->getTextForPage( $cat, NS_CATEGORY );
			$tplNamesForCat = $this->extractTplNameFromHasTemplateFact( $catPageText );
			$templateNames = array_merge( $templateNames, $tplNamesForCat );
		}
		return $templateNames;
	}

	/**
	 * Extract template names from facts of the form [[Has template::Template:...]]
	 * @param string $wikiText
	 * @return array
	 */
	private function extractTplNameFromHasTemplateFact( $wikiText ) {
		preg_match_all( '/\[\[Has template::Template:([^\|\]]+)(\|[^\|\]]*)?\]\]/', $wikiText, $matches ); // get Has template property, if exists
		$templateNames = $matches[1];
		return $templateNames;
	}
}
