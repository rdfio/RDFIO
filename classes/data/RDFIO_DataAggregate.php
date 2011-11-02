<?php 

/**
 * 
 * Data container, possibly analogous to the "resource index" (or the "triple set") 
 * data structure of ARC2. It relates to the SMWSemanticData class of SMW such that
 * an RDFIODataAggregate class could consist many SMWSemanticData objects, since those
 * can have only one subject, while the RDFIODataAggregate (as well as ARC2 data 
 * structures) can always have more than one subject.
 * 
 * @author samuel lampa
 *
 */

class RDFIODataAggregate {
	protected $mData = null;

	protected $mARC2TripleSetToSMWParser = null;
	protected $mSMWToARC2TripleSetParser = null;
	protected $mARC2ResourceIndexToSMWParser = null;
	protected $mSMWToARC2ResourceIndexParser = null;
		
	public function __construct() {
		// TODO: Add code
	}
	
	public function setFromRawData( $rawData, $dataFormat ) {
		switch ( $dataFormat ) {
			case ( 'rdfxml' ): 
				$this->setFromRDFXML( $rawData );
				break;
			case ( 'turtle' ):
				$this->setFromTurtle( $rawData );
				break;
		}
	}
	
	# Common RDF Formats
	
	public function setFromRDFXML( $data ) {
		$rawDataParser = new RDFIORDFXMLToARC2Parser();
		$arc2ResourceIndex = $rawDataParser->executeForData( $data );
		
		# Convert ARC2 data structure to RDFIO:s internal data structure
		$arc2ToRDFIOParser = new RDFIOARC2ToRDFIOParser();
		$rdfioData = $arc2ToRDFIOParser->executeForData( $arc2ResourceIndex );

		$this->setData( $rdfioData );
		
		# TODO: Continue here on tuesday ...
		# Shouln't the arc2 data be parsed to RDFIO internal data
		# structure, and not SWM? Or should it go via SMW data structure
		# first ... so that we have a general SMW->RDFIO parser as well?
		# Answer: Of course directly to RDFIO structure, since SMW ditto
		# does not handle URI:s. More valid question is whether to parse
		# via SMW structure before sending to a writer class, but I guess
		# that would be preferred, for maximum interoperability (not for
		# performance though ... which is why I'm wondering ... maybe start
		# with direct write, from RDFIO internals, and instead make an
		# SMW->RDFIO parser, for doing writes of SMW objects?!! 
		# Well, sounds quite reasonable ... probly go for that ...)
		
	}
	
	public function getAsRDFXML( $data ) {
		// TODO: Add code
	}
	public function getAsTurle( $data ) {
		// TODO: Add code		
	}
	public function setFromTurle( $data ) {
		// TODO: Add code		
	}
	public function setFromNTriples( $data ) {
		// TODO: Add code		
	}
	public function getAsNTriples( $data ) {
		// TODO: Add code		
	}
	
	# ARC2 data structures
	
	public function setFromARC2TripleSet( $arc2triplesData ) {
		// TODO: Add code
	}
	public function getAsARC2TripleSet( $arc2triplesData ) {
		// TODO: Add code		
	}
	public function setFromARC2ResourceIndex( $arc2triplesData ) {
		// TODO: Add code		
	}
	public function getAsARC2ResourceIndex( $arc2triplesData ) {
		// TODO: Add code		
	}
	
	# Getters and setters
	
	public function setData( &$data ) {
		$this->mData = $data;	
	}
	public function getData() {
		return $this->mData;
	}
}
