<?php

class RDFIOWikiPage {
	protected $title;
	protected $equivalentUris;
	protected $facts;
	protected $categories;

	/*
	 * Public ----------------------------------------------------------------
	 */
	
	function __construct( $title ) {
		$this->title = $title;
		$this->equivalentUris = array();
		$this->facts = array();
		$this->categories = array();
	}

	public function addEquivalentURI( $equivURI ) {
		# Add Equivalent URI, if not exists
		if ( !$this->equivalentURIExists( $equivURI ) ) {
			$this->equivalentUris[] = $equivURI;
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
	
	/*
	 * Private ----------------------------------------------------------------
	 */
		
	private function equivalentURIExists( $equivURI ) {
		return in_array( $equivURI, $this->equivalentUris );
	}

	private function categoryExists( $category ) {
		return in_array( $category, $this->categories );
	}
}

?>