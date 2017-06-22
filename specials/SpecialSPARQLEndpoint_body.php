<?php

class SPARQLEndpoint extends SpecialPage {
	protected $sparqlendpoint;
	protected $storewrapper;

	public function __construct() {
		parent::__construct( 'SPARQLEndpoint' );
		$this->sparqlendpoint = new ARC2_StoreEndpoint( $this->getSPARQLEndpointConfig(), $this );
		if ( !$this->sparqlendpoint->isSetUp() ) {
			$this->sparqlendpoint->setUp();
		}
		$this->storewrapper = new RDFIOARC2StoreWrapper();
	}

	/**
	 * Execute the SPARQL Endpoint Special page
	 */
	public function execute( $par ) {
		global $rogQueryByEquivURIs, $rogOutputEquivUris;

		$this->setHeaders();
		$options = $this->buildOptionsObj( $this->getRequest(), $rogQueryByEquivURIs, $rogOutputEquivUris );
		$user = $this->getUser();

		if ( $options->query == '' ) {
			$this->printHTMLForm( $options );
			return;
		}
		if ( $options->queryByEquivUris ) {
			$this->urisToEquivURIsInQuery( $options );
		}

		switch ( $options->queryType ) {
			case 'select':
			case 'construct':
				$this->executeReadOnlyQuery( $options );
				return;
			case 'insert':
				if ( !$this->allowInsert( $user ) ) {
					$this->errorMsg( 'Current user is not allowed to do INSERT statements.' );
					$this->printHTMLForm( $options );
					return;
				}
				$this->importTriplesInQuery( $options );
				$this->printHTMLForm( $options );
				return;
			case 'delete':
				if ( !$this->allowDelete( $user ) ) {
					$this->errorMsg( 'Current user is not allowed to do DELETE statements.' );
					$this->printHTMLForm( $options );
				}
				$this->deleteTriplesInQuery( $options );
				$this->printHTMLForm( $options );
				return;
		}
		$this->errorMsg('Invalid query type (Valid ones are SELECT, CONSTRUCT, INSERT and DELETE), or combination of query type and output format, try another combination!');
		$this->printHTMLForm( $options );
	}

	/**
	 * Execute method for SPARQL queries that only queries and returns results, but
	 * does not modify, add or delete triples.
	 */
	private function executeReadOnlyQuery( $options ) {
		$wikiOut = $this->getOutput();

		$outputSer = $this->passSparqlToARC2AndGetSerializedOutput();

		if ( $outputSer == '' ) {
			$this->errorMsg( 'No results from SPARQL query!' );
			return;
		}

		$outputArr = unserialize( $outputSer );
		if ( $options->outputEquivUris ) {
			$outputArr = $this->toEquivURIsInSparqlResults( $outputArr );
		}

		if ( $options->queryType == 'select' ) {
			if ( $options->outputType == 'htmltab' ) {
				$resultHtml = $this->sparqlResultToHTML( $outputArr );
				$this->printHTMLForm( $options );
				$wikiOut->addHTML( $resultHtml );
				return;
			}

			if ( $options->outputType == 'xml' ) {
				$this->prepareCreatingDownloadableFile( $options );
				// Using echo instead of $wgOut->addHTML() here, since output format is not HTML
				echo $this->sparqlendpoint->getSPARQLXMLSelectResultDoc( $outputArr );
				return;
			}

			$this->errorMsg( 'Invalid Output type for SELECT query' );
			$this->printHTMLForm( $options );
			return;
		}

		if ( $options->queryType == 'construct' ) {
			if ( $options->outputType == 'rdfxml' ) {
				// Here the results should be RDF/XML triples,
				// not just plain XML SPARQL result set
				$tripleindex = $outputArr['result'];

				$arc2 = new ARC2_Class( array(), $this );
				$triples = $arc2->toTriples( $tripleindex );

				if ( $options->outputEquivUris ) {
					$triples = $this->storewrapper->complementTriplesWithEquivURIs( $triples );
				}

				$this->prepareCreatingDownloadableFile( $options );
				// Using echo instead of $wgOut->addHTML() here, since output format is not HTML
				echo $this->triplesToRDFXML( $triples );
				return;

			}
			$this->errorMsg( 'Invalid Output type for CONSTRUCT query' );
			$this->printHTMLForm( $options );
			return;
		}
	}

