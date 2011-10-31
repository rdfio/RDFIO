<?php 

/**
 * 
 * Data container for raw data in text format, such as RDF/XML, Turtle JSON etc.
 * The main motivation behind the class is to contain both the data itself and
 * metadata such as the exact format, in one object, to make data passing more
 * clean and manageable. 
 * 
 * @author samuel lampa
 *
 */

class RDFIORawData {
	
	protected $mData = null;
	protected $mDataFormat = null;
	
	public function __construct() {
		// TODO: Add code
	}
	
	# Getters and setters
	
	public function getData() { 
	    return $this->mData;
	}
	public function setData( $data ) { 
	    $this->mData = $data;
	}
	public function getDataFormat() { 
	    return $this->mDataFormat;
	}
	public function setDataFormat( $dataFormat ) { 
	    $this->mDataFormat = $dataFormat;
	}
	
}
	