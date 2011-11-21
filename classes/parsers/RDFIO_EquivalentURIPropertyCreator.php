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
			// FIXME: The following is completely wrong, it does not result in the same Wiki Page Title
			//        as the page in the original import data. Why? - Since we use the it's "native" URI as 
			//        in the normal data aggregate ...
			//        Actually, we should not at att work with URI:s here, since we actually know the exact
			//        wiki titles we want to create! 
			$subjectURIStr = $uriResolverURI . $origDataURI->getAsWikiPageName();
			$subjectURIStr = str_replace( ' ', '_', $subjectURIStr ); // TODO: Replace with xmlify method!
			$subjectURI = RDFIOURI::newFromString($subjectURIStr, $equivURIFactsDataAggregate);
			
			# Predicate
			// TODO: Should one use the Equivalent URI resolver URI, or rather the owl#sameAs at once?
			// TODO: Add "Property:" namespace here as well, somewhere?
			// TODO: Add "type:URL" to the property, somehow?
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