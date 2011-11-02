<?php 

/**
 * 
 * Data object, analogous to SMWSemanticData in SMW, which is an aggregation
 * of facts, for one given subject / wiki page. See also RDFIOTriple, which 
 * is a specialization (or generalization?) of this class, which can only 
 * contain one fact.
 * 
 * @author samuel lampa
 *
 */

class RDFIOSubjectData {
	
	protected $mSubject = null;
		
	public function __construct() {
		// TODO: Add code
	}
	
	# Getters and setters
	
	public function getSubject() { 
	    return $this->mSubject;
	}
	public function setSubject( $subject ) { 
	    $this->mSubject = $subject;
	}
}
