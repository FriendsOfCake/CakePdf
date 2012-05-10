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

	public function output(CakePdf $pdf) {
		$DomPDF = new DOMPDF();
		$DomPDF->set_paper($pdf->pageSize(), $pdf->orientation());
		$DomPDF->load_html($pdf->html());
		$DomPDF->render();
		return $DomPDF->output();
	}

}