<?php
require APP . 'Vendor/autoload.php';

use Dompdf\Dompdf;

App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');

class DomPdfEngine extends AbstractPdfEngine {

/**
 * Constructor
 *
 * @param $Pdf CakePdf instance
 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 */
	public function output() {
		$DomPDF = new Dompdf();
		
		$options = $this->config('options');
		if ($options) foreach ($this->config('options') as $option => $value) {
			$DomPDF->set_option($option, $value);
		}
		
		$DomPDF->set_paper($this->_Pdf->pageSize(), $this->_Pdf->orientation());
		$DomPDF->load_html($this->_Pdf->html());
		$DomPDF->render();
		return $DomPDF->output();
	}

}
