<?php 

class RDFIOEquivalentURIPropertyCreator {
	
	public function __construct() {
		// ...
	}
	
	public function execute( RDFIODataAggregate $dataAggregate ) {
		$origDataURIs = $dataAggregate->getAllURIs();
		foreach( $origDataURIs as $origDataURI ) {
			// TODO: Do stuff ...
		}
	}
	
}