<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Cake\Core\App;
use Cake\Core\Exception\Exception;

/**
 *
 * @author jmischer
 * 
 */
class PdfReactorEngine extends AbstractPdfEngine {
	/**
	 * 
	 * @param CakePdf $Pdf
	 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);
	}
	
	/**
	 * Generates Pdf from html.
	 *
	 * @return string raw pdf data
	 */
	public function output() {
		// Get client config
		$client = $this->getConfig('client',
				'\com\realobjects\pdfreactor\webservice\client\PDFreactor');
		
		// Get pdf reactor
		$pdf_reactor = $this->createInstance($client);
		
		// Get engine options
		$options = $this->getConfig('options', []);
		
		// Create pdf reactor render configuration
		$config = $this->createConfig($options, $this->_Pdf);
		
		// Return output
		return $this->_output($pdf_reactor, $config);
	}
	
	/**
	 * Creates the pdf reactor instance.
	 * 
	 * @param $options
	 * @throws Exception
	 * @return object
	 */
	protected function createInstance($options) {
		// Extract service url and client class name from client config if array
		$service_url = null;
		if (is_array($options)) {
			if (isset($options['serviceUrl'])) {
				$service_url = $options['serviceUrl'];
			}
			$client = $options['className'];
		} else {
			$client = $options;
		}
		
		// Check client is an object, otherwise try create instance it
		if (is_object($client)) {
			$pdf_reactor = $client;
		} else {
			// Get client class name
			$client_class_name = App::className($client);
			if (!class_exists($client_class_name)) {
				throw new Exception(__d('cake_pdf',
						'PDFreactor: Client "{0}" not found', $client));
			}
			
			// Initialize pdf reactor client instance
			$pdf_reactor = new $client_class_name($service_url);
		}
		if (!method_exists($pdf_reactor, 'convertAsBinary')) {
			throw new Exception(__d('cake_pdf',
					'PDFreactor: Missing method "convertAsBinary" for client "{0}"', 
					get_class($pdf_reactor)));
		}
		return $pdf_reactor;
	}
	
	/**
	 * Create the pdf reactor configuration for rendering.
	 *
	 * @param array $options
	 * @param CakePdf $cakepdf
	 */
	protected function createConfig(array $options, CakePdf $cakepdf) {
		// Set config
		$config = $options;
		
		// Set document to render
		$config['document'] = $cakepdf->html() ?: '<html />';
		
		// Return config
		return $config;
	}
	
	/**
	 * 
	 * @param object $pdfReactor
	 * @param \CakePdf\Pdf\CakePdf $cakepdf
	 * @throws Exception
	 * @return string
	 */
	protected function _output($pdfReactor, $config) {
		try {
			// Convert as binary and return result
			return $pdfReactor->convertAsBinary($config);
		} catch (\Exception $ex) {
			throw new Exception(
					__d('cake_pdf', 'PDFreactor: {0}', $ex->getMessage()),
					$ex->getCode(), $ex);
		}
	}
}

