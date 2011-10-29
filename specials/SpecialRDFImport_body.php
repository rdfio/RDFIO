<?php
class RDFImport extends SpecialPage {

	protected $m_action;
	protected $m_dataformat;
	protected $m_importdata;
	protected $m_edittoken;
	protected $m_smwbatchwriter;
	protected $m_haswriteaccess;
	protected $m_nsprefix_in_wikititles_properties;
	protected $m_nsprefix_in_wikititles_entities;
	protected $m_show_abbrscreen_properties;
	protected $m_show_abbrscreen_entities;

	function __construct() {
		global $wgUser;

		$userrights = $wgUser->getRights();
		if ( in_array( 'edit', $userrights ) && in_array( 'createpage', $userrights ) ) {
			$this->m_haswriteaccess = true;
		} else {
			$this->m_haswriteaccess = false;
		}
		parent::__construct( 'RDFImport' );
    }

	function execute( $par ) {
		global $wgOut, $wgUser;

		$this->setHeaders();
		$this->handleRequestData();

		if ( $this->m_action == 'Import' ) {

			$title = Title::newFromText('Test2');
			
			$parser = new RDFIOARC2Parser();
			$parser->setInput("hej hej");
			$output = $parser->getInput();

			$article = new Article($title);
			#$content = "Hejsan hoppsan, 1, 2, 3 ...";
			$summary = "A Bot edit ...";
			 
			$content = $article->fetchContent();
			$content_new = $content . " ... " . $output;
			$article->doEdit($content_new, $summary);
			
			$wgOut->addHTML('<pre>' . htmlentities( $content_new ) . '</pre>');
			
		} else {
			$this->outputHTMLForm();
		}
	}

	/**
	 * Get data from the request object and store it in class variables
	 */
	function handleRequestData() {
		global $wgRequest;
		$this->m_action = $wgRequest->getText( 'action' );
		$this->m_dataformat = $wgRequest->getText( 'dataformat' );
		$this->m_importdata = $wgRequest->getText( 'importdata' );
		$this->m_edittoken = $wgRequest->getText( 'token' );
		$this->m_nsprefix_in_wikititles_properties = $wgRequest->getBool( 'nspintitle_prop', false );
		$this->m_show_abbrscreen_properties = $wgRequest->getBool( 'abbrscr_prop', false );
		$this->m_nsprefix_in_wikititles_entities = $wgRequest->getBool( 'nspintitle_ent', false );
		$this->m_show_abbrscreen_entities = $wgRequest->getBool( 'abbrscr_ent', false );
	}

	/**
	 * Output the HTML for the form, to the user
	 */
	function outputHTMLForm() {
		global $wgOut;
		$wgOut->addScript( $this->getExampleDataJs() );
		$wgOut->addHTML( $this->getHTMLFormContent() );
	}

	/**
	 * Get RDF/XML stub for for the import form, including namespace definitions
	 * @return string
	 */
	public function getExampleRDFXMLData() {
		return '<rdf:RDF\\n\
		        xmlns:rdfs=\\"http://www.w3.org/2000/01/rdf-schema#\\"\\n\
		        xmlns:rdf=\\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\\"\\n\
		        xmlns:n0pred=\\"http://bio2rdf.org/go_resource:\\"\\n\
		        xmlns:ns0pred=\\"http://www.w3.org/2002/07/owl#\\">\\n\
		        \\n\
		        <rdf:Description rdf:about=\\"http://bio2rdf.org/go:0032283\\">\\n\
		            <n0pred:accession>GO:0032283</n0pred:accession>\\n\
		            <rdfs:label>plastid acetate CoA-transferase complex [go:0032283]</rdfs:label>\\n\
		            <n0pred:definition>An acetate CoA-transferase complex located in the stroma of a plastid.</n0pred:definition>\\n\
		            <rdf:type rdf:resource=\\"http://bio2rdf.org/go_resource:term\\"/>\\n\
		            <n0pred:name>plastid acetate CoA-transferase complex</n0pred:name>\\n\
		            <n0pred:is_a rdf:resource=\\"http://bio2rdf.org/go:0009329\\"/>\\n\
		            <rdf:type rdf:resource=\\"http://bio2rdf.org/go_resource:Term\\"/>\\n\
		            <urlImage xmlns=\\"http://bio2rdf.org/bio2rdf_resource:\\">http://bio2rdf.org/image/go:0032283</urlImage>\\n\
		            <xmlUrl xmlns=\\"http://bio2rdf.org/bio2rdf_resource:\\">http://bio2rdf.org/xml/go:0032283</xmlUrl>\\n\
		            <rights xmlns=\\"http://purl.org/dc/terms/\\" rdf:resource=\\"http://www.geneontology.org/GO.cite.shtml\\"/>\\n\
		            <ns0pred:sameAs rdf:resource=\\"http://purl.org/obo/owl/GO#GO_0032283\\"/>\\n\
		            <url xmlns=\\"http://bio2rdf.org/bio2rdf_resource:\\">http://bio2rdf.org/html/go:0032283</url>\\n\
		        </rdf:Description>\\n\
		        \\n\
		        </rdf:RDF>';
	}

