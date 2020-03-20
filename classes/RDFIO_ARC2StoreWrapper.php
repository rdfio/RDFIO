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
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBprefix;
		$this->uriResolverUrl = '';
		if ( !is_null( $tripleStore ) ) {
			$this->arc2store = $tripleStore;
			return;
		}
		$arc2StoreConfig = array(
			'db_host' => $wgDBserver,
			'db_name' => $wgDBname,
			'db_user' => $wgDBuser,
			'db_pwd' => $wgDBpassword,
			'store_name' => preg_replace( '/s?unittest_/', '', $wgDBprefix ) . 'arc2store', // Determines table prefix
		);
		$this->arc2store = ARC2::getStore( $arc2StoreConfig );
		$this->arc2store->createDBCon();
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

		$equivUriCache = array();

		foreach ( $triples as $tripleidx => $triple ) {
			// Subject
			$subjUri = $triple['s'];
			if ( array_key_exists( $subjUri, $equivUriCache ) ) {
				$triples[$tripleidx]['s'] = $equivUriCache[$subjUri];
			} else {
				$subjEquivUris = $this->getEquivURIsForURI( $subjUri );
				if ( count( $subjEquivUris ) > 0 ) {
					$triples[$tripleidx]['s'] = $subjEquivUris[0];
					$equivUriCache[$subjUri] = $subjEquivUris[0];
				}
			}

			// Property
			$propUri = $triple['p'];
			if ( array_key_exists( $propUri, $equivUriCache ) ) {
				$triples[$tripleidx]['p'] = $equivUriCache[$propUri];
			} else {
				$propEquivUris = $this->getEquivURIsForURI( $triple['p'] );
				if ( !is_null( $propUrisFilter ) ) {
					// Only include URIs that occur in the filter
					$propEquivUris = array_intersect( $propEquivUris, $propUrisFilter );
				}
				if ( count( $propEquivUris ) > 0 ) {
					$triples[$tripleidx]['p'] = $propEquivUris[0];
					$equivUriCache[$propUri] = $propEquivUris[0];
				}
			}

			// Object
			if ( $triple['o_type'] === 'uri' ) {
				$objUri = $triple['o'];
				if ( array_key_exists( $objUri, $equivUriCache ) ) {
					$triples[$tripleidx]['o'] = $equivUriCache[$objUri];
				} else {
					$objEquivUris = $this->getEquivURIsForURI( $objUri );
					if ( count( $objEquivUris ) > 0 ) {
						$triples[$tripleidx]['o'] = $objEquivUris[0];
						$equivUriCache[$objUri] = $objEquivUris[0];
					}
				}
			}
		}

		return $triples;
	}

	/**
	 * For a given RDF URI, return it's corresponding equivalend URIs
	 * as defined in wiki articles by the Equivalent URI property
	 * @param string $uri
	 * @param boolean $isProperty
	 * @return array $equivUris
	 */
	public function getEquivURIsForURI( $uri ) {
		$equivUris = array();

		$query = 'SELECT ?equivUri WHERE { { <' . $uri . '> <' . self::EQUIV_URI . '> ?equivUri } UNION { <' . $uri . '> <' . self::EQUIV_PROPERTY_URI . '> ?equivUri } }';
		$results = $this->arc2store->query( $query );

		if ( $this->arc2store->getErrors() ) {
			foreach ( $this->arc2store->getErrors() as $error ) {
				throw new RDFIOARC2StoreWrapperException( $error );
			}
			return;
		}

		$rows = $results['result']['rows'];

		if ( $rows !== null && count( $rows ) > 0 ) {
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

		if ( $wikiTitle != '' && $isProperty  ) {
			$propertyNS = Title::newFromDBkey( $wikiTitle )->getNsText();
			$wikiTitle = str_replace( $propertyNS . ':', '', $wikiTitle );
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
