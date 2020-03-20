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

		// ----------------------------------------------------------------------
		//  1. Loop over wiki pages
		// ----------------------------------------------------------------------
		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			/* @var $wikiPage RDFIOWikiPage */

			if ( $wikiTitle == '' ) {
				throw new MWException( 'Could not import page: Title is empty!' );
			}

			// ----------------------------------------------------------------------
			//  3. Find all existing fact statements in page (to be updated)
			// ----------------------------------------------------------------------
			$oldWikiText = $this->getTextForPage( $wikiTitle );
			$newWikiText = $oldWikiText; // using new variable to separate extraction from editing

			// ----------------------------------------------------------------------
			//  2. Get the old wiki text for current page
			// ----------------------------------------------------------------------
			$oldFacts = $this->extractFacts( $oldWikiText );

			// ----------------------------------------------------------------------
			//  4. Find all existing template statements in page (to be updated)
			// ----------------------------------------------------------------------
			$oldTplCalls = $this->extractTemplateCalls( $oldWikiText );

			// ----------------------------------------------------------------------
			//  5. Find all templates that might be used (in current page, and via all its categories)
			// ----------------------------------------------------------------------
			$newCategories = $wikiPage->getCategories();
			$tplsForNewCats = $this->getTemplatesForCategories( $newCategories );

			// Collect old and new template names in one array
			$allTemplateNames = [];
			foreach ( array_keys( $oldTplCalls ) as $tplName ) {
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
				$wikiTextUpdated = $newWikiText;
				// ----------------------------------------------------------------------
				//  8. Update all existing fact statements on page
				// ----------------------------------------------------------------------
				$wikiTextUpdated = $this->updateExplicitFactsInText( $fact, $oldFacts, $wikiTextUpdated );

				// ----------------------------------------------------------------------
				//  9. Update in all existing template calls, based on index
				// ----------------------------------------------------------------------
				$currentTplCalls = $this->extractTemplateCalls( $wikiTextUpdated );
				$wikiTextUpdated = $this->updateTemplateCalls( $fact, $propTplIndex, $currentTplCalls, $wikiTextUpdated );

				// ----------------------------------------------------------------------
				// 10. If the fact is not updated yet, write via any relevant templates as new template calls
				// ----------------------------------------------------------------------
				//if ( $wikiTextUpdated === $newWikiText ) {
				//	$wikiTextUpdated = $this->addViaNewTemplateCalls( $wikiTextUpdated );
				//}

				// ----------------------------------------------------------------------
				// 11. If neither of 8-10 was done, add as new fact statements
				// ----------------------------------------------------------------------
				$prop = $fact['p'];
				if ( !array_key_exists( $prop, $oldFacts ) && !( $propTplIndex[$prop] ) ) {
					$wikiTextUpdated = $this->addNewExplicitFact( $fact, $wikiTextUpdated );
				}

				// ----------------------------------------------------------------------
				// 12. Update any URI-type objects with an Equivalent URI fact.
				// ----------------------------------------------------------------------

				// Update main wiki text variable with changes for fact
				$newWikiText = $wikiTextUpdated;
			}

			// ----------------------------------------------------------------------
			// 13. Add category tags (if template with category has not been added yet).
			// ----------------------------------------------------------------------
			foreach ( $newCategories as $cat ) {
				$newWikiText = $this->addNewCategory( $cat, $newWikiText );
			}

			// ----------------------------------------------------------------------
			// 14. Write updated page
			// ----------------------------------------------------------------------
			$this->writeToPage( $wikiTitle, $newWikiText, 'Page updated by RDFIO' );
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
	private function updateExplicitFactsInText( $fact, $oldFacts, $wikiText ) {
		$prop = $fact['p'];
		$newVal = $fact['o'];

		$propWikified = $this->getWikifiedTitle( $prop );

		if ( array_key_exists( $propWikified, $oldFacts ) ) {
			$oldVal = $oldFacts[$propWikified]['value'];
			$wikiText = str_replace( $oldVal, $newVal, $wikiText );
		}
		return $wikiText;
	}


	/**
	 * @param $fact
	 * @param $propTplIndex
	 * @param $wikiText
	 * @return array
	 */
	private function updateTemplateCalls( $fact, $propTplIndex, $oldTemplateCalls, $wikiText ) {
		$prop = $fact['p'];
		$newVal = $fact['o'];

		if ( array_key_exists( $prop, $propTplIndex ) ) {
			foreach ( $propTplIndex[$prop] as $tplName => $paramName ) {
				$oldTplCallText = $oldTemplateCalls[$tplName]['calltext'];

				preg_match( '/\|' . $paramName . '\=([^\=\|\}\n]+)/', $oldTplCallText, $matches );
				if ( $matches ) {
					$oldVal = $matches[1];
					$newTplCallText = str_replace( $oldVal, $newVal, $oldTplCallText );
				} else {
					$newTplCallText = str_replace('}}', '|' . $paramName . '=' . $newVal . "\n}}", $oldTplCallText);
				}
				$wikiText = str_replace( $oldTplCallText, $newTplCallText, $wikiText );
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
		$prop = $fact['p'];
		$val = $fact['o'];

		$propWikified = $this->getWikifiedTitle( $prop );

		$oldFacts = $this->extractFacts( $wikiText );
		if ( !array_key_exists( $propWikified, $oldFacts ) ) {
			$newFactText = "\n" . '[[' . $propWikified . '::' . $val . ']]';
			$wikiText .= $newFactText;
		}

		return $wikiText;
	}

	/**
	 * @param string $category
	 * @param string $wikiText
	 * @return string $wikiText
	 */
	private function addNewCategory( $category, $wikiText ) {
		$oldCategories = $this->extractCategories( $wikiText );
		$catTitleWikified = $this->getWikifiedTitle( $category, NS_CATEGORY );
		if ( !array_key_exists( $catTitleWikified, $oldCategories ) ) {
			$newCatText = "\n" . '[[Category:' . $catTitleWikified . "]]"; // Is there an inbuilt class method to do this?  Can't find one in Category.
			$wikiText .= $newCatText;
		}
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
		preg_match_all( '/\[\[([^\:]+)::([^\|\]]+)(\|([^\]]*))?\]\]/', $wikiContent, $matches );
		$wikiText = $matches[0];
		$propName = $matches[1];
		$propVal = $matches[2];
		foreach ( $propName as $idx => $pName ) {
			$facts[$pName] = array( 'property' => $pName, 'value' => $propVal[$idx], 'wikitext' => $wikiText[$idx] );
		}
		return $facts;
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
				$paramVals[] = array( 'name' => $name, 'val' => $vals[$idx] );
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
	private function writeToPage( $wikiTitle, $content, $summary ) {
		$mwTitleObj = Title::newFromText( $wikiTitle );
		$mwArticleObj = WikiPage::factory( $mwTitleObj );
		$mwArticleObj->doEditContent( ContentHandler::makeContent( $content, $mwTitleObj), $summary );
		// This is needed to populate the semantic data in the ARC2 store:
		$mwArticleObj->doSecondaryDataUpdates();
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
