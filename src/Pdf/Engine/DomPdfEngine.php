<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Dompdf\Dompdf;

class DomPdfEngine extends AbstractPdfEngine
{

    /**
     * Constructor
     *
     * @param CakePdf $Pdf CakePdf instance
     */
    public function __construct(CakePdf $Pdf)
    {
        parent::__construct($Pdf);
        if (!defined('DOMPDF_FONT_CACHE')) {
            define('DOMPDF_FONT_CACHE', TMP);
        }
        if (!defined('DOMPDF_TEMP_DIR')) {
            define('DOMPDF_TEMP_DIR', TMP);
        }
    }

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output()
    {
        $DomPDF = new Dompdf();
        $DomPDF->set_paper($this->_Pdf->pageSize(), $this->_Pdf->orientation());
        $DomPDF->load_html($this->_Pdf->html());
        $DomPDF->render();
        return $DomPDF->output();
    }
}
