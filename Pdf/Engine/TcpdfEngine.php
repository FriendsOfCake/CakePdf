<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');

class TcpdfEngine extends AbstractPdfEngine {

/**
 * Constructor
 *
 * @param $Pdf CakePdf instance
 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);

		App::import('Vendor', 'CakePdf.DomPDF', array('file' => 'tcpdf' . DS . 'tcpdf.php'));
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 */
	public function output() {
		$TCPDF = new TCPDF($this->_Pdf->orientation(), 'mm', $this->_Pdf->pageSize());
		$TCPDF->AddPage();
		$TCPDF->writeHTML($this->_Pdf->html());
		return $TCPDF->Output('', 'S');
	}
}