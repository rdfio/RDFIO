<?php 

class RDFIOFact {
	
	protected $mPredicate = null;
	protected $mObject = null;
	
	public function __construct() {
		// ...
	}
	
	# Factory methods 
	
	public static function newFromPredicateAndObject( &$predicate, &$object ) {
		$newFact = new RDFIOFact();
		$newFact->setPredicate( $predicate );
		$newFact->setObject( $object );
		return $newFact;
	}
	
	# Getters and setters
	
	public function getPredicate() { 
	    return $this->mPredicate;
	}
	public function setPredicate( &$predicate ) { 
	    $this->mPredicate = $predicate;
	}
	public function getObject() { 
	    return $this->mObject;
	}
	public function setObject( &$object ) { 
	    $this->mObject = $object;
	}
}
