<?php

/**
 * RDFIO specific class for holding data (title, facts (property/object tuples),
 * categories, equivalentURIs etc. about a wiki article, to make it easy to
 * collect data and later write these data to MediaWiki.
 */
class RDFIOWikiPage {
	protected $title;
	protected $equivalentUris;
	protected $facts;
	protected $categories;

	function __construct( $title ) {
		$this->setTitle( $title );
		$this->equivalentUris = array();
		$this->facts = array();
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
			$this->facts[] = $fact; // TODO: Detect duplicates?
		}
	}

	public function addCategory( $category ) {
		if ( !is_null( $category ) && !$this->categoryExists( $category ) ) {
			$this->categories[] = $category;
		}
	}

	public function getEquivalentUris() {
		return $this->equivalentUris;
	}

	public function getFacts() {
		return $this->facts;
	}

	public function getCategories() {
		return $this->categories;
	}

	public function setTitle( $wikiTitle ) {
		// Sanitize the title a bit
		$wikiTitle = RDFIOUtils::sanitizeWikiTitleString( $wikiTitle );
		$this->title = $wikiTitle;
	}

	private function equivalentURIExists( $equivURI ) {
		return in_array( $equivURI, $this->equivalentUris );
	}

	private function categoryExists( $category ) {
		return in_array( $category, $this->categories );
	}
}

