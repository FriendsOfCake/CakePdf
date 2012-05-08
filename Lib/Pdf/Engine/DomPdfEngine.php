<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');
class DomPdfEngine extends AbstractPdfEngine {

	public function __construct() {
		if (!defined('DOMPDF_FONT_CACHE')) {
			define('DOMPDF_FONT_CACHE', TMP);
		}
		if (!defined('DOMPDF_TEMP_DIR')) {
			define('DOMPDF_TEMP_DIR', TMP);
		}

		App::import('Vendor', 'CakePdf.DomPDF', array('file' => 'dompdf' . DS . 'dompdf_config.inc.php'));
	}

	public function output($html) {
		$DomPDF = new DOMPDF('A4');
		$DomPDF->load_html($html);
		$DomPDF->render();
		return $DomPDF->output();
	}

}