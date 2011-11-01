<?php

/**
 * 
 * Will be used in a more low-level fashion, I think ...
 * @author samuel lampa
 *
 */

class RDFIOWikiWriter {

	public function __construct() {
		// ...
	}

	public function execute() {

		// thrownig this test code here, as a reminder ...
		$title = Title::newFromText('Test2');
		$article = new Article($title);
		$summary = "A Bot edit ...";
		$content = $article->fetchContent();
		$content_new = $content . ' ... some more content';
		$article->doEdit($content_new, $summary);
		
	}
}