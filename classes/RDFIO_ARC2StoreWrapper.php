<?php

/**
 * RDFIOARC2StoreWrapper contains utility functionality that requires connecting to the
 * ARC2 triplestore (Not to confuse with the RDFIOARC2Store, which is an implementation
 * of SMWStore "interface". Here we're wrapping some direct queries to the ARC2 triplestore.
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOARC2StoreWrapper {
	protected $arc2store;
	protected $uriResolverUrl;

	const EQUIV_URI = 'http://www.w3.org/2002/07/owl#sameAs';
	const EQUIV_PROPERTY_URI = 'http://www.w3.org/2002/07/owl#equivalentProperty';

	function __construct( $tripleStore = null ) {
		global $smwgARC2StoreConfig;
		$this->uriResolverUrl = '';
		if ( !is_null( $tripleStore ) ) {
			$this->arc2store = $tripleStore;
			return;
		}
		$this->arc2store = ARC2::getStore( $smwgARC2StoreConfig );
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
	function toEquivUrisInTriples( $triples, $propUrisFilter = null ) {
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
			$propertyUri = $triple['p'];
			$propertyUris = array( $propertyUri );

			$propEquivUris = $this->getEquivURIsForURI( $propertyUri, true );
			if ( count( $propEquivUris ) > 0 ) {
				$propertyUris = $propEquivUris;
			}
			if ( count( $propEquivUris ) > 0 && !is_null( $propUrisFilter ) ) {
				// Only include URIs that occur in the filter
				$propEquivUris = array_intersect( $propEquivUris, $propUrisFilter );
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
				foreach ( $propertyUris as $propertyUri ) {
					foreach ( $objEquivUris as $objEquivUri ) {
						$newtriple = array(
							's' => $subjEquivUri,
							'p' => $propertyUri,
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
		$results = $this->arc2store->query( $query );

		if ( $this->arc2store->getErrors() ) {
			foreach ( $this->arc2store->getErrors() as $error ) {
				throw new RDFIOARC2StoreWrapperException( $error );
			}
			return;
		}

		$rows = $results['result']['rows'];

		if ( count( $rows ) > 0 ) {
			foreach ( $rows as $equivUriId => $equivUri ) {
				$equivUris[$equivUriId] = $equivUri['equivUri'];
			}
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
		$results = $this->arc2store->query( $query );

		if ( !$this->arc2store->getErrors() ) {
			$rows = $results['result']['rows'];
			if ( count( $rows ) > 0 ) {
				$row = $rows[0];
				$uri = $row['uri'];
			}
		} else {
			foreach ( $this->arc2store->getErrors() as $error ) {
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
		$internalUri = $this->getURIForEquivURI( $uriEncoded, $isProperty );
		$internalUriDecoded = SMWExporter::getInstance()->decodeURI( $internalUri );

		// Remove URI parts, so that we get a clean title
		$uriParts = explode( '/', rtrim( $internalUriDecoded, '/' ) );
		$wikiTitle = str_replace( '_', ' ', array_pop( $uriParts ) );
		if ( $isProperty ) {
			$wikiTitle = str_replace( 'Property:', '', $wikiTitle );
		}

		return $wikiTitle;
	}

	/////// Utility methods ///////

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
