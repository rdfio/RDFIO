<?php 

// TODO: Deprecate ...

class RDFIOARC2ToRDFIOParser extends RDFIOParser {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		$arc2Triples = $this->getInput();
		$rdfioTriples = array();
		foreach ( $arc2Triples as $arc2Triple ) {
			$subjectString = $arc2Triple['s'];
			$predicateString = $arc2Triple['p'];
			$objectString = $arc2Triple['o'];
			$subjectTypeString = $arc2Triple['s_type'];
			$objectTypeString = $arc2Triple['o_type'];
			$objectDataTypeString = $arc2Triple['o_datatype'];
			$objectLangString = $arc2Triple['o_lang'];

			# Subject			
			switch ( $subjectTypeString ) {
				case 'uri':
					$subject = RDFIOURI::newFromString( $subjectString );
					break;
				case 'literal':
					$subject = RDFIOLiteral::newFromString( $subjectString );
			}
			
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
