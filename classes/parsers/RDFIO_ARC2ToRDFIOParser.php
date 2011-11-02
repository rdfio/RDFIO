<?php 

// TODO: Deprecate ...

class RDFIOARC2ToRDFIOParser extends RDFIOParser {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		$arc2ResourceIndex = $this->getInput();
		$subjectDatas = array();

		foreach ( $arc2ResourceIndex as $subjectString => $arc2SubjectData ) {
			$subject = RDFIOURI::newFromString( $subjectString );
			$subjectData = RDFIOSubjectData::newFromSubject( $subject );

			foreach ( $arc2SubjectData as $predicateString => $arc2PredicateData ) {
				$predicate = RDFIOURI::newFromString( $predicateString );
				
				foreach ( $arc2PredicateData as $arc2ObjectData ) {
					
					$objectString = $arc2ObjectData['value'];
					$objectTypeString = $arc2ObjectData['type'];

					switch ( $objectTypeString ) {
						case 'uri':
							$object = RDFIOURI::newFromString( $objectString );
							break;
						case 'literal':
							$object = RDFIOLiteral::newFromString( $objectString );
					}
					
					$fact = RDFIOFact::newFromPredicateAndObject( $predicate, $object );
					$subjectData->addFact( $fact );
				}
				
			}
			
			$subjectDatas[] = $subjectData;
		} 	

		$this->setResults( $subjectDatas );
		
	}

}
