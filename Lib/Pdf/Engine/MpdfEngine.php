<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');
class MpdfEngine extends AbstractPdfEngine {

	public function __construct() {
		App::import('Vendor', 'CakePdf.Mpdf', array('file' => 'mpdf' . DS . 'mpdf.php'));
	}

	public function output(CakePdf $pdf) {
		//mPDF often produces a whole bunch of errors, although there is a pdf created when debug = 0
		//Configure::write('debug', 0);
		$pdf = new mPDF();
		$pdf->writeHTML($pdf->html());
		return $pdf->Output('', 'S');
	}

}