	private function passSparqlToARC2AndGetSerializedOutput() {
		// Make sure ARC2 returns a PHP serialization, so that we
		// can do stuff with it programmatically
		$_POST['output'] = 'php_ser';

		$this->sparqlendpoint->handleRequest();
		if ( $this->sparqlendpoint->getErrors() ) {
			$this->errorMsgArr( $this->sparqlendpoint->getErrors );
			return null;
		}

		return $this->sparqlendpoint->getResult();
	}

	/**
	 * Figure out options for the query, based on arguments in the request,and global settings variables
	 * all taken as parameters.
	 * @param request string
	 * @param $queryByEquivURIs bool
	 * @param $outputEquivURIs bool
	 * @return $seOptions RDFIOSPARQLEndpointOptions
	 */
	private function buildOptionsObj( $request, $queryByEquivURIs, $outputEquivURIs ) {
		$seOptions = new RDFIOSPARQLEndpointOptions();

		$seOptions->query = $request->getText( 'query' );
		$seOptions->queryByEquivUris = isset( $queryByEquivURIs ) ? $queryByEquivURIs : $request->getBool( 'equivuri_q' );
		$seOptions->outputEquivUris = isset( $outputEquivURIs ) ? $outputEquivURIs : $request->getBool( 'equivuri_o' );
		$seOptions->outputType = $request->getText( 'output' );

		if ( $seOptions->query != '' ) {
			$result = $this->extractQueryInfosAndType( $seOptions->query );
			if ( $result == null ) {
				return null;
			}
			$seOptions->queryInfos = $result[0];
			$seOptions->queryType = $result[1];
		}

		return $seOptions;
	}

	/**
	 * Extract query information via ARC2's SPARQL (plus) parser
	 * @param $query string
	 * @return array
	 */
	private function extractQueryInfosAndType( $query ) {
		// Convert Sparql Update syntax to ARC2's SPARQL+ syntax:
		$querySparqlPlus = str_replace( 'INSERT DATA', 'INSERT INTO <>', $query );

		$parser = new ARC2_SPARQLPlusParser( array(), $this );
		$parser->parse( $querySparqlPlus, '' );
		if ( $parser->getErrors() ) {
			$this->errorMsgArr( $parser->getErrors() );
			return null;
		}

		$queryInfos = $parser->getQueryInfos();
		if ( array_key_exists( 'query', $queryInfos ) ) {
			$queryType = $queryInfos['query']['type'];
		}
		return array( $queryInfos, $queryType );
	}

	/**
	 * Modify the SPARQL pattern to allow querying using the original URI
	 */
	private function urisToEquivURIsInQuery( $options ) {
		$queryInfo = $options->queryInfos;
		$triple = $queryInfo['query']['pattern']['patterns'][0]['patterns'][0];

		if ( $triple['s_type'] === 'uri' ) {
			$triple['s'] = 's';
			$triple['s_type'] = 'var';
			$newTriple = $this->createEquivURITriple( $triple['s'], 's', $this->storewrapper->getEquivURIURI() );
			// TODO: Shouldn't the new triple replace the old one, not just be added?
			$queryInfo['query']['pattern']['patterns'][0]['patterns'][] = $newTriple;
		}
		if ( $triple['p_type'] === 'uri' ) {
			$triple['p'] = 'p';
			$triple['p_type'] = 'var';
			$newTriple = $this->createEquivURITriple( $triple['p'], 'p', $this->storewrapper->getEquivPropertyURIURI() );
			$queryInfo['query']['pattern']['patterns'][0]['patterns'][] = $newTriple;
		}
		if ( $triple['o_type'] === 'uri' ) {
			$triple['o'] = 'o';
			$triple['o_type'] = 'var';
			$newTriple = $this->createEquivURITriple( $triple['o'], 'o', $this->storewrapper->getEquivURIURI() );
			$queryInfo['query']['pattern']['patterns'][0]['patterns'][] = $newTriple;
		}

		// restore the first triple into its original location
		$queryInfo['query']['pattern']['patterns'][0]['patterns'][0] = $triple;

		$sparqlserializer = new ARC2_SPARQLSerializerPlugin( array(), $this );
		$query = $sparqlserializer->toString( $queryInfo );

		// Modify the $_POST variable directly, so that ARC2 can pick up the modified query
		$_POST['query'] = $query;
	}

