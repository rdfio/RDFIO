<?php 

class RDFIORawData {
	protected $mData = null;
	protected $mDataType = null;
	
	# Getters and setters
	
	public function setData( &$input ) {
		$this->mInput = $input;	
	}
	public function getData() {
		return $this->mInput;
	}
	public function setDataType( $results ) {
		$this->mResults = $results;	
	}
	public function getDataType() {
		return $this->mResults;
	}	
}
