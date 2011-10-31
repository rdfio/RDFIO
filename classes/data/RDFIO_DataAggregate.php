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