	/**
	 * Create an RDF triple that links a wiki page to its corresponding
	 * equivalent URI
	 * @param string $uri
	 * @param string $varname
	 * @param boolean $isproperty
	 * @return array $equivuritriple
	 */
	private function createEquivURITriple( $uri, $varname, $equivUriUri ) {
		$equivuritriple = array(
			'type' => 'triple',
			's' => $varname,
			'p' => $equivUriUri,
			'o' => $uri,
			's_type' => 'var',
			'p_type' => 'uri',
			'o_type' => 'uri',
			'o_datatype' => '',
			'o_lang' => ''
		);
		return $equivuritriple;
	}

	/**
	 * Check if writing to wiki is allowed, and handle a number
	 * of exceptions to that, by showing error messages etc
	 * @return bool
	 */
	private function allowInsert( $user ) {
		global $rogAllowRemoteEdit;

		if ( !isset( $rogAllowRemoteEdit ) ) {
			$this->errorMsg( '$rogAllowRemoteEdit variable not set, so insert not allowed.');
			return false;
		}

		if ( !$rogAllowRemoteEdit ) {
			$this->errorMsg( '$rogAllowRemoteEdit set to false, so insert not allowed.');
			return false;
		}

		if ( !$user->matchEditToken( $this->getRequest()->getText( 'token' ) ) ) {
			$this->errorMsg( 'Cross-site request forgery detected! ');
			return false;
		}

		if ( in_array( 'edit', $user->getRights() ) && in_array( 'createpage', $user->getRights() )) {
			return true;
		}

		$this->errorMsg( 'The current user lacks access either to edit or create pages (or both) in this wiki');
		return false;
	}

	/**
	 * Check if deleting from wiki is allowed, and handle a number
	 * of exceptions to that, by showing error messages etc
	 */
	private function allowDelete( $user ) {
		if ( in_array( 'edit', $user->getRights() ) && in_array( 'delete', $user->getRights() ) ) {
			return true;
		}
		$this->errorMsg( 'The current user lacks delete access');
		return false;
	}

	/**
	 * Do preparations for getting outputted data as a downloadable file
	 * rather than written to the current page
	 */
	private function prepareCreatingDownloadableFile( $options ) {
		$wOut = $this->getOutput();
		// Disable MediaWikis theming
		$wOut->disable();
		// Enables downloading as a stream, which is important for large dumps
		wfResetOutputBuffers();
		// Send headers telling that this is a special content type
		// and potentially is to be downloaded as a file
		$this->setHeadersForOutputType( $options->outputType );
	}

	/**
	 * Print out the HTML Form
	 */
	private function printHTMLForm( $options ) {
		$wOut = $this->getOutput();
		$wOut->addScript( $this->getHTMLFormScript() );
		$wOut->addHTML( $this->getHTMLForm( $options->query ) );
	}

	/**
	 * Extract the main content from ARC:s SPARQL result HTML
	 * and do some enhancing (wikify tables)
	 * @param string $output
	 * @return string $html
	 */
	private function sparqlResultToHTML( $resultStructure ) {
		$html = '';
		$html = '<h3>Result:</h3><div style="font-size: 11px;">' . $html . '</div>';
		$html .= '<table class="wikitable sortable">';

		$result = $resultStructure['result'];
		$vars = $result['variables'];

		$html .= '<tr>';
		foreach ( $vars as $var ) {
			$html .= '<th width="34%">' . $var . '</th>';
		}
		$html .= '</tr>';

		$rows = $result['rows'];
		foreach ( $rows as $row ) {
			$html .= "<tr>";
			foreach ( $vars as $var ) {
				$val = $row[$var];
				//$valueType = $row[$variable . ' type'];
				$html .= '<td style="font-size:9px!important;white-space:nowrap!important;">' . $val . '</td>';
			}
			$html .= '</tr>';
		}

		$html .= '</table>';
		return $html;
	}

