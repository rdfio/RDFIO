<?php 

class RDFIOData {
	protected $mData = null;
	protected $mDataType = null;
	
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
