<?php 

class RDFIOFact {
	
	protected $mPredicate = null;
	protected $mObject = null;
	
	public function __construct() {
		// ...
	}
	
	# Getters and setters
	
	public function getPredicate() { 
	    return $this->mPredicate;
	}
	public function setPredicate( $predicate ) { 
	    $this->mPredicate = $predicate;
	}
	public function getObject() { 
	    return $this->mObject;
	}
	public function setObject( $object ) { 
	    $this->mObject = $object;
	}
}
