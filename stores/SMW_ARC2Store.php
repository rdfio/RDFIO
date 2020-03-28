<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not a valid entry point.' );
}

global $IP;

/**
 * SMWARC2Store extends SMWSQLStore3 and forwards all update/delete to ARC2 via SPARQL+
 * queries. The class was based on JosekiStore in the SparqlExtension, which in turn is
 * loosely based on/insipred by RAPStore.
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class SMWARC2Store extends SMWSQLStore3 {
	protected $arc2store;

	public function __construct() {
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBprefix;
		parent::__construct();
		// Customize these details if you
		// want to use an external database
		$arc2StoreConfig = array(
			'db_host' => $wgDBserver,
			'db_name' => $wgDBname,
			'db_user' => $wgDBuser,
			'db_pwd' => $wgDBpassword,
			'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
		);
		$this->arc2store = ARC2::getStore( $arc2StoreConfig );
		$this->arc2store->createDBCon();
	}

	/**
	 * wraps removeDataForURI()
	 * @param $subject
	 */
	public function deleteSubject( Title $subject ) {
		$subjectUri = SMWExporter::getInstance()->expandURI( $this->getURI( $subject ) );
		$this->removeDataForURI( $subjectUri );

		return parent::deleteSubject( $subject ); // Also update via SQLStore3
	}

	/**
	 * Does update. First deletes, then inserts.
	 * @param $data
	 */
	public function updateData( SMWSemanticData $data ) {
		// NOTE: Should doDataUpdate() be used instead? (See SMWStore class)
		$exportData = SMWExporter::getInstance()->makeExportData( $data );
		$subjectUri = SMWExporter::getInstance()->expandURI( $exportData->getSubject()->getUri() );

		$this->removeDataForURI( $subjectUri );
		$tripleList = $exportData->getTripleList();

		$sparqlUpdateText = "INSERT INTO <> {\n"; // Follows ARC2 SPARQL+ syntax (not SPARQL Update)
		foreach ( $tripleList as $triple ) {
			$subject = $triple[0];
			$predicate = $triple[1];
			$object = $triple[2];

			$objectStr = '';
			$subjectStr = '';
			$predicateStr = '';

			if ( $object instanceof SMWExpLiteral ) {
				$objectStr = '"' . addslashes( $object->getLexicalForm() ) . '"' . ( ( $object->getDatatype() == '' ) ? '' : '^^<' . $object->getDatatype() . '>' );
			} elseif ( $object instanceof SMWExpResource ) {
				$objectStr = '<' . SMWExporter::getInstance()->expandURI( $object->getUri() ) . '>';
			} else {
				$objectStr = '""';
			}

			if ( $subject instanceof SMWExpResource ) {
				$subjectStr = '<' . SMWExporter::getInstance()->expandURI( $subject->getUri() ) . '>';
			}

			if ( $predicate instanceof SMWExpResource ) {
				$predicateStr = '<' . SMWExporter::getInstance()->expandURI( $predicate->getUri() ) . '>';
			}

			$sparqlUpdateText .= $subjectStr . ' ' . $predicateStr . ' ' . $objectStr . " .\n";
		}
		$sparqlUpdateText .= "}\n";

		wfDebugLog( 'SPARQL_LOG', $sparqlUpdateText );
		$this->executeArc2Query( $sparqlUpdateText );
		parent::updateData( $data );
	}

	/**
	 * Move/rename page
	 * @param $oldtitle
	 * @param $newtitle
	 * @param $pageid
	 * @param $redirid
	 */
	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {
		// Save it in parent store now!
		// We need that so we get all information correctly!
		parent::changeTitle( $oldTitle, $newTitle, $pageId, $redirectId );

		// Delete old stuff
		$oldUri = SMWExporter::getInstance()->expandURI( $this->getURI( $oldTitle ) );
		$this->removeDataForURI( $oldUri );

		$newpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$newpage->setValues( $newTitle->getDBkey(), $newTitle->getNamespace(), $pageId );
		$semdata = $this->getSemanticData( $newpage );
		$this->updateData( $semdata );

		$oldpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$oldpage->setValues( $oldTitle->getDBkey(), $oldTitle->getNamespace(), $redirectId );
		$semdata = $this->getSemanticData( $oldpage );
		$this->updateData( $semdata, false );
	}

	/**
	 * Communicates with ARC2 RDF Store
	 * @param $requestString
	 */
	public function executeArc2Query( $requestString ) {

		$query = $requestString;
		$result = $this->arc2store->query( $query );

		$errors = $this->arc2store->getErrors();
		foreach ( $errors as $error ) {
			throw new RDFIOARC2StoreException( $error );
		}

		return $result;
	}

	/**
	 * Insert new pages into endpoint. Used to import data.
	 * @param $title
	 */
	private function insertData( Title $title, $pageid ) {
		$newpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$newpage->setValues( $title->getDBkey(), $title->getNamespace(), $pageid );
		$semdata = $this->getSemanticData( $newpage );
		$this->updateData( $semdata );
	}

	/**
	 * deletes triples that have $uri as subject
	 * @param $uri
	 */
	private function removeDataForURI( $uri ) {
		$sparqlUpdateText = 'DELETE { <' . $uri . '> ?x ?y . }';
		$response = $this->executeArc2Query( $sparqlUpdateText );
		return $response;
	}

	/**
	 * Having a title of a page, what is the URI that is described by that page?
	 * The result still requires expandURI()
	 * @param string $title
	 * @return string $uri
	 */
	private function getURI( $title ) {
		$uri = '';
		if ( $title instanceof Title ) {
			$wikiPageDI = SMWDIWikiPage::newFromTitle( $title );
			$exp = SMWExporter::getInstance()->makeExportDataForSubject( $wikiPageDI );
			$uri = $exp->getSubject()->getUri();
		} else {
			// There could be other types as well that we do NOT handle here
		}

		return $uri; // still requires expandURI()
	}
}

class RDFIOARC2StoreException extends MWException {
}
