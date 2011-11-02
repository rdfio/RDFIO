<?php 

// TODO: Deprecate ...

class RDFIOARC2ToRDFIOParser extends RDFIOParser {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		
	}
	
	/*
	 * Saving this method, for possible future use, in case
	 * one wants triples instead of RDFIOSubejctDatas.
	 */
	public function parseToTriples() {
		$arc2Triples = $this->getInput();
		$rdfioTriples = array();
		
		/**
		 * Data structure of one ARC2 triple:
		 * Array
		 * (
		 *     [s] => http://bio2rdf.org/go:0032283
		 *     [p] => http://bio2rdf.org/go_resource:accession
		 *     [o] => GO:0032283
		 *     [s_type] => uri
		 *     [o_type] => literal
		 *     [o_datatype] => 
		 *     [o_lang] => 
		 * )
		 */
		
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
			
			$rdfioTriple = array(
								's' => $subject, 
								'p' => $predicate, 
								'o' => $object 
								);
								
			$rdfioTriples[] = $rdfioTriple;
			
		} 
		
		$this->setResults( $rdfioTriples );
	}
}
