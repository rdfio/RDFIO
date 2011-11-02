<?php 

/**
 * 
 * Data object, comprising a semantic triple. See also RDFIOData, which 
 * is a generalization (or specialization?) of this class which can contain
 * more than one fact for the given subject. 
 * 
 * @author samuel lampa
 *
 */
class RDFIOTriple {
	
	protected $mSubject = null;
	protected $mPredicate = null;
	protected $mObject = null;
	
	private function __construct() {
		// TODO: Add code
	}
	
	# Creator methods
	
	public static function newFromSPOTriplet( $subject, $object, $predicate ) {
		$newTriple = new RDFIOTriple();
		$newTriple->setSubject( $subject );
		$newTriple->setPredicate( $predicate );
		$newTriple->setObject( $object );
		return $newTriple;
	}
	
	# Getters and setters
	
	public function getSubject() { 
	    return $this->mSubject;
	}
	public function setSubject( $subject ) { 
	    $this->mSubject = $subject;
	}
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
	