	/**
	 * After a query is parsed, import the parsed data to the wiki
	 */
	private function importTriplesInQuery( $options ) {
		$rdfImporter = new RDFIORDFImporter();
		$triples = $options->queryInfos['query']['construct_triples'];
		try {
			$rdfImporter->importTriples( $triples );
			$this->successMsg( 'Successfully imported the triples!' );
		} catch ( MWException $e ) {
			$this->errorMsg( 'Could not perform import!<br>' . $e->getMessage() );
		}
	}

	/**
	 * After a query is parsed, delete the parsed data from the wiki
	 */
	private function deleteTriplesInQuery( $options ) {
		$triples = $options->queryInfos['query']['construct_triples'];
		$rdfImporter = new RDFIOSMWBatchWriter( $triples, 'triples_array' );
		$rdfImporter->executeDelete();
	}

	/**
	 * Replace URI:s with an accompanying "Equivalent URI" one. If
	 * there are more than one Equivalent URI for a given URI, the others than
	 * the first one will be ignored.
	 * @param array $sparqlResult
	 * @return array $sparqlResult
	 */
	private function toEquivURIsInSparqlResults( $sparqlResult ) {
		$rows = $sparqlResult['result']['rows'];
		$vars = $sparqlResult['result']['variables'];
		foreach ( $rows as $rowid => $row ) {
			foreach ( $vars as $var ) {
				$typeKey = "$var type";
				$type = $row[$typeKey];
				$uri = $row[$var];
				if ( $type === 'uri' ) {
					try {
						$equivURIs = $this->storewrapper->getEquivURIsForURI( $uri );
					} catch ( RDFIOARC2StoreWrapperException $e ) {
						$this->errorMsg( $e );
						return;
					}
					if ( !empty( $equivURIs ) ) {
						$equivURI = $equivURIs[0];
						// Replace URI with the 'Equivalent URI'
						$rows[$rowid][$var] = $equivURI;
					}
				}
			}
		}
		// Put back the modified rows into the results structure
		$sparqlResult['result']['rows'] = $rows;
		return $sparqlResult;
	}


	/**
	 * Convert an ARC triples array into RDF/XML
	 * @param array $triples
	 * @return string $rdfxml
	 */
	private function triplesToRDFXML( $triples ) {
		$ser = new ARC2_RDFXMLSerializer();
		// Serialize into RDF/XML, since it will contain
		// all URIs in un-abbreviated form, so that they
		// can easily be replaced by search-and-replace
		$rdfxml = $ser->getSerializedTriples( $triples );
		if ( $ser->getErrors() ) {
			$this->errorMsgArr( $ser->getErrors() );
			return null;
		}
		return $rdfxml;
	}

	/**
	 * Get a configuration array for initializing the ARCs
	 * SPARQL endpoint
	 */
	private function getSPARQLEndpointConfig() {
		global $smwgARC2StoreConfig;
		$epconfig = $smwgARC2StoreConfig;
		$epconfig['endpoint_features'] =
			array(
				'select',
				'construct',
				'ask',
				'describe',
				// 'load',
				// 'insert', 				  // This is not needed, since it is done via SMWWriter instead
				// 'delete', 				  // This is not needed, since it is done via SMWWriter instead
				// 'dump'    				  // dump is a special command for streaming SPOG export
			);
		$epconfig['endpoint_timeout'] = 60;   // not implemented in ARC2 preview
		// 'endpoint_read_key' => '',         // optional
		// 'endpoint_write_key' => 'somekey', // optional
		// 'endpoint_max_limit' => 250,       // optional
		return $epconfig;
	}

