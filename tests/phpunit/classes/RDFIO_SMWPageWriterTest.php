<?php  

class RDFIOSMWPageWriterTest extends MediaWikiTestCase {

    protected function setUp() {
        parent::setUp();
    }

    protected function tearDown() {
        parent::tearDown();
    }
    
// class RDFIOSMWPageWriter {

// 	public function __construct() {}

// 	/**
// 	 * Main function, that takes an array of RDFIOWikiPage objects, and writes to
// 	 * MediaWiki using the WikiObjectModel extension.
// 	 * @param array $wikiPages
// 	 */
// 	public function import( $wikiPages ) {
// 		global $wgOut;

// 		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
// 			// Get data from Wiki Page object
// 			$facts = $wikiPage->getFacts();
// 			$equivUris = $wikiPage->getEquivalentUris();
// 			$categories = $wikiPage->getCategories();
				
// 			// Get property objects from WOM
// 			$womPropertyObjs = array();
// 			$womCategoryObjs = array();
// 			$wikiContent = "";
// 			$mwTitleObj = Title::newFromText( $wikiTitle );
			
// 			// If page exists, get it's data from WOM
// 			if ( is_object($mwTitleObj) && $mwTitleObj->exists() ) {
// 				$womWikiPage = WOMProcessor::getPageObject( $mwTitleObj );
				
// 				// Get wiki text
// 				$wikiContent = $womWikiPage->getWikiText();
				
// 				// Get properties
// 				try{
// 					$propertyObjIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//property' );
// 					// use page object functions
// 					foreach ( $propertyObjIds as $propertyObjId ) {
// 						$womPropertyObj = $womWikiPage->getObject( $propertyObjId );
// 						$womPropertyName = $womPropertyObj->getPropertyName();
// 						$womPropertyObjs[$womPropertyName] = $womPropertyObj;
// 					}
// 				} catch( Exception $e ) {
// 					#$wgOut->addHTML( '<pre>Exception when talking to WOM: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</pre>' );
// 				}
				
// 				// Get categories
// 				try {
// 					$categoryObjIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//category' );
// 					foreach ( $categoryObjIds as $categoryObjId ) {
// 						$womCategoryObj = $womWikiPage->getObject( $categoryObjId );
// 						$womCategoryName = $womCategoryObj->getName();
// 						$womCategoryObjs[$womCategoryName] = $womCategoryObj; 
// 					}
// 				} catch( Exception $e ) {
// 					#$wgOut->addHTML( '<pre>Exception when talking to WOM: ' . $e->getMessage() . '</pre>' );
// 				}
				
// 			}

// 			// Add facts (properties) to the wiki text
// 			$newPropertiesAsWikiText = "\n";
// 			foreach ( $facts as $fact ) {
// 				$pred = $fact['p'];
// 				$obj = $fact['o'];
				
// 				$predTitle = Title::newFromText( $pred );
// 				$predTitleWikified = $predTitle->getText();
				
// 				$isEquivURI = strpos( $pred, "Equivalent URI" ) !== false;
// 				$hasLocalUrl = strpos( $obj, "Special:URIResolver" ) !== false;
// 				if ( $hasLocalUrl && $isEquivURI ) {
// 					// Don't update Equivalent URI if the URL is a local URL (thus containing
// 					// "Special:URIResolver").
// 				} else if ( !array_key_exists( $predTitleWikified, $womPropertyObjs ) ) { // If property already exists ...
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
// 			$wikiContent .= $newPropertiesAsWikiText;
			
// 			// Add categories to the wiki text
// 			$newCategoriesAsWikiText = "\n";
// 			foreach( $categories as $category ) {

// 				$categoryTitle = Title::newFromText( $category );
// 				$categoryTitleWikified = $categoryTitle->getText();
				
// 				if ( !array_key_exists( $categoryTitleWikified, $womCategoryObjs ) ) { // If property already exists ...
// 					$newWomCategoryObj = new WOMCategoryModel( $categoryTitleWikified );
// 					$newCategoryAsWikiText = $newWomCategoryObj->getWikiText();
// 					$newCategoriesAsWikiText .= $newCategoryAsWikiText . "\n";
// 				} 
// 			}
// 			$wikiContent .= $newCategoriesAsWikiText;
				
// 			// Write to wiki
// 			$this->writeToArticle($wikiTitle, $wikiContent, 'Update by RDFIO');
// 		}
// 	}
	
// 	/**
// 	 * The actual write function, that takes the parsed and updated content as 
// 	 * a string and writes to the wiki.
// 	 * @param string $wikiTitle
// 	 * @param string $content
// 	 * @param string $summary
// 	 */
// 	protected function writeToArticle( $wikiTitle, $content, $summary ) {
// 		$mwTitleObj = Title::newFromText( $wikiTitle );
// 		$mwArticleObj = new Article( $mwTitleObj );
// 		$mwArticleObj->doEdit( $content, $summary );
// 	}

// }

}
    