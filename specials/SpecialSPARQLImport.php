<?php

class SPARQLImport extends RDFIOSpecialPage {

	function __construct() {
		parent::__construct( 'SPARQLImport' );
		$this->triplesPerBatch = 25; // Limits how many triples are loaded per time
	}

	/**
	 * The main code goes here
	 * @param string $par (unused)
	 */
	function execute( $par ) {
		unset( $par ); // Needed to suppress warning about unused variable which we include just for consistency.

		$wOut = $this->getOutput();
		$wRequest = $this->getRequest();
		$wUser = $this->getUser();

		try {
			$this->setHeaders();
			$submitButtonText = "Import";

			// For now, print the result XML from the SPARQL query
			if ( $wRequest->getText( 'action' ) === 'import' ) {

				if ( $this->allowInsert( $wUser, $wRequest ) ) {
					$offset = $wRequest->getVal( 'offset', 0 );
					$limit = $this->triplesPerBatch;
					$submitButtonText = "Import next $limit triples...";
					$wOut->addHTML( $this->getHTMLForm( $submitButtonText ) );
					$importInfo = $this->import( $limit, $offset );
					$externalSparqlUrl = $importInfo['externalSparqlUrl'];
					$dataSourceImporter = new RDFIORDFImporter();
					$dataSourceImporter->addDataSource( $externalSparqlUrl, 'SPARQL' );
				} else {
					$errMsg = "The current logged in user does not have write access";
					$this->errorMsg( $errMsg );
				}

			} else {
				$wOut->addHTML( $this->getHTMLForm( $submitButtonText ) );
				$wOut->addHTML( '<div id=sources style="display:none">' );
				$wOut->addWikiText( '{{#ask: [[Category:RDFIO Data Source]] [[RDFIO Import Type::SPARQL]] |format=list }}' );
				$wOut->addHTML( '</div>' );
			}
		} catch ( RDFIOException $e ) {
			$this->errorMsg( $e->getMessage() );
		}

	}

	function resourceType( $resourceStr ) {
		if ( substr( $resourceStr, 0, 4 ) === 'http' ) {
			return 'uri';
		} else {
			return 'literal';
		}
	}

	protected function import( $limit = 25, $offset = 0 ) {
		$wOut = $this->getOutput();
		$wRequest = $this->getRequest();

		$externalSparqlUrl = $wRequest->getText( 'extsparqlurl' );
		if ( $externalSparqlUrl === '' ) {
			throw new RDFIOException( 'Empty SPARQL Url provided!' );
		} else if ( !RDFIOUtils::isURI( $externalSparqlUrl ) ) {
			throw new RDFIOException( 'Invalid SPARQL Url provided! (Must start with \'http://\' or \'https://\')' );
		}
		$sparqlQuery = urlencode( "SELECT DISTINCT * WHERE { ?s ?p ?o } OFFSET $offset LIMIT $limit" );
		$sparqlQueryUrl = $externalSparqlUrl . '/' . '?query=' . $sparqlQuery;
		$sparqlResultXml = file_get_contents( $sparqlQueryUrl );

		$sparqlResultXmlObj = simplexml_load_string( $sparqlResultXml );

		$importTriples = array();

		if ( is_object( $sparqlResultXmlObj ) ) {
			foreach ( $sparqlResultXmlObj->results->children() as $result ) {
				$triple = array();
				// $wgOut->addHTML( print_r($result, true) );
				foreach ( $result as $binding ) {
					if ( $binding['name'] == 's' ) {
						$s = (string)$binding->uri[0];
						if ( $s == '' ) {
							throw new Exception( 'Could not extract subject from empty string (' . print_r( $binding->uri, true ) . '), in SPARQLImport' );
						}
						$triple['s'] = $s;
						$triple['s_type'] = $this->resourceType( $triple['s'] );
					} else if ( $binding['name'] == 'p' ) {
						$p = (string)$binding->uri[0];
						if ( $p == '' ) {
							throw new Exception( 'Could not extract predicate from empty string (' . print_r( $binding->uri, true ) . '), in SPARQLImport' );
						}
						$triple['p'] = $p;
						$triple['p_type'] = $this->resourceType( $triple['p'] );
					} else if ( $binding['name'] == 'o' ) {
						$o = (string)$binding->uri[0];
						if ( $o == '' ) {
							throw new Exception( 'Could not extract object from empty string (' . print_r( $binding->uri, true ) . '), in SPARQLImport' );
						}
						$triple['o'] = $o;
						$triple['o_type'] = $this->resourceType( $triple['o'] );
						$triple['o_datatype'] = '';
					}
				}
				$importTriples[] = $triple;
			}
			$rdfImporter = new RDFIORDFImporter();
			$rdfImporter->importTriples( $importTriples );
			$wOut->addHTML( $rdfImporter->showImportedTriples( $importTriples ) );
		} else {
			$this->errorMsg( 'There was a problem importing from the endpoint. Are you sure that the given URL is a valid SPARQL endpoint?' );
		}
		return array( 'externalSparqlUrl' => $externalSparqlUrl );
	}

	protected function getHTMLForm( $buttonText ) {
		global $wgArticlePath;
		$wRequest = $this->getRequest();
		$wUser = $this->getUser();

		$thisPageUrl = str_replace( '/$1', '', $wgArticlePath ) . "/Special:SPARQLImport";
		$extSparqlUrl = $wRequest->getText( 'extsparqlurl', '' );
		$limit = $this->triplesPerBatch;
		$offset = $wRequest->getText( 'offset', 0 - $limit ) + $limit;
		$htmlForm = '
		<form method="get" action="' . $thisPageUrl . '" >
				URL of SPARQL endpoint:<br>
				<input type="hidden" name="action" value="import">
				<div id="urlfields">
				<input type="text" name="extsparqlurl" id="extsparqlurl" size="60" value="' . $extSparqlUrl . '"></input>
				<a href="#" onClick="addSources();">Use previous source</a>
				</div>
				<p><span style="font-style: italic; font-size: 11px">Example: http://www.semantic-systems-biology.org/biogateway/endpoint</span></p>
				<input type="hidden" name="offset" value="' . $offset . '">
				<input type="hidden" name="token" value="' . $wUser->getEditToken() . '">
				<input type="submit" value="' . $buttonText . '">
		</form>';
		$htmlForm .= $this->getJs();
		return $htmlForm;
	}

	public function getJs() {
		$jsCode = '
<script type="text/javascript">
function addSources() {
	var sourceList = document.getElementById("sources").getElementsByTagName("p")[0];
	var sources = sourceList.getElementsByTagName("a");
	var urlForm = document.getElementById("urlfields");
	var urlTextField = document.getElementById("extsparqlurl");
	var selectList = document.createElement("select");
	selectList.id = "sourceSelect";
	urlForm.appendChild(selectList);
	for (var i = 0; i < sources.length; i++) {
		var option = document.createElement("option");
		option.value = sources[i].innerHTML;
		option.text = sources[i].innerHTML;
		selectList.appendChild(option);
	}
	selectList.onchange = function() {selectedUrl = selectList.options[selectList.selectedIndex].value; selectedUrl1 = selectedUrl.substring(0,1).toLowerCase(); selectedUrl2 = selectedUrl.substring(1); selectedUrl = selectedUrl1.concat(selectedUrl2); urlTextField.value = selectedUrl};
}
</script>
					';
		return $jsCode;
	}

}
