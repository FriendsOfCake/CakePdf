<?php
namespace CakePdf\Pdf\Engine;

use App\I18n\Multibyte;
use CakePdf\Pdf\Engine\AbstractPdfEngine;
class DomPdfEngine extends AbstractPdfEngine {

/**
 * Constructor
 *
 * @param $Pdf CakePdf instance
 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);
		if (!defined('DOMPDF_FONT_CACHE')) {
			define('DOMPDF_FONT_CACHE', TMP);
		}
		if (!defined('DOMPDF_TEMP_DIR')) {
			define('DOMPDF_TEMP_DIR', TMP);
		}

		App::import('Vendor', 'CakePdf.DomPDF', array('file' => 'dompdf' . DS . 'dompdf_config.inc.php'));
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 */
	public function output() {
		$DomPDF = new DOMPDF();
		$DomPDF->set_paper($this->_Pdf->pageSize(), $this->_Pdf->orientation());
		$DomPDF->load_html($this->_Pdf->html());
		$DomPDF->render();
		return $DomPDF->output();
	}

}