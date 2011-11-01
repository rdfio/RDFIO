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
	protected $mData = null;
	protected $mDataType = null;

	protected $mARC2TripleSetToSMWParser = null;
	protected $mSMWToARC2TripleSetParser = null;
	protected $mARC2ResourceIndexToSMWParser = null;
	protected $mSMWToARC2ResourceIndexParser = null;
		
	public function __construct() {
		// TODO: Add code
	}
	
	public function setFromRawData( $rawData, $dataFormat ) {
		switch ( $dataFormat ) {
			case ( 'rdfxml' ): 
				$this->setFromRDFXML( $rawData );
				break;
			case ( 'turtle' ):
				$this->setFromTurtle( $rawData );
				break;
		}
	}
	
	# Common RDF Formats
	
	public function setFromRDFXML( $data ) {
		$rawDataParser = new RDFIORDFXMLToARC2Parser();
		$rawDataParser->setInput( $data );
		$rawDataParser->execute();
		
		$arc2Triples = $rawDataParser->getResults();
		
		
		# Convert ARC2 data structure to SMW (1.6) data structure
		$arc2ToSMWParser = new RDFIOARC2ToSMWParser();
		$arc2ToSMWParser->setInput( $arc2Triples );
		$arc2ToSMWParser->execute();
	}
	
	public function getAsRDFXML( $data ) {
		// TODO: Add code
	}
	public function getAsTurle( $data ) {
		// TODO: Add code		
	}
	public function setFromTurle( $data ) {
		// TODO: Add code		
	}
	public function setFromNTriples( $data ) {
		// TODO: Add code		
	}
	public function getAsNTriples( $data ) {
		// TODO: Add code		
	}
	
	# ARC2 data structures
	
	public function setFromARC2TripleSet( $arc2triplesData ) {
		// TODO: Add code
	}
	public function getAsARC2TripleSet( $arc2triplesData ) {
		// TODO: Add code		
	}
	public function setFromARC2ResourceIndex( $arc2triplesData ) {
		// TODO: Add code		
	}
	public function getAsARC2ResourceIndex( $arc2triplesData ) {
		// TODO: Add code		
	}
	
	# Getters and setters
	
	public function setData( &$data ) {
		$this->mData = $data;	
	}
	public function getData() {
		return $this->mData;
	}
	public function setDataType( $dataType ) {
		$this->mDataType = $dataType;	
	}
	public function getDataType() {
		return $this->mDataType;
	}	
}