	/**
	 * Set headers appropriate to the filetype specified in $outputtype
	 * @param string $outputType
	 */
	private function setHeadersForOutputType( $outputType ) {
		$wRequest = $this->getRequest();

		$contentTypeMap = array(
			'xml'     => 'application/xml',
			'rdfxml'  => 'application/xml',
			'json'    => 'application/json',
			'turtle'  => 'text/html',
			'htmltab' => '', // Not applicable
			'tsv'     => 'text/html'
		);

		$extensionMap = array(
			'xml'     => '.xml',
			'rdfxml'  => '_rdf.xml',
			'json'    => '.json',
			'turtle'  => '.ttl',
			'htmltab' => '', // Not applicable
			'tsv'     => '.tsv'
		);

		if ( $outputType != 'htmltab' ) { // For HTML table we are taking care of the output earlier
			$wRequest->response()->header( 'Content-type: ' . $contentTypeMap[$outputType] . '; charset=utf-8' );

			$fileName = urlencode('sparql_output_' . wfTimestampNow() . $extensionMap[$outputType] );
			$wRequest->response()->header( 'Content-disposition: attachment;filename=' . $fileName );
		}
	}

	/**
	 * Add a formatted success message to the HTML output, with $message as message.
	 * @param $message
	 */
	private function successMsg( $message ) {
		$wOut = $this->getOutput();
		$wOut->addHTML( RDFIOUtils::fmtSuccessMsgHTML( "Success!", $message ) );
	}

	/**
	 * Add a formatted error message to the HTML output, with $message as message.
	 * @param $message
	 */
	private function errorMsg( $message ) {
		$wOut = $this->getOutput();
		$wOut->addHTML( RDFIOUtils::fmtErrorMsgHTML( "Error!", $message ) );
	}

	/**
	 * Add a formatted error message to the HTML output, taking an array of messages
	 * @param $messages array
	 */
	private function errorMsgArr( $messages ) {
		$allMsgs = '';
		foreach ( $messages as $msg ) {
			$allMsgs .= '<p>' . $msg . '</p>';
		}
		$this->errorMsg( $allMsgs );
	}

