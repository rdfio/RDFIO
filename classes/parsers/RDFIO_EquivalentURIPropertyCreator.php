<?php 

class RDFIOEquivalentURIPropertyCreator {
	
	public function __construct() {
		// ...
	}
	
	public function execute( RDFIODataAggregate $dataAggregate ) {
		$equivURIFactsDataAggregate = new RDFIODataAggregate();
		$origDataURIs = $dataAggregate->getAllURIs();
		foreach( $origDataURIs as $origDataURI ) {
			# Subject
			$uriResolverURI = RDFIOARC2StoreWrapper::getURIResolverURI();	
			$subjectURIStr = $uriResolverURI . $origDataURI->getAsWikiPageName();
			$subjectURIStr = str_replace( ' ', '_', $subjectURIStr );
			$subjectURI = RDFIOURI::newFromString($subjectURIStr, $equivURIFactsDataAggregate);
			
			# Predicate
			// TODO: Should one use the Equivalent URI resolver URI, or rather the owl#sameAs at once?
			$equivURIPropertyURI = $uriResolverURI . 'Equivalent_URI';
			$equivURIpropURI = RDFIOURI::newFromString($equivURIPropertyURI, $equivURIFactsDataAggregate);
			
			# Object
			$equivURIURIString = $origDataURI->getIdentifier();
			$equivURIURI = RDFIOLiteral::newFromString( $equivURIURIString, $equivURIFactsDataAggregate );
			
			# Connecting things ...
			$equivURIFact = RDFIOFact::newFromPredicateAndObject( $equivURIpropURI, $equivURIURI );
			$subjectData = RDFIOSubjectData::newFromSubject( $subjectURI );
			$subjectData->addFact( $equivURIFact );
			$equivURIFactsDataAggregate->addSubjectData( $subjectData );
		}
		return $equivURIFactsDataAggregate;
	}
	
}