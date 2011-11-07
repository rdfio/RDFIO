<?php 

/**
 * 
 * Data container, possibly analogous to the "resource index" (or the "triple set") 
 * data structure of ARC2. It relates to the SMWSemanticData class of SMW such that
 * an RDFIODataAggregate class could consist many SMWSemanticData objects, since those
 * can have only one subject, while the RDFIODataAggregate (as well as ARC2 data 
 * structures) can always have more than one subject.
 * 
 * @author samuel lampa
 *
 */

class RDFIODataAggregate {
	protected $mSubjectDatas = array();
	protected $mNamespacePrefixesFromParser = null;
	
	protected $mARC2TripleSetToSMWParser = null;
	protected $mSMWToARC2TripleSetParser = null;
	protected $mARC2ResourceIndexToSMWParser = null;
	protected $mSMWToARC2ResourceIndexParser = null;
		
	public function __construct() {
		// TODO: Add code
	}
	
	# Data access methods
	
	/**
	 * @param string $uriStr
	 */
	public function getSubjectDataFromURI( $uriStr ) {
		foreach ( $this->getSubjectDatas() as $subjectData ) {
			if ( $uriStr == $subjectData->getSubject()->getIdentifier() )
				return $subjectData;
		}
		return null;
	}
	
	public function getAllURIs() {
		$allURIs = array();
		foreach( $this->getSubjectDatas() as $subjectData ) {
			$allURIs[] = $subjectData->getSubject();
			foreach( $subjectData->getFacts() as $fact ) {
				$allURIs[] = $fact->getPredicate();
				$object =  $fact->getObject();
				if ( has_class($object) == 'RDFIOURI' )
					$allURIs[] = $object;
			}
		}
		return $allURIs;
	}
	
	# Factory methods, from RDF text formats

	public static function newFromRawData( $rawData, $dataFormat ) {
		switch ( $dataFormat ) {
			case ( 'rdfxml' ): 
				return RDFIODataAggregate::newFromRDFXML( $rawData );
			case ( 'turtle' ):
				return RDFIODataAggregate::newFromTurtle( $rawData );
		}
	}
	
	public static function newFromRDFXML( $rdfXmlData ) {
		$rawDataParser = new RDFIORDFXMLToARC2Parser();
		$rawDataParser->execute( $rdfXmlData );
		
		$arc2ResourceIndex = $rawDataParser->getArc2ResourceIndex();
		$arc2NameSpacePrefixes = $rawDataParser->getArc2NamespacePrefixes();
		
		# Convert ARC2 data structure to RDFIO:s internal data structure
		$arc2ToRDFIOParser = new RDFIOARC2ToRDFIOParser();
		$newDataAggregate = $arc2ToRDFIOParser->execute($arc2ResourceIndex, $arc2NameSpacePrefixes);
		
		return $newDataAggregate;
	}
	
	public function getAsRDFXML( $data ) {
		// TODO: Add code
	}
	public function getAsTurle( $data ) {
		// TODO: Add code		
	}
	public static function newFromTurle( $data ) {
		// TODO: Add code		
	}
	public static function newFromNTriples( $data ) {
		// TODO: Add code		
	}
	public function getAsNTriples( $data ) {
		// TODO: Add code		
	}
	
	# Factory methods, from ARC2 data structures
	
	public static function newFromARC2TripleSet( $arc2triplesData ) {
		// TODO: Add code
	}
	public function getAsARC2TripleSet( $arc2triplesData ) {
		// TODO: Add code		
	}
	public static function newFromARC2ResourceIndex( $arc2triplesData ) {
		// TODO: Add code		
	}
	public function getAsARC2ResourceIndex( $arc2triplesData ) {
		// TODO: Add code		
	}
	
	# Getters and setters
	
	public function setSubjectDatas( &$data ) {
		$this->mSubjectDatas = $data;	
	}
	public function getSubjectDatas() {
		return $this->mSubjectDatas;
	}
	public function addSubjectData( $mSubjectData ) {
		$this->mSubjectDatas[] = $mSubjectData;
	}
	public function getNamespacePrefixesFromParser() { 
	    return $this->mNamespacePrefixesFromParser;
	}
	public function setNamespacePrefixesFromParser( $namespacePrefixesFromParser ) { 
	    $this->mNamespacePrefixesFromParser = $namespacePrefixesFromParser;
	}
}
