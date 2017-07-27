<?php

/**
 * RDFIO specific class for holding data (title, facts (property/object tuples),
 * categories, equivalentURIs etc. about a wiki article, to make it easy to
 * collect data and later write these data to MediaWiki.
 */
class RDFIOWikiPage {
	protected $title;
	protected $equivalentUris;
	protected $factIndex; // Array of type [ 'property' => 'object' ]
	protected $categories;

	function __construct( $title ) {
		$this->setTitle( $title );
		$this->equivalentUris = array();
		$this->factIndex = array();
		$this->categories = array();
	}

	public function addEquivalentURI( $equivURI ) {
		// Add Equivalent URI, if not exists
		if ( !$this->equivalentURIExists( $equivURI ) ) {
			$this->equivalentUris[] = $equivURI;
			$this->addFact( array( 'p' => 'Equivalent URI', 'o' => $equivURI ) );
		}
	}

	public function addFact( $fact ) {
		if ( !is_null( $fact ) ) {
			$this->factIndex[$fact['p']] = $fact['o'];
		}
	}

	public function addCategory( $category ) {
		if ( !is_null( $category ) && !$this->categoryExists( $category ) ) {
			$this->categories[] = $category;
		}
	}

	public function addDataType( $dataType ) {
		$allowedTypes = array(
			'Annotation URI',
			'Boolean',
			'Code',
			'Date',
			'Email',
			'External identifier',
			'Geographic coordinate',
			'Monolingual text',
			'Number',
			'Page',
			'Quantity',
			'Record',
			'Telephone number',
			'Temperature',
			'Text',
			'Reference',
			'URL',
		);
		if ( !in_array( $dataType, $allowedTypes ) ) {
			throw new RDFIOException( 'Datatype not in allowed datatypes: ' . $dataType );
		}
		$this->addFact( array( 'p' => 'Has type', 'o' => $dataType ) );
	}

	public function isProperty() {
		$ns = Title::newFromDBkey( $this->getTitle() )->getNamespace();

		if ( $ns === SMW_NS_PROPERTY ) {
			return true;
		}
		return false;
	}

	public function getEquivalentUris() {
		return $this->equivalentUris;
	}

	public function getFacts() {
		$facts = array();
		foreach( $this->factIndex as $prop => $obj ) {
			$facts[] = array( 'p' => $prop, 'o' => $obj );
		}
		return $facts;
	}

	public function getCategories() {
		return $this->categories;
	}

	/**
	 * @return string title
	 */
	public function getTitle() {
		return $this->title;
	}

	public function setTitle( $wikiTitle ) {
		$this->title = $wikiTitle;
	}

	private function equivalentURIExists( $equivURI ) {
		return in_array( $equivURI, $this->equivalentUris );
	}

	private function categoryExists( $category ) {
		return in_array( $category, $this->categories );
	}
}

