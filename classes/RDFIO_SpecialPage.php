<?php

/**
 * Class RDFIOSpecialPage contains common functionality to be used
 * by multiple special pages in the RDFIO extension.
 */
class RDFIOSpecialPage extends SpecialPage {

	function __construct() {
		parent::__construct( 'SPARQLEndpoint' );
	}

	/**
	 * Do preparations for getting outputted data as a downloadable file
	 * rather than written to the current page
	 */
	protected function prepareCreatingDownloadableFile( $options ) {
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
	 * Check if writing to wiki is allowed, and handle a number
	 * of exceptions to that, by showing error messages etc
	 * @return bool
	 */
	protected function allowInsert( $user ) {
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
	protected function allowDelete( $user ) {
		if ( in_array( 'edit', $user->getRights() ) && in_array( 'delete', $user->getRights() ) ) {
			return true;
		}
		$this->errorMsg( 'The current user lacks delete access');
		return false;
	}

	/**
	 * Add a formatted success message to the HTML output, with $message as message.
	 * @param $message
	 */
	protected function successMsg( $message ) {
		$wOut = $this->getOutput();
		$wOut->addHTML( RDFIOUtils::fmtSuccessMsgHTML( "Success!", $message ) );
	}

	/**
	 * Add a formatted error message to the HTML output, with $message as message.
	 * @param $message
	 */
	protected function errorMsg( $message ) {
		$wOut = $this->getOutput();
		$wOut->addHTML( RDFIOUtils::fmtErrorMsgHTML( "Error!", $message ) );
	}

	/**
	 * Add a formatted error message to the HTML output, taking an array of messages
	 * @param $messages array
	 */
	protected function errorMsgArr( $messages ) {
		$allMsgs = '';
		foreach ( $messages as $msg ) {
			$allMsgs .= '<p>' . $msg . '</p>';
		}
		$this->errorMsg( $allMsgs );
	}
}
