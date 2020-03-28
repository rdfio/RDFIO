<?php

class RDFImport extends RDFIOSpecialPage {

	private $wOut;

	function __construct() {
		parent::__construct( 'RDFImport', 'rdfio-import' );
	}

	/**
	 * The main code goes here
	 * @param string $par (unused)
	 */
	function execute( $par ) {
	    global $wgShowExceptionDetails;

		unset( $par ); // Needed to suppress warning about unused variable which we include just for consistency.

		// Require rdfio-import permission for the current user
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			throw new PermissionsError( 'rdfio-import', array( 'rdfio-specialpage-access-permission-missing' ) );
		}

		$this->wOut = $this->getOutput();

		// Set HTML headers sent to the browser
		$this->setHeaders();

		// The main code
		$requestData = $this->getRequestData();
		if ( $requestData->action === 'import' ) {
			if ( !$requestData->hasWriteAccess ) {
				$this->errorMsg( "The current user does not have write access in the wiki" );
				return;
			}

			try {
				$importInfo = $this->importData( $requestData );
				$triples = $importInfo['triples'];
				if ( $triples ) {
					$this->showHTMLFormAndInfo( $requestData );

					$this->successMsg('Successfully imported ' . count( $triples ) . ' triples!' );

					// Show imported triples
					$rdfImporter = new RDFIORDFImporter();
					$this->addHTML( $rdfImporter->showImportedTriples( $triples ) );

					if ( $requestData->externalRdfUrl ) {
						$rdfImporter->addDataSource( $requestData->externalRdfUrl, 'RDF' );
					}
					return;

				} else if ( !$triples ) {
					$this->errorMsg( 'No new triples to import, in in-data' );
				}
			} catch ( MWException $e ) {
				$this->errorMsg( $e->getMessage() );
				if ( $wgShowExceptionDetails ) {
				    $this->errorMsg( '<pre>' . $e->getTraceAsString() . '</pre>' );
				}
			}
		}
		$this->showHTMLFormAndInfo( $requestData );
	}

	/**
	 * Add wiki text to output. Requires that $this->wOut is already
	 * initialized to $this->getOutput();
	 * @param $text The wiki text to add.
	 */
	private function addWikiText( $text ) {
		if ( method_exists( $this->wOut, 'addWikiTextAsInterface' ) ) {
			$this->wOut->addWikiTextAsInterface( $text );
		} else {
			$this->wOut->addWikiText( $text );
		}
	}

	/**
	 * Add HTML content to output. Requires that $this->wOut is already
	 * initialized to $this->getOutput();
	 * @param $text The HTML content to add.
	 */
	private function addHTML( $html ) {
		$this->wOut->addHTML( $html );
	}

	/**
	 * Import data into wiki pages
	 */
	function importData( RDFIORequestData $requestData ) {
		$rdfImporter = new RDFIORDFImporter();
		if ( $requestData->importSource === 'url' ) {
			if ( $requestData->externalRdfUrl === '' ) {
				throw new RDFIOException( 'URL field is empty!' );
			} else if ( substr( $requestData->externalRdfUrl, 0, 4 ) !== 'http' ) {
				throw new RDFIOException( 'Invalid URL provided!' );
			}
			$rdfData = file_get_contents( $requestData->externalRdfUrl );
		} else if ( $requestData->importSource === 'textfield' ) {
			if ( $requestData->importData === '' )
				throw new RDFIOException( 'RDF field is empty!' );
			$rdfData = $requestData->importData;
		} else {
			throw new RDFIOException( 'Import source is not selected!' );
		}

		switch ( $requestData->dataFormat ) {
			case 'rdfxml':
				$importInfo = $rdfImporter->importRdfXml( $rdfData );
				$triples = $importInfo['triples'];
				break;
			case 'turtle':
				$importInfo = $rdfImporter->importTurtle( $rdfData );
				$triples = $importInfo['triples'];
				break;
		}

		$output = array( 'triples' => $triples );
		return $output;
	}

	/**
	 * Get data from the request object and store it in class variables
	 */
	function getRequestData() {
		global $wgRequest, $wgArticlePath;

		$requestData = new RDFIORequestData();
		$requestData->action = $wgRequest->getText( 'action' );
		$requestData->editToken = $wgRequest->getText( 'token' );
		$requestData->importSource = $wgRequest->getText( 'importsrc' );
		$requestData->externalRdfUrl = $wgRequest->getText( 'extrdfurl' );
		$requestData->importData = $wgRequest->getText( 'importdata' );
		$requestData->dataFormat = $wgRequest->getText( 'dataformat' );
		$requestData->hasWriteAccess = $this->allowInsert( $this->getUser(), $wgRequest );
		$requestData->articlePath = $wgArticlePath;

		return $requestData;
	}

	/**
	 * Show the RDF Import Form HTML, and some additional info HTML
	 */
	function showHTMLFormAndInfo( $requestData ) {
		$wUser = $this->getUser();

		$this->addHTML( $this->getHTMLForm( $requestData, $wUser ) );
		$this->addHTML( '<div id=sources style="display:none">' );
		$this->addWikiText( '{{#ask: [[Category:RDFIO Data Source]] [[RDFIO Import Type::RDF]] |format=list }}' );
		$this->addHTML( '</div>' );
	}

	/**
	 * Output the HTML for the form, to the user
	 */
	function getHTMLForm( $requestData, $user ) {
		$formText = "";
		$formText .= $this->getJsCode();
		$formText .= $this->getHTMLFormContent( $requestData, $user );
		return $formText;
	}

	/**
	 * Get RDF/XML stub for for the import form, including namespace definitions
	 * @return string
	 */
	public function getExampleRDFXMLData() {
		return '<rdf:RDF\\n\
xmlns:rdf=\\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\\"\\n\
xmlns:cd=\\"http://www.recshop.fake/cd#\\"\\n\
xmlns:countries=\\"http://www.countries.org/onto/\\"\\n\
xmlns:rdfs=\\"http://www.w3.org/2000/01/rdf-schema#\\"\\n\
xmlns:cat=\\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\\"\\n\
>\\n\
\\n\
<rdf:Description\\n\
rdf:about=\\"http://www.recshop.fake/cd/Empire Burlesque\\">\\n\
<cd:artist>Bob Dylan</cd:artist>\\n\
<cd:country rdf:resource=\\"http://www.countries.org/onto/USA\\"/>\\n\
<cd:company>Columbia</cd:company>\\n\
<cd:price>10.90</cd:price>\\n\
<cd:year>1985</cd:year>\\n\
<cat:type>Album</cat:type>\\n\
</rdf:Description>\\n\
\\n\
<rdf:Description\\n\
rdf:about=\\"http://www.recshop.fake/cd/Hide your heart\\">\\n\
<cd:artist>Bonnie Tyler</cd:artist>\\n\
<cd:country>UK</cd:country>\\n\
<cd:company>CBS Records</cd:company>\\n\
<cd:price>9.90</cd:price>\\n\
<cd:year>1988</cd:year>\\n\
<cat:type>Album</cat:type>\\n\
</rdf:Description>\\n\
\\n\
<rdf:Description\\n\
rdf:about=\\"http://www.countries.org/onto/USA\\">\\n\
<rdfs:label>USA</rdfs:label>\\n\
</rdf:Description>\\n\
\\n\
<rdf:Description rdf:about=\\"http://www.countries.org/onto/Albums\\">\\n\
<rdfs:subClassOf rdf:resource=\\"http://www.countries.org/onto/MediaCollections\\"/>\\n\
</rdf:Description>\\n\
</rdf:RDF>';
	}

	/**
	 * Get Turtle stub for for the import form, including namespace definitions
	 * @return string
	 */
	public function getExampleTurtleData() {
		$exampleData = <<<EOT
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\\n\
@prefix cd: <http://www.recshop.fake/cd#> .\\n\
@prefix countries: <http://www.countries.org/onto/> .\\n\
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\\n\
@prefix cat: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\\n\
\\n\
<http://www.recshop.fake/cd/Empire Burlesque>\\n\
	cd:artist \\"Bob Dylan\\" ;\\n\
	cd:country countries:USA ;\\n\
	cd:company \\"Columbia\\" ;\\n\
	cd:price \\"10.90\\" ;\\n\
	cd:year \\"1985\\" ;\\n\
	cat:type \\"Album\\" .\\n\
\\n\
<http://www.recshop.fake/cd/Hide your heart>\\n\
	cd:artist \\"Bonnie Tyler\\" ;\\n\
	cd:country \\"UK\\" ;\\n\
	cd:company \\"CBS Records\\" ;\\n\
	cd:price \\"9.90\\" ;\\n\
	cd:year \\"1988\\" ;\\n\
	cat:type \\"Album\\" .\\n\
\\n\
countries:USA\\n\
	rdfs:label \\"USA\\" .\\n\
\\n\
countries:Albums\\n\
	rdfs:subClassOf countries:MediaCollections .';
EOT;
		return $exampleData;
	}

	/**
	 * Generate the main HTML form, if the variable $extraFormContent is set, the
	 * content of it will be prepended before the form
	 * @param RDFIORequestData $requestData
	 * @param string $extraFormContent
	 * @return string $htmlFormContent
	 */
	public function getHTMLFormContent( $requestData, $user, $extraFormContent = '' ) {
		$textfieldHiddenHTML = '';
		$urlChecked = ( $requestData->importSource === 'url' );
		$textfieldChecked = ( $requestData->importSource === 'textfield' );

		// Show (and pre-select) the URL field, as default
		if ( !$urlChecked && !$textfieldChecked ) {
			$urlChecked = true;
		}
		if ( !$textfieldChecked ) {
			$textfieldHiddenHTML = 'style="display: none"';
		}

		$urlCheckedContent = $urlChecked ? 'checked="true"' : '';
		$textfieldCheckedHTML = $textfieldChecked ? 'checked="true"' : '';

		// Create the HTML form for RDF/XML Import
		$htmlFormContent = '<script type="text/javascript">
				function showUrlFields() {
					document.getElementById("urlfields").style.display = "";
					document.getElementById("datafields").style.display = "none";
				}
				function showDataFields() {
					document.getElementById("urlfields").style.display = "none";
					document.getElementById("datafields").style.display = "";
				}
				</script>
				<form method="post" action=""
				name="createEditQuery"><input type="hidden" name="action" value="import">
				' . $extraFormContent . '
					<table border="0">
						<tbody>
							<tr>
								<td colspan="3">
								Action:
								<input type="radio" name="importsrc" value="url" ' . $urlCheckedContent . ' onclick="javascript:showUrlFields();" />' . wfMessage( 'rdfio-import-rdf-from-url' )->parse() . '
								<input type="radio" name="importsrc" value="textfield" ' . $textfieldCheckedHTML . ' onclick="javascript:showDataFields();" />' . wfMessage( 'rdfio-paste-rdf' )->parse() . '
								</td>
							</tr>
						</tbody>
					</table>
						
					<div id="urlfields">
						External URL:
						<input type="text" size="100" name="extrdfurl" id="extrdfurl">
						<a href="#" onClick="addSourcesToMenu();">' . wfMessage( 'rdfio-use-previous-source' )->parse() . '</a>
					</div>
						
					<div id="datafields" ' . $textfieldHiddenHTML . '>
						<table style="border: none"><tbody>
							<tr>
								<td colspan="3">' . wfMessage( 'rdfio-data-to-import' )->parse() . ':</td>
							</tr>
							<tr>
								<td colspan="3"><textarea cols="80" rows="16" name="importdata" id="importdata">' . $requestData->importData . '</textarea></td>
							</tr>
							<tr>
								<td style="width: 100px;">' . wfMessage( 'rdfio-data-format' )->parse() . ':</td>
								<td>
									<select id="dataformat" name="dataformat">
										<option value="turtle" selected="selected">Turtle</option>
										<option value="rdfxml">RDF/XML</option>
									</select>
								</td>
								<td style="text-align: right; font-size: 10px;">' . wfMessage( 'rdfio-paste-example-data' )->parse() . '
									[<a href="#" onClick="pasteExampleRDFXMLData(\'importdata\');">' . wfMessage( 'rdfio-rdfxml-format' )->parse() . '</a>]
									[<a href="#" onClick="pasteExampleTurtleData(\'importdata\');">' . wfMessage( 'rdfio-turtle-format' )->parse() . '</a>]
									[<a href="#" onClick="document.getElementById(\'importdata\').value = \'\';">' . wfMessage( 'rdfio-clear' )->parse() . '</a>]
								</td>
							</tr>
						</tbody></table>
					</div>
					<input type="submit" value="Submit">
					<input type="hidden" name="token" value="' . $user->getEditToken() . '">
				</form>';

		return $htmlFormContent;
	}

	/**
	 * Generate the javascript code used in the main HTML form for
	 * loading example data into the main textarea
	 * also set the dataformat to the correct one
	 * @return string $exampleDataJs
	 */
	public function getJsCode() {
		$jsCode = '
<script type="text/javascript">
function pasteExampleRDFXMLData(textFieldId) {
	var textfield = document.getElementById(textFieldId);
	var exampledata = "' . $this->getExampleRDFXMLData() . '";
	textfield.value = exampledata;
	document.getElementById("dataformat").options[1].selected = true;
	
}	
function pasteExampleTurtleData(textFieldId) {
	var textfield = document.getElementById(textFieldId);
	var exampledata = "' . $this->getExampleTurtleData() . '";
	textfield.value = exampledata;
	document.getElementById("dataformat").options[0].selected = true;
}

function addSourcesToMenu() {
	var sourceList = document.getElementById("sources").getElementsByTagName("p")[0];
	var sources = sourceList.getElementsByTagName("a");
	var urlForm = document.getElementById("urlfields");
	var urlTextField = document.getElementById("extrdfurl");
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

class RDFIORequestData {
	public $action;
	public $editToken;
	public $importSource;
	public $externalRdfUrl;
	public $importData;
	public $dataFormat;
	public $hasWriteAccess;
	public $articlePath;

	public function __construct() {
	}
}
