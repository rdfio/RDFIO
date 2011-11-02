<?php 

// TODO: Deprecate ...

class RDFIOARC2ToRDFIOParser extends RDFIOParser {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		$arc2ResourceIndex = $this->getInput();
		$rdfioTriples = array();

		# TODO: Remove debug code ...
		echo "<pre>";
		print_r($arc2ResourceIndex);
		echo "</pre>";
		
		foreach ( $arc2ResourceIndex as $subjectString => $subjectData ) {
			$subject = RDFIOURI::newFromString( $subjectString );
			$subjectData = RDFIOSubjectData::newFromSubject( $subject );
			
			# Predicate
			$predicate = RDFIOURI::newFromString( $predicateString );
			
			# Object
			switch ( $objectTypeString ) {
				case 'uri':
					$object = RDFIOURI::newFromString( $objectString );
					break;
				case 'literal':
					$object = RDFIOLiteral::newFromString( $objectString );
			}
			
			$rdfioTriple = RDFIOTriple::newFromSPOTriplet( $subject, $predicate, $object );
			$rdfioTriples[] = $rdfioTriple;
		} 		
		
	}

}