	/**
	 * Generate the main HTML form, if the variable $extraFormContent is set, the
	 * content of it will be prepended before the form
	 * @param string $extraFormContent
	 * @return string $htmlFormContent
	 */
	public function getHTMLFormContent( $extraFormContent = '' ) {
		global $wgRequest, $wgUser, $wgArticlePath;

		// Abbreviation (and screen) options for properties
		$checked_nspintitle_properties = $wgRequest->getBool( 'nspintitle_prop', false ) == 1 ? ' checked="true" ' : '';
		$checked_abbrscr_properties = $wgRequest->getBool( 'abbrscr_prop', false ) == 1 ? ' checked="true" ' : '';

		// Abbreviation (and screen) options for entities
		$checked_nspintitle_entities = $wgRequest->getBool( 'nspintitle_ent', false ) == 1 ? ' checked="true" ' : '';
		$checked_abbrscr_entities = $wgRequest->getBool( 'abbrscr_ent', false ) == 1 ? ' checked="true" ' : '';

		$this->m_importdata = $wgRequest->getText( 'importdata', '' );

		// Create the HTML form for RDF/XML Import
		$htmlFormContent = '<form method="post" action="' . str_replace( '/$1', '', $wgArticlePath ) . '/Special:RDFImport"
			name="createEditQuery"><input type="hidden" name="action" value="Import">
			' . $extraFormContent . '
			<table border="0"><tbody>
			<tr><td colspan="3">RDF/XML data to import:</td><tr>
			<tr><td colspan="3"><textarea cols="80" rows="9" name="importdata" id="importdata">' . $this->m_importdata . '</textarea>
			</td></tr>
			<tr><td width="100">Data format:</td>
			<td>
			<select id="dataformat" name="dataformat">
			  <option value="rdfxml" selected="selected">RDF/XML</option>
			  <option value="turtle" >Turtle</option>
			</select>
			</td>
			<td style="text-align: right; font-size: 10px;">
			[<a href="#" onClick="pasteExampleRDFXMLData(\'importdata\');">Paste example data</a>]
			[<a href="#" onClick="document.getElementById(\'importdata\').value = \'\';">Clear</a>]
			</td>
			</tr>
			<tr>
			<td colspan="3">
			<table width="100%" class="wikitable">
			<tr>
			<th style="text-size: 11px">
			Options for properties
			</th>
			<th style="text-size: 11px">
			Options for non-properties
			</th>
			</tr>
			<tr>
			<td style="font-size: 11px">
			<input type="checkbox" name="nspintitle_prop" id="abbrprop" value="1" ' . $checked_nspintitle_properties . ' /> Use namespace prefixes in wiki titles
			</td>
			<td style="font-size: 11px">
			<input type="checkbox" name="nspintitle_ent" id="abbrent" value="1" ' . $checked_nspintitle_entities . ' /> Use namespace prefixes in wiki titles
			</td>
			</tr>
			<tr>
			<td style="font-size: 11px">
			<input type="checkbox" name="abbrscr_prop" id="abbrscrprop" value="1" ' . $checked_abbrscr_properties . ' /> Show abbreviation screen
			</td>
			<td style="font-size: 11px">
			<input type="checkbox" name="abbrscr_ent" id="abbrscrent" value="1" ' . $checked_abbrscr_entities . ' /> Show abbreviation screen
			</td>
			</tr>
			</table>
			</td>
			</tr>
			</tbody></table>
			<input type="submit" value="Submit">' . Html::Hidden( 'token', $wgUser->editToken() ) . '
			</form>';
		return $htmlFormContent;
	}

	/**
	 * Generate the javascriptcode used in the main HTML form for
	 * loading example data into the main textarea
	 * @return string $exampleDataJs
	 */
	public function getExampleDataJs() {
		$exampleDataJs = '
			<script type="text/javascript">
			function pasteExampleRDFXMLData(textFieldId) {
			var textfield = document.getElementById(textFieldId);
			var exampledata = "' . $this->getExampleRDFXMLData() . '";
			textfield.value = exampledata;
			}
			</script>
			';
		return $exampleDataJs;
	}

}
