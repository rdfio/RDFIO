<?php

/**
 * RDFIOARC2StoreWrapper contains utility functionality that requires connecting to the
 * ARC2 triplestore (Not to confuse with the RDFIOARC2Store, which is an implementation
 * of SMWStore "interface". Here we're wrapping some direct queries to the ARC2 triplestore.
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOARC2StoreWrapper {
	protected $tripleStore;
	protected $uriResolverUrl;

	const EQUIV_URI = 'http://www.w3.org/2002/07/owl#sameAs';
	const EQUIV_PROPERTY_URI = 'http://www.w3.org/2002/07/owl#equivalentProperty';

	function __construct( $tripleStore = null ) {
		global $smwgARC2StoreConfig;
		$this->uriResolverUrl = '';
		if ( !is_null( $tripleStore ) ) {
			$this->tripleStore = $tripleStore;
		}
		$this->tripleStore = ARC2::getStore( $smwgARC2StoreConfig );
	}

	/**
	 * For all property URIs and all subject and objects which have URIs, add
	 * triples using equivalent uris for these URIs (in all combinations
	 * thereof). If $propUrisFilter is set, allow only triples with properties
	 * included in this filter array.
	 * @param array $triples
	 * @param array $propUrisFilter
	 * @return array $triples
	 */
	function complementTriplesWithEquivURIs( $triples, $propUrisFilter = '' ) {
		$newTriples = array();

		foreach ( $triples as $triple ) {
			// Subject
			$subjEquivUris = array( $triple['s'] );
			if ( $triple['s_type'] === 'uri' ) {
				$subjUri = $triple['s'];
				$subjEquivUrisTmp = $this->getEquivURIsForURI( $subjUri );
				if ( count( $subjEquivUrisTmp ) > 0 ) {
					$subjEquivUris = $subjEquivUrisTmp;
				}
			}

			// Property
			$propertyuri = $triple['p'];
			$propEquivUris = array( $triple['p'] );
			$propEquivUrisTmp = $this->getEquivURIsForURI( $propertyuri, true );


			if ( count( $propEquivUrisTmp ) > 0 ) {
				if ( $propUrisFilter != '' ) {
					// Only include URIs that occur in the filter
					$propEquivUrisTmp = array_intersect( $propEquivUrisTmp, $propUrisFilter );
				}
				if ( $propEquivUrisTmp != '' ) {
					$propEquivUris = $propEquivUrisTmp;
				}
			}

			// Object
			$objEquivUris = array( $triple['o'] );
			if ( $triple['o_type'] === 'uri' ) {
				$objUri = $triple['o'];
				$objEquivUrisTmp = $this->getEquivURIsForURI( $objUri );
				if ( count( $objEquivUrisTmp ) > 0 ) {
					$objEquivUris = $objEquivUrisTmp;
				}
			}

			// Generate triples
			foreach ( $subjEquivUris as $subjEquivUri ) {
				foreach ( $propEquivUris as $propEquivUri ) {
					foreach ( $objEquivUris as $objEquivUri ) {
						$newtriple = array(
							's' => $subjEquivUri,
							'p' => $propEquivUri,
							'o' => $objEquivUri
						);
						$newTriples[] = $newtriple;
					}
				}
			}
		}
		return $newTriples;
	}

	/**
	 * For a given RDF URI, return it's corresponding equivalend URIs
	 * as defined in wiki articles by the Equivalent URI property
	 * @param string $uri
	 * @param boolean $isProperty
	 * @return array $equivUris
	 */
	public function getEquivURIsForURI( $uri, $isProperty = false ) {
		$equivUris = array();
		if ( $isProperty ) {
			$equivUriUri = self::EQUIV_PROPERTY_URI;
		} else {
			$equivUriUri = self::EQUIV_URI;
		}

		$query = 'SELECT ?equivUri WHERE { <' . $uri . '> <' . $equivUriUri . '> ?equivUri }';
		$results = $this->tripleStore->query( $query );

		if ( $this->tripleStore->getErrors() ) {
			foreach ( $this->tripleStore->getErrors() as $error ) {
				throw new RDFIOARC2StoreWrapperException( $error );
			}
			return;
		}

		$equivUris = $results['result']['rows'];
		foreach ( $equivUris as $equivUriId => $equivUri ) {
			$equivUris[$equivUriId] = $equivUri['equivUri'];
		}

		return $equivUris;
	}

	/**
	 * Given an Equivalent URI (as defined in a wiki article, return the URI used by SMW
	 * @param string $equivUri
	 * @return string $uri
	 */
	public function getURIForEquivURI( $equivUri, $isProperty = false ) {
		$uri = '';
		if ( $isProperty ) {
			$equivUriUri = self::EQUIV_PROPERTY_URI;
		} else {
			$equivUriUri = self::EQUIV_URI;
		}
		$query = 'SELECT ?uri WHERE { ?uri <' . $equivUriUri . '> <' . $equivUri . '> }';
		$results = $this->tripleStore->query( $query );
		if ( !$this->tripleStore->getErrors() ) {
			$rows = $results['result']['rows'];
			if ( count( $rows ) > 0 ) {
				$row = $rows[0];
				$uri = $row['uri'];
			}
		} else {
			foreach ( $this->tripleStore->getErrors() as $error ) {
				throw new RDFIOARC2StoreWrapperException( $error );
			}
		}
		return $uri;
	}

	/**
	 * For a URI that is defined using the "Original URI" property, return the wiki
	 * article corresponding to that entity
	 * @param string $uri
	 * @return string $wikititle;
	 */
	public function getWikiTitleByEquivalentURI( $uri, $isProperty = false ) {
		$uriEncoded = str_replace( ' ', '%20', $uri );
		$titleResolverUri = $this->getURIForEquivURI( $uriEncoded, $isProperty );
		$titleResolverUriDec = SMWExporter::getInstance()->decodeURI( $titleResolverUri );

		$uriParts = explode( '/', rtrim( $titleResolverUriDec, '/' ) );
		$wikiTitle = str_replace( '_', ' ', array_pop( $uriParts ) );
		if ( $isProperty ) {
			$wikiTitle = str_replace( 'Property:', '', $wikiTitle );
		}
		return $wikiTitle;
	}

	/////// Utility methods ///////

	/**
	 * Get the base URI used by SMW to identify wiki articles
	 * @return string $localWikiNamespace
	 */
	public function getLocalWikiNamespace() { // TODO: Search and replace getURIResolverURI
		global $smwgNamespace;
		if ( substr( $smwgNamespace, 0, 4 ) === 'http' ) {
			$localWikiNamespace = $smwgNamespace;
		} else {
			if ( $this->uriResolverUrl === '' ) {
				$this->uriResolverUrl = SpecialPage::getTitleFor( 'URIResolver' ) . '/';
			}
			$localWikiNamespace = $this->uriResolverUrl;
		}
		return $localWikiNamespace;
	}

	/**
	 * Get SMWs internal URI for corresponding to the "Equivalent URI" property
	 * @return string
	 */
	public function getEquivURIURI() {
		// return $this->getURIResolverURI() . 'Property-3AEquivalent_URI';
		return self::EQUIV_URI;
	}

	/**
	 * Get SMWs internal URI for corresponding to the "Equivalent URI" property,
	 * for property pages
	 * @return string
	 */
	public function getEquivPropertyURIURI() {
		return self::EQUIV_PROPERTY_URI;
	}

}


class RDFIOARC2StoreWrapperException extends MWException {
}
