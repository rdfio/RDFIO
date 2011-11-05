<?php 

// TODO: Deprecate ...

class RDFIOARC2ToRDFIOParser {
	
	public function __construct() {
		// ...
	}
	
	public function execute( $arc2ResourceIndex, $arc2NameSpacePrefixes ) {

		$newDataAggregate = new RDFIODataAggregate();
		$subjectDatas = array();
		
		foreach ( $arc2ResourceIndex as $subjectString => $arc2SubjectData ) {
			$subject = RDFIOURI::newFromString( $subjectString, $newDataAggregate );
			$subjectData = RDFIOSubjectData::newFromSubject( $subject );

			foreach ( $arc2SubjectData as $predicateString => $arc2PredicateData ) {
				$predicate = RDFIOURI::newFromString( $predicateString, $newDataAggregate );
				
				foreach ( $arc2PredicateData as $arc2ObjectData ) {
					
					$objectString = $arc2ObjectData['value'];
					$objectTypeString = $arc2ObjectData['type'];

					switch ( $objectTypeString ) {
						case 'uri':
							$object = RDFIOURI::newFromString( $objectString, $newDataAggregate );
							break;
						case 'literal':
							$object = RDFIOLiteral::newFromString( $objectString, $newDataAggregate );
					}
					
					$fact = RDFIOFact::newFromPredicateAndObject( $predicate, $object );
					$subjectData->addFact( $fact );
				}
				
			}
			
			$subjectDatas[] = $subjectData;
		} 	

		$newDataAggregate->setSubjectDatas( $subjectDatas );
		$newDataAggregate->setNamespacePrefixesFromParser( $arc2NameSpacePrefixes );
		
		return $newDataAggregate;
	}

}
