<?php 

// TODO: Deprecate ...

class RDFIOARC2ToRDFIOParser extends RDFIOParser {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		$arc2triples = $this->getInput();
		
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
		
		foreach ( $arc2triples as $arc2triple ) {
			$subjectString = $arc2triple['s'];
			$predicateString = $arc2triple['p'];
			$objectString = $arc2triple['o'];
			$subjectTypeString = $arc2triple['s_type'];
			$objectTypeString = $arc2triple['o_type'];
			$objectDataTypeString = $arc2triple['o_datatype'];
			$objectLangString = $arc2triple['o_lang'];

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
			
			# TODO: Ahh ... right ... I maybe shouldnät have a specialized URI/Literal
			# object, since I need methods to "getAsWikiPage" for both ... and then it
			# is easier to have just "Resource" class, with the type (uri/literal) stored
			# as a variable ... or else I'll need to type resolution and stuff ... 
			
		} 

	}
	
}