<?php 

/**
 * 
 * General ARC2Parser class, not be instanced directly, but overloaded.
 * The idea is that child classes overload the constructor, and loads
 * a different parser object there, based upon which input format they
 * support.
 * @author samuel
 *
 */

class RDFIOARC2Parser {
	
	protected $mArc2Parser = null;
	protected $mArc2ResourceIndex = null;
	protected $mArc2NamespacePrefixes = null;
	
	public function __construct() {
		// ..
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param string $rawData
	 */
	public function execute( $rawData ) {
		$this->mArc2Parser->parseData( $rawData );

		// TODO: Figure out if this is needed, since we add it to the 
		//       converter singleton below, anyway
		$arc2NamespacePrefixes = $this->setArc2NamespacePrefixes = $this->mArc2Parser->nsp;

		// Set this in the single ton URIToWikiTltle converter, so that it can later be used
		// by URI resources for convertnig themselves to Wiki Titles 
		$uriToTitleConverter = RDFIOURIToWikiTitleConverter::singleton();
		$uriToTitleConverter->setNamespacePrefixesFromParser( $arc2NamespacePrefixes );

		$this->setArc2NamespacePrefixes( $arc2NamespacePrefixes );
		
		$arc2ResourceIndex = ARC2::getSimpleIndex( $this->mArc2Parser->getTriples(), $flatten_objects = false );
		$this->setArc2ResourceIndex( $arc2ResourceIndex );
	}
	
	public function getArc2ResourceIndex() { 
	    return $this->mArc2ResourceIndex;
	}
	public function setArc2ResourceIndex( $arc2ResourceIndex ) { 
	    $this->mArc2ResourceIndex = $arc2ResourceIndex;
	}
	public function getArc2NamespacePrefixes() { 
	    return $this->mArc2NamespacePrefixes;
	}
	public function setArc2NamespacePrefixes( $arc2NamespacePrefixes ) { 
	    $this->mArc2NamespacePrefixes = $arc2NamespacePrefixes;
	}
}