	/**
	 * Get the HTML for the main SPARQL querying form. If $query is set, use it to prefill the main textarea
	 * @param string $query
	 * @return string $htmlForm
	 */
	private function getHTMLForm( $query = '' ) {
		global $wgArticlePath;

		$wRequest = $this->getRequest();
		$wUser = $this->getUser();
		$uriResolverURI = SpecialPage::getTitleFor( 'URIResolver' )->getFullURL() . '/';
		$defaultQuery = "@PREFIX w : <$uriResolverURI> .\n\nSELECT *\nWHERE { ?s ?p ?o }\nLIMIT 25";

		if ( $query == '' ) {
			$query = $defaultQuery;
		}

		$chkEquivUriQ = $wRequest->getBool( 'equivuri_q', false ) == 1 ? ' checked="true" ' : '';
		$chkEquivUriO = $wRequest->getBool( 'equivuri_o', false ) == 1 ? ' checked="true" ' : '';
		$chkFilterVocab = $wRequest->getBool( 'filtervocab', false ) == 1 ? ' checked="true" ' : '';
		$selOutputHTML = $wRequest->getText( 'output', '' ) == 'htmltab' ? ' selected="selected" ' : '';
		$selOutputRDFXML = $wRequest->getText( 'output', '' ) == 'rdfxml' ? ' selected="selected" ' : '';

		// Make the HTML format selected by default
		if ( $selOutputRDFXML == '' ) {
			$selOutputHTML = ' selected="selected" ';
		}

		$htmlForm = '<form method="post" action="' . str_replace( '/$1', '', $wgArticlePath ) . '/Special:SPARQLEndpoint"
	        name="createEditQuery">
	<div style="font-size: 10px">
	<table border="0"><tbody>
		<tr><td colspan="3">Enter SPARQL query:</td><tr>
		<tr><td colspan="3"><textarea cols="80" rows="9" name="query">' . $query . '</textarea></td></tr>
		<tr>
	        <td style="vertical-align: top; border-right: 1px solid #ccc;">
				<table border="0" style="background: transparent; font-size: 11px;">
					<tr>
						<td style="text-align: right">Query by Equivalent URIs:</td>
						<td><input type="checkbox" name="equivuri_q" value="1" ' . $chkEquivUriQ . '/></td>
					</tr>
				</table>
	        </td>
	        <td width="170" style="vertical-align: top; border-right: 1px solid #ccc;">
				<table border="0" style="font-size: 11px; background: transparent;">
					<tr>
						<td style="text-align: right">Output Equivalent URIs:</td>
						<td><input type="checkbox" name="equivuri_o" id="outputequivuri" value="1" ' . $chkEquivUriO /* . ' onChange="toggleDisplay(\'byontology\');" */ . '/></td>
					</tr>
				</table>
	        </td>
	        <td width="260" style="vertical-align: top;">
				<table border="0" style="font-size: 11px; background: transparent;" >
				<tr><td style="text-align: right" width="180">Output format:</td>
				<td style="vertical-align: top">
				<select id="output" name="output" onChange="toggleDisplay(\'byontology\');" >
				  <!-- <option value="" >default</option> -->
				  <!-- <option value="json" >JSON</option> -->
				  <!-- <option value="plain" >Plain</option> -->
				  <!-- <option value="php_ser" >Serialized PHP</option> -->
				  <!-- <option value="turtle" >Turtle</option> -->
				  <option value="htmltab" ' . $selOutputHTML . '>HTML</option>
				  <option value="xml" >XML Resultset</option>
				  <option value="rdfxml" ' . $selOutputRDFXML . '>RDF/XML</option>
				  <!-- <option value="infos" >Query Structure</option> -->
				  <!-- <option value="tsv" >TSV</option> -->
				</select>
				</td></tr>
				<tr>
				<td colspan="2">
				<span style="font-family: arial, helvetica, sans-serif; font-size: 10px; color: #777;">(RDF/XML requires creating triples using <a href="http://www.w3.org/TR/rdf-sparql-query/#construct">CONSTRUCT</a>)</span>
				</td>
				</table>
	        </td>
		</tr>
		<tr>
	        <td colspan="3">
				<div id="byontology" style="display: none; background: #ffd; border: 1px solid #ee7;">
					<table border="0" style="font-size: 11px; background: transparent;" >
						<tr><td style="text-align: right;">Filter by vocabulary:</td>
							<td><input type="checkbox" name="filtervocab" value="1" ' . $chkFilterVocab . '/></td>
							<td style="text-align: right">Vocabulary URL:</td>
							<td><input type="text" name="filtervocaburl" size="48" /></td>
						</tr>
						<tr>
							<td>&#160;</td>
							<td>&#160;</td>
							<td>&#160;</td>
							<td><span style="font-family: arial, helvetica, sans-serif; font-size: 10px; color: #777">Example: http://xmlns.com/foaf/spec/index.rdf</span></td>
						</tr>
					</table>
				</div>
	        </td>
	    </tr>
	</table>
	</div>
	<input type="submit" value="Submit">
	<input type="hidden" name="token" value="' . $wUser->getEditToken() . '">
</form>';
		return $htmlForm;
	}

	/**
	 * Get the javascript used for some functionality in the main SPARQL
	 * querying HTML form
	 * @return string $htmlFormScript
	 */
	private function getHTMLFormScript() {
		$htmlFormScript = "<script type=\"text/javascript\">
	    function toggleDisplay(id1) {
	    	var bostyle = document.getElementById(id1).style.display;
	    	var fmtsel = document.getElementById('output');
	    	var fmt = fmtsel.options[fmtsel.selectedIndex].value;
	    	var outsel = document.getElementById('outputequivuri');
	    	if ( outsel.checked && fmt.match('rdfxml') ) {
				document.getElementById(id1).style.display = 'block';
			} else {
				document.getElementById(id1).style.display = 'none';
			}
		}
	 	</script>";
		return $htmlFormScript;
	}
}

class RDFIOSPARQLEndpointOptions {
	public $query;
	public $queryType;
	public $queryByEquivUris = false;
	public $outputEquivUris = false;
	public $outputType;
	public $queryInfos = array();

	function __construct() {}
}
