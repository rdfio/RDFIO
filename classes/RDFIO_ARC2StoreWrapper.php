<?php

/**
 * RDFIOARC2StoreWrapper contains utility functionality that requires connecting to the
 * ARC based RDF store
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOARC2StoreWrapper {
    protected $arcStore;

    function __construct() {
        global $smwgARC2StoreConfig;
        $this->arcStore = ARC2::getStore( $smwgARC2StoreConfig );
    }

    /**
     * For a given RDF URI, return it's corresponding equivalend URIs
     * as defined in wiki articles by the Equivalent URI property
     * @param string $uri
     * @param boolean $is_property
     * @return array $equivuris
     */
    public function getEquivURIsForURI( $uri, $is_property = false ) {
        $equivuris = array();
        $store = $this->arcStore;
        if ( $is_property ) {
            $equivuriuri = $this->getEquivPropertyURIURI();
        } else {
            $equivuriuri = $this->getEquivURIURI();
        }
        $q = "SELECT ?equivuri WHERE { <$uri> <$equivuriuri> ?equivuri }";
        $rs = $store->query( $q );
        if ( !$store->getErrors() ) {
            $equivuris = $rs['result']['rows'];
            foreach ( $equivuris as $equivuriid => $equivuri ) {
                $equivuris[$equivuriid] = $equivuri['equivuri'];
            }
        } else {
            foreach ( $store->getErrors() as $error ) {
        		echo( "<pre>Error in getEquivURIsForURI: " . $error . "</pre>" );
        	}
        }
        return $equivuris;
    }

    /**
     * Given an Equivalent URI (ast defined in a wiki article, return the URI used by SMW
     * @param string $equivuri
     * @return string $uri
     */
    public function getURIForEquivURI( $equivuri, $is_property ) {
        $uri = '';
        $store = $this->arcStore;
        if ( $is_property ) {
        	$equivuriuri = $this->getEquivPropertyURIURI();
        } else {
        	$equivuriuri = $this->getEquivURIURI();
        }
        $q = "SELECT ?uri WHERE { ?uri <$equivuriuri> <$equivuri> }";
        $rs = $store->query( $q );
        if ( !$store->getErrors() ) {
            $rows = $rs['result']['rows'];
            if ( count($rows) > 0 ) {
	            $row = $rows[0];
	            $uri = $row['uri'];
            }
        } else {
            foreach ( $store->getErrors() as $error ) {
        		echo( "<pre>Error in getURIForEquivURI: " . $error . "</pre>" );
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
    public function getWikiTitleByEquivalentURI( $uri, $is_property = false ) {
   		$wikititleresolveruri = $this->getURIForEquivURI( $uri, $is_property );
        $resolveruri = $this->getURIResolverURI();
        $wikititle = str_replace( $resolveruri, '', $wikititleresolveruri );
        $wikititle = $this->decodeURI( $wikititle );
        return $wikititle;
    }
    
    /////// Utility methods ///////
    
    // TODO: Should these methods be static?
    
    /**
     * This function unescapes URIs generated with SMWExporter::encodeURI. This
     * allows services that receive a URI to extract e.g. the according wiki page.
     */
    static public function decodeURI( $uri ) {
    	$uri = str_replace( array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
    		array( ':', '"', '#', '&', "'", '+', '!', '%' ), $uri );
   		$uri = str_replace( '%2D', '-', $uri );
	   	return $uri;
    }    
    
    /**
     * Get the base URI used by SMW to identify wiki articles
     * @return string $uriresolveruri
     */
    function getURIResolverURI() {
    	$resolver = SpecialPage::getTitleFor( 'URIResolver' );
    	$uriresolveruri = $resolver->getFullURL() . '/';
    	return $uriresolveruri;
    }
    
    /**
     * Get SMWs internal URI for corresponding to the "Equivalent URI" property
     * @return string
     */
    function getEquivURIURI() {
    	// return $this->getURIResolverURI() . 'Property-3AEquivalent_URI';
    	return 'http://www.w3.org/2002/07/owl#sameAs';
    }
    
    /**
     * Get SMWs internal URI for corresponding to the "Equivalent URI" property,
     * for property pages
     * @return string
     */
    function getEquivPropertyURIURI() {
    	return 'http://www.w3.org/2002/07/owl#equivalentProperty';
    }
    
}
