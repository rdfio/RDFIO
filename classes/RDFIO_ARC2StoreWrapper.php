<?php

/**
 * RDFIOARC2StoreWrapper contains utility functionality that requires connecting to the
 * ARC2 triplestore (Not to confuse with the RDFIOARC2Store, which is an implementation
 * of SMWStore "interface". Here we're wrapping some direct queries to the ARC2 triplestore. 
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOARC2StoreWrapper {
    protected $arcStore;
    protected $uriResolverUrl;

    function __construct() {
        global $smwgARC2StoreConfig;
        $this->arcStore = ARC2::getStore( $smwgARC2StoreConfig );
        $this->uriResolverUrl = '';
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
        $store = $this->arcStore;
        if ( $isProperty ) {
            $equivUriUri = $this->getEquivPropertyURIURI();
        } else {
            $equivUriUri = $this->getEquivURIURI();
        }
        $q = "SELECT ?equivUri WHERE { <$uri> <$equivUriUri> ?equivUri }";
        $rs = $store->query( $q );
        if ( !$store->getErrors() ) {
            $equivUris = $rs['result']['rows'];
            foreach ( $equivUris as $equivUriId => $equivUri ) {
                $equivUris[$equivUriId] = $equivUri['equivUri'];
            }
        } else {
            foreach ( $store->getErrors() as $error ) {
                throw new RDFIOARC2StoreWrapperException( $error );
            }
        }
        return $equivUris;
    }

    /**
     * Given an Equivalent URI (ast defined in a wiki article, return the URI used by SMW
     * @param string $equivUri
     * @return string $uri
     */
    public function getURIForEquivURI( $equivUri, $isProperty ) {
        $uri = '';
        $store = $this->arcStore;
        if ( $isProperty ) {
            $equivUriUri = $this->getEquivPropertyURIURI();
        } else {
            $equivUriUri = $this->getEquivURIURI();
        }
        $q = "SELECT ?uri WHERE { ?uri <$equivUriUri> <$equivUri> }";
        $rs = $store->query( $q );
        if ( !$store->getErrors() ) {
            $rows = $rs['result']['rows'];
            if ( count($rows) > 0 ) {
                $row = $rows[0];
                $uri = $row['uri'];
            }
        } else {
            foreach ( $store->getErrors() as $error ) {
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
        $wikiTitleResolverUri = $this->getURIForEquivURI( $uri, $isProperty );
        $resolverUri = $this->getLocalWikiNamespace();
        $wikiTitle = str_replace( $resolverUri, '', $wikiTitleResolverUri );
        $wikiTitle = SMWExporter::decodeURI( $wikiTitle );
        return $wikiTitle;
    }
    
    /////// Utility methods ///////
    
    /**
     * Get the base URI used by SMW to identify wiki articles
     * @return string $localWikiNamespace
     */
    public function getLocalWikiNamespace() { // TODO: Search and replace getURIResolverURI
        global $smwgNamespace;
        if ( substr( $smwgNamespace, 0, 4 ) === "http" ) {
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
    public static function getEquivURIURI() {
        // return $this->getURIResolverURI() . 'Property-3AEquivalent_URI';
        return 'http://www.w3.org/2002/07/owl#sameAs';
    }
    
    /**
     * Get SMWs internal URI for corresponding to the "Equivalent URI" property,
     * for property pages
     * @return string
     */
    public static function getEquivPropertyURIURI() {
    	return 'http://www.w3.org/2002/07/owl#equivalentProperty';
    }
    
}


class RDFIOARC2StoreWrapperException extends MWException { }
