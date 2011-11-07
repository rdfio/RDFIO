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
			$subjectURIStr = str_replace( ' ', '_', $subjectURIStr ); // TODO: Replace with xmlify method!
			$subjectURI = RDFIOURI::newFromString($subjectURIStr, $equivURIFactsDataAggregate);
			
			# Predicate
			// TODO: Should one use the Equivalent URI resolver URI, or rather the owl#sameAs at once?
			$equivURIPropertyURIStr = $uriResolverURI . 'Equivalent_URI';
			$equivURIpropURI = RDFIOURI::newFromString($equivURIPropertyURIStr, $equivURIFactsDataAggregate);
			
			# Object
			$equivURIURIStr = $origDataURI->getIdentifier();
			$equivURIURI = RDFIOLiteral::newFromString( $equivURIURIStr, $equivURIFactsDataAggregate );
			
			# Connecting things ...
			$equivURIFact = RDFIOFact::newFromPredicateAndObject( $equivURIpropURI, $equivURIURI );
			$subjectData = RDFIOSubjectData::newFromSubject( $subjectURI );
			$subjectData->addFact( $equivURIFact );
			$equivURIFactsDataAggregate->addSubjectData( $subjectData );
		}
		return $equivURIFactsDataAggregate;
	}
	
}