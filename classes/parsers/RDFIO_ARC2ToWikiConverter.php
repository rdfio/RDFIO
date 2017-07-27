<?php

class RDFIOARC2ToWikiConverter extends RDFIOParser {
	protected $wikiPages;

	public function __construct() {
		$this->wikiPages = array();
	}

	/**
	 * Take ARC2 array data structures (triples, triple index, and namespaces)
	 * and convert to an array of RDFIOWikiPage objects.
	 *
	 * @param array $arc2Triples
	 * @param array $arc2ResourceIndex
	 * @param array $arc2NSPrefixes
	 * @throws MWException
	 * @return Ambigous <multitype:, RDFIOWikiPage>
	 */
	public function convert( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		// Instatiate wiki title converters (converting from URI and related RDF data to Wiki Title)
		$uriToTitleConv = new RDFIOURIToWikiTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );
		$uriToPropTitleConv = new RDFIOURIToPropertyTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );

		/*
		 * The main loop, doing the convertion of triples into 
		 * a representation of wiki pages instead.
		 */
		foreach ( $arc2Triples as $triple ) {
			// Convert URI:s to wiki titles
			$wikiPageTitle = $uriToTitleConv->convert( $triple['s'] );

			if ( $triple['p'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' ) {

				// Add categorization of page
				$catPageTitle = $uriToTitleConv->convert( $triple['o'] );
				$catPageTitleWithNS = Title::makeTitleSafe( NS_CATEGORY, $catPageTitle )->getFullText();
				// Add data for the subject page
				$this->addDataToPage( $wikiPageTitle, $triple['s'], $fact = null, $catPageTitleWithNS );
				// Add data for the category page
				$this->addDataToPage( $catPageTitleWithNS, $triple['o'] );

			} else if ( $triple['p'] === 'http://www.w3.org/2000/01/rdf-schema#subClassOf' ) {

				// Add categorization of page
				$catPageTitle = $uriToTitleConv->convert( $triple['o'] );
				$catPageTitleWithNS = Title::makeTitleSafe( NS_CATEGORY, $catPageTitle )->getFullText();
				$pageTitleWithNS = Title::makeTitleSafe( NS_CATEGORY, $wikiPageTitle )->getFullText();

				// Add data for the subject page
				$this->addDataToPage( $pageTitleWithNS, $triple['s'], $fact = null, $catPageTitleWithNS );

				// Add data for the category page
				$this->addDataToPage( $catPageTitleWithNS, $triple['o'] );

			} else {
				// Separate handling for properties
				$propertyTitle = $uriToPropTitleConv->convert( $triple['p'] );
				// Add the property namespace to property title
				$propTitleWithNS = Title::makeTitleSafe( SMW_NS_PROPERTY, $propertyTitle )->getFullText();
				// Add Equivalent URI to property page
				$this->addDataToPage( $propTitleWithNS, $triple['p'] );

				/*
				 * Decide whether to create a page for the linked "object" or not,
				 * depending on object datatype (uri or literal)
				 */
				$propertyDataType = null;
				switch ( $triple['o_type'] ) {
					case 'uri':
						// Create new page for the object
						$object = $uriToTitleConv->convert( $triple['o'] );
						$this->addDataToPage( $object, $triple['o'] );
						// Since URIs convert to pages, properties linking to URIs will be of 'Page' type
						$propertyDataType = 'Page';
						break;
					case 'literal':
						$object = $triple['o'];

						// Determine data type of property
						$xsd = 'http://www.w3.org/2001/XMLSchema#';
						switch( $triple['o_datatype'] ) {
							case $xsd . 'byte':
							case $xsd . 'decimal':
							case $xsd . 'int':
							case $xsd . 'integer':
							case $xsd . 'long':
							case $xsd . 'negativeInteger':
							case $xsd . 'nonNegativeInteger':
							case $xsd . 'nonPositiveInteger':
							case $xsd . 'positiveInteger':
							case $xsd . 'short':
							case $xsd . 'unsignedLong':
							case $xsd . 'unsignedInt':
							case $xsd . 'unsignedShort':
							case $xsd . 'unsignedByte':
							case $xsd . 'float':
							case $xsd . 'double':
								$propertyDataType = 'Number';
								break;
							case $xsd . 'string':
								$propertyDataType = 'Text';
								break;
							case $xsd . 'date':
							case $xsd . 'dateTime':
							case $xsd . 'duration':
							case $xsd . 'time':
								$propertyDataType = 'Date';
								break;
							case $xsd . 'boolean':
							case $xsd . 'bool':
								$propertyDataType = 'Boolean';
								break;
							case $xsd . 'anyURI':
								$propertyDataType = 'URL';
								break;
							default:
								if ( substr( $object, 0, 4 ) === 'http' ) {
									$propertyDataType = 'URL';
									break;
								}
								$propertyDataType = 'Text';
						}
						break;
					default:
						throw new RDFIOARC2ToWikiConverterException( 'Error in ARC2ToWikiConverter: Unknown type ("' . $triple['o_type'] . '") of object ("' . $triple['o'] . '") in triple! (not "uri" nor "literal")!' );
				}

				// Add Data type to property page.
				// NOTE: This is important to do BEFORE adding any fact using the property,
				// in order for the fact to get correct encoding in the ARC2 store.
				if( !is_null( $propertyDataType ) ) {
					$this->addDataToPage( $propTitleWithNS, null, null, null, $propertyDataType );
				}

				// Create a fact array
				$fact = array( 'p' => $propertyTitle, 'o' => $object );
				// Add data to class variables
				$this->addDataToPage( $wikiPageTitle, $triple['s'], $fact );
			}
		}

		return $this->wikiPages;
	}

	/**
	 * Convenience function to add a $fact (predicate/object tuple) or $category into
	 * the RDFIOWikiPage according to the title specified in $pageTitle
	 * @param string $pageTitle
	 * @param string $equivURI
	 * @param string $fact
	 * @param string $category
	 */
	private function addDataToPage( $pageTitle, $equivURI = null, $fact = null, $category = null, $dataType = null ) {
		// Create page array if not exists in array
		if ( !array_key_exists( $pageTitle, $this->wikiPages ) ) {
			$this->wikiPages[$pageTitle] = new RDFIOWikiPage( $pageTitle );
		}
		if ( !is_null( $equivURI ) ) {
			$this->wikiPages[$pageTitle]->addEquivalentURI( $equivURI );
		}
		if ( !is_null( $fact ) ) {
			$this->wikiPages[$pageTitle]->addFact( $fact );
		}
		if ( !is_null( $category ) ) {
			$this->wikiPages[$pageTitle]->addCategory( $category );
		}
		if ( !is_null( $dataType ) ) {
			$this->wikiPages[$pageTitle]->addDataType( $dataType );
		}
	}

}

class RDFIOARC2ToWikiConverterException extends MWException {
}
